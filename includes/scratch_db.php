<?php
require_once __DIR__ . '/db.php';
$pdo = get_db_connection();

echo "Table: destinos\n";
$stmt = $pdo->query('DESCRIBE destinos');
foreach ($stmt->fetchAll() as $row) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\nTable: regiones\n";
$stmt = $pdo->query('DESCRIBE regiones');
foreach ($stmt->fetchAll() as $row) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
