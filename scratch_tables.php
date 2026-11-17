<?php
require_once __DIR__ . '/includes/db.php';
$pdo = get_db_connection();
$stmt = $pdo->query('DESCRIBE notificaciones');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
