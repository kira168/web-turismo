<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/functions.php';

// Validar que la petición sea vía POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Obtener ID del usuario desde las distintas variables de sesión posibles
$usuario_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? null;

// Validar que el usuario tenga una sesión activa
if (!$usuario_id) {
    header('Location: login.php');
    exit;
}

// Recibir y sanitizar los datos del formulario
$paquete_id   = (int)($_POST['paquete_id'] ?? 0);
$calificacion = (int)($_POST['calificacion'] ?? 5);
$comentario   = trim($_POST['comentario'] ?? '');

// Obtener la URL de origen para regresar al usuario tras guardar
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';

// Validar que el comentario no esté vacío
if (empty($comentario)) {
    header('Location: ' . $referer);
    exit;
}

// Intentar guardar la opinión en la base de datos
if (function_exists('createResena')) {
    createResena($usuario_id, $paquete_id, $calificacion, $comentario);
} else {
    try {
        // Conexión estandarizada según tu proyecto
        if (function_exists('get_db_connection')) {
            $pdo = get_db_connection();
        } elseif (function_exists('getDBConnection')) {
            $pdo = getDBConnection();
        } else {
            global $pdo;
        }

        if (isset($pdo)) {
            // Inserción en la tabla opiniones (coincidiendo con la lectura de reviews_section.php)
            $stmt = $pdo->prepare("INSERT INTO opiniones (user_id, paquete_id, rating, calificacion, opinion, comentario, fecha) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$usuario_id, $paquete_id, $calificacion, $calificacion, $comentario, $comentario]);
        }
    } catch (Exception $e) {
        error_log("Error al guardar opinion: " . $e->getMessage());
    }
}

// Redirigir de regreso a la página del paquete con ancla a la sección de opiniones
header('Location: ' . $referer . '#reviews-section');
exit;