<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$packageId = isset($_POST['paquete_id']) ? (int)$_POST['paquete_id'] : null;
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$source = trim($_POST['source'] ?? 'web');

$leadData = [
    'usuario_id' => $_SESSION['user_id'] ?? null,
    'nombre' => $nombre,
    'email' => $email,
    'telefono' => $telefono,
    'paquete_id' => $packageId,
    'estado' => 'interesado',
    'source' => $source,
];

createLead($leadData);

header('Content-Type: application/json');
echo json_encode(['success' => true]);
