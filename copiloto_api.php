<?php
session_start();
mb_internal_encoding('UTF-8');
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
error_reporting(0);

// Recibir JSON del Frontend
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
$userMessage = trim($input['message'] ?? '');

// Normalizador universal de texto
function normalizeText($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $unwanted = [
        'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ñ'=>'n',
        'à'=>'a', 'è'=>'e', 'ì'=>'i', 'ò'=>'o', 'ù'=>'u', 'ü'=>'u'
    ];
    $text = strtr($text, $unwanted);
    return trim(preg_replace('/[^a-z0-9\s]/', '', $text));
}

$userClean = normalizeText($userMessage);

// -------------------------------------------------------------------------
// PASO 1: SALUDO INICIAL ("hola") - REINICIA LA SESIÓN
// -------------------------------------------------------------------------
if (empty($userClean) || preg_match('/^(hola|buenas|buenos dias|buenas tardes|buenas noches|hey|hi)$/u', $userClean)) {
    unset($_SESSION['last_destination'], $_SESSION['last_city'], $_SESSION['travel_month']);

    echo json_encode([
        'message' => '¡Hola viajero! 👋 ¿Qué destino de Perú te gustaría explorar hoy?',
        'map_location' => ['lat' => -9.19, 'lng' => -75.01, 'zoom' => 5, 'title' => 'Perú']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------------------
// PASO ADICIONAL: MANEJO DE "NO SÉ", "NOSE", "INDECISO"
// -------------------------------------------------------------------------
if (preg_match('/(no se|nose|no estoy seguro|ni idea|indeciso|no sabria)/u', $userClean)) {
    unset($_SESSION['last_destination'], $_SESSION['last_city']);
    echo json_encode([
        'message' => "¡No te preocupes! 😉 Para ayudarte a decidir, ¿prefieres el clima cálido de la **Costa** 🌊, los paisajes andinos de la **Sierra** 🏔️ o la aventura en la **Selva** 🐒?",
        'map_location' => ['lat' => -9.19, 'lng' => -75.01, 'zoom' => 5, 'title' => 'Perú']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------------------
// PASO 2: EL USUARIO PREGUNTA "¿QUÉ DESTINOS?"
// -------------------------------------------------------------------------
if (preg_match('/(que destinos|cuales destinos|destinos|opciones|regiones|que hay)/u', $userClean)) {
    echo json_encode([
        'message' => "¡Claro! Estos son los principales destinos y regiones de nuestro querido Perú:\n\n" .
                     "• **Selva** (Iquitos, Tarapoto, Tingo María, Puerto Maldonado, Manu)\n" .
                     "• **Sierra** (Cusco, Arequipa, Puno, Ayacucho, Cajamarca, Chachapoyas)\n" .
                     "• **Costa** (Lima, Paracas, Ica, Trujillo, Chiclayo)\n\n" .
                     "¿Cuál de estos te gustaría explorar?",
        'map_location' => ['lat' => -9.19, 'lng' => -75.01, 'zoom' => 5, 'title' => 'Perú']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// MAPEO DE CIUDADES ESPECÍFICAS
$ciudadesDB = [
    // Sierra
    'cajamarca'       => ['key' => 'cajamarca', 'nombre' => 'Cajamarca', 'region' => 'sierra', 'lat' => -7.1617, 'lng' => -78.5128, 'zoom' => 10],
    'arequipa'        => ['key' => 'arequipa', 'nombre' => 'Arequipa', 'region' => 'sierra', 'lat' => -16.4090, 'lng' => -71.5375, 'zoom' => 10],
    'cusco'           => ['key' => 'cusco', 'nombre' => 'Cusco', 'region' => 'sierra', 'lat' => -13.5226, 'lng' => -71.9673, 'zoom' => 10],
    'machu picchu'    => ['key' => 'cusco', 'nombre' => 'Machu Picchu', 'region' => 'sierra', 'lat' => -13.1631, 'lng' => -72.5450, 'zoom' => 11],
    'puno'            => ['key' => 'puno', 'nombre' => 'Puno', 'region' => 'sierra', 'lat' => -15.8402, 'lng' => -70.0219, 'zoom' => 10],
    'ayacucho'        => ['key' => 'ayacucho', 'nombre' => 'Ayacucho', 'region' => 'sierra', 'lat' => -13.1588, 'lng' => -74.2239, 'zoom' => 10],
    'chachapoyas'     => ['key' => 'chachapoyas', 'nombre' => 'Chachapoyas', 'region' => 'sierra', 'lat' => -6.2294, 'lng' => -77.8714, 'zoom' => 10],
    
    // Selva
    'tarapoto'         => ['key' => 'tarapoto', 'nombre' => 'Tarapoto', 'region' => 'selva', 'lat' => -6.4861, 'lng' => -76.3725, 'zoom' => 10],
    'iquitos'          => ['key' => 'iquitos', 'nombre' => 'Iquitos', 'region' => 'selva', 'lat' => -3.7491, 'lng' => -73.2538, 'zoom' => 10],
    'tingo maria'      => ['key' => 'tingo maria', 'nombre' => 'Tingo María', 'region' => 'selva', 'lat' => -9.2944, 'lng' => -76.0028, 'zoom' => 10],
    'puerto maldonado' => ['key' => 'puerto maldonado', 'nombre' => 'Puerto Maldonado', 'region' => 'selva', 'lat' => -12.5933, 'lng' => -69.1891, 'zoom' => 10],
    
    // Costa
    'ica'       => ['key' => 'ica', 'nombre' => 'Ica', 'region' => 'costa', 'lat' => -14.0678, 'lng' => -75.7286, 'zoom' => 10],
    'paracas'   => ['key' => 'paracas', 'nombre' => 'Paracas', 'region' => 'costa', 'lat' => -13.8340, 'lng' => -76.2505, 'zoom' => 11],
    'lima'      => ['key' => 'lima', 'nombre' => 'Lima', 'region' => 'costa', 'lat' => -12.0464, 'lng' => -77.0428, 'zoom' => 10],
    'trujillo'  => ['key' => 'trujillo', 'nombre' => 'Trujillo', 'region' => 'costa', 'lat' => -8.1116, 'lng' => -79.0286, 'zoom' => 10],
    'chiclayo'  => ['key' => 'chiclayo', 'nombre' => 'Chiclayo', 'region' => 'costa', 'lat' => -6.7714, 'lng' => -79.8409, 'zoom' => 10],
];

// BASE DE DATOS DE DESTINOS CON IDENTIFICACIÓN EXACTA DE CIUDAD
$destinosDB = [
    'selva' => [
        'title' => 'la Selva Amazónica',
        'keywords' => ['selva', 'amazonia', 'amazonas', 'jungla', 'aventura'],
        'desc' => "¡La exuberante Selva Peruana te espera! 🐒🌿 Contamos con espectaculares opciones en **Tarapoto, Iquitos, Tingo María y Puerto Maldonado**.",
        'recom' => "En la Selva te recomiendo **Tarapoto** o **Tingo María**.",
        'lat' => -3.7491, 'lng' => -73.2538, 'zoom' => 6,
        'paquetes_list' => [
            ['id' => 70, 'city_key' => 'tarapoto', 'name' => 'Tarapoto Exótico & Laguna Azul', 'precio' => 380.00, 'duracion' => '4 días / 3 noches'],
            ['id' => 71, 'city_key' => 'tarapoto', 'name' => 'Tarapoto Aventurero y Cataratas', 'precio' => 290.00, 'duracion' => '3 días / 2 noches'],
            ['id' => 47, 'city_key' => 'tingo maria', 'name' => 'Romance Amazónico – Tingo María', 'precio' => 409.00, 'duracion' => '5 días / 4 noches'],
            ['id' => 46, 'city_key' => 'tingo maria', 'name' => 'Selva Extrema – Tingo María', 'precio' => 319.00, 'duracion' => '4 días / 3 noches'],
            ['id' => 44, 'city_key' => 'puerto maldonado', 'name' => 'Ecoaventura Puerto Maldonado Premium', 'precio' => 462.00, 'duracion' => '4 días / 3 noches'],
            ['id' => 48, 'city_key' => 'iquitos', 'name' => 'Iquitos Mágico en el Amazonas', 'precio' => 520.00, 'duracion' => '4 días / 3 noches']
        ]
    ],
    'sierra' => [
        'title' => 'la Sierra Peruana',
        'keywords' => ['sierra', 'andes', 'andina', 'andinos', 'andino', 'paisajes andinos', 'montana'],
        'desc' => "¡La Sierra Peruana es majestuosa! 🏔️✨ Explora **Cajamarca, Cusco, Arequipa, Puno, Ayacucho y Chachapoyas**.",
        'recom' => "En la Sierra te sugiero **Cajamarca** para historia o **Cusco** para Machu Picchu.",
        'lat' => -13.5226, 'lng' => -71.9673, 'zoom' => 6,
        'paquetes_list' => [
            // PAQUETES CAJAMARCA
            ['id' => 50, 'city_key' => 'cajamarca', 'name' => 'Carnaval Cajamarquino', 'precio' => 305.00, 'duracion' => '4 días / 3 noches'],
            ['id' => 51, 'city_key' => 'cajamarca', 'name' => 'Cajamarca Monumental', 'precio' => 320.00, 'duracion' => '4 días / 3 noches'],
            ['id' => 52, 'city_key' => 'cajamarca', 'name' => 'Cajamarca – Reino Inca del Norte', 'precio' => 230.00, 'duracion' => '3 días / 2 noches'],
            // PAQUETES CUSCO
            ['id' => 1,  'city_key' => 'cusco', 'name' => 'Magia de Machu Picchu', 'precio' => 1890.00, 'duracion' => '5 días / 4 noches'],
            ['id' => 34, 'city_key' => 'cusco', 'name' => 'Salkantay Trek a Machu Picchu', 'precio' => 580.00, 'duracion' => '5 días / 4 noches'],
            // PAQUETES AREQUIPA Y PUNO
            ['id' => 60, 'city_key' => 'arequipa', 'name' => 'Arequipa y Cañón del Colca', 'precio' => 410.00, 'duracion' => '4 días / 3 noches'],
            ['id' => 32, 'city_key' => 'puno', 'name' => 'Puno y Lago Titicaca Encantado', 'precio' => 350.00, 'duracion' => '3 días / 2 noches']
        ]
    ],
    'costa' => [
        'title' => 'la Costa Peruana',
        'keywords' => ['costa', 'playa', 'playas', 'mar', 'calido', 'sol'],
        'desc' => "¡Disfruta del sol, las playas y la gastronomía! ☀️🌊 Experiencias en **Ica, Paracas, Lima, Trujillo y Chiclayo**.",
        'recom' => "En la Costa te recomiendo **Ica y Paracas**.",
        'lat' => -14.0678, 'lng' => -75.7286, 'zoom' => 6,
        'paquetes_list' => [
            ['id' => 3,  'city_key' => 'ica', 'name' => 'Ica y Paracas Mágico', 'precio' => 191.00, 'duracion' => '3 días / 2 noches'],
            ['id' => 4,  'city_key' => 'ica', 'name' => 'Tubulares en Huacachina & Ica', 'precio' => 150.00, 'duracion' => '2 días / 1 noche'],
            ['id' => 5,  'city_key' => 'paracas', 'name' => 'Reserva Nacional de Paracas e Islas Ballestas', 'precio' => 180.00, 'duracion' => '2 días / 1 noche'],
            ['id' => 9,  'city_key' => 'trujillo', 'name' => 'Ruta Arqueológica Trujillo & Chiclayo', 'precio' => 269.00, 'duracion' => '4 días / 3 noches'],
            ['id' => 12, 'city_key' => 'lima', 'name' => 'Lima Colonial y Gastronómica', 'precio' => 150.00, 'duracion' => '2 días / 1 noche']
        ]
    ]
];

// DICCIONARIO CLIMÁTICO
$descripcionesClima = [
    'sierra' => [
        'enero' => 'plena época de lluvias andinas, pero con valles muy verdes y ríos caudalosos.',
        'febrero' => 'el pico más alto de lluvias en los Andes (se aconseja tomar previsiones).',
        'marzo' => 'lluvias moderadas y paisajes verdes al finalizar la temporada fuerte.',
        'abril' => 'transición del clima con lluvias ligeras y campos verdes florecidos.',
        'mayo' => 'inicio de la temporada seca con días soleados y cielos despejados.',
        'junio' => 'cielos azules, sol brillante durante el día y frío seco por las noches.',
        'julio' => 'excelente sol de montaña, ideal para trekkings y excursiones al aire libre.',
        'agosto' => 'días secos, soleados y con aire fresco de montaña.',
        'septiembre' => 'días muy agradables, templados y con casi nula presencia de lluvias.',
        'setiembre' => 'días muy agradables, templados y con casi nula presencia de lluvias.',
        'octubre' => 'clima cálido de día con algunas garúas o lluvias esporádicas.',
        'noviembre' => 'inicio de lluvias esporádicas y temperaturas suaves.',
        'diciembre' => 'comienzo de las lluvias andinas y valles reverdecientes.'
    ],
    'costa' => [
        'enero' => 'sol radiante, clima cálido de verano y excelente tiempo de playa.',
        'febrero' => 'pleno verano costero, días muy soleados y ambiente caluroso.',
        'marzo' => 'clima cálido, soleado y perfecto para actividades al aire libre.',
        'abril' => 'sol agradable con temperaturas templadas de fin de verano.',
        'mayo' => 'transición a temperaturas más frescas y mañanas con neblina leve.',
        'junio' => 'tiempo fresco con cielos parcialmente nublados.',
        'julio' => 'invierno costero con garúas ligeras, humedad y clima fresco.',
        'agosto' => 'clima fresco con neblina matutina y humedad moderada.',
        'septiembre' => 'temperaturas templadas con ligeros despejes de sol por las tardes.',
        'setiembre' => 'temperaturas templadas con ligeros despejes de sol por las tardes.',
        'octubre' => 'días más templados y mayor presencia de sol.',
        'noviembre' => 'temperaturas en aumento y días más soleados anticipando el verano.',
        'diciembre' => 'inicio del verano costero con sol y buen clima de playa.'
    ],
    'selva' => [
        'enero' => 'temporada de lluvias intensas y ríos amazónicos con caudal alto.',
        'febrero' => 'lluvias frecuentes en la Amazonía que llenan las cochas y ríos.',
        'marzo' => 'clima húmedo con lluvias tropicales y vegetación en su máximo esplendor.',
        'abril' => 'fin de las lluvias fuertes y ríos en excelente nivel para navegar.',
        'mayo' => 'inicio de la época con menos lluvias, ideal para adentrarse en la selva.',
        'junio' => 'temporada seca (vaciante), perfecta para caminatas y observar animales.',
        'julio' => 'días soleados, ríos en nivel bajo y playas de río ideales para explorar.',
        'agosto' => 'clima cálido, soleado y seco para excursiones y caminatas.',
        'septiembre' => 'sol constante y baja probabilidad de lluvias en la selva.',
        'setiembre' => 'sol constante y baja probabilidad de lluvias en la selva.',
        'octubre' => 'días calurosos con chubascos o lluvias breves de refresco.',
        'noviembre' => 'reinicio gradual de lluvias tropicales y aumento de ríos.',
        'diciembre' => 'temporada de lluvias tropicales con ambiente cálido y húmedo.'
    ]
];

$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'setiembre', 'octubre', 'noviembre', 'diciembre'];

// DETECTAR MES SI ESTÁ EN EL MENSAJE
$detectedMonth = null;
foreach ($meses as $m) {
    if (strpos($userClean, $m) !== false) {
        $detectedMonth = $m;
        $_SESSION['travel_month'] = $m;
        break;
    }
}

// 1. PRIMERO BUSCAR SI ES UNA CIUDAD ESPECÍFICA
$detectedCity = null;
foreach ($ciudadesDB as $cityKey => $cityData) {
    if (strpos($userClean, $cityKey) !== false) {
        $detectedCity = $cityData;
        $_SESSION['last_city'] = $cityData;
        $_SESSION['last_destination'] = $cityData['region'];
        break;
    }
}

// 2. SI NO DIJO CIUDAD, BUSCAR SI DIJO REGIÓN GENERAL
if (!$detectedCity) {
    foreach ($destinosDB as $key => $data) {
        foreach ($data['keywords'] as $kw) {
            if (strpos($userClean, $kw) !== false) {
                $_SESSION['last_destination'] = $key;
                unset($_SESSION['last_city']);
                break 2;
            }
        }
    }
}

$activeContext = $_SESSION['last_destination'] ?? null;
$activeCity = $_SESSION['last_city'] ?? null;
$activeMonth = $_SESSION['travel_month'] ?? null;

// -------------------------------------------------------------------------
// PASO 6: SOLICITUD DE PAQUETES Y PRECIOS (FILTRADO ESTRICTO Y EXACTO)
// -------------------------------------------------------------------------
if (preg_match('/\b(paquetes|precios|ver paquetes|mostrar paquetes|opciones)\b/u', $userClean) || 
   (preg_match('/\b(si|claro|muestrame|muestra)\b/u', $userClean) && $activeContext && $activeMonth)) {

    $info = $destinosDB[$activeContext];
    $txtMes = $activeMonth ? " para **" . ucfirst($activeMonth) . "**" : "";

    $paquetesFiltrados = [];

    // FILTRADO ESTRICTO: Si el usuario eligió una ciudad, solo extrae paquetes con su 'city_key'
    if ($activeCity) {
        $targetKey = $activeCity['key'];
        foreach ($info['paquetes_list'] as $pkg) {
            if (isset($pkg['city_key']) && $pkg['city_key'] === $targetKey) {
                $paquetesFiltrados[] = $pkg;
            }
        }
    } else {
        // Si no seleccionó ciudad pero seleccionó región (ej. Selva/Costa/Sierra)
        $paquetesFiltrados = $info['paquetes_list'];
    }

    $lugarNombre = $activeCity ? $activeCity['nombre'] : $info['title'];
    $searchTerm = $activeCity ? $activeCity['nombre'] : $activeContext;
    $urlBusqueda = "index.php?search=" . urlencode($searchTerm);

    // CONSTRUCCIÓN DEL MENSAJE DEPENDIENDO DE SI EXISTEN PAQUETES O NO
    if (!empty($paquetesFiltrados)) {
        $topPaquetes = array_slice($paquetesFiltrados, 0, 5);
        $listaPaquetes = "";
        $count = 1;
        foreach ($topPaquetes as $pkg) {
            $precioTxt = (isset($pkg['precio']) && $pkg['precio'] > 0) 
                ? " - **S/ " . number_format($pkg['precio'], 2) . "**" 
                : " - *(Consultar precio)*";
                
            $listaPaquetes .= "{$count}. [**{$pkg['name']}**](paquete.php?id={$pkg['id']}) ({$pkg['duracion']}){$precioTxt}\n";
            $count++;
        }

        $reply = "📦 **¡Excelente! Aquí tienes algunos paquetes recomendados para {$lugarNombre}{$txtMes}:**\n\n" .
                 $listaPaquetes . "\n" .
                 "👉 **[Haz clic aquí para ver todos los paquetes en la web]({$urlBusqueda})**";
    } else {
        // Si no hay paquetes en el array local pero existe el destino, evita mostrar otras ciudades
        $reply = "📦 **¡Tenemos opciones disponibles para {$lugarNombre}{$txtMes}!**\n\n" .
                 "Puedes revisar la lista completa y actualizada directamente en nuestro catálogo:\n\n" .
                 "👉 **[Haz clic aquí para ver los paquetes de {$lugarNombre} en la web]({$urlBusqueda})**";
    }

    $mapData = $activeCity ? 
        ['lat' => $activeCity['lat'], 'lng' => $activeCity['lng'], 'zoom' => $activeCity['zoom'], 'title' => $activeCity['nombre']] : 
        ['lat' => $info['lat'], 'lng' => $info['lng'], 'zoom' => $info['zoom'], 'title' => $info['title']];

    echo json_encode([
        'message' => $reply,
        'redirect_url' => $urlBusqueda,
        'map_location' => $mapData
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------------------
// PASO 5: EL USUARIO RESPONDE EL MES
// -------------------------------------------------------------------------
if ($detectedMonth && $activeContext && isset($destinosDB[$activeContext])) {
    $info = $destinosDB[$activeContext];
    $climaTxt = $descripcionesClima[$activeContext][$detectedMonth] ?? "excelentes condiciones para viajar.";

    $lugarNombre = $activeCity ? $activeCity['nombre'] : $info['title'];

    $reply = "¡Excelente! Para viajar a **{$lugarNombre}** en **" . ucfirst($detectedMonth) . "** tendrás {$climaTxt}\n\n" .
             "¿Te gustaría ver los **paquetes disponibles** para esta fecha? 🎒✈️";

    $mapData = $activeCity ? 
        ['lat' => $activeCity['lat'], 'lng' => $activeCity['lng'], 'zoom' => $activeCity['zoom'], 'title' => $activeCity['nombre']] : 
        ['lat' => $info['lat'], 'lng' => $info['lng'], 'zoom' => $info['zoom'], 'title' => $info['title']];

    echo json_encode([
        'message' => $reply,
        'map_location' => $mapData
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------------------
// PASO 4: USUARIO PREGUNTA O RESPONDE "SÍ" A LA RECOMENDACIÓN DE LUGAR
// -------------------------------------------------------------------------
$quiereRecomendacion = preg_match('/(recomienda|recomiendas|recomendar|recomendacion|sugieres|que lugar|donde ir)/u', $userClean);
$respondioSiSinMes = (preg_match('/\b(si|claro|por favor)\b/u', $userClean) && $activeContext && !$activeMonth);

if ($quiereRecomendacion || $respondioSiSinMes) {
    if ($activeContext && isset($destinosDB[$activeContext])) {
        $info = $destinosDB[$activeContext];
        
        $reply = "📍 {$info['recom']}\n\n¿En qué **mes del año** tienes planeado viajar?";

        echo json_encode([
            'message' => $reply,
            'map_location' => ['lat' => $info['lat'], 'lng' => $info['lng'], 'zoom' => $info['zoom'], 'title' => $info['title']]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        echo json_encode([
            'message' => "Para darte la mejor recomendación, ¿prefieres explorar la **Costa**, la **Sierra** o la **Selva**?",
            'map_location' => ['lat' => -9.19, 'lng' => -75.01, 'zoom' => 5, 'title' => 'Perú']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// -------------------------------------------------------------------------
// PASO 3.A: EL USUARIO DIJO UNA CIUDAD ESPECÍFICA ("Arequipa", "Tarapoto", etc.)
// -------------------------------------------------------------------------
if ($detectedCity) {
    $reply = "¡**{$detectedCity['nombre']}** es un destino maravilloso! 🌴✨\n\n¿En qué **mes del año** tienes planeado realizar tu viaje?";

    echo json_encode([
        'message' => $reply,
        'map_location' => [
            'lat' => $detectedCity['lat'], 
            'lng' => $detectedCity['lng'], 
            'zoom' => $detectedCity['zoom'], 
            'title' => $detectedCity['nombre']
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------------------
// PASO 3.B: EL USUARIO DIJO UNA REGIÓN GENERAL ("Selva", "Sierra", "Costa")
// -------------------------------------------------------------------------
if ($activeContext && isset($destinosDB[$activeContext])) {
    $info = $destinosDB[$activeContext];
    $reply = $info['desc'] . "\n\n¿Te gustaría saber **qué lugar te recomiendo** o en qué **mes** piensas viajar?";

    echo json_encode([
        'message' => $reply,
        'map_location' => ['lat' => $info['lat'], 'lng' => $info['lng'], 'zoom' => $info['zoom'], 'title' => $info['title']]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------------------
// RESCATE FINAL POR DEFECTO
// -------------------------------------------------------------------------
echo json_encode([
    'message' => "Para ayudarte mejor, ¿prefieres viajar a la **Selva**, la **Sierra** o la **Costa**?",
    'map_location' => ['lat' => -9.19, 'lng' => -75.01, 'zoom' => 5, 'title' => 'Perú']
], JSON_UNESCAPED_UNICODE);
exit;