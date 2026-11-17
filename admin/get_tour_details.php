<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

$package = getPackageById($id);
if (!$package) {
    echo json_encode(['success' => false, 'error' => 'Paquete no encontrado']);
    exit;
}

$itinerary = getPackageItinerary($id);
$includes = getPackageIncludes($id);
$notIncludes = getPackageNotIncludes($id);
$hotels = getPackageHotels($id);

echo json_encode([
    'success' => true,
    'package' => $package,
    'itinerary' => $itinerary,
    'includes' => $includes,
    'not_includes' => $notIncludes,
    'hotels' => $hotels
], JSON_UNESCAPED_UNICODE);
