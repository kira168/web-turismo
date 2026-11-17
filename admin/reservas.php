<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Gestionar reservas';
$pdo = get_db_connection();

// Manejar eliminación de reserva
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM reservas WHERE id = ?');
    $stmt->execute([$delId]);
    header('Location: reservas.php');
    exit;
}

// Manejar cambios de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserva_id'], $_POST['nuevo_estado'])) {
    updateReservationStatus($_POST['reserva_id'], $_POST['nuevo_estado']);
    $redirectUrl = 'reservas.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
    header('Location: ' . $redirectUrl);
    exit;
}

// Filtros
$filterEstado = $_GET['estado'] ?? '';
$filterPago = $_GET['pago'] ?? '';
$search = $_GET['search'] ?? '';

// Si viene con parámetro 'view', prefiltrar por ese código
if (!empty($_GET['view']) && empty($search)) {
    $search = trim($_GET['view']);
}

$query = 'SELECT r.*, p.name AS paquete_name, p.img AS paquete_img, p.destino, p.duration FROM reservas r LEFT JOIN paquetes p ON r.paquete_id = p.id WHERE 1=1';
$params = [];

if ($filterEstado !== '') {
    $query .= ' AND r.estado = ?';
    $params[] = $filterEstado;
}

if ($filterPago !== '') {
    $query .= ' AND r.payment_status = ?';
    $params[] = $filterPago;
}

if ($search !== '') {
    $query .= ' AND (r.numero_reserva LIKE ? OR r.nombre_cliente LIKE ? OR r.email_cliente LIKE ? OR r.telefono LIKE ?)';
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$query .= ' ORDER BY r.created_at DESC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reservas = $stmt->fetchAll();
?>
<?php include __DIR__ . '/includes/admin_header.php'; ?>

<style>
.reservas-filter-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
}
.filter-form-grid {
    display: grid;
    grid-template-columns: 2fr 1.2fr auto auto;
    gap: 15px;
    align-items: flex-end;
}
@media (max-width: 900px) {
    .filter-form-grid {
        grid-template-columns: 1fr;
    }
}
.filter-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.filter-field label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #475569;
}
.filter-field input, .filter-field select {
    padding: 9px 12px;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.9rem;
    outline: none;
    transition: border-color 0.2s;
}
.filter-field input:focus, .filter-field select:focus {
    border-color: var(--admin-card-blue, #0f2a4a);
}
.btn-filter-submit {
    background: #0f2a4a;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 42px;
}
.btn-filter-clear {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
    padding: 10px 16px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 42px;
    box-sizing: border-box;
}
.actions-cell-flex {
    display: flex;
    align-items: center;
    gap: 8px;
}
#reciboImpresion {
    display: none;
}

@media print {
    /* Ocultar elementos de la interfaz de administración */
    .admin-sidebar,
    .admin-topbar,
    .reservas-filter-card,
    .admin-panel,
    .modal-overlay,
    .admin-footer,
    nav, header, aside, .no-print {
        display: none !important;
    }

    body, html {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        height: auto !important;
    }

    .admin-wrapper, .admin-content {
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    /* Mostrar únicamente el Recibo de Reserva */
    #reciboImpresion {
        display: block !important;
        width: 100% !important;
        max-width: 800px !important;
        margin: 0 auto !important;
        padding: 15px 25px !important;
        background: #ffffff !important;
        color: #1e293b !important;
        font-family: 'Poppins', Arial, sans-serif !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    @page {
        size: A4 portrait;
        margin: 12mm 15mm;
    }
}
</style>

<!-- Barra de Búsqueda y Filtros Activos -->
<div class="reservas-filter-card">
    <form action="reservas.php" method="GET" class="filter-form-grid">
        <div class="filter-field">
            <label><i class="fa-solid fa-magnifying-glass"></i> Buscar por código, cliente, correo o celular</label>
            <input type="text" name="search" placeholder="Ej: RES-..., Juan, correo@..." value="<?= htmlspecialchars($search) ?>">
        </div>

        <div class="filter-field">
            <label><i class="fa-solid fa-flag"></i> Estado</label>
            <select name="estado">
                <option value="">Todos los estados</option>
                <option value="pendiente" <?= $filterEstado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                <option value="confirmada" <?= $filterEstado === 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
                <option value="pagada" <?= $filterEstado === 'pagada' ? 'selected' : '' ?>>Pagada</option>
                <option value="cancelada" <?= $filterEstado === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                <option value="completada" <?= $filterEstado === 'completada' ? 'selected' : '' ?>>Completada</option>
            </select>
        </div>

        <div>
            <button type="submit" class="btn-filter-submit"><i class="fa-solid fa-filter"></i> Filtrar</button>
        </div>

        <?php if (!empty($search) || !empty($filterEstado)): ?>
        <div>
            <a href="reservas.php" class="btn-filter-clear"><i class="fa-solid fa-rotate-left"></i> Limpiar</a>
        </div>
        <?php endif; ?>
    </form>
</div>

<div class="admin-panel">
    <div class="admin-panel-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="admin-panel-title"><i class="fa-solid fa-calendar-check"></i> Listado de Reservas</h3>
        <p style="margin: 0; font-size: 0.95rem; color: #475569;">Total mostradas: <strong><?= count($reservas) ?></strong></p>
    </div>
    <div class="admin-panel-body">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Contacto</th>
                        <th>Paquete</th>
                        <th>Salida</th>
                        <th>Viajeros</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $reserva): ?>
                        <tr>
                            <td><strong style="color: #0f2a4a;"><?= htmlspecialchars($reserva['numero_reserva']) ?></strong></td>
                            <td><?= htmlspecialchars($reserva['nombre_cliente']) ?></td>
                            <td>
                                <div><small><i class="fa-solid fa-envelope" style="color: #94a3b8;"></i> <?= htmlspecialchars($reserva['email_cliente']) ?></small></div>
                                <div><small><i class="fa-solid fa-phone" style="color: #94a3b8;"></i> <?= htmlspecialchars($reserva['telefono'] ?? '-') ?></small></div>
                            </td>
                            <td><?= htmlspecialchars($reserva['paquete_name'] ?? 'Paquete no encontrado') ?></td>
                            <td><?= htmlspecialchars($reserva['fecha_salida']) ?></td>
                            <td style="text-align: center;"><?= htmlspecialchars($reserva['cantidad_personas']) ?></td>
                            <td><strong>$<?= number_format($reserva['total'], 2) ?></strong></td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($reserva['estado']) ?>">
                                    <?= htmlspecialchars(ucfirst($reserva['estado'])) ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions-cell-flex">
                                    <!-- Ver detalle -->
                                    <button type="button" class="btn-action btn-view" title="Ver detalle completo" onclick='verDetalleReserva(<?= json_encode($reserva, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                        <i class="fa-solid fa-eye"></i>
                                    </button>

                                    <!-- Cambiar estado -->
                                    <form method="post" style="display: inline; margin: 0;">
                                        <input type="hidden" name="reserva_id" value="<?= htmlspecialchars($reserva['id']) ?>">
                                        <select name="nuevo_estado" onchange="this.form.submit()" style="padding: 5px 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.85rem;">
                                            <option value="<?= htmlspecialchars($reserva['estado']) ?>">Estado...</option>
                                            <option value="pendiente">Pendiente</option>
                                            <option value="confirmada">Confirmada</option>
                                            <option value="pagada">Pagada</option>
                                            <option value="cancelada">Cancelada</option>
                                            <option value="completada">Completada</option>
                                        </select>
                                    </form>

                                    <!-- Eliminar reserva -->
                                    <a href="reservas.php?delete=<?= htmlspecialchars($reserva['id']) ?>" class="btn-action btn-delete" title="Eliminar reserva" onclick="return confirm('¿Seguro que deseas eliminar la reserva <?= htmlspecialchars($reserva['numero_reserva']) ?>?');">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($reservas)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 30px; color: #64748b;">
                                <i class="fa-solid fa-calendar-xmark" style="font-size: 2rem; display: block; margin-bottom: 10px; color: #cbd5e1;"></i>
                                No se encontraron reservas con los filtros aplicados.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detalle de Reserva -->
<div id="reservaModal" class="modal-overlay">
    <div class="modal-container" style="max-width: 600px;">
        <button type="button" class="modal-close" onclick="cerrarDetalleReserva()">&times;</button>
        <div class="modal-header">
            <h2 id="modalReservaCodigo">Detalle de Reserva</h2>
            <p>Información completa del cliente y su itinerario.</p>
        </div>
        <div style="padding: 10px 0;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.95rem;">
                <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 10px; font-weight: 600; color: #64748b;">Cliente:</td><td id="modalReservaCliente" style="padding: 10px; font-weight: 700; color: #0f2a4a;"></td></tr>
                <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 10px; font-weight: 600; color: #64748b;">Correo:</td><td id="modalReservaEmail" style="padding: 10px;"></td></tr>
                <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 10px; font-weight: 600; color: #64748b;">Teléfono:</td><td id="modalReservaTelefono" style="padding: 10px;"></td></tr>
                <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 10px; font-weight: 600; color: #64748b;">Paquete:</td><td id="modalReservaPaquete" style="padding: 10px; font-weight: 600;"></td></tr>
                <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 10px; font-weight: 600; color: #64748b;">Fecha Salida:</td><td id="modalReservaFecha" style="padding: 10px;"></td></tr>
                <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 10px; font-weight: 600; color: #64748b;">Viajeros:</td><td id="modalReservaPersonas" style="padding: 10px;"></td></tr>
                <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 10px; font-weight: 600; color: #64748b;">Monto Total:</td><td id="modalReservaTotal" style="padding: 10px; font-size: 1.15rem; font-weight: 800; color: #0f2a4a;"></td></tr>
                <tr style="border-bottom: 1px solid #f1f5f9;"><td style="padding: 10px; font-weight: 600; color: #64748b;">Estado Reserva:</td><td id="modalReservaEstado" style="padding: 10px;"></td></tr>
                <tr><td style="padding: 10px; font-weight: 600; color: #64748b;">Fecha de Registro:</td><td id="modalReservaFechaReg" style="padding: 10px;"></td></tr>
            </table>

            <div style="display: flex; gap: 10px; margin-top: 25px; justify-content: flex-end; flex-wrap: wrap;">
                <a id="btnWhatsAppModal" href="#" target="_blank" class="btn-add" style="background: #25D366; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-brands fa-whatsapp"></i> Contactar por WhatsApp
                </a>
                <a id="btnVerBoletaModal" href="#" target="_blank" class="btn-filter-clear" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px; background: #f8fafc; color: #0f2a4a; border: 1.5px solid #cbd5e1; font-weight: 600; padding: 10px 16px; border-radius: 8px;">
                    <i class="fa-solid fa-file-invoice"></i> Ver Boleta
                </a>
                <button type="button" class="btn-filter-clear" onclick="window.print()" style="background: #0f2a4a; color: #fff; border: 1.5px solid #0f2a4a; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; border-radius: 8px; cursor: pointer;">
                    <i class="fa-solid fa-print"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function verDetalleReserva(reserva) {
    // 1. Llenar Modal en Pantalla
    document.getElementById('modalReservaCodigo').textContent = 'Reserva ' + reserva.numero_reserva;
    document.getElementById('modalReservaCliente').textContent = reserva.nombre_cliente;
    document.getElementById('modalReservaEmail').innerHTML = '<a href="mailto:' + reserva.email_cliente + '">' + reserva.email_cliente + '</a>';
    document.getElementById('modalReservaTelefono').textContent = reserva.telefono || 'No registrado';
    document.getElementById('modalReservaPaquete').textContent = reserva.paquete_name || 'Paquete Turístico';
    document.getElementById('modalReservaFecha').textContent = reserva.fecha_salida;
    document.getElementById('modalReservaPersonas').textContent = reserva.cantidad_personas + ' persona(s)';
    document.getElementById('modalReservaTotal').textContent = '$' + parseFloat(reserva.total).toFixed(2) + ' USD';
    document.getElementById('modalReservaEstado').innerHTML = '<span class="badge badge-' + reserva.estado + '">' + reserva.estado.toUpperCase() + '</span>';
    document.getElementById('modalReservaFechaReg').textContent = reserva.created_at;

    const phoneClean = (reserva.telefono || '').replace(/[^0-9]/g, '');
    const waUrl = 'https://wa.me/' + phoneClean + '?text=' + encodeURIComponent('Hola ' + reserva.nombre_cliente + ', te contactamos de Turismo Inkaiko respecto a tu reserva ' + reserva.numero_reserva);
    document.getElementById('btnWhatsAppModal').href = waUrl;

    const btnVerBoleta = document.getElementById('btnVerBoletaModal');
    if (btnVerBoleta) {
        btnVerBoleta.href = '../boleta.php?id=' + reserva.id;
    }

    // 2. Llenar Recibo de Impresión Oficial (#reciboImpresion)
    const personas = Math.max(1, parseInt(reserva.cantidad_personas) || 1);
    const total = parseFloat(reserva.total) || 0;
    const unitario = total / personas;
    const base = total / 1.18;
    const igv = total - base;

    document.getElementById('printReservaNumero').textContent = reserva.numero_reserva;
    document.getElementById('printReservaCliente').textContent = reserva.nombre_cliente;
    document.getElementById('printReservaEmail').textContent = reserva.email_cliente;
    document.getElementById('printReservaTelefono').textContent = reserva.telefono || 'No registrado';
    document.getElementById('printReservaEstado').textContent = (reserva.estado || 'CONFIRMADA').toUpperCase();
    document.getElementById('printReservaPago').textContent = (reserva.payment_status || 'PENDIENTE').toUpperCase();
    document.getElementById('printReservaFechaReg').textContent = reserva.created_at || '';

    document.getElementById('printReservaPaquete').textContent = reserva.paquete_name || 'Tour Inkaiko';
    document.getElementById('printTablePaquete').textContent = reserva.paquete_name || 'Tour Inkaiko';
    document.getElementById('printReservaFechaSalida').textContent = reserva.fecha_salida;
    document.getElementById('printReservaPersonas').textContent = personas + ' persona(s)';
    document.getElementById('printTablePersonas').textContent = personas + ' pasaje(s)';
    document.getElementById('printTableUnitario').textContent = '$' + unitario.toFixed(2) + ' USD';
    document.getElementById('printTableTotal').textContent = '$' + total.toFixed(2) + ' USD';

    document.getElementById('printTotalBase').textContent = '$' + base.toFixed(2) + ' USD';
    document.getElementById('printTotalIgv').textContent = '$' + igv.toFixed(2) + ' USD';
    document.getElementById('printTotalFinal').textContent = '$' + total.toFixed(2) + ' USD';

    const qrData = encodeURIComponent('TURISMO INKAIKO - RESERVA: ' + reserva.numero_reserva + ' | CLIENTE: ' + reserva.nombre_cliente + ' | SALIDA: ' + reserva.fecha_salida + ' | TOTAL: $' + total.toFixed(2) + ' USD');
    document.getElementById('printQrCode').src = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' + qrData;

    document.getElementById('reservaModal').classList.add('active');
}

function cerrarDetalleReserva() {
    document.getElementById('reservaModal').classList.remove('active');
}

// Cerrar con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarDetalleReserva();
    }
});

// Cerrar al hacer clic en el fondo oscuro
const modalElement = document.getElementById('reservaModal');
if (modalElement) {
    modalElement.addEventListener('click', function(e) {
        if (e.target === modalElement) {
            cerrarDetalleReserva();
        }
    });
}
</script>


<!-- Plantilla Oculta de Recibo / Boleta para Impresión -->
<div id="reciboImpresion">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f2a4a; padding-bottom: 15px; margin-bottom: 20px;">
        <div>
            <img src="../assets/images/logo.png" alt="Turismo Inkaiko" style="height: 50px; margin-bottom: 8px; display: block;" onerror="this.style.display='none'">
            <h2 style="font-size: 18px; color: #0f2a4a; margin: 0 0 4px; text-transform: uppercase;">Turismo Inkaiko S.A.C.</h2>
            <div style="font-size: 12px; color: #475569; line-height: 1.5;">
                <strong>RUC:</strong> 20608549210<br>
                <strong>Dirección:</strong> Portal de Panes 123, Plaza de Armas, Cusco - Perú<br>
                <strong>Central WhatsApp:</strong> +51 989 003 025 | <strong>Email:</strong> reservas@turismoinkaiko.com
            </div>
        </div>
        <div style="width: 270px; border: 2.5px solid #0f2a4a; border-radius: 8px; text-align: center; padding: 12px; background: #fafcff;">
            <div style="font-size: 14px; font-weight: 700; color: #0f2a4a;">R.U.C. 20608549210</div>
            <div style="background: #0f2a4a; color: #fff; font-size: 12px; font-weight: 700; padding: 6px; margin: 8px -12px; text-transform: uppercase;">RECIBO / BOLETA DE RESERVA</div>
            <div id="printReservaNumero" style="font-size: 16px; font-weight: 800; color: #b45309; letter-spacing: 1px;"></div>
        </div>
    </div>

    <!-- 1. Datos del Pasajero -->
    <div style="background: #f8fafc; border-left: 4px solid #d49a00; padding: 6px 12px; font-weight: 700; color: #0f2a4a; font-size: 13px; text-transform: uppercase; margin-bottom: 12px;">
        1. Información del Pasajero / Titular
    </div>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px; font-size: 12.5px; margin-bottom: 20px;">
        <div><strong style="color: #64748b;">Cliente / Pasajero:</strong> <span id="printReservaCliente" style="font-weight: 700; color: #0f2a4a;"></span></div>
        <div><strong style="color: #64748b;">Fecha Emisión:</strong> <span id="printReservaFechaReg"></span></div>
        <div><strong style="color: #64748b;">Correo Electrónico:</strong> <span id="printReservaEmail"></span></div>
        <div><strong style="color: #64748b;">Estado Reserva:</strong> <span id="printReservaEstado" style="font-weight: 700; color: #15803d; text-transform: uppercase;"></span></div>
        <div><strong style="color: #64748b;">Teléfono de Contacto:</strong> <span id="printReservaTelefono"></span></div>
        <div><strong style="color: #64748b;">Estado de Pago:</strong> <span id="printReservaPago" style="font-weight: 700; text-transform: uppercase;"></span></div>
    </div>

    <!-- 2. Servicio Turístico -->
    <div style="background: #f8fafc; border-left: 4px solid #d49a00; padding: 6px 12px; font-weight: 700; color: #0f2a4a; font-size: 13px; text-transform: uppercase; margin-bottom: 12px;">
        2. Servicio Turístico e Itinerario Contratado
    </div>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px; font-size: 12.5px; margin-bottom: 20px;">
        <div><strong style="color: #64748b;">Paquete / Tour:</strong> <span id="printReservaPaquete" style="font-weight: 700; color: #0f2a4a;"></span></div>
        <div><strong style="color: #64748b;">Fecha de Salida:</strong> <span id="printReservaFechaSalida" style="font-weight: 700; color: #b45309;"></span></div>
        <div><strong style="color: #64748b;">Cantidad de Personas:</strong> <span id="printReservaPersonas" style="font-weight: 600;"></span></div>
        <div><strong style="color: #64748b;">Modalidad:</strong> <span>Servicio Turístico Guiado Oficial</span></div>
    </div>

    <!-- 3. Tabla Desglose -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12.5px;">
        <thead>
            <tr style="background: #0f2a4a; color: #ffffff; text-transform: uppercase; font-size: 11.5px;">
                <th style="padding: 8px 10px; text-align: center; width: 40px;">#</th>
                <th style="padding: 8px 10px; text-align: left;">Descripción del Paquete</th>
                <th style="padding: 8px 10px; text-align: center; width: 90px;">Pasajeros</th>
                <th style="padding: 8px 10px; text-align: right; width: 110px;">P. Unitario</th>
                <th style="padding: 8px 10px; text-align: right; width: 110px;">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-bottom: 1px solid #cbd5e1;">
                <td style="padding: 10px; text-align: center; font-weight: 700;">1</td>
                <td style="padding: 10px;">
                    <strong id="printTablePaquete" style="color: #0f2a4a;"></strong>
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                        Incluye transporte turístico oficial, guiado certificado y tickets de acceso al circuito programado.
                    </div>
                </td>
                <td id="printTablePersonas" style="padding: 10px; text-align: center; font-weight: 600;"></td>
                <td id="printTableUnitario" style="padding: 10px; text-align: right;"></td>
                <td id="printTableTotal" style="padding: 10px; text-align: right; font-weight: 800; color: #0f2a4a;"></td>
            </tr>
        </tbody>
    </table>

    <!-- Totales -->
    <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
        <table style="width: 280px; border-collapse: collapse; font-size: 13px;">
            <tr>
                <td style="padding: 4px 8px; color: #64748b; font-weight: 600;">Base Imponible:</td>
                <td id="printTotalBase" style="padding: 4px 8px; text-align: right; font-weight: 600;"></td>
            </tr>
            <tr>
                <td style="padding: 4px 8px; color: #64748b; font-weight: 600;">IGV (18% Incluido):</td>
                <td id="printTotalIgv" style="padding: 4px 8px; text-align: right; font-weight: 600;"></td>
            </tr>
            <tr style="border-top: 2px solid #0f2a4a; background: #f8fafc; font-size: 15px; font-weight: 800; color: #0f2a4a;">
                <td style="padding: 8px;">IMPORTE TOTAL:</td>
                <td id="printTotalFinal" style="padding: 8px; text-align: right;"></td>
            </tr>
        </table>
    </div>

    <!-- Términos y Recomendaciones -->
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 15px; margin-bottom: 20px; font-size: 11px; color: #64748b; line-height: 1.5;">
        <strong style="color: #0f2a4a; text-transform: uppercase; display: block; margin-bottom: 5px;">Términos y Condiciones del Recibo de Reserva:</strong>
        <ul style="margin: 0; padding-left: 18px;">
            <li>Presentar este recibo / voucher impreso o digital en su smartphone al momento de iniciar el servicio turístico.</li>
            <li>Todo pasajero debe portar su Documento Nacional de Identidad (DNI) o Pasaporte original y vigente.</li>
            <li>Estar en el punto de encuentro con <strong>20 minutos de anticipación</strong> a la hora coordinada.</li>
        </ul>
    </div>

    <!-- Pie: QR y Firma/Sello -->
    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #cbd5e1; padding-top: 15px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <img id="printQrCode" src="" alt="QR" style="width: 80px; height: 80px; border: 1px solid #cbd5e1; padding: 2px; border-radius: 4px; background: #fff;">
            <div style="font-size: 10.5px; color: #64748b; line-height: 1.4;">
                <strong>Comprobante Electrónico Válido</strong><br>
                Turismo Inkaiko S.A.C.<br>
                Verificación oficial de reserva
            </div>
        </div>
        <div style="text-align: center; min-width: 200px;">
            <div style="width: 160px; height: 1px; background: #64748b; margin: 30px auto 4px;"></div>
            <div style="font-size: 11px; font-weight: 700; color: #0f2a4a;">TURISMO INKAIKO S.A.C.</div>
            <div style="font-size: 10px; color: #059669; font-weight: 600;">✓ RESERVA CONFIRMADA</div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
