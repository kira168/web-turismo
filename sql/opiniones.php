<?php
// Funciones ajustadas a la estructura exacta de la tabla 'resenas'

function getOpinions() {
    global $pdo;
    if (!isset($pdo)) return [];

    try {
        $stmt = $pdo->query("
            SELECT 
                r.id,
                r.usuario_id AS user_id,
                r.calificacion AS rating,
                r.comentario AS opinion,
                r.created_at AS fecha,
                COALESCE(
                    NULLIF(u.nombre_completo, ''), 
                    NULLIF(u.nombre, ''), 
                    'Viajero Inkaiko'
                ) AS nombre_completo 
            FROM resenas r 
            LEFT JOIN usuarios u ON r.usuario_id = u.id 
            ORDER BY r.created_at DESC, r.id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function saveUserOpinion($userId, $titulo, $opinion, $rating, $paqueteId = 10) {
    global $pdo;
    if (!isset($pdo)) return false;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO resenas (usuario_id, paquete_id, calificacion, comentario, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$userId, $paqueteId, (int)$rating, $opinion]);
    } catch (PDOException $e) {
        return false;
    }
}