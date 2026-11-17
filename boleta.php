<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/functions.php';

$pdo = get_db_connection();

$reservaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';

if ($reservaId <= 0 && empty($codigo)) {
    die("Error: Se requiere un ID o código de reserva válido.");
}

// Buscar reserva
if ($reservaId > 0) {
    $stmt = $pdo->prepare("
        SELECT r.*, p.name AS paquete_nombre, p.destino, p.duration, p.price AS precio_base_paquete, p.img AS paquete_img
        FROM reservas r
        LEFT JOIN paquetes p ON r.paquete_id = p.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reservaId]);
} else {
    $stmt = $pdo->prepare("
        SELECT r.*, p.name AS paquete_nombre, p.destino, p.duration, p.price AS precio_base_paquete, p.img AS paquete_img
        FROM reservas r
        LEFT JOIN paquetes p ON r.paquete_id = p.id
        WHERE r.numero_reserva = ?
    ");
    $stmt->execute([$codigo]);
}

$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reserva) {
    die("Reserva no encontrada.");
}

// Control de acceso: permitido para administradores, o el cliente dueño de la reserva
$currentUserId = $_SESSION['user_id'] ?? 0;
$currentUserRole = $_SESSION['user_role'] ?? '';
$currentUserEmail = $_SESSION['user_email'] ?? '';

$esAdmin = ($currentUserRole === 'admin');
$esDuenio = ($currentUserId > 0 && (int)$reserva['usuario_id'] === (int)$currentUserId) || 
            (!empty($currentUserEmail) && strtolower($currentUserEmail) === strtolower($reserva['email_cliente']));

// Si no es admin ni dueño, requerir login
if (!$esAdmin && !$esDuenio) {
    // Si no hay sesión, redirigir a login
    if (!$currentUserId) {
        header("Location: login.php?redirect_to=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    } else {
        die("Acceso no autorizado para visualizar esta boleta de reserva.");
    }
}

$numReserva = $reserva['numero_reserva'];
$clienteNombre = $reserva['nombre_cliente'];
$clienteEmail = $reserva['email_cliente'];
$clienteTelefono = $reserva['telefono'] ?: 'No registrado';
$paqueteNombre = $reserva['paquete_nombre'] ?: 'Tour Inkaiko Exclusivo';
$paqueteDestino = $reserva['destino'] ?: 'Perú';
$paqueteDuracion = $reserva['duration'] ?: 'Full Day';
$fechaSalida = date('d/m/Y', strtotime($reserva['fecha_salida']));
$cantidadPersonas = max(1, (int)$reserva['cantidad_personas']);
$totalMonto = (float)$reserva['total'];
$precioUnitario = $totalMonto / $cantidadPersonas;
$estadoReserva = strtoupper($reserva['estado']);
$estadoPago = strtoupper($reserva['payment_status'] ?: 'PENDIENTE');
$fechaEmision = !empty($reserva['created_at']) ? date('d/m/Y H:i', strtotime($reserva['created_at'])) : date('d/m/Y H:i');

$autoPrint = isset($_GET['print']) || isset($_GET['autoprint']);
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=" . urlencode("TURISMO INKAIKO - RESERVA: $numReserva | CLIENTE: $clienteNombre | SALIDA: $fechaSalida | TOTAL: $" . number_format($totalMonto, 2) . " USD");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleta de Reserva <?= htmlspecialchars($numReserva) ?> - Turismo Inkaiko</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            padding: 30px 15px;
            font-size: 13px;
            line-height: 1.5;
        }

        /* Barra Superior No Imprimible */
        .no-print-toolbar {
            max-width: 820px;
            margin: 0 auto 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 14px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        .btn-toolbar {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
        }
        .btn-toolbar.print {
            background: #0f2a4a;
            color: #ffffff;
        }
        .btn-toolbar.print:hover {
            background: #d49a00;
            color: #0f2a4a;
        }
        .btn-toolbar.back {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .btn-toolbar.back:hover {
            background: #e2e8f0;
        }

        /* Contenedor Hoja A4 Boleta */
        .boleta-paper {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.07);
            border: 1px solid #e2e8f0;
            position: relative;
        }

        /* Cabecera Principal */
        .boleta-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            padding-bottom: 25px;
            border-bottom: 2px solid #0f2a4a;
            margin-bottom: 25px;
        }
        .company-info {
            flex: 1;
        }
        .company-logo {
            max-height: 55px;
            width: auto;
            margin-bottom: 12px;
            display: block;
        }
        .company-name {
            font-size: 18px;
            font-weight: 800;
            color: #0f2a4a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .company-details {
            font-size: 12px;
            color: #64748b;
            line-height: 1.6;
        }

        /* Recuadro RUC / Comprobante Oficial */
        .ruc-box {
            width: 290px;
            border: 2.5px solid #0f2a4a;
            border-radius: 10px;
            text-align: center;
            background: #fafcff;
            padding: 16px 12px;
            box-shadow: 0 4px 10px rgba(15, 42, 74, 0.05);
        }
        .ruc-box-number {
            font-size: 15px;
            font-weight: 700;
            color: #0f2a4a;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        .ruc-box-title {
            background: #0f2a4a;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            padding: 6px 10px;
            margin: 6px -12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .ruc-box-code {
            font-size: 16px;
            font-weight: 800;
            color: #b45309;
            margin-top: 8px;
            letter-spacing: 1px;
        }

        /* Secciones de Datos */
        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: #0f2a4a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #f8fafc;
            padding: 7px 12px;
            border-left: 4px solid #d49a00;
            margin-bottom: 14px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 24px;
            margin-bottom: 25px;
            font-size: 12.5px;
        }
        .info-item {
            display: flex;
        }
        .info-label {
            width: 140px;
            font-weight: 600;
            color: #64748b;
        }
        .info-value {
            flex: 1;
            font-weight: 600;
            color: #0f2a4a;
        }

        /* Badges de Estado */
        .badge-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-confirmada, .badge-pagada, .badge-paid {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .badge-pendiente, .badge-pending {
            background: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .badge-cancelada {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        /* Tabla de Liquidación */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 12.5px;
        }
        .invoice-table th {
            background: #0f2a4a;
            color: #ffffff;
            font-weight: 600;
            padding: 10px 12px;
            text-align: left;
            text-transform: uppercase;
            font-size: 11.5px;
            letter-spacing: 0.5px;
        }
        .invoice-table th:last-child,
        .invoice-table td:last-child {
            text-align: right;
        }
        .invoice-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        .invoice-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        /* Totales */
        .totals-wrapper {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 25px;
        }
        .totals-table {
            width: 320px;
            border-collapse: collapse;
            font-size: 13px;
        }
        .totals-table td {
            padding: 6px 12px;
        }
        .totals-table tr.total-row td {
            background: #f8fafc;
            border-top: 2px solid #0f2a4a;
            font-size: 16px;
            font-weight: 800;
            color: #0f2a4a;
            padding: 10px 12px;
        }

        /* Políticas y Recomendaciones */
        .terms-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px 18px;
            margin-bottom: 25px;
        }
        .terms-box h4 {
            font-size: 12px;
            font-weight: 700;
            color: #0f2a4a;
            text-transform: uppercase;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .terms-box ol {
            padding-left: 20px;
            font-size: 11px;
            color: #64748b;
            line-height: 1.6;
        }

        /* Pie de Página: QR y Firmas */
        .boleta-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px dashed #cbd5e1;
        }
        .qr-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .qr-image {
            width: 95px;
            height: 95px;
            border: 1px solid #cbd5e1;
            padding: 3px;
            border-radius: 6px;
            background: #fff;
        }
        .qr-text {
            font-size: 10.5px;
            color: #64748b;
            max-width: 220px;
            line-height: 1.5;
        }
        .stamp-section {
            text-align: center;
            min-width: 220px;
        }
        .stamp-line {
            width: 180px;
            height: 1px;
            background: #64748b;
            margin: 40px auto 6px;
        }
        .stamp-title {
            font-size: 11px;
            font-weight: 700;
            color: #0f2a4a;
            text-transform: uppercase;
        }
        .stamp-sub {
            font-size: 10px;
            color: #64748b;
        }

        /* Reglas de Impresión (@media print) */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print-toolbar {
                display: none !important;
            }
            .boleta-paper {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
                margin: 0 !important;
            }
            @page {
                size: A4 portrait;
                margin: 12mm 15mm;
            }
        }
    </style>
</head>
<body>

    <!-- Barra de Herramientas para Navegador (Oculta al Imprimir) -->
    <div class="no-print-toolbar">
        <div>
            <span style="font-weight: 700; color: #0f2a4a; font-size: 15px;">
                <i class="fa-solid fa-file-invoice" style="color: #d49a00;"></i> Comprobante Oficial de Reserva
            </span>
            <span style="color: #64748b; font-size: 13px; margin-left: 10px;">(Listo para imprimir o guardar en PDF)</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <?php if ($esAdmin): ?>
                <a href="admin/reservas.php" class="btn-toolbar back">
                    <i class="fa-solid fa-arrow-left"></i> Volver a Reservas
                </a>
            <?php else: ?>
                <a href="mi-cuenta.php" class="btn-toolbar back">
                    <i class="fa-solid fa-arrow-left"></i> Volver a Mi Cuenta
                </a>
            <?php endif; ?>
            
            <button type="button" class="btn-toolbar print" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Imprimir Boleta
            </button>
        </div>
    </div>

    <!-- Hoja de la Boleta -->
    <div class="boleta-paper">
        
        <!-- Encabezado con Datos de la Empresa y Cuadro SUNAT -->
        <div class="boleta-header">
            <div class="company-info">
                <img src="assets/images/logo.png" alt="Turismo Inkaiko" class="company-logo" onerror="this.style.display='none'">
                <div class="company-name">Turismo Inkaiko S.A.C.</div>
                <div class="company-details">
                    <strong>RUC:</strong> 20608549210<br>
                    <strong>Dirección:</strong> Portal de Panes 123, Plaza de Armas, Cusco - Perú<br>
                    <strong>Central Telefónica:</strong> +51 989 003 025 | +51 987 654 321<br>
                    <strong>Email:</strong> reservas@turismoinkaiko.com | <strong>Web:</strong> www.turismoinkaiko.com
                </div>
            </div>

            <div class="ruc-box">
                <div class="ruc-box-number">R.U.C. 20608549210</div>
                <div class="ruc-box-title">BOLETA DE RESERVA TURÍSTICA</div>
                <div class="ruc-box-code"><?= htmlspecialchars($numReserva) ?></div>
            </div>
        </div>

        <!-- Información del Pasajero / Cliente -->
        <div class="section-title">
            <i class="fa-solid fa-user-check"></i> Datos del Pasajero / Titular de Reserva
        </div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Señor(a):</span>
                <span class="info-value"><?= htmlspecialchars($clienteNombre) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Fecha de Emisión:</span>
                <span class="info-value"><?= htmlspecialchars($fechaEmision) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Correo Electrónico:</span>
                <span class="info-value"><?= htmlspecialchars($clienteEmail) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Estado Reserva:</span>
                <span class="info-value">
                    <span class="badge-status badge-<?= strtolower($estadoReserva) ?>">
                        <?= htmlspecialchars($estadoReserva) ?>
                    </span>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Teléfono / WhatsApp:</span>
                <span class="info-value"><?= htmlspecialchars($clienteTelefono) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Estado de Pago:</span>
                <span class="info-value">
                    <span class="badge-status badge-<?= strtolower($estadoPago) ?>">
                        <?= htmlspecialchars($estadoPago) ?>
                    </span>
                </span>
            </div>
        </div>

        <!-- Información del Servicio Turístico -->
        <div class="section-title">
            <i class="fa-solid fa-map-location-dot"></i> Itinerario y Servicio Turístico
        </div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Tour / Paquete:</span>
                <span class="info-value" style="font-size: 13.5px;"><?= htmlspecialchars($paqueteNombre) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Fecha de Salida:</span>
                <span class="info-value" style="color: #b45309; font-size: 13.5px;">
                    <i class="fa-regular fa-calendar-check"></i> <?= htmlspecialchars($fechaSalida) ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Destino Principal:</span>
                <span class="info-value"><?= htmlspecialchars($paqueteDestino) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Total Viajeros:</span>
                <span class="info-value"><?= htmlspecialchars($cantidadPersonas) ?> persona(s)</span>
            </div>
            <div class="info-item">
                <span class="info-label">Modalidad:</span>
                <span class="info-value">Servicio Turístico Guiado Exclusivo</span>
            </div>
            <div class="info-item">
                <span class="info-label">Moneda:</span>
                <span class="info-value">USD - Dólares Americanos</span>
            </div>
        </div>

        <!-- Tabla Desglose Económico -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 50px;">Item</th>
                    <th style="width: 100px; text-align: center;">Cantidad</th>
                    <th>Descripción del Paquete Turístico</th>
                    <th style="width: 120px; text-align: right;">P. Unitario</th>
                    <th style="width: 120px; text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: center; font-weight: 700;">1</td>
                    <td style="text-align: center; font-weight: 600;"><?= htmlspecialchars($cantidadPersonas) ?> pasaje(s)</td>
                    <td>
                        <strong style="color: #0f2a4a; font-size: 13px;"><?= htmlspecialchars($paqueteNombre) ?></strong>
                        <div style="font-size: 11px; color: #64748b; margin-top: 3px;">
                            Incluye traslados oficiales, guía profesional bilingüe certificado y tickets de acceso al circuito programado.
                        </div>
                    </td>
                    <td style="text-align: right;">$<?= number_format($precioUnitario, 2) ?> USD</td>
                    <td style="text-align: right; font-weight: 700; color: #0f2a4a;">$<?= number_format($totalMonto, 2) ?> USD</td>
                </tr>
            </tbody>
        </table>

        <!-- Totales -->
        <div class="totals-wrapper">
            <table class="totals-table">
                <tr>
                    <td style="color: #64748b; font-weight: 600;">Op. Gravada / Base:</td>
                    <td style="text-align: right; font-weight: 600;">$<?= number_format($totalMonto / 1.18, 2) ?> USD</td>
                </tr>
                <tr>
                    <td style="color: #64748b; font-weight: 600;">I.G.V. (18% Incluido):</td>
                    <td style="text-align: right; font-weight: 600;">$<?= number_format($totalMonto - ($totalMonto / 1.18), 2) ?> USD</td>
                </tr>
                <tr class="total-row">
                    <td>TOTAL GENERAL:</td>
                    <td style="text-align: right;">$<?= number_format($totalMonto, 2) ?> USD</td>
                </tr>
            </table>
        </div>

        <!-- Políticas y Términos Importantes (Voucher de Presentación) -->
        <div class="terms-box">
            <h4><i class="fa-solid fa-circle-exclamation" style="color: #d49a00;"></i> Términos y Recomendaciones para el Pasajero</h4>
            <ol>
                <li><strong>Presentación del Voucher:</strong> Este comprobante es su pase oficial de confirmación. Deberá presentarlo en formato físico o digital en su smartphone al momento del abordaje o encuentro con el guía.</li>
                <li><strong>Documentos de Identidad:</strong> Es requisito indispensable que todos los viajeros porten su Documento Nacional de Identidad (DNI) o Pasaporte original y vigente.</li>
                <li><strong>Puntualidad:</strong> Se solicita estar presente con <strong>20 minutos de anticipación</strong> a la hora fijada para la salida en el punto o recepción del hotel.</li>
                <li><strong>Cambios o Reprogramaciones:</strong> Cualquier solicitud de cambio debe comunicarse con un mínimo de 48 horas de anticipación a través de <em>reservas@turismoinkaiko.com</em> o a la central telefónica.</li>
            </ol>
        </div>

        <!-- Footer con QR y Sello de Operaciones -->
        <div class="boleta-footer">
            <div class="qr-section">
                <img src="<?= htmlspecialchars($qrUrl) ?>" alt="QR Verificación" class="qr-image" onerror="this.style.display='none'">
                <div class="qr-text">
                    <strong>Código de Verificación Electrónica:</strong><br>
                    <?= htmlspecialchars($numReserva) ?><br>
                    Comprobante emitido válidamente por el sistema web de Turismo Inkaiko S.A.C.
                </div>
            </div>

            <div class="stamp-section">
                <div class="stamp-line"></div>
                <div class="stamp-title">TURISMO INKAIKO S.A.C.</div>
                <div class="stamp-sub">Dpto. de Operaciones & Reservas</div>
                <div class="stamp-sub" style="color: #059669; font-weight: 600; margin-top: 4px;">
                    <i class="fa-solid fa-circle-check"></i> EMISIÓN CONFIRMADA
                </div>
            </div>
        </div>

    </div>

    <?php if ($autoPrint): ?>
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                window.print();
            }, 600);
        });
    </script>
    <?php endif; ?>

</body>
</html>
