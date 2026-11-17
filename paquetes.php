<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Nuestros Destinos';
$search = trim($_GET['search'] ?? '');
$filters = [
    'category' => trim($_GET['category'] ?? ''),
    'min_price' => trim($_GET['min_price'] ?? ''),
    'max_price' => trim($_GET['max_price'] ?? ''),
];

$packages = getPackages($search, $filters);
$categoriasFiltros = getDistinctCategories();

recordPageView('Destinos', 'Catalogo');
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<!-- Hero Banner Ultra Moderno -->
<section class="destinos-hero-modern">
    <div class="destinos-hero-overlay"></div>
    <div class="container destinos-hero-container">
        
        <div class="destinos-badge-glow">
            <span class="badge-dot"></span> CATÁLOGO DE EXPERIENCIAS
        </div>
        
        <h1 class="destinos-hero-title">Encuentra Tu Próximo Destino</h1>
        <p class="destinos-hero-subtitle">Filtra entre nuestras mejores rutas, paisajes y aventuras exclusivas por todo el Perú.</p>
        
        <!-- Tarjeta Flotante de Filtros -->
        <div class="filter-card-floating">
            <form action="destinos.php" method="get" class="filter-grid-form">
                
                <!-- Input Búsqueda -->
                <div class="input-box-modern">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d4a017" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="search" name="search" placeholder="¿A dónde quieres ir?" value="<?= htmlspecialchars($search) ?>">
                </div>

                <!-- Select Categoría -->
                <?php if (!empty($categoriasFiltros)): ?>
                <div class="input-box-modern">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d4a017" stroke-width="2.5"><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <select name="category">
                        <option value="">Todas las regiones</option>
                        <?php foreach ($categoriasFiltros as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $filters['category'] === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Rango de Precio -->
                <div class="input-box-modern price-inputs">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d4a017" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    <input type="number" name="min_price" placeholder="Mín $" value="<?= htmlspecialchars($filters['min_price']) ?>">
                    <span class="dash">-</span>
                    <input type="number" name="max_price" placeholder="Máx $" value="<?= htmlspecialchars($filters['max_price']) ?>">
                </div>

                <button type="submit" class="btn-modern-search">
                    <span>Explorar</span>
                </button>
            </form>
        </div>

    </div>
</section>

<!-- Catálogo de Tarjetas -->
<section class="section section-packages bg-catalog">
    <div class="container">
        
        <?php if (!empty($search) || !empty($filters['category']) || !empty($filters['min_price']) || !empty($filters['max_price'])): ?>
            <div class="active-filters-bar">
                <span>🔍 Filtros activos en el catálogo</span>
                <a href="destinos.php" class="btn-reset-modern">Limpiar búsqueda ✕</a>
            </div>
        <?php endif; ?>

        <?php if (empty($packages)): ?>
            <div class="empty-catalog-card">
                <div class="empty-icon">🏔️</div>
                <h3>Sin resultados para esta búsqueda</h3>
                <p>Intenta ajustando el rango de precio o cambiando el término de búsqueda.</p>
                <a href="destinos.php" class="button button-primary">Restablecer catálogo</a>
            </div>
        <?php else: ?>
            <div class="grid cards-grid">
                <?php foreach ($packages as $package): ?>
                    <article class="card card-package card-hover-effect">
                        <div class="card-image-wrap">
                            <img src="<?= htmlspecialchars($package['img']) ?>" alt="<?= htmlspecialchars($package['name']) ?>" loading="lazy">
                            <span class="badge-rating">⭐ <?= htmlspecialchars($package['rating']) ?></span>
                        </div>
                        <div class="card-body">
                            <span class="package-duration-tag">⏱️ <?= htmlspecialchars($package['durationLabel']) ?></span>
                            <h3 class="package-title"><?= htmlspecialchars($package['name']) ?></h3>
                            
                            <div class="card-bottom-row">
                                <div class="price-tag">
                                    <small><?= htmlspecialchars(__('from')) ?></small>
                                    <strong><?= htmlspecialchars($package['priceLabel'] ?: formatPrice($package['price'])) ?></strong>
                                </div>
                                <a class="btn-card-action" href="paquete.php?id=<?= htmlspecialchars($package['id']) ?>">
                                    Ver paquete
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<!-- Estilos Visuales de Alto Impacto -->
<style>
    .destinos-hero-modern {
        position: relative;
        padding: 75px 20px 85px;
        background: url('https://images.unsplash.com/photo-1526392060635-9d6019884377?q=80&w=2000&auto=format&fit=crop') center/cover no-repeat;
        color: #ffffff;
        text-align: center;
    }
    .destinos-hero-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.85) 0%, rgba(15, 23, 42, 0.95) 100%);
    }
    .destinos-hero-container {
        position: relative;
        z-index: 2;
        max-width: 950px;
        margin: 0 auto;
    }
    .destinos-badge-glow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(212, 160, 23, 0.15);
        border: 1px solid rgba(212, 160, 23, 0.4);
        color: #f3c649;
        padding: 6px 16px;
        border-radius: 30px;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 1.2px;
        margin-bottom: 18px;
    }
    .badge-dot {
        width: 8px;
        height: 8px;
        background: #f3c649;
        border-radius: 50%;
        box-shadow: 0 0 10px #f3c649;
    }
    .destinos-hero-title {
        font-size: 2.8rem;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: -0.5px;
        margin-bottom: 12px;
    }
    .destinos-hero-subtitle {
        font-size: 1.1rem;
        color: #94a3b8;
        max-width: 650px;
        margin: 0 auto 35px;
    }

    /* Buscador Flotante */
    .filter-card-floating {
        background: #ffffff;
        padding: 12px;
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.35);
        border: 1px solid rgba(255,255,255,0.2);
    }
    .filter-grid-form {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .input-box-modern {
        display: flex;
        align-items: center;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 0 14px;
        flex: 1;
        min-width: 180px;
        transition: border-color 0.2s;
    }
    .input-box-modern:focus-within {
        border-color: #d4a017;
        background: #ffffff;
    }
    .input-box-modern input, .input-box-modern select {
        width: 100%;
        border: none;
        background: transparent;
        padding: 12px 8px;
        font-size: 0.92rem;
        color: #0f172a;
        outline: none;
    }
    .price-inputs input { width: 45%; text-align: center; }
    .price-inputs .dash { color: #94a3b8; font-weight: bold; }

    .btn-modern-search {
        background: linear-gradient(135deg, #d4a017 0%, #b8860b 100%);
        color: #ffffff;
        border: none;
        padding: 12px 30px;
        font-weight: 700;
        font-size: 0.95rem;
        border-radius: 10px;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-modern-search:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(212, 160, 23, 0.4);
    }

    /* Tarjetas del Catálogo */
    .bg-catalog { background: #f1f5f9; padding-top: 40px; }
    .card-hover-effect {
        border-radius: 16px !important;
        overflow: hidden;
        border: 1px solid #e2e8f0 !important;
        transition: transform 0.3s ease, box-shadow 0.3s ease !important;
    }
    .card-hover-effect:hover {
        transform: translateY(-6px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
    }
    .card-image-wrap {
        position: relative;
        height: 210px;
        overflow: hidden;
    }
    .card-image-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .badge-rating {
        position: absolute;
        top: 12px;
        right: 12px;
        background: rgba(15, 23, 42, 0.75);
        color: #ffffff;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        backdrop-filter: blur(4px);
    }
    .package-duration-tag {
        font-size: 0.8rem;
        color: #64748b;
        font-weight: 600;
        display: block;
        margin-bottom: 6px;
    }
    .package-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 18px;
        line-height: 1.3;
    }
    .card-bottom-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 12px;
        border-top: 1px solid #f1f5f9;
        margin-top: auto;
    }
    .price-tag small { font-size: 0.75rem; color: #94a3b8; display: block; text-transform: uppercase; }
    .price-tag strong { font-size: 1.15rem; color: #d4a017; font-weight: 800; }
    .btn-card-action {
        background: #0f172a;
        color: #ffffff;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        transition: background 0.2s;
    }
    .btn-card-action:hover { background: #d4a017; }

    .active-filters-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #ffffff;
        padding: 12px 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    }
    .btn-reset-modern { color: #ef4444; font-weight: 700; text-decoration: none; }
    .empty-catalog-card { text-align: center; padding: 60px 20px; background: #ffffff; border-radius: 16px; }
    .empty-icon { font-size: 3rem; margin-bottom: 12px; }

    @media (max-width: 768px) {
        .destinos-hero-title { font-size: 2rem; }
        .filter-grid-form { flex-direction: column; }
        .input-box-modern { width: 100%; }
        .btn-modern-search { width: 100%; }
    }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>