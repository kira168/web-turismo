<?php
// includes/i18n.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Language Handling
if (isset($_GET['lang']) && in_array($_GET['lang'], ['es', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$current_lang = $_SESSION['lang'] ?? 'es';

// Currency Handling (Soporta GET, SESSION y COOKIE de forma persistente)
if (isset($_GET['currency']) && in_array(strtoupper($_GET['currency']), ['USD', 'PEN', 'EUR'])) {
    $cur = strtoupper($_GET['currency']);
    $_SESSION['currency'] = $cur;
    @setcookie('currency', $cur, time() + 31536000, '/');
} elseif (isset($_COOKIE['currency']) && in_array(strtoupper($_COOKIE['currency']), ['USD', 'PEN', 'EUR'])) {
    $_SESSION['currency'] = strtoupper($_COOKIE['currency']);
}
$current_currency = $_SESSION['currency'] ?? 'USD';

// Exchange Rates (Base is USD)
$exchange_rates = [
    'USD' => 1.0,
    'PEN' => 3.80,
    'EUR' => 0.92
];

function formatPrice($priceUSD) {
    global $current_currency, $exchange_rates;
    if ($priceUSD === null || $priceUSD === '') return '';
    $currency = $current_currency ?? ($_SESSION['currency'] ?? 'USD');
    $rate = $exchange_rates[$currency] ?? 1.0;
    $converted = (float)$priceUSD * $rate;
    
    if ($currency === 'PEN') {
        return 'S/ ' . number_format($converted, 2) . ' PEN';
    } elseif ($currency === 'EUR') {
        return '€ ' . number_format($converted, 2) . ' EUR';
    } else {
        return '$ ' . number_format($converted, 2) . ' USD';
    }
}

// Translations
$translations = [
    'es' => [
        'nav_home' => 'Inicio',
        'nav_destinations' => 'Destinos',
        'nav_packages' => 'Paquetes',
        'nav_about' => 'Nosotros',
        'nav_contact' => 'Contacto',
        'nav_dashboard' => 'Dashboard',
        'hero_eyebrow' => 'Descubre Turismo Inkaiko',
        'hero_title' => 'Tu Próxima Aventura Empieza Aquí',
        'hero_subtitle' => 'Explora los mejores destinos, paquetes exclusivos y experiencias inolvidables a lo largo de todo el Perú.',
        'search_placeholder' => '¿A dónde quieres ir?',
        'btn_search' => 'Buscar',
        'popular_searches' => 'Búsquedas populares:',
        'popular_destinations' => 'Destinos populares',
        'popular_destinations_desc' => 'Descubre los mejores destinos en Costa, Sierra y Selva.',
        'featured_packages' => 'Paquetes destacados',
        'featured_packages_desc' => 'Explora los paquetes más populares con experiencias únicas.',
        'btn_view_package' => 'Ver paquete',
        'gallery' => 'Galería viajera',
        'gallery_desc' => 'Imágenes inspiradoras de Perú y experiencias para tu próximo viaje.',
        'testimonials' => 'Testimonios reales',
        'testimonials_desc' => 'Reseñas de viajeros que ya vivieron la experiencia Inkaiko.',
        'from' => 'Desde',
        'packages_count' => 'paquetes',
        'my_account' => 'Mi cuenta',
        'logout' => 'Cerrar sesión',
        'login' => 'Iniciar sesión',
        'book_now' => 'Reservar ahora'
    ],
    'en' => [
        'nav_home' => 'Home',
        'nav_destinations' => 'Destinations',
        'nav_packages' => 'Packages',
        'nav_about' => 'About Us',
        'nav_contact' => 'Contact',
        'nav_dashboard' => 'Dashboard',
        'hero_eyebrow' => 'Discover Turismo Inkaiko',
        'hero_title' => 'Your Next Adventure Starts Here',
        'hero_subtitle' => 'Explore the best destinations, exclusive packages and unforgettable experiences throughout Peru.',
        'search_placeholder' => 'Where do you want to go?',
        'btn_search' => 'Search',
        'popular_searches' => 'Popular searches:',
        'popular_destinations' => 'Popular Destinations',
        'popular_destinations_desc' => 'Discover the best destinations in the Coast, Highlands, and Jungle.',
        'featured_packages' => 'Featured Packages',
        'featured_packages_desc' => 'Explore our most popular packages with unique experiences.',
        'btn_view_package' => 'View Package',
        'gallery' => 'Travel Gallery',
        'gallery_desc' => 'Inspiring images of Peru and experiences for your next trip.',
        'testimonials' => 'Real Testimonials',
        'testimonials_desc' => 'Reviews from travelers who have already lived the Inkaiko experience.',
        'from' => 'From',
        'packages_count' => 'packages',
        'my_account' => 'My Account',
        'logout' => 'Logout',
        'login' => 'Login',
        'book_now' => 'Book Now'
    ]
];

function __($key) {
    global $translations, $current_lang;
    return $translations[$current_lang][$key] ?? $translations['es'][$key] ?? $key;
}
