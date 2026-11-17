<?php
ini_set('display_errors', 0); // Desactivar en producción por seguridad
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/functions.php';

// Función helper para escapar texto en HTML de forma segura
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Manejo de mensajes de estado tras redirección o errores
$reviewSuccess = isset($_GET['review']) && $_GET['review'] === 'success';
$reviewError = '';

// Valores por defecto para el formulario en caso de error
$formNombre = '';
$formEmail = '';
$formComentario = '';
$formPaqueteId = null;
$formCalificacion = 5;

// Procesar el envío de una nueva reseña (Patrón POST-Redirect-GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $calificacion = (int)($_POST['calificacion'] ?? 5);
    $comentario = trim($_POST['comentario'] ?? '');
    $paqueteId = !empty($_POST['paquete_id']) ? (int)$_POST['paquete_id'] : null;

    // Repopular variables para conservar entradas en caso de error
    $formNombre = $nombre;
    $formEmail = $email;
    $formComentario = $comentario;
    $formPaqueteId = $paqueteId;
    $formCalificacion = $calificacion;

    // Validaciones
    if (empty($nombre) || empty($email) || empty($comentario)) {
        $reviewError = 'Por favor completa todos los campos obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $reviewError = 'Por favor ingresa un correo electrónico válido.';
    } elseif ($calificacion < 1 || $calificacion > 5) {
        $reviewError = 'La calificación seleccionada no es válida.';
    } else {
        $user = getUserByEmail($email);
        if (!$user) {
            // Genera contraseña aleatoria segura
            $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $userId = createUser($nombre, $email, '', $randomPassword);
        } else {
            $userId = $user['id'];
        }

        if ($userId) {
            if (!$paqueteId) {
                $allPackages = getPackages();
                $paqueteId = !empty($allPackages) ? (int)$allPackages[0]['id'] : 1;
            }

            createResena($userId, $paqueteId, $calificacion, $comentario);
            
            // Redirección limpia (Evita reenvío del formulario al refrescar)
            header('Location: destinos.php?review=success');
            exit;
        } else {
            $reviewError = 'Ocurrió un problema al registrar tus datos. Por favor reinténtalo.';
        }
    }
}

$pageTitle = 'Nuestros Destinos';
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

$packages = getPackages($search, ['category' => $category]);
$categoriasFiltros = getDistinctCategories();

recordPageView('Destinos', 'Catalogo');
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<!-- Portada con Imagen Oficial de Machu Picchu -->
<section class="destinos-machupicchu-hero">
    <div class="machupicchu-overlay"></div>
    <div class="container hero-content-wrapper">
        
        <span class="hero-top-tag">Vive la Magia del Perú</span>
        <h1 class="hero-title-machu">El Viaje de tus Sueños Comienza Aquí</h1>
        <p class="hero-subtitle-machu">Descubre paisajes deslumbrantes, historia viva y aventuras inolvidables en cada rincón del Perú.</p>
        
        <!-- Buscador XL Flotante Ampliado -->
        <div class="search-floating-card-xl">
            <form action="destinos.php" method="get" class="search-form-xl" role="search">
                
                <div class="search-col-xl">
                    <label for="search-input">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"></path><circle cx="12" cy="10" r="3"></circle></svg> 
                        ¿A dónde quieres ir?
                    </label>
                    <input type="search" id="search-input" name="search" placeholder="Escribe un destino, tour o actividad..." value="<?= e($search) ?>">
                </div>

                <?php if (!empty($categoriasFiltros)): ?>
                <div class="search-col-xl border-left-xl">
                    <label for="category-select">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg> 
                        Categoría / Región
                    </label>
                    <select name="category" id="category-select">
                        <option value="">Todas las regiones</option>
                        <?php foreach ($categoriasFiltros as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                <?= e($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="search-col-btn-xl">
                    <button type="submit" class="btn-machu-search-xl">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <span>Buscar Destino</span>
                    </button>
                </div>

            </form>
        </div>

    </div>
</section>

<!-- Catálogo de Tarjetas Completamente Fluido y Adaptable -->
<section class="section section-packages bg-light-destinos">
    <div class="destinos-full-container">
        
        <?php if (!empty($search) || !empty($category)): ?>
            <div class="filter-results-tag">
                <span>Resultados de búsqueda para: <strong><?= e($search ?: $category) ?></strong></span>
                <a href="destinos.php" class="btn-clear-machu">Limpiar filtros ✕</a>
            </div>
        <?php endif; ?>

        <?php if (empty($packages)): ?>
            <div class="no-results-machu">
                <div class="no-results-icon" aria-hidden="true">⛰️</div>
                <h3>No encontramos paquetes para tu búsqueda</h3>
                <p>Intenta buscando con otra palabra clave o selecciona otra región.</p>
                <a href="destinos.php" class="button button-primary">Ver todos los paquetes</a>
            </div>
        <?php else: ?>
            <div class="cards-grid-4">
                <?php foreach ($packages as $package): ?>
                    <article class="card card-package card-machu-style">
                        <div class="card-img-container">
                            <img src="<?= e($package['img'] ?? 'assets/img/default-package.jpg') ?>" alt="<?= e($package['name']) ?>" loading="lazy">
                        </div>
                        <div class="card-body">
                            <h3 class="card-title-machu"><?= e($package['name']) ?></h3>
                            <p class="card-subtitle-machu"><?= e($package['location'] ?? 'Cusco - Machu Picchu') ?> · <?= e($package['durationLabel'] ?? '') ?></p>
                            
                            <div class="card-bottom-machu">
                                <div class="price-rating-box">
                                    <small><?= e(__('from')) ?> <strong><?= formatPrice(!empty($package['price']) && $package['price'] > 0 ? $package['price'] : 191) ?></strong></small>
                                    <span class="badge-inline-rating">⭐ <?= e((string)($package['rating'] ?? '5.0')) ?></span>
                                </div>
                                <a class="btn-outline-machu" href="paquete.php?id=<?= urlencode($package['id']) ?>">
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

<!-- SECCIÓN DE TESTIMONIOS Y RESEÑAS -->
<section class="section section-testimonials bg-white-reviews">
    <div class="container">

        <?php if ($reviewSuccess): ?>
            <div class="alert alert-success text-center mb-4" role="alert" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
                ¡Muchas gracias! Tu reseña ha sido registrada con éxito.
            </div>
        <?php endif; ?>

        <?php if ($reviewError): ?>
            <div class="alert alert-danger text-center mb-4" role="alert" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
                <?= e($reviewError) ?>
            </div>
        <?php endif; ?>

        <?php include __DIR__ . '/includes/reviews_section.php'; ?>

    </div>
</section>

<!-- MODAL PARA CREAR RESEÑA -->
<div id="reviewModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" style="display: <?= $reviewError ? 'flex' : 'none' ?>; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.65); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #fff; border-radius: 16px; padding: 2rem; max-width: 500px; width: 90%; position: relative; box-shadow: 0 15px 30px rgba(0,0,0,0.3);">
        <button id="closeReviewModal" type="button" aria-label="Cerrar modal" style="position: absolute; top: 15px; right: 20px; background: none; border: none; font-size: 1.6rem; cursor: pointer; color: #666;">&times;</button>
        
        <h3 id="modalTitle" style="margin-top: 0; margin-bottom: 1rem; text-align: center; color: #0f172a; font-weight: 800;">Escribe tu Reseña</h3>
        
        <form action="destinos.php" method="post">
            <input type="hidden" name="action" value="submit_review">
            
            <div style="margin-bottom: 1rem;">
                <label for="modal-nombre" style="display: block; font-weight: 600; margin-bottom: 0.4rem; color: #334155;">Tu Nombre *</label>
                <input type="text" id="modal-nombre" name="nombre" value="<?= e($formNombre) ?>" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="modal-email" style="display: block; font-weight: 600; margin-bottom: 0.4rem; color: #334155;">Tu Email *</label>
                <input type="email" id="modal-email" name="email" value="<?= e($formEmail) ?>" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="modal-paquete" style="display: block; font-weight: 600; margin-bottom: 0.4rem; color: #334155;">Destino / Paquete Visitado</label>
                <select id="modal-paquete" name="paquete_id" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;">
                    <option value="">-- Selecciona un paquete --</option>
                    <?php if (!empty($packages)): ?>
                        <?php foreach ($packages as $pkg): ?>
                            <option value="<?= (int)$pkg['id'] ?>" <?= $formPaqueteId === (int)$pkg['id'] ? 'selected' : '' ?>><?= e($pkg['name']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="modal-calificacion" style="display: block; font-weight: 600; margin-bottom: 0.4rem; color: #334155;">Calificación</label>
                <select id="modal-calificacion" name="calificacion" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;">
                    <option value="5" <?= $formCalificacion === 5 ? 'selected' : '' ?>>⭐⭐⭐⭐⭐ (5/5)</option>
                    <option value="4" <?= $formCalificacion === 4 ? 'selected' : '' ?>>⭐⭐⭐⭐ (4/5)</option>
                    <option value="3" <?= $formCalificacion === 3 ? 'selected' : '' ?>>⭐⭐⭐ (3/5)</option>
                    <option value="2" <?= $formCalificacion === 2 ? 'selected' : '' ?>>⭐⭐ (2/5)</option>
                    <option value="1" <?= $formCalificacion === 1 ? 'selected' : '' ?>>⭐ (1/5)</option>
                </select>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="modal-comentario" style="display: block; font-weight: 600; margin-bottom: 0.4rem; color: #334155;">Tu Comentario *</label>
                <textarea id="modal-comentario" name="comentario" rows="4" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;" placeholder="Cuéntanos cómo fue tu experiencia..."><?= e($formComentario) ?></textarea>
            </div>

            <button type="submit" class="btn-machu-search-xl" style="width: 100%; justify-content: center; padding: 12px;">Publicar Reseña</button>
        </form>
    </div>
</div>

<style>
    /* Reset local */
    .bg-light-destinos *, .bg-light-destinos *::before, .bg-light-destinos *::after {
        box-sizing: border-box;
    }

    /* Hero Banner */
    .destinos-machupicchu-hero {
        position: relative;
        min-height: 70vh;
        padding: 90px 20px 70px;
        background-image: url('https://www.ytuqueplanes.com/imagenes//fotos/novedades/b-Macchupicchu-todo-lo-que-debes-conocer-antes-de-visitarlo.webp');
        background-position: center center;
        background-size: cover;
        background-repeat: no-repeat;
        text-align: center;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .machupicchu-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.45) 0%, rgba(15, 23, 42, 0.65) 100%);
        z-index: 1;
    }
    .hero-content-wrapper {
        position: relative;
        z-index: 2;
        max-width: 1050px;
        width: 100%;
        margin: 0 auto;
    }
    .hero-top-tag {
        display: inline-block;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(10px);
        color: #ffffff;
        padding: 8px 24px;
        border-radius: 30px;
        font-size: 0.88rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 22px;
        border: 1px solid rgba(255, 255, 255, 0.4);
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    }
    .hero-title-machu {
        font-size: 3.2rem;
        font-weight: 900;
        color: #ffffff;
        margin-bottom: 16px;
        line-height: 1.15;
        letter-spacing: -0.5px;
        text-shadow: 0 3px 6px rgba(0,0,0,0.8);
    }
    .hero-subtitle-machu {
        font-size: 1.2rem;
        font-weight: 500;
        color: #ffffff;
        max-width: 850px;
        margin: 0 auto 40px auto;
        line-height: 1.5;
        text-shadow: 0 2px 4px rgba(0,0,0,0.9);
    }

    /* Buscador XL */
    .search-floating-card-xl {
        background: #ffffff;
        border-radius: 24px;
        padding: 16px 24px;
        box-shadow: 0 25px 60px rgba(0,0,0,0.3);
        max-width: 1000px;
        margin: 0 auto;
    }
    .search-form-xl {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .search-col-xl {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 8px 12px;
    }
    .search-col-xl.border-left-xl {
        border-left: 2px solid #f1f5f9;
    }
    .search-col-xl label {
        font-size: 0.82rem;
        font-weight: 800;
        color: #d4a017;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 6px;
    }
    .search-col-xl input, .search-col-xl select {
        width: 100%;
        border: none;
        background: transparent;
        font-size: 1.05rem;
        font-weight: 600;
        color: #0f172a;
        outline: none;
    }
    .search-col-xl input::placeholder {
        color: #94a3b8;
        font-weight: 400;
    }
    .btn-machu-search-xl {
        background: linear-gradient(135deg, #d4a017 0%, #b8860b 100%);
        color: #ffffff;
        border: none;
        padding: 16px 32px;
        border-radius: 18px;
        font-weight: 800;
        font-size: 1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        white-space: nowrap;
        transition: all 0.25s ease;
        box-shadow: 0 8px 20px rgba(212, 160, 23, 0.35);
    }
    .btn-machu-search-xl:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(212, 160, 23, 0.5);
    }

    /* Contenedor Adaptable */
    .section-packages.bg-light-destinos {
        width: 100% !important;
        padding: 60px 0 !important;
        background: #f8fafc !important;
    }

    .destinos-full-container {
        width: 100% !important;
        max-width: 95% !important;
        margin: 0 auto !important;
        padding: 0 15px !important;
    }

    /* Grilla de Tarjetas */
    .cards-grid-4 {
        display: grid !important;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important;
        gap: 25px !important;
        width: 100% !important;
    }

    .bg-white-reviews { background: #ffffff; padding: 60px 0; }
    
    .card-machu-style {
        width: 100% !important;
        max-width: 100% !important;
        background: #ffffff !important;
        border-radius: 20px !important;
        overflow: hidden !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03) !important;
        transition: transform 0.3s ease, box-shadow 0.3s ease !important;
        display: flex !important;
        flex-direction: column !important;
        margin: 0 !important;
    }
    .card-machu-style:hover {
        transform: translateY(-6px) !important;
        box-shadow: 0 15px 30px rgba(0,0,0,0.08) !important;
    }
    .card-img-container {
        position: relative !important;
        width: 100% !important;
        height: 220px !important;
        overflow: hidden !important;
    }
    .card-img-container img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
    }
    .card-machu-style .card-body {
        padding: 20px !important;
        display: flex !important;
        flex-direction: column !important;
        flex: 1 1 auto !important;
    }
    .card-title-machu {
        font-size: 1.15rem !important;
        font-weight: 800 !important;
        color: #1e293b !important;
        margin: 0 0 8px 0 !important;
        line-height: 1.3 !important;
    }
    .card-subtitle-machu {
        font-size: 0.85rem !important;
        color: #64748b !important;
        margin-bottom: 20px !important;
    }
    .card-bottom-machu {
        margin-top: auto !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 14px !important;
    }
    .price-rating-box {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
    }
    .price-rating-box small {
        font-size: 0.82rem !important;
        color: #94a3b8 !important;
    }
    .price-rating-box strong {
        color: #334155 !important;
        font-size: 0.95rem !important;
    }
    .badge-inline-rating {
        background: #f1f5f9 !important;
        padding: 4px 10px !important;
        border-radius: 12px !important;
        font-size: 0.8rem !important;
        font-weight: 700 !important;
        color: #334155 !important;
    }
    .btn-outline-machu {
        display: block !important;
        width: 100% !important;
        text-align: center !important;
        border: 1.5px solid #e2e8f0 !important;
        background: #ffffff !important;
        color: #d4a017 !important;
        padding: 10px !important;
        border-radius: 25px !important;
        font-size: 0.9rem !important;
        font-weight: 700 !important;
        text-decoration: none !important;
        transition: all 0.2s ease !important;
    }
    .btn-outline-machu:hover {
        border-color: #d4a017 !important;
        background: #fefce8 !important;
    }

    .filter-results-tag { display: flex; justify-content: space-between; align-items: center; background: #ffffff; padding: 14px 22px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
    .btn-clear-machu { color: #ef4444; font-weight: 700; text-decoration: none; }
    .no-results-machu { text-align: center; padding: 60px 20px; background: #ffffff; border-radius: 20px; }
    .no-results-icon { font-size: 3.5rem; margin-bottom: 12px; }

    /* Ajustes Móviles */
    @media (max-width: 900px) {
        .destinos-machupicchu-hero { min-height: auto; padding: 70px 15px 40px; }
        .hero-title-machu { font-size: 2.2rem; }
        .hero-subtitle-machu { font-size: 1.05rem; }
        .search-form-xl { flex-direction: column; align-items: stretch; }
        .search-col-xl.border-left-xl { border-left: none; border-top: 2px solid #f1f5f9; padding-top: 12px; }
        .btn-machu-search-xl { width: 100%; justify-content: center; }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('reviewModal');
    const openBtn = document.getElementById('openReviewModal');
    const closeBtn = document.getElementById('closeReviewModal');

    if (modal) {
        if (openBtn) {
            openBtn.addEventListener('click', function() {
                modal.style.display = 'flex';
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
        }

        window.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>