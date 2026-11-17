<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Gestionar tours';
$pdo = get_db_connection();

// Eliminar tour completo
if (isset($_GET['delete'])) {
    $id_to_delete = (int)$_GET['delete'];
    deletePackageFull($id_to_delete);
    header('Location: tours.php?msg=deleted');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_tour') {
    $data = [
        'id'            => !empty($_POST['id']) ? (int)$_POST['id'] : null,
        'name'          => trim($_POST['name'] ?? ''),
        'destino'       => trim($_POST['destino'] ?? ''),
        'category'      => trim($_POST['category'] ?? ''),
        'duration'      => (int)($_POST['duration'] ?? 1),
        'durationLabel' => trim($_POST['durationLabel'] ?? ''),
        'price'         => (float)($_POST['price'] ?? 0),
        'oldPrice'      => (float)($_POST['oldPrice'] ?? 0),
        'promo'         => isset($_POST['promo']) ? 1 : 0,
        'discount'      => (int)($_POST['discount'] ?? 0),
        'img'           => trim($_POST['img'] ?? ''),
        'rating'        => trim($_POST['rating'] ?? '4.9'),
        'reviews'       => (int)($_POST['reviews'] ?? 10),
        'popular'       => (int)($_POST['popular'] ?? 0),
        'priceLabel'    => trim($_POST['priceLabel'] ?? 'por persona'),
        'description'   => trim($_POST['description'] ?? ''),
        'notes'         => trim($_POST['notes'] ?? ''),
    ];

    if ($data['name'] === '') $errors[] = 'El nombre del paquete es obligatorio.';
    if ($data['destino'] === '') $errors[] = 'El destino es obligatorio.';
    if ($data['price'] <= 0) $errors[] = 'El precio debe ser mayor a cero.';
    if ($data['img'] === '') $errors[] = 'La URL de la imagen de portada es obligatoria.';
    if ($data['durationLabel'] === '') {
        $data['durationLabel'] = $data['duration'] . ' Días / ' . max(1, $data['duration'] - 1) . ' Noches';
    }

    if (empty($errors)) {
        // 1. Guardar o actualizar paquete principal
        $packageId = savePackage($data);

        // 2. Guardar Itinerario Detallado (Día a Día)
        $dias = [];
        if (!empty($_POST['itinerary_titles']) && is_array($_POST['itinerary_titles'])) {
            foreach ($_POST['itinerary_titles'] as $idx => $title) {
                $dayNum = !empty($_POST['itinerary_days'][$idx]) ? (int)$_POST['itinerary_days'][$idx] : ($idx + 1);
                $desc = $_POST['itinerary_descriptions'][$idx] ?? '';
                if (trim($title) !== '' || trim($desc) !== '') {
                    $dias[] = [
                        'day' => $dayNum,
                        'title' => $title,
                        'description' => $desc
                    ];
                }
            }
        }
        savePackageItinerary($packageId, $dias);

        // 3. Guardar Qué Incluye
        $incluyeRaw = $_POST['incluye_texto'] ?? '';
        $incluyeList = array_filter(array_map('trim', explode("\n", str_replace("\r", "", $incluyeRaw))));
        savePackageIncludes($packageId, $incluyeList);

        // 4. Guardar No Incluye
        $noIncluyeRaw = $_POST['no_incluye_texto'] ?? '';
        $noIncluyeList = array_filter(array_map('trim', explode("\n", str_replace("\r", "", $noIncluyeRaw))));
        savePackageNotIncludes($packageId, $noIncluyeList);

        // 5. Guardar Opciones de Hoteles y Tarifas
        $hoteles = [];
        if (!empty($_POST['hotel_names']) && is_array($_POST['hotel_names'])) {
            foreach ($_POST['hotel_names'] as $idx => $hName) {
                if (trim($hName) !== '') {
                    $hoteles[] = [
                        'hotel'          => $hName,
                        'hotel_category' => $_POST['hotel_categories'][$idx] ?? '3 Estrellas',
                        'simple'         => $_POST['hotel_simples'][$idx] ?? 0,
                        'doble'          => $_POST['hotel_dobles'][$idx] ?? 0,
                        'triple'         => $_POST['hotel_triples'][$idx] ?? 0,
                    ];
                }
            }
        }
        savePackageHotels($packageId, $hoteles);

        header('Location: tours.php?msg=saved');
        exit;
    }
}

$paquetes = $pdo->query('SELECT * FROM paquetes ORDER BY id DESC')->fetchAll();
?>
<?php include __DIR__ . '/includes/admin_header.php'; ?>

<style>
/* Estilos para el Editor Completo de Tours */
.tour-modal-container {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 25px 60px rgba(15, 42, 74, 0.35), 0 0 0 1px rgba(226, 232, 240, 0.9);
    width: 95%;
    max-width: 980px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    padding: 28px 32px;
    box-sizing: border-box;
    position: relative;
    overflow: hidden;
}

.tour-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 16px;
    margin-bottom: 16px;
}

.tour-modal-title {
    font-size: 1.45rem;
    font-weight: 700;
    color: #0f2a4a;
    margin: 0 0 4px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.tour-modal-subtitle {
    margin: 0;
    font-size: 0.88rem;
    color: #64748b;
}

/* Barra de pestañas */
.tour-tabs-nav {
    display: flex;
    gap: 8px;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 18px;
    overflow-x: auto;
    padding-bottom: 2px;
}

.tour-tab-btn {
    background: transparent;
    border: none;
    padding: 10px 16px;
    font-size: 0.92rem;
    font-weight: 600;
    color: #64748b;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    white-space: nowrap;
    border-radius: 6px 6px 0 0;
}

.tour-tab-btn:hover {
    color: #0f2a4a;
    background: #f8fafc;
}

.tour-tab-btn.active {
    color: #0f2a4a;
    border-bottom-color: #d4a017;
    background: #f1f5f9;
}

.tour-tab-counter {
    background: #e2e8f0;
    color: #1e293b;
    font-size: 0.72rem;
    padding: 2px 7px;
    border-radius: 10px;
    font-weight: 700;
}

.tour-tab-btn.active .tour-tab-counter {
    background: #0f2a4a;
    color: #fff;
}

/* Área de contenido de pestañas */
.tour-tab-content-area {
    flex: 1;
    overflow-y: auto;
    padding-right: 10px;
    margin-bottom: 15px;
}

.tour-tab-pane {
    display: none;
    animation: fadeInTab 0.2s ease-out;
}

.tour-tab-pane.active {
    display: block;
}

@keyframes fadeInTab {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Grid de Formulario General */
.tour-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

@media (max-width: 768px) {
    .tour-form-grid {
        grid-template-columns: 1fr;
    }
}

.tour-form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.tour-form-group.full-width {
    grid-column: 1 / -1;
}

.tour-form-group label {
    font-size: 0.86rem;
    font-weight: 600;
    color: #334155;
}

.tour-form-group input[type="text"],
.tour-form-group input[type="number"],
.tour-form-group select,
.tour-form-group textarea {
    padding: 10px 12px;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.92rem;
    color: #1e293b;
    font-family: inherit;
    box-sizing: border-box;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.tour-form-group input:focus,
.tour-form-group select:focus,
.tour-form-group textarea:focus {
    outline: none;
    border-color: #d4a017;
    box-shadow: 0 0 0 3px rgba(212, 160, 23, 0.15);
}

.tour-info-banner {
    background: #f8fafc;
    border-left: 4px solid #0f2a4a;
    padding: 12px 16px;
    border-radius: 0 8px 8px 0;
    margin-bottom: 16px;
    font-size: 0.88rem;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Tarjetas de Itinerario */
.itinerary-day-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.02);
    transition: all 0.2s ease;
}

.itinerary-day-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.itinerary-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px dashed #e2e8f0;
}

.itinerary-day-badge {
    background: #0f2a4a;
    color: #ffffff;
    font-weight: 700;
    font-size: 0.82rem;
    padding: 4px 12px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-delete-row {
    background: #fee2e2;
    color: #dc2626;
    border: none;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: background 0.2s;
}

.btn-delete-row:hover {
    background: #fecaca;
}

/* Incluye y No Incluye en 2 Columnas */
.includes-split-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media (max-width: 768px) {
    .includes-split-grid {
        grid-template-columns: 1fr;
    }
}

.include-box {
    background: #f0fdf4;
    border: 1.5px solid #bbf7d0;
    border-radius: 10px;
    padding: 16px;
}

.not-include-box {
    background: #fef2f2;
    border: 1.5px solid #fecaca;
    border-radius: 10px;
    padding: 16px;
}

.box-header-title {
    font-size: 0.98rem;
    font-weight: 700;
    margin: 0 0 6px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.box-help-text {
    font-size: 0.8rem;
    color: #64748b;
    margin: 0 0 10px;
    line-height: 1.4;
}

.box-textarea {
    width: 100%;
    border-radius: 8px;
    padding: 12px;
    font-family: inherit;
    font-size: 0.9rem;
    line-height: 1.6;
    border: 1.5px solid #cbd5e1;
    box-sizing: border-box;
}

/* Tabla de Hoteles */
.hotels-custom-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.hotels-custom-table th {
    background: #f1f5f9;
    color: #334155;
    padding: 10px 12px;
    font-weight: 700;
    text-align: left;
    border-bottom: 2px solid #e2e8f0;
}

.hotels-custom-table td {
    padding: 8px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.hotels-custom-table input[type="text"],
.hotels-custom-table input[type="number"],
.hotels-custom-table select {
    width: 100%;
    padding: 8px 10px;
    border: 1.5px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.88rem;
    box-sizing: border-box;
}

.btn-add-section {
    background: #0f2a4a;
    color: #ffffff;
    border: none;
    padding: 9px 16px;
    border-radius: 8px;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: all 0.2s ease;
    margin-top: 10px;
}

.btn-add-section:hover {
    background: #1e3a8a;
    transform: translateY(-1px);
}

/* Footer Modal */
.tour-modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #f1f5f9;
    padding-top: 16px;
    margin-top: 5px;
}

.tour-loading-indicator {
    font-size: 0.86rem;
    color: #0f2a4a;
    display: none;
    align-items: center;
    gap: 8px;
    font-weight: 600;
}

.tour-loading-indicator.active {
    display: inline-flex;
}
</style>

<div class="admin-panel">
    <div class="admin-panel-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="admin-panel-title"><i class="fa-solid fa-map-location-dot"></i> Gestionar tours y paquetes</h3>
        <button onclick="openTourModal()" class="btn-add"><i class="fa-solid fa-plus"></i> Crear nuevo tour</button>
    </div>
    <div class="admin-panel-body">
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 600;">
                <i class="fa-solid fa-circle-check" style="color: #10b981; font-size: 1.2rem;"></i>
                <span>¡El paquete turístico, su itinerario, servicios incluidos y tarifas de hoteles se guardaron exitosamente!</span>
            </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 600;">
                <i class="fa-solid fa-trash" style="color: #ef4444; font-size: 1.2rem;"></i>
                <span>El paquete turístico y todos sus datos complementarios fueron eliminados correctamente.</span>
            </div>
        <?php endif; ?>

        <!-- Modal Editor Completo de Tours -->
        <div id="tourModal" class="modal-overlay">
            <div class="tour-modal-container">
                <button type="button" class="modal-close" onclick="closeTourModal()">&times;</button>
                
                <div class="tour-modal-header">
                    <div>
                        <h2 class="tour-modal-title" id="tourModalTitle"><i class="fa-solid fa-mountain-sun" style="color: #d4a017;"></i> Crear tour nuevo</h2>
                        <p class="tour-modal-subtitle" id="tourModalSubtitle">Configura todos los detalles, itinerario por días, servicios y hoteles del paquete.</p>
                    </div>
                </div>

                <!-- Pestañas de Navegación -->
                <div class="tour-tabs-nav">
                    <button type="button" class="tour-tab-btn active" data-tab="tab-general" onclick="switchTourTab('tab-general')">
                        <i class="fa-solid fa-circle-info"></i> 1. Información General
                    </button>
                    <button type="button" class="tour-tab-btn" data-tab="tab-itinerary" onclick="switchTourTab('tab-itinerary')">
                        <i class="fa-solid fa-route"></i> 2. Itinerario Detallado <span class="tour-tab-counter" id="tabItineraryCount">0</span>
                    </button>
                    <button type="button" class="tour-tab-btn" data-tab="tab-includes" onclick="switchTourTab('tab-includes')">
                        <i class="fa-solid fa-list-check"></i> 3. Incluye y No Incluye
                    </button>
                    <button type="button" class="tour-tab-btn" data-tab="tab-hotels" onclick="switchTourTab('tab-hotels')">
                        <i class="fa-solid fa-hotel"></i> 4. Opciones de Hoteles <span class="tour-tab-counter" id="tabHotelsCount">0</span>
                    </button>
                </div>

                <form action="tours.php" method="post" id="tourForm" style="display: flex; flex-direction: column; flex: 1; overflow: hidden; margin: 0;">
                    <input type="hidden" name="action" value="save_tour">
                    <input type="hidden" name="id" id="modalId">

                    <!-- Área con Scroll de Pestañas -->
                    <div class="tour-tab-content-area">
                        
                        <!-- PESTAÑA 1: INFORMACIÓN GENERAL -->
                        <div id="tab-general" class="tour-tab-pane active">
                            <div class="tour-form-grid">
                                <div>
                                    <label>Nombre del paquete *</label>
                                    <input type="text" name="name" id="modalName" placeholder="Ej: Cusco Mágico, Valle Sagrado y Machu Picchu" required>
                                </div>
                                <div>
                                    <label>Destino principal *</label>
                                    <input type="text" name="destino" id="modalDestino" placeholder="Ej: Cusco, Arequipa, Puno..." required>
                                </div>
                                <div>
                                    <label>Categoría</label>
                                    <input type="text" name="category" id="modalCategory" placeholder="Ej: Clásico, Aventura, Cultural, De Lujo...">
                                </div>
                                <div>
                                    <label>Duración (en días) *</label>
                                    <input type="number" name="duration" id="modalDuration" min="1" placeholder="Ej: 4" required oninput="autoUpdateDurationLabel()">
                                </div>
                                <div>
                                    <label>Etiqueta de duración visible</label>
                                    <input type="text" name="durationLabel" id="modalDurationLabel" placeholder="Ej: 4 Días / 3 Noches">
                                </div>
                                <div>
                                    <label>Precio por persona ($ USD) *</label>
                                    <input type="number" step="0.01" name="price" id="modalPrice" placeholder="Ej: 650.00" required>
                                </div>
                                <div>
                                    <label>Precio anterior tachado ($ USD)</label>
                                    <input type="number" step="0.01" name="oldPrice" id="modalOldPrice" placeholder="Ej: 750.00 (opcional)">
                                </div>
                                <div>
                                    <label>Etiqueta de precio</label>
                                    <input type="text" name="priceLabel" id="modalPriceLabel" placeholder="Ej: por persona">
                                </div>
                                <div class="full-width">
                                    <label>Imagen principal de portada (URL) *</label>
                                    <div style="display: flex; gap: 10px;">
                                        <input type="text" name="img" id="modalImg" placeholder="https://ejemplo.com/machu-picchu.jpg" required style="flex: 1;" oninput="updateCoverPreview()">
                                        <div id="coverPreviewBox" style="width: 50px; height: 42px; border-radius: 6px; border: 1.5px solid #cbd5e1; background: #f8fafc center/cover no-repeat; flex-shrink: 0;"></div>
                                    </div>
                                </div>
                                <div>
                                    <label>Rating de estrellas</label>
                                    <input type="text" name="rating" id="modalRating" placeholder="Ej: 4.9">
                                </div>
                                <div>
                                    <label>Cantidad de reseñas</label>
                                    <input type="number" name="reviews" id="modalReviews" min="0" placeholder="Ej: 120">
                                </div>
                                <div class="full-width">
                                    <label>Resumen de la Experiencia / Descripción detallada</label>
                                    <textarea name="description" id="modalDescription" rows="3" placeholder="Describe brevemente la experiencia que vivirá el viajero en este tour..."></textarea>
                                </div>
                                <div class="full-width">
                                    <label>Notas internas para el administrador</label>
                                    <textarea name="notes" id="modalNotes" rows="2" placeholder="Notas internas para el counter o reservaciones (no visibles para el cliente)..."></textarea>
                                </div>
                                <div class="full-width" style="display: flex; gap: 16px; align-items: center; background: #f8fafc; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
                                        <input type="checkbox" name="promo" id="modalPromo" value="1"> ¿Paquete en Oferta / Destacado?
                                    </label>
                                    <div style="display: flex; align-items: center; gap: 8px; margin-left: 15px;">
                                        <label style="font-weight: 600; font-size: 0.85rem;">% Descuento:</label>
                                        <input type="number" name="discount" id="modalDiscount" min="0" max="100" style="width: 80px;" placeholder="Ej: 15">
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 8px; margin-left: 15px;">
                                        <label style="font-weight: 600; font-size: 0.85rem;">Prioridad / Popularidad:</label>
                                        <input type="number" name="popular" id="modalPopular" min="0" style="width: 80px;" placeholder="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PESTAÑA 2: ITINERARIO DETALLADO -->
                        <div id="tab-itinerary" class="tour-tab-pane">
                            <div class="tour-info-banner">
                                <i class="fa-solid fa-circle-info" style="color: #0f2a4a; font-size: 1.1rem;"></i>
                                <span>Define las actividades día por día. Se mostrarán con el formato de cronograma interactivo que ven los usuarios.</span>
                            </div>

                            <div id="itineraryDaysContainer">
                                <!-- Filas de días generadas dinámicamente -->
                            </div>

                            <button type="button" class="btn-add-section" onclick="addItineraryDayRow()">
                                <i class="fa-solid fa-plus"></i> Agregar Nuevo Día al Itinerario
                            </button>
                        </div>

                        <!-- PESTAÑA 3: INCLUYE Y NO INCLUYE -->
                        <div id="tab-includes" class="tour-tab-pane">
                            <div class="tour-info-banner">
                                <i class="fa-solid fa-circle-check" style="color: #16a34a; font-size: 1.1rem;"></i>
                                <span>Escribe cada servicio en una línea separada. Se organizarán automáticamente con iconos atractivos en la ficha del tour.</span>
                            </div>

                            <div class="includes-split-grid">
                                <div class="include-box">
                                    <h4 class="box-header-title" style="color: #16a34a;">
                                        <i class="fa-solid fa-circle-check"></i> Qué incluye este paquete
                                    </h4>
                                    <p class="box-help-text">Escribe un servicio por línea (se mostrarán con checks verdes):</p>
                                    <textarea name="incluye_texto" id="modalIncluyeTexto" class="box-textarea" rows="10" placeholder="Traslados aeropuerto - hotel - aeropuerto&#10;3 noches de alojamiento en Cusco&#10;Desayunos diarios según hotel&#10;Tour Valle Sagrado con almuerzo buffet&#10;Tickets de tren turístico a Machu Picchu&#10;Guía profesional oficial bilingüe"></textarea>
                                </div>

                                <div class="not-include-box">
                                    <h4 class="box-header-title" style="color: #dc2626;">
                                        <i class="fa-solid fa-circle-xmark"></i> Qué NO incluye este paquete
                                    </h4>
                                    <p class="box-help-text">Escribe un servicio por línea (se mostrarán con X roja):</p>
                                    <textarea name="no_incluye_texto" id="modalNoIncluyeTexto" class="box-textarea" rows="10" placeholder="Pasajes aéreos nacionales o internacionales&#10;Tasas de aeropuerto&#10;Propinas a guías y maleteros&#10;Almuerzos o cenas no especificados"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- PESTAÑA 4: HOTELES Y TARIFAS -->
                        <div id="tab-hotels" class="tour-tab-pane">
                            <div class="tour-info-banner">
                                <i class="fa-solid fa-bed" style="color: #0f2a4a; font-size: 1.1rem;"></i>
                                <span>Ingresa los hoteles disponibles y las tarifas en dólares ($ USD) para habitaciones Simple, Doble y Triple.</span>
                            </div>

                            <div style="overflow-x: auto; background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px;">
                                <table class="hotels-custom-table" id="hotelsTable">
                                    <thead>
                                        <tr>
                                            <th style="min-width: 220px;">Nombre del Hotel</th>
                                            <th style="min-width: 140px;">Categoría</th>
                                            <th style="min-width: 110px;">Simple ($)</th>
                                            <th style="min-width: 110px;">Doble ($)</th>
                                            <th style="min-width: 110px;">Triple ($)</th>
                                            <th style="width: 50px; text-align: center;">Quitar</th>
                                        </tr>
                                    </thead>
                                    <tbody id="hotelsTableBody">
                                        <!-- Filas de hoteles generadas dinámicamente -->
                                    </tbody>
                                </table>
                            </div>

                            <button type="button" class="btn-add-section" onclick="addHotelRow()">
                                <i class="fa-solid fa-plus"></i> Agregar Opción de Hotel
                            </button>
                        </div>

                    </div>

                    <!-- Footer del Modal -->
                    <div class="tour-modal-footer">
                        <div class="tour-loading-indicator" id="tourModalLoading">
                            <i class="fa-solid fa-spinner fa-spin"></i> Cargando itinerario y servicios...
                        </div>
                        <div style="display: flex; gap: 10px; margin-left: auto;">
                            <button type="button" class="btn-filter-clear" onclick="closeTourModal()" style="padding: 10px 18px; border-radius: 8px; font-weight: 600;">Cancelar</button>
                            <button type="submit" class="btn-add" style="padding: 10px 22px; border-radius: 8px; font-weight: 700; background: #16a34a; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-cloud-arrow-up"></i> Guardar y Publicar Tour
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de Paquetes -->
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th style="width: 70px;">Foto</th>
                        <th>Nombre del Tour</th>
                        <th>Destino</th>
                        <th>Precio</th>
                        <th>Categoría</th>
                        <th>Duración</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paquetes as $pkg): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($pkg['id']) ?></strong></td>
                            <td>
                                <img src="<?= htmlspecialchars($pkg['img']) ?>" alt="Foto" style="width: 50px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0;" onerror="this.src='../assets/images/destinos/machu-picchu.jpg'">
                            </td>
                            <td>
                                <strong style="color: #0f2a4a; display: block;"><?= htmlspecialchars($pkg['name']) ?></strong>
                                <?php if (!empty($pkg['promo'])): ?>
                                    <span style="font-size: 0.75rem; background: #fef08a; color: #854d0e; padding: 2px 6px; border-radius: 4px; font-weight: 600;">En Oferta</span>
                                <?php endif; ?>
                            </td>
                            <td><i class="fa-solid fa-location-dot" style="color: #d4a017;"></i> <?= htmlspecialchars($pkg['destino']) ?></td>
                            <td><strong style="color: #0f2a4a; font-size: 1rem;">$<?= number_format($pkg['price'], 2) ?></strong></td>
                            <td><span style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 0.85rem; font-weight: 600;"><?= htmlspecialchars($pkg['category'] ?: 'Clásico') ?></span></td>
                            <td><?= htmlspecialchars($pkg['durationLabel'] ?: $pkg['duration'] . ' Días') ?></td>
                            <td>
                                <div class="actions-cell-flex" style="justify-content: center;">
                                    <!-- Ver en web -->
                                    <a href="../paquete.php?id=<?= $pkg['id'] ?>" target="_blank" class="btn-action btn-view" title="Ver paquete en la página web">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>

                                    <!-- Editar tour -->
                                    <button onclick='openTourModal(<?= htmlspecialchars(json_encode($pkg), ENT_QUOTES, "UTF-8") ?>)' class="btn-action btn-edit" title="Editar tour completo">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>

                                    <!-- Eliminar tour -->
                                    <a href="tours.php?delete=<?= htmlspecialchars($pkg['id']) ?>" class="btn-action btn-delete" title="Eliminar tour" onclick="return confirm('¿Seguro que deseas eliminar este tour y todo su itinerario y hoteles asociados?');">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($paquetes)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 30px; color: #64748b;">
                                No hay paquetes turísticos registrados. ¡Haz clic en "Crear nuevo tour" para empezar!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>

<script>
// Manejo de Pestañas
function switchTourTab(tabId) {
    document.querySelectorAll('.tour-tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
    });
    document.querySelectorAll('.tour-tab-pane').forEach(pane => {
        pane.classList.toggle('active', pane.id === tabId);
    });
}

function updateCoverPreview() {
    const url = document.getElementById('modalImg').value.trim();
    const box = document.getElementById('coverPreviewBox');
    if (url) {
        box.style.backgroundImage = 'url("' + url + '")';
    } else {
        box.style.backgroundImage = 'none';
    }
}

function autoUpdateDurationLabel() {
    const dur = parseInt(document.getElementById('modalDuration').value) || 1;
    const labelInput = document.getElementById('modalDurationLabel');
    if (!labelInput.value || labelInput.value.includes('Días')) {
        const noches = Math.max(1, dur - 1);
        labelInput.value = dur + ' Días / ' + noches + ' Noches';
    }
}

// ITINERARIO DINÁMICO
function addItineraryDayRow(dayNum = null, title = '', desc = '') {
    const container = document.getElementById('itineraryDaysContainer');
    const existingDays = container.querySelectorAll('.itinerary-day-card').length;
    const currentDay = dayNum !== null ? dayNum : (existingDays + 1);

    const card = document.createElement('div');
    card.className = 'itinerary-day-card';
    card.innerHTML = `
        <div class="itinerary-card-header">
            <span class="itinerary-day-badge"><i class="fa-solid fa-calendar-day"></i> Día <span class="day-number-label">${currentDay}</span></span>
            <input type="hidden" name="itinerary_days[]" class="input-day-number" value="${currentDay}">
            <button type="button" class="btn-delete-row" onclick="removeItineraryDay(this)">
                <i class="fa-solid fa-trash"></i> Eliminar Día
            </button>
        </div>
        <div style="display: flex; flex-direction: column; gap: 8px;">
            <div>
                <label style="font-size: 0.82rem; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">Título del Día</label>
                <input type="text" name="itinerary_titles[]" value="${escapeHtml(title)}" placeholder="Ej: Cusco – City Tour y Ruinas Cercanas" style="width: 100%; padding: 8px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; box-sizing: border-box;" required>
            </div>
            <div>
                <label style="font-size: 0.82rem; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;">Descripción detallada de actividades</label>
                <textarea name="itinerary_descriptions[]" rows="3" placeholder="Recepción en aeropuerto, traslado al hotel, visita guiada por la tarde..." style="width: 100%; padding: 8px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem; box-sizing: border-box; font-family: inherit;">${escapeHtml(desc)}</textarea>
            </div>
        </div>
    `;
    container.appendChild(card);
    updateItineraryCounters();
}

function removeItineraryDay(btn) {
    const card = btn.closest('.itinerary-day-card');
    card.remove();
    renumberItineraryDays();
    updateItineraryCounters();
}

function renumberItineraryDays() {
    const container = document.getElementById('itineraryDaysContainer');
    const cards = container.querySelectorAll('.itinerary-day-card');
    cards.forEach((card, idx) => {
        const num = idx + 1;
        card.querySelector('.day-number-label').textContent = num;
        card.querySelector('.input-day-number').value = num;
    });
}

function updateItineraryCounters() {
    const count = document.querySelectorAll('#itineraryDaysContainer .itinerary-day-card').length;
    document.getElementById('tabItineraryCount').textContent = count;
}

// HOTELES DINÁMICOS
function addHotelRow(hotel = '', category = '3 Estrellas', simple = '', doble = '', triple = '') {
    const tbody = document.getElementById('hotelsTableBody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input type="text" name="hotel_names[]" value="${escapeHtml(hotel)}" placeholder="Ej: Monasterio 5★ o Hotel San Agustín" required>
        </td>
        <td>
            <select name="hotel_categories[]">
                <option value="3 Estrellas" ${category === '3 Estrellas' ? 'selected' : ''}>3 Estrellas</option>
                <option value="4 Estrellas" ${category === '4 Estrellas' ? 'selected' : ''}>4 Estrellas</option>
                <option value="5 Estrellas" ${category === '5 Estrellas' ? 'selected' : ''}>5 Estrellas</option>
                <option value="Económico" ${category === 'Económico' ? 'selected' : ''}>Económico</option>
                <option value="Boutique" ${category === 'Boutique' ? 'selected' : ''}>Boutique</option>
            </select>
        </td>
        <td>
            <input type="number" step="0.01" name="hotel_simples[]" value="${simple}" placeholder="0.00">
        </td>
        <td>
            <input type="number" step="0.01" name="hotel_dobles[]" value="${doble}" placeholder="0.00">
        </td>
        <td>
            <input type="number" step="0.01" name="hotel_triples[]" value="${triple}" placeholder="0.00">
        </td>
        <td style="text-align: center;">
            <button type="button" class="btn-delete-row" style="padding: 6px 9px;" onclick="removeHotelRow(this)">
                <i class="fa-solid fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    updateHotelCounters();
}

function removeHotelRow(btn) {
    const tr = btn.closest('tr');
    tr.remove();
    updateHotelCounters();
}

function updateHotelCounters() {
    const count = document.querySelectorAll('#hotelsTableBody tr').length;
    document.getElementById('tabHotelsCount').textContent = count;
}

// ABRIR Y LLENAR MODAL
function openTourModal(tourData = null) {
    switchTourTab('tab-general');
    const loading = document.getElementById('tourModalLoading');
    loading.classList.remove('active');

    // Limpiar contenedores dinámicos
    document.getElementById('itineraryDaysContainer').innerHTML = '';
    document.getElementById('hotelsTableBody').innerHTML = '';
    document.getElementById('modalIncluyeTexto').value = '';
    document.getElementById('modalNoIncluyeTexto').value = '';

    if (tourData) {
        document.getElementById('tourModalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square" style="color: #d4a017;"></i> Editar tour: ' + escapeHtml(tourData.name);
        document.getElementById('tourModalSubtitle').textContent = 'Modifica los datos principales, itinerario por días, servicios y opciones de alojamiento.';
        document.getElementById('modalId').value = tourData.id || '';
        document.getElementById('modalName').value = tourData.name || '';
        document.getElementById('modalDestino').value = tourData.destino || '';
        document.getElementById('modalCategory').value = tourData.category || '';
        document.getElementById('modalDuration').value = tourData.duration || '';
        document.getElementById('modalDurationLabel').value = tourData.durationLabel || '';
        document.getElementById('modalPrice').value = tourData.price || '';
        document.getElementById('modalOldPrice').value = tourData.oldPrice || '';
        document.getElementById('modalImg').value = tourData.img || '';
        document.getElementById('modalRating').value = tourData.rating || '4.9';
        document.getElementById('modalReviews').value = tourData.reviews || '0';
        document.getElementById('modalPopular').value = tourData.popular || '0';
        document.getElementById('modalPriceLabel').value = tourData.priceLabel || 'por persona';
        document.getElementById('modalDescription').value = tourData.description || '';
        document.getElementById('modalNotes').value = tourData.notes || '';
        document.getElementById('modalPromo').checked = tourData.promo == 1;
        document.getElementById('modalDiscount').value = tourData.discount || '';
        updateCoverPreview();

        // Cargar datos complementarios vía AJAX
        loading.classList.add('active');
        fetch('get_tour_details.php?id=' + tourData.id)
            .then(res => res.json())
            .then(data => {
                loading.classList.remove('active');
                if (data.success) {
                    // Itinerario
                    if (data.itinerary && data.itinerary.length > 0) {
                        data.itinerary.forEach((d, i) => {
                            addItineraryDayRow(d.dia || d.day || (i + 1), d.titulo || d.title || '', d.descripcion || d.description || '');
                        });
                    } else {
                        addItineraryDayRow(1);
                    }

                    // Incluye
                    if (data.includes && data.includes.length > 0) {
                        const incText = data.includes.map(item => item.item || item.included_item || '').join('\n');
                        document.getElementById('modalIncluyeTexto').value = incText;
                    }

                    // No incluye
                    if (data.not_includes && data.not_includes.length > 0) {
                        const noIncText = data.not_includes.map(item => item.item || item.not_included_item || '').join('\n');
                        document.getElementById('modalNoIncluyeTexto').value = noIncText;
                    }

                    // Hoteles
                    if (data.hotels && data.hotels.length > 0) {
                        data.hotels.forEach(h => {
                            addHotelRow(h.hotel || '', h.hotel_category || '3 Estrellas', h.simple || '', h.doble || '', h.triple || '');
                        });
                    } else {
                        addHotelRow();
                    }
                }
            })
            .catch(err => {
                loading.classList.remove('active');
                console.error('Error al cargar datos del tour:', err);
                addItineraryDayRow(1);
                addHotelRow();
            });

    } else {
        document.getElementById('tourModalTitle').innerHTML = '<i class="fa-solid fa-mountain-sun" style="color: #d4a017;"></i> Crear tour nuevo';
        document.getElementById('tourModalSubtitle').textContent = 'Llena los datos para publicar un nuevo paquete turístico completo.';
        document.getElementById('modalId').value = '';
        document.getElementById('modalName').value = '';
        document.getElementById('modalDestino').value = '';
        document.getElementById('modalCategory').value = 'Clásico';
        document.getElementById('modalDuration').value = '4';
        document.getElementById('modalDurationLabel').value = '4 Días / 3 Noches';
        document.getElementById('modalPrice').value = '';
        document.getElementById('modalOldPrice').value = '';
        document.getElementById('modalImg').value = '';
        document.getElementById('modalRating').value = '4.9';
        document.getElementById('modalReviews').value = '25';
        document.getElementById('modalPopular').value = '0';
        document.getElementById('modalPriceLabel').value = 'por persona';
        document.getElementById('modalDescription').value = '';
        document.getElementById('modalNotes').value = '';
        document.getElementById('modalPromo').checked = false;
        document.getElementById('modalDiscount').value = '';
        updateCoverPreview();

        // Plantillas por defecto para nuevo tour
        addItineraryDayRow(1, 'Llegada y Recepción', 'Recepción en el aeropuerto o terminal, traslado al hotel y tiempo libre para aclimatarse.');
        addItineraryDayRow(2, 'Tour Principal y Visitas Arqueológicas', 'Visita guiada por los principales atractivos turísticos con transporte turístico y guía bilingüe.');
        
        document.getElementById('modalIncluyeTexto').value = "Traslados aeropuerto - hotel - aeropuerto\nNoches de alojamiento en hotel seleccionado\nDesayunos diarios según hotel\nTransporte turístico privado o compartido\nGuía oficial de turismo bilingüe\nTickets de ingreso a los centros arqueológicos\nAsistencia permanente 24/7";
        document.getElementById('modalNoIncluyeTexto').value = "Pasajes aéreos o buses interprovinciales\nTasas de aeropuerto\nPropinas a guías y maleteros\nComidas no especificadas en el programa";

        addHotelRow('Hotel Turístico Estándar', '3 Estrellas', '350.00', '280.00', '250.00');
    }

    document.getElementById('tourModal').classList.add('active');
}

function closeTourModal() {
    document.getElementById('tourModal').classList.remove('active');
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>
