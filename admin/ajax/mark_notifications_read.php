<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';

if (isset($_SESSION['user_id'])) {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('UPDATE notificaciones SET leida = 1 WHERE usuario_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
}
