<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Hoteles';
$hotels = getAllHotels();
$categories = getHotelCategories();
$services = getHotelServices();

// Filtros
$filterCategory = $_GET['categoria'] ?? '';
$filterService = $_GET['servicio'] ?? '';
$filterPackage = $_GET['paquete'] ?? '';

if ($filterCategory || $filterService || $filterPackage) {
    $hotels = array_filter($hotels, function($hotel) use ($filterCategory, $filterService, $filterPackage) {
        if ($filterCategory && stripos($hotel['hotel_category'], $filterCategory) === false) return false;
        if ($filterService && stripos($hotel['services'], $filterService) === false) return false;
        if ($filterPackage && stripos($hotel['package_name'], $filterPackage) === false) return false;
        return true;
    });
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<section class="section section-hotels">
    <div class="container section-header">
        <h1>Catálogo de Hoteles</h1>
        <p>Descubre nuestros hoteles asociados con diferentes categorías y servicios.</p>
    </div>

    <div class="container">
        <form class="filter-form" method="get">
            <div class="filter-group">
                <label for="categoria">Categoría:</label>
                <select name="categoria" id="categoria">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= htmlspecialchars($category) ?>" <?= $filterCategory === $category ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="servicio">Servicio:</label>
                <select name="servicio" id="servicio">
                    <option value="">Todos los servicios</option>
                    <?php foreach ($services as $service): ?>
                        <option value="<?= htmlspecialchars($service) ?>" <?= $filterService === $service ? 'selected' : '' ?>>
                            <?= htmlspecialchars($service) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="paquete">Paquete:</label>
                <input type="text" name="paquete" id="paquete" placeholder="Buscar por paquete..." value="<?= htmlspecialchars($filterPackage) ?>">
            </div>
            <button type="submit" class="button button-primary">Filtrar</button>
            <a href="hoteles.php" class="button button-secondary">Limpiar</a>
        </form>

        <div class="grid hotels-grid">
            <?php foreach ($hotels as $hotel): ?>
                <article class="card card-hotel">
                    <div class="hotel-header">
                        <h3><?= htmlspecialchars($hotel['hotel']) ?></h3>
                        <span class="hotel-category"><?= htmlspecialchars($hotel['hotel_category']) ?></span>
                    </div>
                    <div class="hotel-services">
                        <p><strong>Servicios:</strong> <?= htmlspecialchars($hotel['services']) ?></p>
                    </div>
                    <div class="hotel-prices">
                        <div class="price-item">
                            <span>Simple</span>
                            <strong>$<?= number_format($hotel['simple'], 2) ?>/noche</strong>
                        </div>
                        <div class="price-item">
                            <span>Doble</span>
                            <strong>$<?= number_format($hotel['doble'], 2) ?>/noche</strong>
                        </div>
                        <div class="price-item">
                            <span>Triple</span>
                            <strong>$<?= number_format($hotel['triple'], 2) ?>/noche</strong>
                        </div>
                    </div>
                    <p class="hotel-package">Disponible en: <?= htmlspecialchars($hotel['package_name']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
