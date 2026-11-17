<?php
/**
 * Mailer SMTP para Turismo Inkaiko
 * Envía correos reales mediante SMTP (compatible con Gmail, Outlook y hosting)
 * Sin dependencias externas, usando sockets seguros SSL/TLS nativos de PHP.
 */

// Configuración SMTP predeterminada
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465); // 465 para SSL, 587 para TLS
define('SMTP_SECURE', 'ssl'); // 'ssl' o 'tls'

// Archivo de credenciales protegidas
define('SMTP_CONFIG_FILE', __DIR__ . '/smtp_config.php');

function getSmtpCredentials()
{
    if (file_exists(SMTP_CONFIG_FILE)) {
        return include SMTP_CONFIG_FILE;
    }
    return [
        'smtp_user' => '', // Correo de Gmail del administrador
        'smtp_pass' => '', // Contraseña de aplicación de 16 letras de Google
        'from_name' => 'Turismo Inkaiko',
        'enabled'   => false
    ];
}

/**
 * Envía un correo electrónico vía SMTP con autenticación SSL/TLS
 *
 * @param string|array $to Correo o lista de correos destinatarios
 * @param string $subject Asunto del correo
 * @param string $htmlBody Contenido HTML
 * @param string $fromName Nombre visible del remitente
 * @return array ['success' => bool, 'message' => string]
 */
function sendMailSmtp($to, $subject, $htmlBody, $fromName = 'Turismo Inkaiko')
{
    $config = getSmtpCredentials();
    
    // Si no está configurado el correo de Gmail, registrar en log y retornar
    if (empty($config['smtp_user']) || empty($config['smtp_pass'])) {
        return [
            'success' => false,
            'message' => 'Credenciales SMTP no configuradas. Por favor agrega tu cuenta de Gmail y contraseña de aplicación.'
        ];
    }

    $smtpHost = SMTP_HOST;
    $smtpPort = SMTP_PORT;
    $smtpUser = trim($config['smtp_user']);
    $smtpPass = str_replace(' ', '', trim($config['smtp_pass'])); // Eliminar espacios si los tiene

    // Normalizar lista de destinatarios
    $recipients = [];
    if (is_array($to)) {
        foreach ($to as $r) {
            $r = trim($r);
            if (filter_var($r, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $r;
            }
        }
    } else {
        $parts = explode(',', $to);
        foreach ($parts as $p) {
            $p = trim($p);
            if (filter_var($p, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $p;
            }
        }
    }

    if (empty($recipients)) {
        return ['success' => false, 'message' => 'No hay destinatarios válidos.'];
    }

    // Conectar vía socket seguro SSL
    $hostAddress = ($smtpPort == 465) ? "ssl://{$smtpHost}:{$smtpPort}" : "tcp://{$smtpHost}:{$smtpPort}";
    
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $socket = @stream_socket_client($hostAddress, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) {
        return ['success' => false, 'message' => "Error de conexión SMTP ($errno): $errstr"];
    }

    // Funciones auxiliares de comunicación SMTP
    $getResponse = function($sock) {
        $response = '';
        while ($line = fgets($sock, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    };

    $sendCommand = function($sock, $cmd) use ($getResponse) {
        fputs($sock, $cmd . "\r\n");
        return $getResponse($sock);
    };

    // 1. Saludo inicial del servidor
    $serverHello = $getResponse($socket);
    if (substr($serverHello, 0, 3) !== '220') {
        fclose($socket);
        return ['success' => false, 'message' => "Respuesta de servidor inválida: $serverHello"];
    }

    // 2. EHLO
    $ehlo = $sendCommand($socket, "EHLO localhost");

    // 3. Autenticación AUTH LOGIN
    $auth = $sendCommand($socket, "AUTH LOGIN");
    if (substr($auth, 0, 3) !== '334') {
        fclose($socket);
        return ['success' => false, 'message' => "Fallo en AUTH LOGIN: $auth"];
    }

    // Enviar usuario (Base64)
    $userResp = $sendCommand($socket, base64_encode($smtpUser));
    if (substr($userResp, 0, 3) !== '334') {
        fclose($socket);
        return ['success' => false, 'message' => "Usuario SMTP no aceptado: $userResp"];
    }

    // Enviar contraseña de aplicación (Base64)
    $passResp = $sendCommand($socket, base64_encode($smtpPass));
    if (substr($passResp, 0, 3) !== '235') {
        fclose($socket);
        return ['success' => false, 'message' => "Contraseña de aplicación de Google rechazada. Verifica que las 16 letras sean correctas."];
    }

    // 4. MAIL FROM
    $mailFrom = $sendCommand($socket, "MAIL FROM: <{$smtpUser}>");
    if (substr($mailFrom, 0, 3) !== '250') {
        fclose($socket);
        return ['success' => false, 'message' => "MAIL FROM rechazado: $mailFrom"];
    }

    // 5. RCPT TO para cada destinatario
    foreach ($recipients as $recipient) {
        $rcpt = $sendCommand($socket, "RCPT TO: <{$recipient}>");
        if (substr($rcpt, 0, 3) !== '250') {
            // Continuar con los demás si uno falla
        }
    }

    // 6. DATA
    $dataResp = $sendCommand($socket, "DATA");
    if (substr($dataResp, 0, 3) !== '354') {
        fclose($socket);
        return ['success' => false, 'message' => "Servidor no listo para recibir contenido: $dataResp"];
    }

    // 7. Cabeceras y Cuerpo del Mensaje
    $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
    $encodedFromName = "=?UTF-8?B?" . base64_encode($fromName) . "?=";
    
    $headers = [
        "From: {$encodedFromName} <{$smtpUser}>",
        "Reply-To: {$encodedFromName} <{$smtpUser}>",
        "To: " . implode(', ', $recipients),
        "Subject: {$encodedSubject}",
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8",
        "Date: " . date('r'),
        "X-Mailer: Turismo Inkaiko System"
    ];

    $emailContent = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n.";
    $sendResp = $sendCommand($socket, $emailContent);

    // 8. QUIT
    $sendCommand($socket, "QUIT");
    fclose($socket);

    if (substr($sendResp, 0, 3) === '250') {
        return ['success' => true, 'message' => 'Correo enviado exitosamente vía Gmail SMTP.'];
    }

    return ['success' => false, 'message' => "Error al finalizar el envío: $sendResp"];
}
