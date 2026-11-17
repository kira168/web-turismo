<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

$reservationNumber = $_GET['reserva'] ?? $_GET['reservation'] ?? '';

$pageTitle = 'Confirmación de reserva';

// Cargar el número de WhatsApp de la configuración
$settingsFile = __DIR__ . '/includes/settings.json';
$settings = [];
if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true) ?: [];
}
$whatsappNumber = $settings['whatsapp'] ?? '51987654321'; // Default si no hay

$whatsappMessage = "Hola, acabo de solicitar la reserva " . $reservationNumber . ". Me gustaría coordinar el pago correspondiente.";
$whatsappUrl = "https://wa.me/" . urlencode($whatsappNumber) . "?text=" . urlencode($whatsappMessage);

?>
<?php include __DIR__ . '/includes/header.php'; ?>
<section class="section section-confirmation" style="padding-top: 150px; min-height: 70vh;">
    <div class="container confirmation-card" style="max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); text-align: center;">
        
        <div style="font-size: 4rem; color: #2ecc71; margin-bottom: 20px;">
            <i class="fa-solid fa-circle-check"></i>
        </div>

        <h1 style="margin-top: 0;">¡Tu solicitud ha sido enviada!</h1>
        <p style="color: #666; font-size: 1.1rem; margin-bottom: 30px;">
            Tu reserva ha sido registrada en nuestro sistema con el código: 
            <strong style="color: var(--admin-card-blue); font-size: 1.2rem;"><?= htmlspecialchars($reservationNumber) ?></strong>
        </p>

        <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
            <h3 style="margin-top: 0; color: #333; font-size: 1.2rem;"><i class="fa-brands fa-whatsapp" style="color: #25D366;"></i> Coordinar Pago</h3>
            <p style="font-size: 0.95rem; color: #555; margin-bottom: 20px;">
                Para finalizar tu reserva y asegurar tus lugares, por favor contáctanos directamente a nuestro WhatsApp para enviarte las instrucciones de pago.
            </p>
            <a href="<?= htmlspecialchars($whatsappUrl) ?>" target="_blank" rel="noopener noreferrer" style="display: inline-block; background-color: #25D366; color: white; padding: 12px 24px; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(37,211,102,0.3); transition: transform 0.2s;">
                <i class="fa-brands fa-whatsapp"></i> Hablar por WhatsApp
            </a>
        </div>
        
        <div class="confirmation-actions" style="display: flex; gap: 10px; justify-content: center;">
            <a href="index.php" class="btn-search-modern" style="text-decoration: none; background: #eee; color: #333; box-shadow: none;">Volver al inicio</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="mi-cuenta.php" class="btn-search-modern" style="text-decoration: none;">Mis reservas</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
