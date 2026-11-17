<?php
// chatbot.php - Conexión con la API de Google Gemini

header('Content-Type: application/json');

// 1. AQUI DEBES PEGAR TU API KEY DE GOOGLE AI STUDIO (Gemini)
// Entra a https://aistudio.google.com/app/apikey y crea una clave
$apiKey = 'TU_GEMINI_API_KEY'; 

// Si aún no has puesto tu API KEY, detenemos el proceso
if ($apiKey === 'TU_API_KEY_AQUI') {
    echo json_encode(['reply' => '¡Hola! Para que pueda pensar por mí mismo, mi creador debe pegar su "API Key" de Gemini en el archivo chatbot.php.']);
    exit;
}

// Recibir el mensaje del usuario
$data = json_decode(file_get_contents('php://input'), true);
$userMessage = $data['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['reply' => '¿En qué te puedo ayudar?']);
    exit;
}

// 2. Darle una personalidad a la IA
$systemPrompt = "Eres un agente de viajes experto y muy amable de la agencia 'Turismo Inkaiko', ubicada en Perú. 
Tu objetivo es ayudar a los clientes a planificar su viaje a destinos peruanos como Cusco, Puno, Lima, Arequipa, Iquitos, Tarapoto, etc.
Debes ser persuasivo, amigable y usar emojis. Trata de mantener tus respuestas cortas (máximo 2 o 3 párrafos cortos) y anima al cliente a reservar paquetes en nuestra página.";

// 3. Preparar la estructura que pide Gemini
$payload = [
    "systemInstruction" => [
        "parts" => [
            ["text" => $systemPrompt]
        ]
    ],
    "contents" => [
        [
            "parts" => [
                ["text" => $userMessage]
            ]
        ]
    ]
];

// 4. Enviar la petición a Google Gemini
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactivar en XAMPP para evitar errores de certificado

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 || $response === false) {
    // Para ver el error real devuelto por Google:
    $errorDetails = $response ? $response : ($curlError ?: 'Error desconocido');
    echo json_encode(['reply' => 'Error de API: HTTP ' . $httpCode . ' - ' . $errorDetails]);
    exit;
}

// 5. Extraer la respuesta de la IA
$responseData = json_decode($response, true);
$parts = $responseData['candidates'][0]['content']['parts'] ?? [];

$botReply = '';
foreach ($parts as $part) {
    if (isset($part['text'])) {
        $botReply .= $part['text'];
    }
}

if (empty(trim($botReply))) {
    // Si sigue vacío, devolvemos el JSON crudo para depurar
    $botReply = 'Respuesta inesperada: ' . print_r($responseData, true);
}

echo json_encode(['reply' => trim($botReply)]);
