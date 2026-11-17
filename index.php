<?php
ob_start();

// 1. GESTIÓN Y PERSISTENCIA DE IDIOMA
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determinar idioma desde la URL o Cookies
if (isset($_GET['lang'])) {
    $lang = strtolower($_GET['lang']);
    if (in_array($lang, ['es', 'en'])) {
        $_SESSION['lang'] = $lang;
        setcookie('site_lang', $lang, time() + (86400 * 30), "/");
    }
}

$current_lang = $_SESSION['lang'] ?? $_COOKIE['site_lang'] ?? 'es';

if ($current_lang === 'es') {
    setcookie('googtrans', '', time() - 3600, '/', $_SERVER['HTTP_HOST'] ?? '');
    setcookie('googtrans', '', time() - 3600, '/');
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CARGA FLEXIBLE DE FUNCTIONS.PHP
$possiblePaths = [
    __DIR__ . '/functions.php',
    __DIR__ . '/includes/functions.php',
    dirname(__DIR__) . '/functions.php'
];

$loaded = false;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    die("Error crítico: No se encuentra functions.php en " . __DIR__);
}

// EVALUACIÓN DE SESIÓN (AUTOLOGIN DETECT)
$userId = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? null;
$usuarioLogueado = !empty($userId);
$nombreUsuarioLogueado = $_SESSION['user_name'] ?? $_SESSION['usuario_nombre'] ?? $_SESSION['user_nombre'] ?? $_SESSION['nombre'] ?? 'Viajero';
$inicialUsuario = !empty($nombreUsuarioLogueado) ? strtoupper(substr(trim($nombreUsuarioLogueado), 0, 1)) : 'U';

// PROCESAMIENTO DE FORMULARIOS (GUARDAR OPINIÓN)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'guardar_opinion') {
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $titulo = trim($_POST['titulo'] ?? '');
    $opinion = trim($_POST['opinion'] ?? '');
    $rating = (int)($_POST['rating'] ?? 5);
    $paqueteIdInput = !empty($_POST['paquete_id']) ? (int)$_POST['paquete_id'] : 1;

    // Captura de ID del usuario activo o registro automático de usuario
    $idUsuarioGuardar = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? $userId ?? null;

    if (!$idUsuarioGuardar) {
        $nombreGuest = trim($_POST['nombre_cliente'] ?? '');
        $emailGuest  = trim($_POST['email_cliente'] ?? '');
        if (empty($nombreGuest)) $nombreGuest = 'Viajero Inkaiko';
        if (empty($emailGuest) || !filter_var($emailGuest, FILTER_VALIDATE_EMAIL)) {
            $emailGuest = 'viajero.' . time() . rand(10, 99) . '@turismoinkaiko.com';
        }

        $userExist = getUserByEmail($emailGuest);
        if ($userExist) {
            $idUsuarioGuardar = $userExist['id'];
        } else {
            $idUsuarioGuardar = createUser($nombreGuest, $emailGuest, '', password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT));
        }

        if ($idUsuarioGuardar) {
            $_SESSION['user_id']    = $idUsuarioGuardar;
            $_SESSION['id']         = $idUsuarioGuardar;
            $_SESSION['user_name']  = $nombreGuest;
            $_SESSION['nombre']     = $nombreGuest;
            $_SESSION['user_email'] = $emailGuest;
            $_SESSION['user_role']  = 'cliente';
        }
    }

    if (empty($titulo)) {
        $titulo = '¡Experiencia fantástica!';
    }
    if (empty($opinion)) {
        $opinion = 'Excelente servicio y puntualidad en todo el recorrido.';
    }

    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("
            INSERT INTO resenas (usuario_id, paquete_id, calificacion, titulo, comentario, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                calificacion = VALUES(calificacion),
                titulo = VALUES(titulo),
                comentario = VALUES(comentario),
                created_at = NOW()
        ");
        $stmt->execute([(int)$idUsuarioGuardar, (int)$paqueteIdInput, (int)$rating, $titulo, $opinion]);

        header("Location: index.php?status=success#opiniones");
        exit();

    } catch (Exception $e) {
        die("Error al guardar en 'resenas': " . $e->getMessage());
    }
}

// CARGA DINÁMICA DE OPINIONES DESDE LA TABLA RESENAS
$listaOpiniones = [];
$totalOpiniones = 0;
$ratingPromedio = 5.0;
$desgloseRatings = [
    'Excelente' => ['count' => 0, 'percent' => 0],
    'Bueno'     => ['count' => 0, 'percent' => 0],
    'Promedio'  => ['count' => 0, 'percent' => 0],
    'Malo'      => ['count' => 0, 'percent' => 0],
    'Horrible'  => ['count' => 0, 'percent' => 0],
];

$pdo = null;
if (function_exists('get_db_connection')) {
    $pdo = get_db_connection();
}

if ($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, 
                   COALESCE(u.name, u.email, 'Viajero Inkaiko') as nombre_usuario
            FROM resenas r 
            LEFT JOIN usuarios u ON r.usuario_id = u.id
            ORDER BY r.created_at DESC
        ");
        $stmt->execute();
        $resenasBD = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sumaRating = 0;
        $counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

        foreach ($resenasBD as $r) {
            $nombreUsuario = $r['nombre_usuario'] ?? 'Viajero Inkaiko';
            $textoUbicacion = 'Cliente Verificado';

            $comentario = $r['comentario'] ?? '';
            $partes = explode("

", $comentario, 2);
            $titulo = !empty($r['titulo']) ? $r['titulo'] : (!empty($partes[0]) ? $partes[0] : 'Opinión de viaje');
            $opinion = !empty($r['titulo']) ? $comentario : (isset($partes[1]) ? $partes[1] : $comentario);

            $rating = (int)($r['calificacion'] ?? 5);
            if ($rating < 1 || $rating > 5) { $rating = 5; }

            $sumaRating += $rating;
            $counts[$rating]++;

            $listaOpiniones[] = [
                'nombre_completo' => (string)$nombreUsuario,
                'titulo'          => (string)$titulo,
                'opinion'         => (string)$opinion,
                'rating'          => $rating,
                'fecha'           => $r['created_at'] ?? date('Y-m-d H:i:s'),
                'aporte_info'     => (string)$textoUbicacion,
                'tour_info'       => 'Experiencia Turismo Inkaiko'
            ];
        }

        $totalOpiniones = count($listaOpiniones);
$paquetesDestacados = function_exists('getPackages') ? array_slice(getPackages(), 0, 4) : [];
        if ($totalOpiniones > 0) {
            $ratingPromedio = round($sumaRating / $totalOpiniones, 1);
            $desgloseRatings = [
                'Excelente' => ['count' => $counts[5], 'percent' => round(($counts[5] / $totalOpiniones) * 100)],
                'Bueno'     => ['count' => $counts[4], 'percent' => round(($counts[4] / $totalOpiniones) * 100)],
                'Promedio'  => ['count' => $counts[3], 'percent' => round(($counts[3] / $totalOpiniones) * 100)],
                'Malo'      => ['count' => $counts[2], 'percent' => round(($counts[2] / $totalOpiniones) * 100)],
                'Horrible'  => ['count' => $counts[1], 'percent' => round(($counts[1] / $totalOpiniones) * 100)],
            ];
        }

    } catch (PDOException $e) {
        $listaOpiniones = [];
        $totalOpiniones = 0;
    }
}

if (file_exists(__DIR__ . '/includes/header.php')) {
    include __DIR__ . '/includes/header.php';
} elseif (file_exists(__DIR__ . '/header.php')) {
    include __DIR__ . '/header.php';
}
?>

<style>
.scroll-top, .back-to-top, a[href="#top"], .btn-scroll-top, #scrollTop, .scroll-to-top, .scrollup {
    display: none !important;
}
/* CONTENEDOR PRINCIPAL DEL MODAL (PANTALLA COMPLETA OSCURECIDA) */
#reviewModal {
    display: none;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background-color: rgba(0, 0, 0, 0.5) !important;
    z-index: 999999 !important;
    justify-content: center !important;
    align-items: center !important;
}

/* CAJA O TARJETA INTERNA DEL MODAL */
#reviewModal .modal-content, 
#reviewModal > div {
    position: relative !important;
    background: #ffffff !important;
    max-width: 500px !important;
    width: 90% !important;
    border-radius: 12px !important;
    padding: 25px !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
    margin: auto !important;
}
/* HERO */
.hero-home-modern {
    position: relative;
    min-height: 85vh;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    align-items: center;
    padding: 60px 2% 90px 2%;
    overflow: hidden;
    color: #ffffff;
    text-align: center;
}

.hero-bg-slider { position: absolute; inset: 0; z-index: 1; }

.hero-bg-slide {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center center;
    background-repeat: no-repeat;
    opacity: 0;
    transition: opacity 1.2s ease-in-out, transform 6s ease-out;
    transform: scale(1.04);
    will-change: transform, opacity;
}

.hero-bg-slide.active { opacity: 1; transform: scale(1); }

.hero-home-modern::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0, 0, 0, 0.05) 0%, rgba(0, 0, 0, 0.15) 50%, rgba(0, 0, 0, 0.35) 100%);
    z-index: 2;
}

.hero-clean-content {
    position: relative;
    z-index: 3;
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    background: none !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
}

.hero-badge-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(15, 23, 42, 0.6);
    border: 1px solid rgba(212, 160, 23, 0.9);
    color: #fef08a;
    padding: 12px 28px;
    border-radius: 30px;
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 22px;
}

.hero-clean-content h1 {
    font-size: 4.6rem;
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 22px;
    color: #ffffff !important;
    text-shadow: 0 2px 4px rgba(0,0,0,0.8);
}

.hero-clean-content p.hero-sub {
    font-size: 1.6rem;
    color: #ffffff !important;
    margin-bottom: 35px;
    line-height: 1.5;
    font-weight: 600;
    max-width: 1000px;
}

.hero-actions-group { display: flex; gap: 20px; align-items: center; justify-content: center; flex-wrap: wrap; }
.btn-hero-primary { background: #d4a017; color: #ffffff !important; padding: 18px 46px; border-radius: 30px; font-size: 1.3rem; font-weight: 700; text-decoration: none; }
.btn-hero-secondary { background: rgba(15, 23, 42, 0.5); border: 1.5px solid rgba(255, 255, 255, 0.9); color: #ffffff !important; padding: 17px 40px; border-radius: 30px; font-size: 1.3rem; font-weight: 600; text-decoration: none; }

.hero-slider-dots { position: absolute; bottom: 22px; left: 50%; transform: translateX(-50%); z-index: 3; display: flex; gap: 10px; background: rgba(15, 23, 42, 0.4); padding: 8px 16px; border-radius: 20px; }
.hero-dot { width: 12px; height: 12px; border-radius: 50%; background: rgba(255, 255, 255, 0.6); cursor: pointer; }
.hero-dot.active { background: #d4a017; width: 30px; border-radius: 10px; }

/* PAQUETES */
.section-packages .container { max-width: 1600px !important; width: 98% !important; }
@media (min-width: 992px) {
    .section-packages .cards-grid { display: grid !important; grid-template-columns: repeat(4, 1fr) !important; gap: 22px !important; }
}

/* GALERÍA DE EXPERIENCIAS EN COLLAGE / MOSAICO */
.experience-collage-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    grid-auto-rows: 240px;
    gap: 20px;
}
.collage-item {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}
.collage-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}
.collage-item:hover img {
    transform: scale(1.08);
}
.collage-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0,0,0,0) 40%, rgba(15,23,42,0.85) 100%);
    display: flex;
    align-items: flex-end;
    padding: 24px;
    color: #ffffff;
}
.collage-title {
    font-size: 1.25rem;
    font-weight: 800;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0,0,0,0.4);
}

.item-large { grid-column: span 6; grid-row: span 2; }
.item-medium { grid-column: span 6; grid-row: span 1; }
.item-tall { grid-column: span 3; grid-row: span 2; }
.item-small { grid-column: span 3; grid-row: span 1; }

@media (max-width: 991px) {
    .experience-collage-grid { grid-auto-rows: 200px; }
    .item-large, .item-medium, .item-tall, .item-small { grid-column: span 6; grid-row: span 1; }
}
@media (max-width: 576px) {
    .experience-collage-grid { grid-template-columns: 1fr; grid-auto-rows: 220px; }
    .item-large, .item-medium, .item-tall, .item-small { grid-column: span 1; grid-row: span 1; }
}

/* SECCIÓN OPINIONES Y RESEÑAS */
.ta-reviews-wrapper { max-width: 1100px; margin: 0 auto; color: #0f172a; }
.ta-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 28px; }
.ta-title { font-size: 2.6rem; font-weight: 800; margin: 0; }
.ta-subtitle-badge { color: #b8860b; font-weight: 700; font-size: 1.15rem; }
.ta-btn-write { background: linear-gradient(135deg, #d4a017 0%, #b8860b 100%); color: #ffffff; border: none; padding: 16px 32px; border-radius: 30px; font-weight: 800; font-size: 1.15rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; }
.ta-btn-write.btn-outline { background: #ffffff; color: #b8860b; border: 2px solid #d4a017; }
.ta-summary-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; padding: 36px; display: flex; align-items: center; gap: 40px; margin-bottom: 40px; }
.ta-score-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 28px 40px; text-align: center; }
.ta-score-number { font-size: 4rem; font-weight: 900; }
.ta-score-label { font-weight: 800; font-size: 1.4rem; color: #d4a017; }
.ta-bars-container { flex: 1; display: flex; flex-direction: column; gap: 14px; }
.ta-bar-row { display: flex; align-items: center; gap: 18px; }
.ta-bar-track { flex: 1; height: 14px; background: #e2e8f0; border-radius: 10px; overflow: hidden; }
.ta-bar-fill { height: 100%; background: #d4a017; }

.ta-reviews-list { display: flex; flex-direction: column; gap: 28px; }
.ta-review-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 32px; }
.ta-user-info { display: flex; align-items: center; gap: 18px; margin-bottom: 18px; }
.ta-avatar { width: 56px; height: 56px; border-radius: 50%; color: #ffffff; font-weight: 800; display: flex; align-items: center; justify-content: center; }

/* MODAL */
.ta-modal { display: none; position: fixed; z-index: 99999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 20px; }
.ta-modal-content { background-color: #ffffff; padding: 35px 40px; border-radius: 24px; width: 100%; max-width: 520px; position: relative; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-height: 92vh; overflow-y: auto; }
.ta-modal-close { position: absolute; right: 22px; top: 18px; background: transparent; border: none; font-size: 2.2rem; color: #64748b; cursor: pointer; }

.ta-modal-heading { font-size: 2rem; font-weight: 800; margin-bottom: 8px; color: #0f172a; text-align: center; }
.ta-modal-desc { font-size: 1.05rem; color: #475569; margin-bottom: 20px; text-align: center; line-height: 1.4; }

.ta-field-group { margin-bottom: 16px; text-align: left; position: relative; }
.ta-input-label { font-size: 0.95rem; font-weight: 700; color: #1e293b; display: block; margin-bottom: 6px; }
.ta-input-style { width: 100%; padding: 13px 16px; border-radius: 12px; border: 1.5px solid #cbd5e1; background-color: #f8fafc; font-size: 1rem; color: #0f172a; outline: none; transition: all 0.2s; box-sizing: border-box; }
.ta-input-style:focus { border-color: #d4a017; background-color: #ffffff; box-shadow: 0 0 0 3px rgba(212, 160, 23, 0.15); }

.password-wrapper { position: relative; width: 100%; }
.toggle-password { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); cursor: pointer; background: none; border: none; font-size: 1.2rem; color: #64748b; padding: 0; }

.pass-rules-box { background: #f1f5f9; border-radius: 10px; padding: 12px 16px; margin-top: 10px; font-size: 0.88rem; border: 1px solid #e2e8f0; }
.pass-rules-title { font-weight: 700; color: #334155; margin-bottom: 6px; }
.pass-rule-item { display: flex; align-items: center; gap: 8px; color: #64748b; margin-bottom: 4px; transition: color 0.2s; }
.pass-rule-item.valid { color: #16a34a; font-weight: 600; }
.pass-rule-item.invalid { color: #dc2626; }

.match-status { font-size: 0.85rem; font-weight: 600; margin-top: 5px; display: none; }
.match-status.valid { color: #16a34a; display: block; }
.match-status.invalid { color: #dc2626; display: block; }

.btn-submit-form { width: 100%; background: linear-gradient(135deg, #d4a017 0%, #b8860b 100%); color: #ffffff; border: none; padding: 15px; border-radius: 30px; font-weight: 800; font-size: 1.1rem; cursor: pointer; transition: all 0.25s; margin-top: 10px; }
.btn-submit-form:disabled { background: #cbd5e1; cursor: not-allowed; opacity: 0.7; }
.switch-mode-text { text-align: center; margin-top: 18px; font-size: 0.95rem; color: #475569; }
.switch-mode-btn { background: none; border: none; color: #d4a017; font-weight: 700; cursor: pointer; text-decoration: underline; padding: 0; }
</style>

<!-- HERO SECTION -->
<section class="hero-home-modern">
    <div class="hero-bg-slider" id="heroBgSlider">
        <div class="hero-bg-slide active" style="background-image: url('assets/images/MAPI.jpg');"></div>
        <div class="hero-bg-slide" style="background-image: url('assets/images/amazon-as.jpg');"></div>
        <div class="hero-bg-slide" style="background-image: url('assets/images/lago-titicaca.jpg');"></div>
        <div class="hero-bg-slide" style="background-image: url('assets/images/Vinicunca.jpg');"></div>
    </div>
    
    <div class="hero-clean-content">
        <div class="hero-badge-tag">
            <span>✨ Agentes Oficiales Autorizados</span>
        </div>
        <h1>Tu Próxima Aventura Empieza Aquí</h1>
        <p class="hero-sub">Itinerarios exclusivos, paquetes a medida y experiencias inolvidables para descubrir la magia de todo el Perú.</p>

        <div class="hero-actions-group">
            <a href="destinos.php" class="btn-hero-primary">Explorar Paquetes</a>
            <a href="contacto.php" class="btn-hero-secondary">Contactar con Asesor</a>
        </div>
    </div>

    <div class="hero-slider-dots" id="heroDots">
        <span class="hero-dot active" onclick="setHeroSlide(0)"></span>
        <span class="hero-dot" onclick="setHeroSlide(1)"></span>
        <span class="hero-dot" onclick="setHeroSlide(2)"></span>
        <span class="hero-dot" onclick="setHeroSlide(3)"></span>
    </div>
</section>

<!-- SECCIÓN: GALERÍA DE EXPERIENCIAS TIPO COLLAGE -->
<section class="section section-gallery" style="padding: 60px 0; background: #f8fafc;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 15px;">
        <div class="section-header" style="text-align: center; margin-bottom: 40px;">
            <h2 style="font-size: 2.8rem; font-weight: 800; color: #0f172a; margin-bottom: 12px;">Galería de Experiencias</h2>
            <p style="font-size: 1.15rem; color: #64748b; margin: 0;">Revive las aventuras vividas por nuestros viajeros alrededor de todo el Perú.</p>
        </div>

        <div class="experience-collage-grid">
            <div class="collage-item item-large">
                <img src="assets/images/MAPI.jpg" alt="Machu Picchu al amanecer">
                <div class="collage-overlay">
                    <h4 class="collage-title">Machu Picchu al amanecer</h4>
                </div>
            </div>
            <div class="collage-item item-medium">
                <img src="assets/images/amazon-as.jpg" alt="Selva Amazónica">
                <div class="collage-overlay">
                    <h4 class="collage-title">Selva Amazónica</h4>
                </div>
            </div>
            <div class="collage-item item-medium">
                <img src="assets/images/lago-titicaca.jpg" alt="Lago Titicaca">
                <div class="collage-overlay">
                    <h4 class="collage-title">Lago Titicaca</h4>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECCIÓN: PAQUETES DESTACADOS (CON MONEDA DINÁMICA) -->
<section class="section section-featured-tours" style="padding: 70px 0; background: #ffffff;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 15px;">
        <div class="section-header" style="text-align: center; margin-bottom: 45px;">
            <span style="display: inline-block; background: rgba(212, 154, 0, 0.12); color: #d49a00; border: 1px solid #d49a00; padding: 5px 16px; border-radius: 20px; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">Experiencias Recomendadas</span>
            <h2 style="font-size: 2.6rem; font-weight: 800; color: #0f172a; margin: 0 0 10px;">Paquetes Turísticos Destacados</h2>
            <p style="font-size: 1.1rem; color: #64748b; margin: 0;">Tarifas convertidas en tiempo real a tu moneda seleccionada (<strong><?= htmlspecialchars($current_currency ?? 'USD') ?></strong>).</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 25px;">
            <?php foreach ($paquetesDestacados as $pkg): ?>
                <div style="background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.06); display: flex; flex-direction: column;">
                    <div style="position: relative; height: 200px; overflow: hidden;">
                        <img src="<?= htmlspecialchars($pkg['img']) ?>" alt="<?= htmlspecialchars($pkg['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <span style="position: absolute; top: 12px; right: 12px; background: rgba(15, 42, 74, 0.85); backdrop-filter: blur(4px); color: #fff; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 700;">
                            ⭐ <?= htmlspecialchars((string)($pkg['rating'] ?? '5.0')) ?>
                        </span>
                        <span style="position: absolute; bottom: 12px; left: 12px; background: #d49a00; color: #0f2a4a; padding: 4px 10px; border-radius: 6px; font-size: 0.78rem; font-weight: 800;">
                            ⏱️ <?= htmlspecialchars($pkg['durationLabel'] ?: $pkg['duration'] . ' Días') ?>
                        </span>
                    </div>

                    <div style="padding: 20px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <span style="font-size: 0.82rem; color: #64748b; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">📍 <?= htmlspecialchars($pkg['destino']) ?></span>
                            <h3 style="font-size: 1.15rem; font-weight: 700; color: #0f2a4a; margin: 6px 0 12px; line-height: 1.3;"><?= htmlspecialchars($pkg['name']) ?></h3>
                        </div>

                        <div style="border-top: 1px solid #f1f5f9; padding-top: 15px; margin-top: 10px; display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <small style="font-size: 0.75rem; color: #64748b; display: block;">Desde</small>
                                <strong style="font-size: 1.05rem; color: #0f2a4a; font-weight: 800;"><?= formatPrice(!empty($pkg['price']) && $pkg['price'] > 0 ? $pkg['price'] : 191) ?></strong>
                            </div>
                            <a href="paquete.php?id=<?= $pkg['id'] ?>" style="background: #0f2a4a; color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; transition: background 0.2s;">
                                Ver Tour
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Botón para ir al catálogo / panel de destinos -->
        <div style="text-align: center; margin-top: 48px;">
            <a href="destinos.php" style="display: inline-flex; align-items: center; justify-content: center; gap: 12px; background: #0f2a4a; color: #ffffff; text-decoration: none; padding: 15px 36px; border-radius: 50px; font-size: 1.08rem; font-weight: 700; box-shadow: 0 10px 25px rgba(15, 42, 74, 0.2); transition: all 0.3s ease;" onmouseover="this.style.background='#d4a017'; this.style.color='#0f2a4a'; this.style.transform='translateY(-3px)'; this.style.boxShadow='0 15px 30px rgba(212, 160, 23, 0.35)';" onmouseout="this.style.background='#0f2a4a'; this.style.color='#ffffff'; this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(15, 42, 74, 0.2)';">
                <i class="fa-solid fa-map-location-dot" style="font-size: 1.2rem;"></i>
                <span>Ver Todos los Destinos</span>
                <i class="fa-solid fa-arrow-right" style="font-size: 0.95rem;"></i>
            </a>

            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <div style="margin-top: 14px;">
                    <a href="admin/destinos.php" style="display: inline-flex; align-items: center; gap: 7px; color: #64748b; font-size: 0.88rem; font-weight: 600; text-decoration: none; padding: 6px 14px; border-radius: 20px; background: #f8fafc; border: 1.5px solid #cbd5e1; transition: all 0.2s;" onmouseover="this.style.borderColor='#0f2a4a'; this.style.color='#0f2a4a';" onmouseout="this.style.borderColor='#cbd5e1'; this.style.color='#64748b';">
                        <i class="fa-solid fa-sliders" style="color: #0f2a4a;"></i> Ir al Panel Admin de Destinos
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- SECCIÓN RESEÑAS / OPINIONES DINÁMICAS -->
<section id="opiniones" class="section section-reviews-home" style="padding: 50px 0; background: #ffffff;">
    <div class="container" style="max-width: 1100px; margin: 0 auto; padding: 0 15px;">
        <div class="ta-reviews-wrapper" style="display: block !important; width: 100%;">
            
            <!-- 1. ENCABEZADO CON TÍTULO Y BOTONES -->
            <div class="ta-header" style="display: flex !important; justify-content: space-between !important; align-items: center !important; flex-wrap: wrap !important; width: 100% !important; margin-bottom: 25px !important;">
                <div class="ta-header-left">
                    <h2 class="ta-title" style="margin: 0; font-size: 2rem; font-weight: 800; color: #0b132a;">Opiniones de Viajeros</h2>
                    <span class="ta-subtitle-badge" style="color: #c99318; font-weight: bold; font-size: 0.95rem;">★ Experiencias 100% Verificadas</span>
                </div>
                
                <div class="ta-header-actions" style="display: flex !important; gap: 12px !important; align-items: center !important;">
                    <?php if ($totalOpiniones > 3): ?>
                        <button type="button" id="btnToggleOpiniones" data-total="<?php echo $totalOpiniones; ?>" onclick="toggleOpiniones()" style="background-color: #ffffff !important; border: 1.5px solid #d4a017 !important; color: #d4a017 !important; padding: 8px 22px; border-radius: 25px; font-weight: bold; cursor: pointer; font-size: 0.9rem;">
                            Ver más (<?php echo $totalOpiniones; ?>) ▼
                        </button>
                    <?php endif; ?>

                    <button type="button" id="openReviewModal" class="ta-btn-write" onclick="abrirModalResena();">
                        ✍️ Escribir una opinión
                    </button>
                </div>
            </div>

            <!-- 2. CONTENEDOR PRINCIPAL DE PUNTUACIÓN Y BARRAS DINÁMICAS -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 25px; margin-bottom: 25px; width: 100%; box-sizing: border-box;">
                <div style="display: flex; align-items: center; gap: 35px; width: 100%;">
                    
                    <!-- Tarjeta Blanca Interna (Puntaje) -->
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px 30px; text-align: center; min-width: 160px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <span style="font-size: 3rem; font-weight: 800; color: #0b132a; display: block; line-height: 1;"><?php echo number_format($ratingPromedio, 1); ?></span>
                        <div style="font-weight: 700; color: #c99318; margin: 10px 0 6px 0; font-size: 1.05rem;">
                            <?php 
                                if ($ratingPromedio >= 4.5) echo 'Excelente';
                                elseif ($ratingPromedio >= 3.5) echo 'Bueno';
                                elseif ($ratingPromedio >= 2.5) echo 'Promedio';
                                elseif ($ratingPromedio >= 1.5) echo 'Malo';
                                else echo 'Horrible';
                            ?>
                        </div>
                        <div style="color: #c99318; font-size: 0.85rem; letter-spacing: 2px;">★★★★★</div>
                        <span style="font-size: 0.85rem; color: #64748b; margin-top: 6px; display: block;">(<?php echo $totalOpiniones; ?> opiniones)</span>
                    </div>

                    <!-- Barras Amarillas Horizontales Dinámicas -->
                    <div style="flex: 1; display: flex; flex-direction: column; gap: 12px;">

                        <?php foreach (['Excelente', 'Bueno', 'Promedio', 'Malo', 'Horrible'] as $label): ?>
                            <div style="display: flex; align-items: center; gap: 15px; font-size: 0.85rem; color: #475569;">
                                <span style="width: 65px; font-weight: 500;"><?php echo $label; ?></span>
                                <div style="flex: 1; height: 10px; background: #e2e8f0; border-radius: 10px; overflow: hidden;">
                                    <div style="width: <?php echo $desgloseRatings[$label]['percent'] ?? 0; ?>%; height: 100%; background: #c99318; border-radius: 10px;"></div>
                                </div>
                                <span style="width: 15px; text-align: right; font-weight: 600;"><?php echo $desgloseRatings[$label]['count'] ?? 0; ?></span>
                            </div>
                        <?php endforeach; ?>

                    </div>
                </div>
            </div>

            <!-- 3. TARJETAS DE OPINIONES DE USUARIOS -->
            <div class="ta-reviews-list" style="display: flex !important; flex-direction: column !important; gap: 16px !important; width: 100% !important;">
                <?php if (empty($listaOpiniones)): ?>
                    <div style="text-align: center; padding: 30px; color: #64748b; font-size: 1rem; background: #f8fafc; border-radius: 16px;">
                        Aún no hay opiniones registradas. ¡Sé el primero en compartir tu experiencia!
                    </div>
                <?php else: ?>
                    <?php foreach ($listaOpiniones as $index => $rev): ?>
                        <?php
                            $nombre = !empty($rev['nombre_completo']) ? $rev['nombre_completo'] : 'Viajero Inkaiko';
                            $partes = explode(' ', trim($nombre));
                            $iniciales = strtoupper(substr($partes[0], 0, 1) . (isset($partes[1]) ? substr($partes[1], 0, 1) : ''));
                            
                            $esOculto = ($index >= 3) ? 'opinion-extra' : '';
                            $estiloOculto = ($index >= 3) ? 'display: none !important;' : '';
                        ?>
                        
                        <div class="ta-review-card <?php echo $esOculto; ?>" style="<?php echo $estiloOculto; ?> background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; width: 100%; box-sizing: border-box;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                <div style="width: 42px; height: 42px; background-color: #c99318; color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1rem;">
                                    <?php echo htmlspecialchars($iniciales); ?>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 1.05rem; font-weight: 700; color: #0b132a;"><?php echo htmlspecialchars($nombre); ?></h4>
                                    <span style="font-size: 0.85rem; color: #64748b;"><?php echo htmlspecialchars($rev['aporte_info'] ?? 'Perú'); ?></span>
                                </div>
                            </div>
                            <h3 style="margin: 0 0 6px 0; font-size: 1rem; font-weight: 800; color: #0b132a; text-transform: uppercase;"><?php echo htmlspecialchars($rev['titulo'] ?? ''); ?></h3>
                            <p style="color: #475569; font-size: 0.9rem; margin: 0; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($rev['opinion'] ?? '')); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>

<!-- MODAL DE RESEÑA DIRECTA -->
<div id="reviewModal" class="ta-modal-overlay" style="display: none;">
    <div class="ta-modal-content" style="background:#fff; border-radius:16px; padding:30px; max-width:520px; width:92%; position:relative; box-shadow:0 15px 40px rgba(0,0,0,0.25);">
        <button type="button" class="ta-modal-close" id="closeReviewModal" onclick="cerrarModalResena()" style="position:absolute; top:15px; right:20px; font-size:1.8rem; background:none; border:none; cursor:pointer; color:#64748b;">&times;</button>
        
        <div id="viewWriteOpinion" class="modal-view" style="display:block;">
            <h3 class="ta-modal-heading" style="color:#0f2a4a; font-size:1.5rem; margin-top:0; margin-bottom:8px;">✍️ Escribe tu Opinión</h3>
            <p class="ta-modal-desc" style="color:#64748b; font-size:0.95rem; margin-top:0; margin-bottom:20px;">Tu experiencia ayuda a otros viajeros a descubrir el Perú.</p>
            
            <form action="index.php" method="POST">
                <input type="hidden" name="action" value="guardar_opinion">
                <input type="hidden" name="paquete_id" value="1">

                <?php if (!$usuarioLogueado): ?>
                    <div class="ta-field-group" style="margin-bottom:14px;">
                        <label class="ta-input-label" style="display:block; font-weight:600; margin-bottom:5px; font-size:0.9rem;">Tu Nombre y Apellido *</label>
                        <input type="text" name="nombre_cliente" class="ta-input-style" placeholder="Ej. Juan Pérez" required style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; box-sizing:border-box;">
                    </div>

                    <div class="ta-field-group" style="margin-bottom:14px;">
                        <label class="ta-input-label" style="display:block; font-weight:600; margin-bottom:5px; font-size:0.9rem;">Tu Correo Electrónico *</label>
                        <input type="email" name="email_cliente" class="ta-input-style" placeholder="tu@email.com" required style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; box-sizing:border-box;">
                    </div>
                <?php else: ?>
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; margin-bottom:16px; font-size:0.92rem; color:#334155;">
                        Publicando como: <strong style="color:#0f2a4a;"><?= htmlspecialchars($nombreUsuarioLogueado) ?></strong>
                    </div>
                <?php endif; ?>
                
                <div class="ta-field-group" style="margin-bottom:14px;">
                    <label class="ta-input-label" style="display:block; font-weight:600; margin-bottom:5px; font-size:0.9rem;">Calificación General *</label>
                    <select name="rating" class="ta-input-style" required style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; box-sizing:border-box; background:#fff;">
                        <option value="5" selected>★★★★★ Excelente (5/5)</option>
                        <option value="4">★★★★☆ Muy Bueno (4/5)</option>
                        <option value="3">★★★☆☆ Promedio (3/5)</option>
                        <option value="2">★★☆☆☆ Regular (2/5)</option>
                        <option value="1">★☆☆☆☆ Malo (1/5)</option>
                    </select>
                </div>

                <div class="ta-field-group" style="margin-bottom:14px;">
                    <label class="ta-input-label" style="display:block; font-weight:600; margin-bottom:5px; font-size:0.9rem;">Título de tu Opinión *</label>
                    <input type="text" name="titulo" class="ta-input-style" placeholder="Ej. ¡Inolvidable viaje a Machu Picchu!" required style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; box-sizing:border-box;">
                </div>

                <div class="ta-field-group" style="margin-bottom:18px;">
                    <label class="ta-input-label" style="display:block; font-weight:600; margin-bottom:5px; font-size:0.9rem;">Tu Comentario / Experiencia *</label>
                    <textarea name="opinion" rows="4" class="ta-input-style" placeholder="Cuéntanos más sobre el servicio, los guías, los paisajes..." required style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; box-sizing:border-box;"></textarea>
                </div>

                <button type="submit" class="btn-submit-form" style="width:100%; background:#0f2a4a; color:#fff; border:none; padding:13px; border-radius:8px; font-weight:700; font-size:1rem; cursor:pointer; transition:background 0.2s;">
                    Publicar Opinión
                </button>

                <?php if (!$usuarioLogueado): ?>
                    <p style="text-align:center; margin-top:16px; margin-bottom:0; font-size:0.88rem; color:#64748b;">
                        ¿Tienes una cuenta? <a href="login.php" style="color:#d4a017; font-weight:700; text-decoration:none;">Inicia sesión</a>
                    </p>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script>
// HERO BACKGROUND SLIDER AUTOMÁTICO
let currentHeroSlide = 0;
let heroSlideTimer = null;

function setHeroSlide(index) {
    const slides = document.querySelectorAll('.hero-bg-slide');
    const dots = document.querySelectorAll('.hero-dot');
    if (!slides.length) return;

    if (index >= slides.length) index = 0;
    if (index < 0) index = slides.length - 1;

    slides.forEach((slide, i) => {
        if (i === index) {
            slide.classList.add('active');
        } else {
            slide.classList.remove('active');
        }
    });

    dots.forEach((dot, i) => {
        if (i === index) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });

    currentHeroSlide = index;
    resetHeroTimer();
}

function nextHeroSlide() {
    const slides = document.querySelectorAll('.hero-bg-slide');
    if (!slides.length) return;
    setHeroSlide((currentHeroSlide + 1) % slides.length);
}

function resetHeroTimer() {
    if (heroSlideTimer) clearInterval(heroSlideTimer);
    heroSlideTimer = setInterval(nextHeroSlide, 5000);
}

// ABRIR Y CERRAR MODAL DE OPINIÓN
function abrirModalResena() {
    const reviewModal = document.getElementById('reviewModal');
    if (reviewModal) {
        reviewModal.style.setProperty('display', 'flex', 'important');
    }
}

function cerrarModalResena() {
    const reviewModal = document.getElementById('reviewModal');
    if (reviewModal) {
        reviewModal.style.setProperty('display', 'none', 'important');
    }
}

// FUNCIÓN PARA EXPANDIR / CONTRAER OPINIONES (VER MÁS)
function toggleOpiniones() {
    const extras = document.querySelectorAll('.opinion-extra');
    const btn = document.getElementById('btnToggleOpiniones');
    if (!extras.length) return;

    const primerExtra = extras[0];
    const estaOculto = (primerExtra.style.display === 'none' || window.getComputedStyle(primerExtra).display === 'none');

    extras.forEach(card => {
        card.style.setProperty('display', estaOculto ? 'block' : 'none', 'important');
    });

    if (btn) {
        const total = btn.getAttribute('data-total') || '<?= $totalOpiniones ?>';
        btn.innerHTML = estaOculto ? 'Mostrar menos ▲' : 'Ver más (' + total + ') ▼';
    }
}

// EVENTOS DOM
document.addEventListener('DOMContentLoaded', function() {
    resetHeroTimer();

    const openModalBtn = document.getElementById('openReviewModal');
    const closeModalBtn = document.getElementById('closeReviewModal');
    const reviewModal = document.getElementById('reviewModal');

    if (openModalBtn) {
        openModalBtn.addEventListener('click', function(e) {
            e.preventDefault();
            abrirModalResena();
        });
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function(e) {
            e.preventDefault();
            cerrarModalResena();
        });
    }

    window.addEventListener('click', function(e) {
        if (e.target === reviewModal) {
            cerrarModalResena();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalResena();
        }
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('opinion_modal') === '1') {
        abrirModalResena();
    }
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
