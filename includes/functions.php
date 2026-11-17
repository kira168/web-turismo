<?php

// Definir la zona horaria predeterminada para el proyecto (Perú UTC-5)
date_default_timezone_set('America/Lima');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/mailer.php';

// Cargar configuración global
$globalSettings = [];
$settingsFile = __DIR__ . '/settings.json';
if (file_exists($settingsFile)) {
    $globalSettings = json_decode(file_get_contents($settingsFile), true) ?: [];
}


function getRegions()
{
    $pdo = get_db_connection();
    $stmt = $pdo->query('SELECT id, name FROM regiones ORDER BY name');
    return $stmt->fetchAll();
}

function getDestinationsByRegion()
{
    $pdo = get_db_connection();
    $stmt = $pdo->query('SELECT d.id, d.region_id, d.name, d.tag, d.rating, d.img, d.packages, r.name AS region_name FROM destinos d JOIN regiones r ON d.region_id = r.id ORDER BY r.name, d.name');
    return $stmt->fetchAll();
}

function getFeaturedDestinations($limit = 6)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT id, name, tag, rating, img, packages FROM destinos ORDER BY id ASC LIMIT ?');
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getDistinctDestinations()
{
    $pdo = get_db_connection();
    $stmt = $pdo->query('SELECT DISTINCT destino FROM paquetes ORDER BY destino ASC');
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getDistinctCategories()
{
    $pdo = get_db_connection();
    $stmt = $pdo->query('SELECT DISTINCT category FROM paquetes ORDER BY category ASC');
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getPackages($search = null, array $filters = [])
{
    $pdo = get_db_connection();
    $sql = 'SELECT * FROM paquetes';
    $conditions = [];
    $params = [];

    if (!empty($search)) {
        $conditions[] = '(name LIKE :search1 OR destino LIKE :search2 OR category LIKE :search3 OR description LIKE :search4)';
        $searchTerm = '%' . $search . '%';
        $params['search1'] = $searchTerm;
        $params['search2'] = $searchTerm;
        $params['search3'] = $searchTerm;
        $params['search4'] = $searchTerm;
    }
    if (!empty($filters['destino'])) {
        $conditions[] = 'destino = :destino';
        $params['destino'] = $filters['destino'];
    }
    if (!empty($filters['category'])) {
        $conditions[] = 'category = :category';
        $params['category'] = $filters['category'];
    }
    if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
        $conditions[] = 'price >= :min_price';
        $params['min_price'] = (float)$filters['min_price'];
    }
    if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
        $conditions[] = 'price <= :max_price';
        $params['max_price'] = (float)$filters['max_price'];
    }

    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY popular DESC, price ASC LIMIT 20';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getPackageById($id)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT * FROM paquetes WHERE id = ?');
    $stmt->execute([(int)$id]);
    return $stmt->fetch();
}

function recordPageView($page, $section)
{
    $pdo = get_db_connection();
    $createdAt = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO page_views (page, section, created_at) VALUES (?, ?, ?)');
    $stmt->execute([$page, $section, $createdAt]);
}

function getTrafficMetrics($limit = 5)
{
    $pdo = get_db_connection();
    $totalViews = $pdo->query('SELECT COUNT(*) FROM page_views')->fetchColumn();
    $topPages = $pdo->query('SELECT page, COUNT(*) AS views FROM page_views GROUP BY page ORDER BY views DESC LIMIT ' . (int)$limit)->fetchAll();
    $topSections = $pdo->query('SELECT section, COUNT(*) AS views FROM page_views GROUP BY section ORDER BY views DESC LIMIT ' . (int)$limit)->fetchAll();
    return [
        'totalViews' => (int)$totalViews,
        'topPages' => $topPages,
        'topSections' => $topSections,
    ];
}

function createLead(array $data)
{
    $pdo = get_db_connection();
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO leads (usuario_id, nombre, email, telefono, paquete_id, estado, source, created_at, updated_at) VALUES (:usuario_id, :nombre, :email, :telefono, :paquete_id, :estado, :source, :created_at, :updated_at) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), telefono = VALUES(telefono), estado = VALUES(estado), source = VALUES(source), updated_at = VALUES(updated_at)');
    $stmt->execute([
        'usuario_id' => $data['usuario_id'] ?? null,
        'nombre' => $data['nombre'] ?? null,
        'email' => $data['email'] ?? null,
        'telefono' => $data['telefono'] ?? null,
        'paquete_id' => $data['paquete_id'] ?? null,
        'estado' => $data['estado'] ?? 'interesado',
        'source' => $data['source'] ?? 'web',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    return $pdo->lastInsertId();
}

function getLeads()
{
    $pdo = get_db_connection();
    $stmt = $pdo->query('SELECT l.*, p.name AS paquete_name FROM leads l LEFT JOIN paquetes p ON l.paquete_id = p.id ORDER BY l.updated_at DESC');
    return $stmt->fetchAll();
}

function savePackage(array $data)
{
    $pdo = get_db_connection();
    if (!empty($data['id'])) {
        $stmt = $pdo->prepare('UPDATE paquetes SET name = ?, destino = ?, category = ?, duration = ?, durationLabel = ?, price = ?, oldPrice = ?, promo = ?, discount = ?, img = ?, rating = ?, reviews = ?, popular = ?, priceLabel = ?, description = ?, notes = ? WHERE id = ?');
        $stmt->execute([
            $data['name'],
            $data['destino'],
            $data['category'],
            $data['duration'],
            $data['durationLabel'],
            $data['price'],
            $data['oldPrice'],
            $data['promo'] ? 1 : 0,
            $data['discount'],
            $data['img'],
            $data['rating'],
            $data['reviews'],
            $data['popular'],
            $data['priceLabel'],
            $data['description'],
            $data['notes'],
            $data['id'],
        ]);
        return $data['id'];
    }

    $nextId = $pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM paquetes')->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO paquetes (id, name, destino, category, duration, durationLabel, price, oldPrice, promo, discount, img, rating, reviews, popular, priceLabel, description, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $nextId,
        $data['name'],
        $data['destino'],
        $data['category'],
        $data['duration'],
        $data['durationLabel'],
        $data['price'],
        $data['oldPrice'],
        $data['promo'] ? 1 : 0,
        $data['discount'],
        $data['img'],
        $data['rating'],
        $data['reviews'],
        $data['popular'],
        $data['priceLabel'],
        $data['description'],
        $data['notes'],
    ]);
    return $nextId;
}

function savePackageItinerary($packageId, array $dias)
{
    $pdo = get_db_connection();
    $stmtDel = $pdo->prepare('DELETE FROM itinerario WHERE paquete_id = ?');
    $stmtDel->execute([(int)$packageId]);

    $stmt = $pdo->prepare('INSERT INTO itinerario (paquete_id, day, title, description) VALUES (?, ?, ?, ?)');
    $dayIndex = 1;
    foreach ($dias as $d) {
        $title = trim($d['title'] ?? '');
        $desc = trim($d['description'] ?? '');
        $dayNum = !empty($d['day']) ? (int)$d['day'] : $dayIndex;
        if ($title !== '' || $desc !== '') {
            $stmt->execute([(int)$packageId, $dayNum, $title, $desc]);
            $dayIndex++;
        }
    }
}

function savePackageIncludes($packageId, array $items)
{
    $pdo = get_db_connection();
    $stmtDel = $pdo->prepare('DELETE FROM paquete_incluye WHERE paquete_id = ?');
    $stmtDel->execute([(int)$packageId]);

    $stmt = $pdo->prepare('INSERT INTO paquete_incluye (paquete_id, included_item) VALUES (?, ?)');
    foreach ($items as $item) {
        $item = trim((string)$item);
        if ($item !== '') {
            $stmt->execute([(int)$packageId, $item]);
        }
    }
}

function savePackageNotIncludes($packageId, array $items)
{
    $pdo = get_db_connection();
    $stmtDel = $pdo->prepare('DELETE FROM paquete_no_incluye WHERE paquete_id = ?');
    $stmtDel->execute([(int)$packageId]);

    $stmt = $pdo->prepare('INSERT INTO paquete_no_incluye (paquete_id, not_included_item) VALUES (?, ?)');
    foreach ($items as $item) {
        $item = trim((string)$item);
        if ($item !== '') {
            $stmt->execute([(int)$packageId, $item]);
        }
    }
}

function savePackageHotels($packageId, array $hoteles)
{
    $pdo = get_db_connection();
    $stmtDel = $pdo->prepare('DELETE FROM tarifas_hoteles WHERE paquete_id = ?');
    $stmtDel->execute([(int)$packageId]);

    $stmt = $pdo->prepare('INSERT INTO tarifas_hoteles (paquete_id, hotel, hotel_category, simple, doble, triple) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($hoteles as $h) {
        $hotel = trim($h['hotel'] ?? '');
        if ($hotel !== '') {
            $cat = !empty($h['hotel_category']) ? trim($h['hotel_category']) : '3 Estrellas';
            $simple = isset($h['simple']) && $h['simple'] !== '' ? (float)$h['simple'] : 0.00;
            $doble = isset($h['doble']) && $h['doble'] !== '' ? (float)$h['doble'] : 0.00;
            $triple = isset($h['triple']) && $h['triple'] !== '' ? (float)$h['triple'] : 0.00;
            $stmt->execute([(int)$packageId, $hotel, $cat, $simple, $doble, $triple]);
        }
    }
}

function deletePackageFull($packageId)
{
    $pdo = get_db_connection();
    $id = (int)$packageId;
    $pdo->prepare('DELETE FROM itinerario WHERE paquete_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM paquete_incluye WHERE paquete_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM paquete_no_incluye WHERE paquete_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM tarifas_hoteles WHERE paquete_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM paquete_imagenes WHERE paquete_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM resenas WHERE paquete_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM paquetes WHERE id = ?')->execute([$id]);
}

function getMultimediaById($id)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT * FROM multimedia WHERE id = ?');
    $stmt->execute([(int)$id]);
    return $stmt->fetch();
}

function createMultimedia(array $data)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('INSERT INTO multimedia (img, title) VALUES (?, ?)');
    $stmt->execute([$data['img'], $data['title']]);
    return $pdo->lastInsertId();
}

function updateMultimedia(array $data)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('UPDATE multimedia SET img = ?, title = ? WHERE id = ?');
    return $stmt->execute([$data['img'], $data['title'], $data['id']]);
}

function deleteMultimedia($id)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('DELETE FROM multimedia WHERE id = ?');
    return $stmt->execute([(int)$id]);
}

function getPackageHotels($packageId)
{
    $pdo = get_db_connection();
    try {
        $stmt = $pdo->prepare('SELECT id, hotel, hotel_category, simple, doble, triple FROM tarifas_hoteles WHERE paquete_id = ? ORDER BY id ASC');
        $stmt->execute([(int)$packageId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        $stmt = $pdo->prepare('SELECT id, hotel, simple, doble, triple FROM tarifas_hoteles WHERE paquete_id = ? ORDER BY id ASC');
        $stmt->execute([(int)$packageId]);
        return $stmt->fetchAll();
    }
}

function getTestimonials($limit = 6)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT name, role, stars, text, avatar FROM testimonios ORDER BY id ASC LIMIT ?');
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getMultimedia($limit = 6)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT id, img, title FROM multimedia ORDER BY id ASC LIMIT ?');
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function createReservation(array $data)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('INSERT INTO reservas (numero_reserva, usuario_id, nombre_cliente, email_cliente, telefono, paquete_id, fecha_salida, cantidad_personas, total, estado, payment_status) VALUES (:numero_reserva, :usuario_id, :nombre_cliente, :email_cliente, :telefono, :paquete_id, :fecha_salida, :cantidad_personas, :total, :estado, :payment_status)');
    $stmt->execute([
        'numero_reserva' => $data['numero_reserva'],
        'usuario_id' => $data['usuario_id'] ?? null,
        'nombre_cliente' => $data['nombre_cliente'],
        'email_cliente' => $data['email_cliente'],
        'telefono' => $data['telefono'],
        'paquete_id' => $data['paquete_id'],
        'fecha_salida' => $data['fecha_salida'],
        'cantidad_personas' => $data['cantidad_personas'],
        'total' => $data['total'],
        'estado' => $data['estado'] ?? 'pendiente',
        'payment_status' => $data['payment_status'] ?? 'pending',
    ]);
    return $pdo->lastInsertId();
}

function updateReservationStatus($reservationId, $estado, $paymentStatus = null, $paymentId = null)
{
    $pdo = get_db_connection();
    $updatedAt = date('Y-m-d H:i:s');
    $sql = 'UPDATE reservas SET estado = ?, updated_at = ?';
    $params = [$estado, $updatedAt];

    if ($paymentStatus !== null) {
        $sql .= ', payment_status = ?';
        $params[] = $paymentStatus;
    }
    if ($paymentId !== null) {
        $sql .= ', payment_id = ?';
        $params[] = $paymentId;
    }

    $sql .= ' WHERE id = ?';
    $params[] = $reservationId;

    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

function getReservations($userId = null, $limit = null)
{
    $pdo = get_db_connection();
    $sql = 'SELECT r.*, p.name AS paquete_name, p.img AS paquete_img FROM reservas r JOIN paquetes p ON r.paquete_id = p.id';
    $params = [];

    if ($userId !== null) {
        $sql .= ' WHERE r.usuario_id = ?';
        $params[] = $userId;
    }

    $sql .= ' ORDER BY r.created_at DESC';

    if ($limit !== null) {
        $sql .= ' LIMIT ?';
        $params[] = $limit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function sendReservationConfirmationEmail($reservationId)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT r.*, p.name AS paquete_name FROM reservas r JOIN paquetes p ON r.paquete_id = p.id WHERE r.id = ?');
    $stmt->execute([$reservationId]);
    $reservation = $stmt->fetch();

    if ($reservation) {
        return enviarNotificacionReservaAdmin([
            'numero_reserva' => $reservation['numero_reserva'],
            'nombre_cliente' => $reservation['nombre_cliente'],
            'email_cliente' => $reservation['email_cliente'],
            'telefono' => $reservation['telefono'],
            'paquete_nombre' => $reservation['paquete_name'],
            'fecha_salida' => $reservation['fecha_salida'],
            'cantidad_personas' => $reservation['cantidad_personas'],
            'total' => $reservation['total'],
        ]);
    }
    return false;
}

function enviarNotificacionReservaAdmin(array $reservaData)
{
    $numero = $reservaData['numero_reserva'] ?? 'RES-000000';
    $nombre = $reservaData['nombre_cliente'] ?? 'Cliente';
    $email = $reservaData['email_cliente'] ?? '';
    $telefono = $reservaData['telefono'] ?? 'No especificado';
    $paquete = $reservaData['paquete_nombre'] ?? 'Paquete Turístico';
    $fecha = $reservaData['fecha_salida'] ?? date('Y-m-d');
    $personas = (int)($reservaData['cantidad_personas'] ?? 1);
    $total = number_format((float)($reservaData['total'] ?? 0), 2);
    $fechaRegistro = date('d/m/Y H:i:s');

    // Destinatarios: Administradores del sistema
    $destinatarios = [];

    // Correo principal configurado en SMTP
    if (function_exists('getSmtpCredentials')) {
        $smtpConfig = getSmtpCredentials();
        if (!empty($smtpConfig['smtp_user']) && filter_var($smtpConfig['smtp_user'], FILTER_VALIDATE_EMAIL)) {
            $destinatarios[] = $smtpConfig['smtp_user'];
        }
    }

    try {
        $pdo = get_db_connection();
        $stmt = $pdo->query('SELECT email FROM usuarios WHERE role = "admin" AND email IS NOT NULL AND email != ""');
        while ($row = $stmt->fetch()) {
            $adminEmail = trim($row['email']);
            if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL) && !in_array($adminEmail, $destinatarios)) {
                $destinatarios[] = $adminEmail;
            }
        }
    } catch (Exception $e) {
        // Continuar
    }

    $to = implode(', ', $destinatarios);
    $asunto = "=?UTF-8?B?" . base64_encode("🔔 Nueva Reserva Recibida: $numero - $nombre") . "?=";

    $htmlBody = "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; color: #333; }
            .container { max-width: 650px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: 1px solid #e1e7ec; }
            .header { background: #0f2a4a; color: #ffffff; padding: 25px 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; font-weight: 700; color: #d49a00; }
            .header p { margin: 5px 0 0; font-size: 14px; opacity: 0.9; color: #e2e8f0; }
            .content { padding: 30px; }
            .badge { display: inline-block; background: #fff3cd; color: #856404; padding: 6px 14px; border-radius: 20px; font-weight: bold; font-size: 13px; margin-bottom: 20px; border: 1px solid #ffeeba; }
            .table-details { width: 100%; border-collapse: collapse; margin-top: 15px; }
            .table-details th, .table-details td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #edf2f7; font-size: 14px; }
            .table-details th { background-color: #f8fafc; color: #475569; width: 38%; font-weight: 600; }
            .table-details td { color: #1e293b; font-weight: 500; }
            .total-row { font-size: 16px; font-weight: 700; color: #0f2a4a; background: #f0fdf4; }
            .btn-cta { display: inline-block; background: #0f2a4a; color: #ffffff !important; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; margin-top: 25px; text-align: center; }
            .footer { background: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Turismo Inkaiko</h1>
                <p>Notificación Oficial de Nueva Reserva</p>
            </div>
            <div class='content'>
                <span class='badge'>Estado: Pendiente de Confirmación</span>
                <p style='font-size: 15px; line-height: 1.5;'>Estimado Administrador,<br>Se ha recibido una nueva solicitud de reserva desde el portal web con los siguientes datos:</p>
                
                <table class='table-details'>
                    <tr><th>Código de Reserva:</th><td><strong style='color: #0f2a4a; font-size: 16px;'>$numero</strong></td></tr>
                    <tr><th>Cliente:</th><td><strong>$nombre</strong></td></tr>
                    <tr><th>Correo Electrónico:</th><td><a href='mailto:$email' style='color: #0073b7;'>$email</a></td></tr>
                    <tr><th>Teléfono / WhatsApp:</th><td><a href='https://wa.me/" . preg_replace('/[^0-9]/', '', $telefono) . "' style='color: #25D366; font-weight: bold;'>$telefono</a></td></tr>
                    <tr><th>Paquete Turístico:</th><td><strong>$paquete</strong></td></tr>
                    <tr><th>Fecha de Salida:</th><td>$fecha</td></tr>
                    <tr><th>Nº de Viajeros:</th><td>$personas viajero(s)</td></tr>
                    <tr class='total-row'><th>Monto Total:</th><td>\$$total USD</td></tr>
                    <tr><th>Fecha y Hora:</th><td>$fechaRegistro</td></tr>
                </table>

                <div style='text-align: center;'>
                    <a href='http://localhost:8080/admin/reservas.php?view=" . urlencode($numero) . "' class='btn-cta'>Gestionar Reserva en el Panel de Administración</a>
                </div>
            </div>
            <div class='footer'>
                <p>Mensaje generado automáticamente por la plataforma Turismo Inkaiko.<br>&copy; " . date('Y') . " Turismo Inkaiko - Cusco, Perú.</p>
            </div>
        </div>
    </body>
    </html>";

    $headers = [];
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: text/html; charset=UTF-8";
    $headers[] = "From: Turismo Inkaiko <kdelgado@turismoinkaiko.net>";
    if (!empty($email)) {
        $headers[] = "Reply-To: $nombre <$email>";
    }
    $headers[] = "X-Mailer: PHP/" . phpversion();

    // Intentar envío real por Gmail SMTP si está configurado
    $mailSent = false;
    $smtpResult = null;
    if (function_exists('sendMailSmtp')) {
        $smtpResult = sendMailSmtp($destinatarios, "🔔 Nueva Reserva Recibida: $numero - $nombre", $htmlBody, "Turismo Inkaiko");
        $mailSent = $smtpResult['success'];
    }

    // Si SMTP no está configurado o falla, usar mail() nativo como fallback
    if (!$mailSent) {
        $mailSent = @mail($to, $asunto, $htmlBody, implode("
", $headers));
    }

    // Confirmación al cliente
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $asuntoCliente = "=?UTF-8?B?" . base64_encode("Confirmación de Solicitud de Reserva $numero - Turismo Inkaiko") . "?=";
        if (function_exists('sendMailSmtp') && !empty(getSmtpCredentials()['smtp_user'])) {
            sendMailSmtp($email, "Confirmación de Solicitud de Reserva $numero - Turismo Inkaiko", $htmlBody, "Turismo Inkaiko");
        } else {
            $headersCliente = [
                "MIME-Version: 1.0",
                "Content-type: text/html; charset=UTF-8",
                "From: Turismo Inkaiko <kdelgado@turismoinkaiko.net>",
                "Reply-To: Turismo Inkaiko <kdelgado@turismoinkaiko.net>",
                "X-Mailer: PHP/" . phpversion()
            ];
            @mail($email, $asuntoCliente, $htmlBody, implode("
", $headersCliente));
        }
    }

    // Registro en log de auditoría
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . '/reservas_emails.log';
    $logEntry = "[" . date('Y-m-d H:i:s') . "] NOTIFICACIÓN RESERVA ENVIADA: Código=$numero | Destinatarios=$to | Cliente=$nombre ($email, $telefono) | Paquete=$paquete | Total=\$$total | Estado=" . ($mailSent ? 'ENVIADO' : 'REGISTRADO_LOCAL') . "
";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);

    return true;
}

function getUserByEmail($email)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE LOWER(TRIM(email)) = LOWER(TRIM(?))');
    $stmt->execute([trim($email)]);
    return $stmt->fetch();
}

function getUserById($id)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
    $stmt->execute([(int)$id]);
    return $stmt->fetch();
}

function createUser($name, $email, $phone, $password)
{
    $pdo = get_db_connection();
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $createdAt = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO usuarios (name, email, phone, password_hash, created_at, email_verified) VALUES (?, ?, ?, ?, ?, 1)');
    $stmt->execute([$name, $email, $phone, $passwordHash, $createdAt]);
    return $pdo->lastInsertId();
}

/**
 * Envía el correo con el código de 6 dígitos para verificar la cuenta
 */
function sendVerificationCodeEmail($email, $name, $code)
{
    $subject = "🔒 Tu Código de Verificación: $code - Turismo Inkaiko";
    $htmlBody = "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 25px 15px; color: #1e293b; }
            .container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
            .header { background: linear-gradient(135deg, #0f2a4a 0%, #1e3a5f 100%); color: #ffffff; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; font-weight: 800; color: #d4a017; letter-spacing: 0.5px; }
            .header p { margin: 6px 0 0; font-size: 13.5px; color: #cbd5e1; }
            .content { padding: 35px 30px; text-align: center; }
            .content h2 { color: #0f2a4a; font-size: 20px; margin: 0 0 12px; font-weight: 700; }
            .content p { font-size: 14.5px; line-height: 1.6; color: #475569; margin: 0 0 25px; }
            .code-box { display: inline-block; background: #f8fafc; border: 2px dashed #d4a017; border-radius: 12px; padding: 18px 35px; margin: 10px 0 20px; }
            .code-digits { font-size: 36px; font-weight: 900; letter-spacing: 8px; color: #0f2a4a; font-family: 'Courier New', monospace; }
            .badge-expiry { display: inline-block; background: #fef3c7; color: #92400e; font-size: 12px; font-weight: 700; padding: 5px 14px; border-radius: 20px; margin-bottom: 25px; }
            .notice-box { background: #f8fafc; border-left: 4px solid #0f2a4a; padding: 12px 16px; text-align: left; font-size: 12.5px; color: #64748b; margin-top: 15px; border-radius: 4px; }
            .footer { background: #f1f5f9; padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Turismo Inkaiko</h1>
                <p>Verificación de Seguridad para Registro</p>
            </div>
            <div class='content'>
                <h2>¡Hola, " . htmlspecialchars($name) . "!</h2>
                <p>Gracias por registrarte en nuestra plataforma turística. Para activar tu cuenta y comprobar que tu correo es válido, ingresa el siguiente código de 6 dígitos:</p>
                
                <div class='code-box'>
                    <div class='code-digits'>$code</div>
                </div>
                <br>
                <div class='badge-expiry'>⏱️ Válido durante los próximos 15 minutos</div>

                <div class='notice-box'>
                    <strong>🔒 Aviso de Seguridad:</strong> Si tú no solicitaste este registro en Turismo Inkaiko, ignora este mensaje. Tu cuenta no podrá ser activada sin este código.
                </div>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " Turismo Inkaiko S.A.C. - Cusco, Perú.<br>Todos los derechos reservados.</p>
            </div>
        </div>
    </body>
    </html>";

    if (function_exists('sendMailSmtp')) {
        return sendMailSmtp($email, $subject, $htmlBody, "Turismo Inkaiko");
    } else {
        $headers = [
            "MIME-Version: 1.0",
            "Content-type: text/html; charset=UTF-8",
            "From: Turismo Inkaiko <nuvk168@gmail.com>",
            "X-Mailer: PHP/" . phpversion()
        ];
        return ['success' => @mail($email, $subject, $htmlBody, implode("
", $headers))];
    }
}

/**
 * Crea o actualiza un usuario en estado pendiente de verificación (email_verified = 0)
 */
function createUserPendingVerification($name, $email, $phone, $password)
{
    $pdo = get_db_connection();
    $email = strtolower(trim($email));
    $name = trim($name);
    $phone = trim($phone);
    
    // Verificar si el correo ya existe
    $existing = getUserByEmail($email);
    $code = sprintf("%06d", random_int(100000, 999999));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    if ($existing) {
        if (!empty($existing['email_verified']) && (int)$existing['email_verified'] === 1) {
            return [
                'success' => false,
                'message' => 'El correo electrónico ya se encuentra registrado y activo.'
            ];
        } else {
            // Actualizar el intento pendiente anterior
            $stmt = $pdo->prepare("UPDATE usuarios SET name = ?, phone = ?, password_hash = ?, verification_code = ?, code_expires_at = ?, email_verified = 0 WHERE id = ?");
            $stmt->execute([$name, $phone, $passwordHash, $code, $expiresAt, $existing['id']]);
            $userId = $existing['id'];
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO usuarios (name, email, phone, password_hash, role, email_verified, verification_code, code_expires_at, created_at) VALUES (?, ?, ?, ?, 'cliente', 0, ?, ?, NOW())");
        $stmt->execute([$name, $email, $phone, $passwordHash, $code, $expiresAt]);
        $userId = $pdo->lastInsertId();
    }

    // Enviar código por correo
    $mailResult = sendVerificationCodeEmail($email, $name, $code);

    return [
        'success'   => true,
        'user_id'   => $userId,
        'email'     => $email,
        'name'      => $name,
        'mail_sent' => !empty($mailResult['success'])
    ];
}

/**
 * Valida el código de 6 dígitos ingresado por el usuario y activa su cuenta
 */
function verifyUserCode($email, $code)
{
    $pdo = get_db_connection();
    $email = strtolower(trim($email));
    $code = trim($code);

    $user = getUserByEmail($email);
    if (!$user) {
        return ['success' => false, 'message' => 'No se encontró ninguna cuenta asociada a este correo electrónico.'];
    }

    if (!empty($user['email_verified']) && (int)$user['email_verified'] === 1) {
        return ['success' => true, 'already_verified' => true, 'user' => $user];
    }

    if (empty($user['verification_code']) || $user['verification_code'] !== $code) {
        return ['success' => false, 'message' => 'El código de 6 dígitos ingresado es incorrecto.'];
    }

    if (!empty($user['code_expires_at']) && strtotime($user['code_expires_at']) < time()) {
        return ['success' => false, 'message' => 'El código ha expirado (validez de 15 minutos). Solicita uno nuevo abajo.'];
    }

    // Activar cuenta en base de datos
    $stmt = $pdo->prepare("UPDATE usuarios SET email_verified = 1, verification_code = NULL, code_expires_at = NULL WHERE id = ?");
    $stmt->execute([$user['id']]);

    // Recargar datos actualizados
    $updatedUser = getUserById($user['id']);

    return [
        'success' => true,
        'user'    => $updatedUser,
        'message' => '¡Cuenta verificada exitosamente!'
    ];
}

/**
 * Reenvía un nuevo código de 6 dígitos a un correo no verificado
 */
function resendVerificationCode($email)
{
    $pdo = get_db_connection();
    $email = strtolower(trim($email));

    $user = getUserByEmail($email);
    if (!$user) {
        return ['success' => false, 'message' => 'No se encontró ninguna cuenta con ese correo.'];
    }

    if (!empty($user['email_verified']) && (int)$user['email_verified'] === 1) {
        return ['success' => false, 'message' => 'Tu cuenta ya está verificada. Puedes iniciar sesión directamente.'];
    }

    $code = sprintf("%06d", random_int(100000, 999999));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    $stmt = $pdo->prepare("UPDATE usuarios SET verification_code = ?, code_expires_at = ? WHERE id = ?");
    $stmt->execute([$code, $expiresAt, $user['id']]);

    $name = $user['name'] ?? 'Cliente';
    $mailResult = sendVerificationCodeEmail($email, $name, $code);

    return [
        'success' => true,
        'message' => 'Te hemos enviado un nuevo código de verificación a tu correo.'
    ];
}

function createResena($usuarioId, $paqueteId, $calificacion, $comentario, $titulo = null)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('INSERT INTO resenas (usuario_id, paquete_id, calificacion, titulo, comentario, created_at) VALUES (?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE calificacion = VALUES(calificacion), titulo = VALUES(titulo), comentario = VALUES(comentario), created_at = NOW()');
    $stmt->execute([(int)$usuarioId, $paqueteId ? (int)$paqueteId : null, (int)$calificacion, $titulo, $comentario]);
    return $pdo->lastInsertId();
}

function getUserReservations($usuarioId)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT r.id, r.numero_reserva, r.nombre_cliente, p.name AS paquete, r.fecha_salida, r.cantidad_personas, r.total, r.estado, r.created_at FROM reservas r JOIN paquetes p ON r.paquete_id = p.id WHERE r.usuario_id = ? ORDER BY r.created_at DESC');
    $stmt->execute([(int)$usuarioId]);
    return $stmt->fetchAll();
}

function createNotification($usuarioId, $titulo, $mensaje, $tipo = 'info')
{
    $pdo = get_db_connection();
    $createdAt = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, created_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([(int)$usuarioId, $titulo, $mensaje, $tipo, $createdAt]);
    return $pdo->lastInsertId();
}

function getPackageImages($packageId)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT * FROM paquete_imagenes WHERE paquete_id = ? ORDER BY id ASC');
    $stmt->execute([(int)$packageId]);
    return $stmt->fetchAll();
}

function getPackageIncludes($packageId)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT *, included_item AS item FROM paquete_incluye WHERE paquete_id = ? ORDER BY id ASC');
    $stmt->execute([(int)$packageId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPackageNotIncludes($packageId)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT *, not_included_item AS item FROM paquete_no_incluye WHERE paquete_id = ? ORDER BY id ASC');
    $stmt->execute([(int)$packageId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPackageItinerary($packageId)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT *, day AS dia, title AS titulo, description AS descripcion FROM itinerario WHERE paquete_id = ? ORDER BY COALESCE(day, id) ASC');
    $stmt->execute([(int)$packageId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getResenias($packageId)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT r.*, u.name FROM resenas r JOIN usuarios u ON r.usuario_id = u.id WHERE r.paquete_id = ? ORDER BY r.created_at DESC');
    $stmt->execute([(int)$packageId]);
    return $stmt->fetchAll();
}

function getPackageReviewSummary($packageId)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT AVG(calificacion) as average_rating, COUNT(*) as review_count FROM resenas WHERE paquete_id = ?');
    $stmt->execute([(int)$packageId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAllHotels()
{
    $pdo = get_db_connection();
    $stmt = $pdo->query('SELECT th.*, p.name AS package_name FROM tarifas_hoteles th LEFT JOIN paquetes p ON th.paquete_id = p.id ORDER BY th.hotel ASC');
    return $stmt->fetchAll();
}

function getHotelCategories()
{
    $pdo = get_db_connection();
    $stmt = $pdo->query('SELECT DISTINCT hotel_category AS categoria FROM tarifas_hoteles WHERE hotel_category IS NOT NULL ORDER BY categoria ASC');
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getHotelServices()
{
    return ['Desayuno', 'Wifi', 'Piscina', 'Traslado', 'Gimnasio', 'Restaurante'];
}

function getNotifications($usuarioId)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT * FROM notificaciones WHERE usuario_id = ? ORDER BY created_at DESC LIMIT 10');
    $stmt->execute([(int)$usuarioId]);
    return $stmt->fetchAll();
}

function getAllUsers()
{
    $pdo = get_db_connection();
    $stmt = $pdo->query('SELECT id, name, email, phone, role, created_at FROM usuarios ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

function updateUser($id, $name, $email, $phone, $role)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('UPDATE usuarios SET name = ?, email = ?, phone = ?, role = ? WHERE id = ?');
    return $stmt->execute([$name, $email, $phone, $role, (int)$id]);
}

function deleteUser($id)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ?');
    return $stmt->execute([(int)$id]);
}

function createDestino(array $data)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('INSERT INTO destinos (region_id, name, tag, rating, img, packages) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $data['region_id'],
        $data['name'],
        $data['tag'],
        $data['rating'],
        $data['img'],
        $data['packages']
    ]);
    return $pdo->lastInsertId();
}

function updateDestino(array $data)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('UPDATE destinos SET region_id = ?, name = ?, tag = ?, rating = ?, img = ?, packages = ? WHERE id = ?');
    return $stmt->execute([
        $data['region_id'],
        $data['name'],
        $data['tag'],
        $data['rating'],
        $data['img'],
        $data['packages'],
        $data['id']
    ]);
}

function deleteDestino($id)
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('DELETE FROM destinos WHERE id = ?');
    return $stmt->execute([(int)$id]);
}