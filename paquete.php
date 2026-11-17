<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/functions.php';

$packageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$package = null;

if ($packageId > 0) {
    $package = getPackageById($packageId);
}

// Si no se encuentra el paquete específico, cargar el primero disponible
if (!$package) {
    $allPackages = getPackages();
    if (!empty($allPackages)) {
        $package = $allPackages[0];
        $packageId = (int)$package['id'];
    } else {
        header('Location: destinos.php');
        exit;
    }
}

$pageTitle = $package['name'] . ' - Turismo Inkaiko';

// Cargar datos complementarios
$itinerario    = getPackageItinerary($packageId);
$incluye       = getPackageIncludes($packageId);
$noIncluye     = getPackageNotIncludes($packageId);
$hoteles       = getPackageHotels($packageId);
$imagenes      = getPackageImages($packageId);
$resenas       = getResenias($packageId);
$reviewSummary = getPackageReviewSummary($packageId);

// Datos del usuario logueado para precargar el formulario
$userName  = $_SESSION['user_name'] ?? $_SESSION['nombre'] ?? '';
$userEmail = $_SESSION['user_email'] ?? $_SESSION['email'] ?? '';
$userPhone = $_SESSION['user_phone'] ?? $_SESSION['telefono'] ?? '';

recordPageView('Paquete', $package['name']);

include __DIR__ . '/includes/header.php';
?>

<style>
.tour-hero {
    position: relative;
    min-height: 480px;
    background: linear-gradient(rgba(15, 42, 74, 0.75), rgba(10, 30, 54, 0.9)), url('<?= htmlspecialchars($package['img']) ?>') center/cover no-repeat;
    display: flex;
    align-items: center;
    padding: 120px 20px 60px;
    color: #fff;
}
.tour-hero-container {
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
}
.tour-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(212, 154, 0, 0.2);
    border: 1px solid #d49a00;
    color: #f5c042;
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 700;
    margin-bottom: 15px;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.tour-hero-title {
    font-size: 2.8rem;
    font-weight: 800;
    margin: 0 0 15px;
    line-height: 1.2;
}
.tour-meta-row {
    display: flex;
    flex-wrap: wrap;
    gap: 25px;
    font-size: 1rem;
    color: #e2e8f0;
}
.tour-meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
}
.tour-meta-item i {
    color: #d49a00;
}

/* Contenido Principal */
.tour-main-layout {
    max-width: 1200px;
    margin: 40px auto 80px;
    padding: 0 20px;
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 40px;
    align-items: start;
}

@media (max-width: 992px) {
    .tour-main-layout {
        grid-template-columns: 1fr;
    }
}

.tour-card-box {
    background: #ffffff;
    border-radius: 14px;
    padding: 32px;
    margin-bottom: 30px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
}
.tour-card-box h2 {
    color: #0f2a4a;
    font-size: 1.5rem;
    font-weight: 700;
    margin-top: 0;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 2px solid #f1f5f9;
    padding-bottom: 12px;
}
.tour-card-box h2 i {
    color: #d49a00;
}

/* Itinerario */
.itinerary-timeline {
    position: relative;
    padding-left: 28px;
    margin-top: 20px;
}
.itinerary-timeline::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 5px;
    bottom: 5px;
    width: 2px;
    background: #e2e8f0;
}
.itinerary-item {
    position: relative;
    margin-bottom: 28px;
}
.itinerary-item:last-child {
    margin-bottom: 0;
}
.itinerary-dot {
    position: absolute;
    left: -28px;
    top: 3px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #d49a00;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #d49a00;
}
.itinerary-day-badge {
    display: inline-block;
    background: #0f2a4a;
    color: #fff;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 700;
    margin-bottom: 8px;
}
.itinerary-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 6px;
}
.itinerary-desc {
    color: #64748b;
    line-height: 1.6;
    margin: 0;
}

/* Incluye / No incluye */
.check-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}
@media (max-width: 600px) {
    .check-grid {
        grid-template-columns: 1fr;
    }
}
.check-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 0.95rem;
    color: #334155;
    line-height: 1.4;
}
.check-item i.fa-check {
    color: #10b981;
    margin-top: 3px;
}
.check-item i.fa-xmark {
    color: #ef4444;
    margin-top: 3px;
}

/* Formulario Flotante de Reserva */
.booking-sticky-card {
    background: #ffffff;
    border-radius: 16px;
    border: 2px solid #d49a00;
    box-shadow: 0 10px 30px rgba(212, 154, 0, 0.12);
    padding: 30px;
    position: sticky;
    top: 90px;
}
.booking-price-header {
    text-align: center;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 18px;
    margin-bottom: 20px;
}
.booking-from {
    font-size: 0.85rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.booking-price-val {
    font-size: 2.3rem;
    font-weight: 800;
    color: #0f2a4a;
    display: block;
}
.booking-form-group {
    margin-bottom: 16px;
}
.booking-form-group label {
    display: block;
    font-size: 0.88rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}
.booking-form-group input {
    width: 100%;
    padding: 11px 14px;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.95rem;
    color: #1e293b;
    box-sizing: border-box;
    transition: border-color 0.2s;
}
.booking-form-group input:focus {
    outline: none;
    border-color: #d49a00;
    box-shadow: 0 0 0 3px rgba(212, 154, 0, 0.15);
}
.booking-total-summary {
    background: #f8fafc;
    border-radius: 8px;
    padding: 14px 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 20px 0;
    font-weight: 700;
}
.booking-total-label {
    color: #475569;
}
.booking-total-amount {
    color: #0f2a4a;
    font-size: 1.35rem;
}
.btn-submit-booking {
    width: 100%;
    background: #0f2a4a;
    color: #ffffff;
    border: none;
    padding: 14px;
    border-radius: 8px;
    font-size: 1.05rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.2s ease;
}
.btn-submit-booking:hover {
    background: #d49a00;
    color: #0f2a4a;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(212, 154, 0, 0.3);
}
.booking-security-note {
    text-align: center;
    font-size: 0.8rem;
    color: #94a3b8;
    margin-top: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
</style>

<!-- Hero Section del Paquete -->
<section class="tour-hero">
    <div class="tour-hero-container">
        <span class="tour-hero-badge"><i class="fa-solid fa-compass"></i> <?= htmlspecialchars($package['category'] ?? 'Tour Exclusivo') ?></span>
        <h1 class="tour-hero-title"><?= htmlspecialchars($package['name']) ?></h1>
        <div class="tour-meta-row">
            <div class="tour-meta-item">
                <i class="fa-solid fa-location-dot"></i>
                <span><?= htmlspecialchars($package['destino']) ?></span>
            </div>
            <div class="tour-meta-item">
                <i class="fa-solid fa-clock"></i>
                <span><?= htmlspecialchars($package['durationLabel'] ?: $package['duration'] . ' Días') ?></span>
            </div>
            <div class="tour-meta-item">
                <i class="fa-solid fa-star"></i>
                <span><?= htmlspecialchars((string)($package['rating'] ?? '4.9')) ?> (<?= htmlspecialchars((string)($package['reviews'] ?? '120')) ?> reseñas)</span>
            </div>
        </div>
    </div>
</section>

<!-- Layout Principal -->
<div class="tour-main-layout">
    
    <!-- Columna Izquierda: Información Detallada -->
    <div class="tour-details-col">
        
        <!-- Descripción -->
        <div class="tour-card-box">
            <h2><i class="fa-solid fa-circle-info"></i> Resumen de la Experiencia</h2>
            <p style="font-size: 1.05rem; line-height: 1.7; color: #475569; margin: 0;">
                <?= nl2br(htmlspecialchars($package['description'] ?: 'Disfruta de una inolvidable expedición con guías certificados y transporte privado, diseñado para brindarte la mejor experiencia en los atractivos más emblemáticos del Perú.')) ?>
            </p>
        </div>

        <!-- Itinerario -->
        <?php if (!empty($itinerario)): ?>
        <div class="tour-card-box">
            <h2><i class="fa-solid fa-route"></i> Itinerario Detallado</h2>
            <div class="itinerary-timeline">
                <?php foreach ($itinerario as $dia): ?>
                    <?php
                        $diaNum = $dia['dia'] ?? $dia['day'] ?? '';
                        $diaTitulo = $dia['titulo'] ?? $dia['title'] ?? 'Día ' . $diaNum;
                        $diaDesc = $dia['descripcion'] ?? $dia['description'] ?? '';
                    ?>
                    <div class="itinerary-item">
                        <div class="itinerary-dot"></div>
                        <span class="itinerary-day-badge">Día <?= htmlspecialchars($diaNum) ?></span>
                        <h4 class="itinerary-title"><?= htmlspecialchars($diaTitulo) ?></h4>
                        <p class="itinerary-desc"><?= nl2br(htmlspecialchars($diaDesc)) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Servicios Incluidos y No Incluidos -->
        <div class="tour-card-box">
            <h2><i class="fa-solid fa-list-check"></i> Qué incluye este paquete</h2>
            
            <div class="check-grid" style="margin-bottom: 25px;">
                <?php if (!empty($incluye)): ?>
                    <?php foreach ($incluye as $inc): ?>
                        <?php $itemText = $inc['item'] ?? $inc['included_item'] ?? ''; ?>
                        <div class="check-item">
                            <i class="fa-solid fa-check"></i>
                            <span><?= htmlspecialchars($itemText) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="check-item"><i class="fa-solid fa-check"></i> <span>Transporte turístico privado ida y vuelta</span></div>
                    <div class="check-item"><i class="fa-solid fa-check"></i> <span>Guía profesional oficial bilingüe</span></div>
                    <div class="check-item"><i class="fa-solid fa-check"></i> <span>Tickets y boletos de ingreso</span></div>
                    <div class="check-item"><i class="fa-solid fa-check"></i> <span>Asistencia personalizada permanente 24/7</span></div>
                <?php endif; ?>
            </div>

            <?php if (!empty($noIncluye)): ?>
                <h3 style="font-size: 1.1rem; color: #64748b; margin-top: 25px; margin-bottom: 15px;">No incluye</h3>
                <div class="check-grid">
                    <?php foreach ($noIncluye as $no): ?>
                        <?php $noItemText = $no['item'] ?? $no['not_included_item'] ?? ''; ?>
                        <div class="check-item">
                            <i class="fa-solid fa-xmark"></i>
                            <span><?= htmlspecialchars($noItemText) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Hoteles / Alojamiento si existen -->
        <?php if (!empty($hoteles)): ?>
        <div class="tour-card-box">
            <h2><i class="fa-solid fa-hotel"></i> Opciones de Alojamiento</h2>
            <div class="table-responsive">
                <table class="admin-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                            <th style="padding: 10px;">Hotel</th>
                            <th style="padding: 10px;">Categoría</th>
                            <th style="padding: 10px;">Simple</th>
                            <th style="padding: 10px;">Doble</th>
                            <th style="padding: 10px;">Triple</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hoteles as $h): ?>
                            <tr style="border-bottom: 1px solid #edf2f7;">
                                <td style="padding: 12px 10px; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($h['hotel']) ?></td>
                                <td style="padding: 12px 10px;"><?= htmlspecialchars($h['hotel_category'] ?? '3 Estrellas') ?></td>
                                <td style="padding: 12px 10px;">$<?= number_format((float)$h['simple'], 2) ?></td>
                                <td style="padding: 12px 10px;">$<?= number_format((float)$h['doble'], 2) ?></td>
                                <td style="padding: 12px 10px;">$<?= number_format((float)$h['triple'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Columna Derecha: Tarjeta de Reserva -->
    <div class="tour-sidebar-col">
        <div class="booking-sticky-card">
            <div class="booking-price-header">
                <span class="booking-from">Precio por persona</span>
                <span class="booking-price-val"><?= formatPrice($package['price']) ?></span>
            </div>

            <form action="reserva.php" method="POST" id="bookingForm">
                <input type="hidden" name="paquete_id" value="<?= htmlspecialchars($package['id']) ?>">
                <input type="hidden" name="precio" id="basePrice" value="<?= htmlspecialchars($package['price']) ?>">

                <div class="booking-form-group">
                    <label for="fecha_salida"><i class="fa-solid fa-calendar-days" style="color: #d49a00;"></i> Fecha de Salida *</label>
                    <input type="date" id="fecha_salida" name="fecha_salida" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
                </div>

                <div class="booking-form-group">
                    <label for="cantidad_personas"><i class="fa-solid fa-users" style="color: #d49a00;"></i> Cantidad de Personas *</label>
                    <input type="number" id="cantidad_personas" name="cantidad_personas" min="1" max="50" value="1" required onchange="calculateTotal()" onkeyup="calculateTotal()">
                </div>

                <div class="booking-form-group">
                    <label for="nombre_cliente"><i class="fa-solid fa-user" style="color: #d49a00;"></i> Nombre Completo *</label>
                    <input type="text" id="nombre_cliente" name="nombre_cliente" required placeholder="Ej. Juan Pérez" value="<?= htmlspecialchars($userName) ?>">
                </div>

                <div class="booking-form-group">
                    <label for="email_cliente"><i class="fa-solid fa-envelope" style="color: #d49a00;"></i> Correo Electrónico *</label>
                    <input type="email" id="email_cliente" name="email_cliente" required placeholder="correo@ejemplo.com" value="<?= htmlspecialchars($userEmail) ?>">
                </div>

                <div class="booking-form-group">
                    <label for="telefono"><i class="fa-solid fa-phone" style="color: #d49a00;"></i> Teléfono / WhatsApp *</label>
                    <input type="tel" id="telefono" name="telefono" required placeholder="+51 989003025" value="<?= htmlspecialchars($userPhone) ?>">
                </div>

                <div class="booking-total-summary">
                    <span class="booking-total-label">Total a pagar:</span>
                    <span class="booking-total-amount" id="displayTotal">$<?= number_format($package['price'], 2) ?> USD</span>
                </div>

                <button type="submit" class="btn-submit-booking">
                    <i class="fa-solid fa-paper-plane"></i> Solicitar Reserva Ahora
                </button>

                <div class="booking-security-note">
                    <i class="fa-solid fa-shield-halved"></i> Notificación inmediata al administrador
                </div>
            </form>
        </div>
    </div>

</div>

<script>
const exchangeRate = <?= (float)($exchange_rates[$current_currency] ?? 1.0) ?>;
const currencySymbol = <?= json_encode($current_currency === 'PEN' ? 'S/ ' : ($current_currency === 'EUR' ? '€ ' : '$ ')) ?>;
const currencyCode = <?= json_encode($current_currency) ?>;

function calculateTotal() {
    const basePrice = parseFloat(document.getElementById('basePrice').value) || 0;
    const qty = parseInt(document.getElementById('cantidad_personas').value) || 1;
    const total = basePrice * Math.max(1, qty) * exchangeRate;
    document.getElementById('displayTotal').textContent = currencySymbol + total.toFixed(2) + ' ' + currencyCode;
}
calculateTotal();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
