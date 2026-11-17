<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$paqueteId = (int)($_POST['paquete_id'] ?? 0);
$package = getPackageById($paqueteId);
if (!$package) {
    header('Location: index.php');
    exit;
}

$nombre = trim($_POST['nombre_cliente'] ?? '');
$email = trim($_POST['email_cliente'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$fechaSalida = $_POST['fecha_salida'] ?? '';
$cantidad = (int)($_POST['cantidad_personas'] ?? 1);
$total = (float)($_POST['precio'] ?? $package['price']);

$errors = [];
if ($nombre === '') {
    $errors[] = 'El nombre es obligatorio.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'El correo electrónico no es válido.';
}
if ($telefono === '') {
    $errors[] = 'El teléfono es obligatorio.';
}
if ($fechaSalida === '') {
    $errors[] = 'La fecha de salida es obligatoria.';
}
if ($cantidad < 1) {
    $errors[] = 'Debes indicar al menos una persona.';
}

if (empty($errors)) {
    $reservationNumber = 'RES-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
    
    // Crear reserva en base de datos
    $totalCalculado = $total * $cantidad;
    $reservationId = createReservation([
        'numero_reserva' => $reservationNumber,
        'usuario_id' => $_SESSION['user_id'] ?? null,
        'nombre_cliente' => $nombre,
        'email_cliente' => $email,
        'telefono' => $telefono,
        'paquete_id' => $paqueteId,
        'fecha_salida' => $fechaSalida,
        'cantidad_personas' => $cantidad,
        'total' => $totalCalculado,
    ]);

    // Enviar correo de notificación al administrador y al cliente
    enviarNotificacionReservaAdmin([
        'numero_reserva' => $reservationNumber,
        'nombre_cliente' => $nombre,
        'email_cliente' => $email,
        'telefono' => $telefono,
        'paquete_nombre' => $package['name'] ?? 'Paquete Turístico',
        'fecha_salida' => $fechaSalida,
        'cantidad_personas' => $cantidad,
        'total' => $totalCalculado,
    ]);

    // Notificar en la base de datos a los administradores
    $pdo = get_db_connection();
    $admins = $pdo->query('SELECT id FROM usuarios WHERE role = "admin"')->fetchAll();
    foreach ($admins as $admin) {
        createNotification($admin['id'], 'Nueva reserva pendiente', 'Se ha solicitado la reserva ' . $reservationNumber . ' por ' . $nombre . ' para el tour ' . ($package['name'] ?? '') . '.', 'info');
    }

    if (isset($_SESSION['user_id'])) {
        createNotification($_SESSION['user_id'], 'Reserva solicitada', 'Tu reserva ha sido solicitada. Número: ' . $reservationNumber, 'success');
    }

    header('Location: confirmacion.php?reserva=' . urlencode($reservationNumber));
    exit;
}

$pageTitle = 'Error de Reserva';
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<section class="section section-booking-result" style="padding-top: 150px; min-height: 70vh;">
    <div class="container booking-result-card" style="max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); text-align: center;">
        <h1 style="color: #e74c3c;">No se pudo enviar la reserva</h1>
        <ul class="alert alert-error" style="list-style: none; padding: 0; margin-bottom: 20px;">
            <?php foreach ($errors as $error): ?>
                <li style="color: #c0392b; margin-bottom: 10px;"><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <a class="btn-search-modern" href="paquete.php?id=<?= htmlspecialchars($paqueteId) ?>" style="display: inline-block; text-decoration: none; padding: 10px 20px;">Volver al paquete</a>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
