<?php
// Forzar codificaci��n UTF-8
header('Content-Type: text/html; charset=utf-8');

if (!isset($pageTitle)) {
    $pageTitle = 'Turismo Inkaiko';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (file_exists(__DIR__ . '/i18n.php')) {
    require_once __DIR__ . '/i18n.php';
}

// Detectar sesi��n de usuario con m��ltiples variantes para evitar incompatibilidad
$userId = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? null;
$isLoggedIn = !empty($userId);

$userRole = $_SESSION['user_role'] ?? $_SESSION['rol'] ?? 'cliente';
$userName = $_SESSION['user_name'] ?? $_SESSION['nombre'] ?? $_SESSION['usuario_nombre'] ?? 'Usuario';
$userInitials = !empty($userName) ? strtoupper(substr($userName, 0, 1)) : 'U';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | Turismo Inkaiko</title>
    
    <!-- PRECONEXIONES DE VELOCIDAD -->
    <link rel="preconnect" href="https://translate.google.com">
    <link rel="preconnect" href="https://translate.googleapis.com">
    <link rel="preconnect" href="https://gstatic.com" crossorigin>

    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* RESET GENERAL DE ESPACIOS */
        html, body {
            overflow-x: hidden !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        body {
            padding-top: 0 !important;
            margin-top: 0 !important;
            top: 0px !important;
            position: static !important;
        }

        /* ELIMINAR TOTALMENTE BORDES, SOMBRAS Y OVERLAYS EXTRA */
        .site-header, 
        .site-header *::before, 
        .site-header *::after,
        header,
        header::before,
        header::after {
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            border: none !important;
            outline: none !important;
            filter: none !important;
            box-shadow: none !important;
        }

        .hero::before, .hero::after,
        .hero-slider::before, .hero-slider::after,
        .slider-container::before, .slider-container::after,
        .carousel::before, .carousel::after,
        .hero-overlay, .slider-overlay, .overlay {
            background: none !important;
            background-color: transparent !important;
            background-image: none !important;
            box-shadow: none !important;
            border: none !important;
            display: none !important;
        }

        /* HEADER TRANSPARENTE FLOTANTE */
        .site-header {
            position: fixed !important;
            top: 20px !important;
            left: 0 !important;
            width: 100% !important;
            height: auto !important;
            min-height: 0 !important;
            z-index: 99999 !important;
            background: transparent !important;
            background-color: transparent !important;
            box-shadow: none !important;
            border: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            pointer-events: none;
        }

        /* C�0�9PSULA BLANCA CENTRADA Y BALANCEADA */
        .header-inner {
            pointer-events: auto;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            width: 92% !important;
            max-width: 1350px !important;
            margin: 0 auto !important;
            padding: 12px 35px !important;
            background-color: #ffffff !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 50px !important;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
            box-sizing: border-box !important;
        }

        /* LOGO CON MARGEN */
        .site-logo {
            height: 60px !important;
            width: auto;
            display: block;
            transition: transform 0.2s ease;
            border: none !important;
            margin-right: 15px !important;
        }

        .site-logo:hover {
            transform: scale(1.03);
        }

        /* NAVEGACI�0�7N CON TEXTOS Y ESPACIOS M�0�9S GRANDES */
        .site-nav {
            display: flex !important;
            align-items: center !important;
            gap: 2.2rem !important;
            border: none !important;
            background: transparent !important;
        }

        .site-nav a,
        .site-nav a:focus,
        .site-nav a:active,
        .site-nav a.active,
        .site-nav a:visited {
            background: transparent !important;
            background-color: transparent !important;
            box-shadow: none !important;
            outline: none !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 4px 2px !important;
            font-size: 1.15rem !important;
            font-weight: 600 !important;
            color: #2c3e50 !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            transition: color 0.2s ease !important;
        }

        .site-nav a:hover,
        .site-nav a.active {
            color: #d97706 !important;
            background: transparent !important;
        }

        .site-nav svg {
            width: 22px !important;
            height: 22px !important;
        }

        /* SELECTORES (IDIOMA Y MONEDA) */
        .header-options {
            display: flex !important;
            gap: 12px !important;
            align-items: center !important;
            margin-left: 10px !important;
        }

        .custom-header-select {
            padding: 8px 26px 8px 14px !important;
            border-radius: 20px !important;
            border: 1.5px solid #d1d5db !important;
            background: #fff !important;
            font-size: 1rem !important;
            font-weight: 500 !important;
            cursor: pointer !important;
            color: #374151 !important;
            width: auto !important;
            box-sizing: border-box !important;
            outline: none !important;
            transition: border-color 0.2s ease !important;
        }

        .custom-header-select:hover,
        .custom-header-select:focus {
            border-color: #d97706 !important;
        }

        /* BOT�0�7N INICIAR SESI�0�7N */
        .button-secondary {
            font-size: 1.05rem !important;
            font-weight: 600 !important;
            padding: 10px 22px !important;
            border-radius: 25px !important;
            border: 1.5px solid #d97706 !important;
            color: #d97706 !important;
            background: transparent !important;
            transition: all 0.2s ease !important;
            margin-left: 5px !important;
        }

        .button-secondary:hover {
            background-color: #d97706 !important;
            color: #ffffff !important;
        }

        /* ESTILOS PARA BOTON DE USUARIO (SOLO AVATAR CIRCULAR) */
        .user-menu {
            position: relative;
            display: inline-flex;
            align-items: center;
        }
        .user-info {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0 !important;
            margin: 0 !important;
            border-radius: 50% !important;
            background: transparent !important;
            border: none !important;
            outline: none !important;
            transition: transform 0.2s ease;
            user-select: none;
        }
        .user-info:hover,
        .user-menu.active .user-info {
            background: transparent !important;
            transform: scale(1.08);
        }
        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(238, 90, 36, 0.35);
            border: 2px solid #ffffff;
            transition: box-shadow 0.2s ease;
        }
        .user-avatar:hover {
            box-shadow: 0 4px 12px rgba(238, 90, 36, 0.55);
        }
        .user-dropdown {
            display: none !important;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 6px;
            background: #ffffff !important;
            box-shadow: 0 10px 25px rgba(0,0,0,0.18) !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 12px !important;
            min-width: 190px !important;
            overflow: hidden !important;
            z-index: 999999 !important;
            opacity: 0;
            visibility: hidden;
            transform: translateY(6px);
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
        }
        .user-dropdown::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 0;
            right: 0;
            height: 10px;
            background: transparent;
        }
        .user-menu:hover .user-dropdown,
        .user-menu.active .user-dropdown {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
            transform: translateY(0) !important;
        }
        .user-dropdown a {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            padding: 12px 18px !important;
            color: #374151 !important;
            text-decoration: none !important;
            font-size: 0.95rem !important;
            font-weight: 500 !important;
            transition: background 0.15s ease, color 0.15s ease !important;
        }
        .user-dropdown a:hover {
            background-color: #f8fafc !important;
            color: #d97706 !important;
        }
        .user-dropdown a.logout-link:hover {
            background-color: #fef2f2 !important;
            color: #dc2626 !important;
        }

        /* OCULTAR POPUPS DE TRADUCCI�0�7N */
        .VIpgJd-yA16f-OtA2Ac-uo322c,
        .goog-te-banner-frame,
        .goog-te-balloon-frame,
        #goog-gt-tt,
        .goog-te-spinner-pos,
        iframe.goog-te-banner-frame {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            width: 0 !important;
            opacity: 0 !important;
            border: none !important;
        }
    </style>

    <!-- SCRIPT DE TRADUCCI�0�7N DE GOOGLE -->
    <script>
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'es',
            includedLanguages: 'es,en,pt,fr,de',
            autoDisplay: false
        }, 'google_translate_element');
    }

    function setTranslateCookie(lang) {
        const domain = window.location.hostname;
        if (lang === 'es') {
            document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; domain=" + domain + "; path=/;";
        } else {
            document.cookie = "googtrans=/es/" + lang + "; path=/;";
            document.cookie = "googtrans=/es/" + lang + "; domain=" + domain + "; path=/;";
        }
    }

    function changeLanguage(lang) {
        setTranslateCookie(lang);

        let attempts = 0;
        const checkWidget = setInterval(function() {
            const translateCombo = document.querySelector('.goog-te-combo');
            attempts++;

            if (translateCombo) {
                clearInterval(checkWidget);
                translateCombo.value = lang;
                translateCombo.dispatchEvent(new Event('change'));
            } else if (attempts > 10) {
                clearInterval(checkWidget);
                window.location.reload();
            }
        }, 100);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const match = document.cookie.match(/googtrans=\/es\/([a-z]{2})/);
        const selector = document.getElementById('customTranslate');
        if (selector) {
            selector.value = (match && match[1]) ? match[1] : 'es';
        }
    });
    </script>
    <script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

    <!-- ESTADO DE SESI�0�7N GLOBAL PARA JAVASCRIPT -->
    <script>
        window.isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
        window.userId = <?= json_encode($userId) ?>;
        window.userName = <?= json_encode($userName) ?>;
        window.userRole = <?= json_encode($userRole) ?>;
    </script>
</head>
<body>
<a href="#main-content" class="skip-link">Saltar al contenido principal</a>
<header class="site-header">
    <div class="header-inner">
        <a href="index.php" class="brand">
            <img src="assets/images/logo.png?v=<?= time() ?>" alt="Turismo Inkaiko Grand Tour Operator" class="site-logo">
        </a>
        <button class="mobile-menu-toggle" aria-label="Abrir menu" onclick="document.querySelector('.site-nav').classList.toggle('active'); this.classList.toggle('active');">
            <span></span><span></span><span></span>
        </button>
        <nav class="site-nav">
            <a href="index.php">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <polyline points="9,22 9,12 15,12 15,22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Inicio
            </a>
            <a href="destinos.php">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2"/>
                </svg>
                Destinos
            </a>

            <?php if ($isLoggedIn && $userRole === 'admin'): ?>
                <a href="admin/dashboard.php">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                        <line x1="9" y1="9" x2="15" y2="9" stroke="currentColor" stroke-width="2"/>
                        <line x1="9" y1="15" x2="15" y2="15" stroke="currentColor" stroke-width="2"/>
                        <line x1="9" y1="12" x2="15" y2="12" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    Dashboard
                </a>
            <?php endif; ?>
            <a href="about.php">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                </svg>
                Nosotros
            </a>
            <a href="contacto.php">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <polyline points="22,6 12,13 2,6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Contacto
            </a>
            
            <script>
            function changeCurrency(moneda) {
                document.cookie = "currency=" + moneda + "; path=/; max-age=31536000";
                const url = new URL(window.location.href);
                url.searchParams.set('currency', moneda);
                window.location.href = url.href;
            }
            function updateUrlParam(key, value) {
                const url = new URL(window.location.href);
                url.searchParams.set(key, value);
                window.location.href = url.href;
            }
            </script>

            <div class="header-options notranslate">
                <select id="customTranslate" class="custom-header-select" onchange="changeLanguage(this.value)" title="Seleccionar idioma">
                    <option value="es">Espa&ntilde;ol</option>
                    <option value="en">English</option>
                    <option value="pt">Portugu&ecirc;s</option>
                    <option value="fr">Fran&ccedil;ais</option>
                    <option value="de">Deutsch</option>
                </select>
                
                <div id="google_translate_element" style="display:none;"></div>

                <select class="custom-header-select" onchange="changeCurrency(this.value)" title="Seleccionar moneda">
                    <option value="USD" <?= ($current_currency === 'USD') ? 'selected' : '' ?>>USD</option>
                    <option value="PEN" <?= ($current_currency === 'PEN') ? 'selected' : '' ?>>PEN</option>
                    <option value="EUR" <?= ($current_currency === 'EUR') ? 'selected' : '' ?>>EUR</option>
                </select>
            </div>

            <?php if ($isLoggedIn): ?>
                <div class="user-menu" id="userMenu">
                    <div class="user-info" id="userInfoBtn" title="<?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>" role="button" tabindex="0">
                        <div class="user-avatar" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);">
                            <?= htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                    <div class="user-dropdown">
                        <div style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; background: #f8fafc;">
                            <div style="font-weight: 700; color: #0f2a4a; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></div>
                            <div style="font-size: 0.8rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($_SESSION['user_email'] ?? $_SESSION['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <?php if ($userRole === 'admin'): ?>
                            <a href="admin/dashboard.php">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="3" width="7" height="7"></rect>
                                    <rect x="14" y="14" width="7" height="7"></rect>
                                    <rect x="3" y="14" width="7" height="7"></rect>
                                </svg>
                                Panel Admin
                            </a>
                        <?php endif; ?>
                        <a href="mi-cuenta.php">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            Mi Cuenta
                        </a>
                        <a href="logout.php" class="logout-link">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <polyline points="16,17 21,12 16,7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <line x1="21" y1="12" x2="9" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Cerrar Sesión
                        </a>
                    </div>
                </div>
                <script>
                (function() {
                    function initUserMenu() {
                        var menu = document.getElementById('userMenu');
                        var btn = document.getElementById('userInfoBtn');
                        if (!menu || !btn) return;
                        
                        function toggle(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            menu.classList.toggle('active');
                        }

                        btn.addEventListener('click', toggle);
                        btn.addEventListener('keydown', function(e) {
                            if (e.key === 'Enter' || e.key === ' ') {
                                toggle(e);
                            }
                        });
                        
                        document.addEventListener('click', function(e) {
                            if (!menu.contains(e.target)) {
                                menu.classList.remove('active');
                            }
                        });

                        document.addEventListener('keydown', function(e) {
                            if (e.key === 'Escape') {
                                menu.classList.remove('active');
                            }
                        });
                    }
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', initUserMenu);
                    } else {
                        initUserMenu();
                    }
                })();
                </script>
            <?php else: ?>
                <a href="login.php" class="button button-secondary" style="display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <polyline points="10,17 15,12 10,7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <line x1="15" y1="12" x2="3" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Iniciar sesi&oacute;n
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main id="main-content">