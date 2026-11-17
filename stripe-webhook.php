<?php
// Webhook endpoint para Stripe (solo si está disponible)
require_once __DIR__ . '/includes/functions.php';

$stripeAvailable = false;
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    $stripeAvailable = true;
    \Stripe\Stripe::setApiKey('sk_test_REEMPLAZAR_CON_TU_CLAVE_STRIPE'); // Reemplazar con clave real
    \Stripe\Stripe::setVerifySslCerts(false); // Para pruebas locales en XAMPP
}

if (!$stripeAvailable) {
    http_response_code(503);
    echo json_encode(['error' => 'Stripe no está configurado']);
    exit();
}

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$endpoint_secret = 'whsec_51TSBDR0ZKGCIkRYI1jmtO0NIKQ2ZAw0fuzU7CJWlJlwoaqG0C3p68xg3jzgGSlc2Ha8s7yZDSQe38aR3IMN7ToFj00dHAQNv7B'; // Reemplazar con endpoint secret de Stripe

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch(\UnexpectedValueException $e) {
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit();
}

switch ($event->type) {
    case 'payment_intent.succeeded':
        $paymentIntent = $event->data->object;
        
        // Buscar reserva por payment_id
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('SELECT id FROM reservas WHERE payment_id = ?');
        $stmt->execute([$paymentIntent->id]);
        $reserva = $stmt->fetch();
        
        if ($reserva) {
            updateReservationStatus($reserva['id'], 'pagada', 'paid', $paymentIntent->id);
            sendReservationConfirmationEmail($reserva['id']);
        }
        break;
        
    case 'payment_intent.payment_failed':
        $paymentIntent = $event->data->object;
        
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('SELECT id FROM reservas WHERE payment_id = ?');
        $stmt->execute([$paymentIntent->id]);
        $reserva = $stmt->fetch();
        
        if ($reserva) {
            updateReservationStatus($reserva['id'], 'pendiente', 'failed', $paymentIntent->id);
        }
        break;
        
    default:
        // Tipo de evento no manejado
        break;
}

http_response_code(200);
?>
