<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Dashboard Admin';

// Obtener estadísticas
$pdo = get_db_connection();

// Total de usuarios
$totalUsers = $pdo->query('SELECT COUNT(*) as count FROM usuarios WHERE role = "cliente"')->fetch()['count'];

// Total de reservas
$totalReservas = $pdo->query('SELECT COUNT(*) as count FROM reservas')->fetch()['count'];

// Total ingresos
$totalIngresos = $pdo->query('SELECT SUM(total) as sum FROM reservas WHERE estado IN ("confirmada", "completada")')->fetch()['sum'] ?? 0;
$leadsCount = $pdo->query('SELECT COUNT(*) as count FROM leads')->fetch()['count'];
$trafficMetrics = getTrafficMetrics();

// Reservas pendientes
$reservasPendientes = $pdo->query('SELECT COUNT(*) as count FROM reservas WHERE estado = "pendiente"')->fetch()['count'];
$reservasConfirmadas = $pdo->query('SELECT COUNT(*) as count FROM reservas WHERE estado = "confirmada"')->fetch()['count'];
$reservasPagadas = $pdo->query('SELECT COUNT(*) as count FROM reservas WHERE estado = "pagada"')->fetch()['count'];
$reservasCanceladas = $pdo->query('SELECT COUNT(*) as count FROM reservas WHERE estado = "cancelada"')->fetch()['count'];

// Paquetes más populares
$paquetesPopulares = $pdo->query('SELECT id, name, popular FROM paquetes ORDER BY popular DESC LIMIT 5')->fetchAll();

// Últimas reservas
$ultimasReservas = $pdo->query('SELECT r.numero_reserva, r.nombre_cliente, p.name, r.total, r.estado, r.created_at FROM reservas r JOIN paquetes p ON r.paquete_id = p.id ORDER BY r.created_at DESC LIMIT 8')->fetchAll();

// Analytics avanzado
$leadsPorEstado = $pdo->query('SELECT estado, COUNT(*) as count FROM leads GROUP BY estado')->fetchAll(PDO::FETCH_KEY_PAIR);
$traficoPorDia = $pdo->query('SELECT DATE(created_at) as fecha, COUNT(*) as visitas FROM page_views WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY fecha')->fetchAll(PDO::FETCH_KEY_PAIR);
$leadsPorDia = $pdo->query('SELECT DATE(created_at) as fecha, COUNT(*) as count FROM leads WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY fecha')->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<?php include __DIR__ . '/includes/admin_header.php'; ?>

<!-- Info Boxes Row (Clickables) -->
<style>
.info-box-clickable {
    text-decoration: none;
    color: inherit;
    display: flex;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
}
.info-box-clickable:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}
</style>
<div class="info-box-container">
    <a href="clientes.php" class="info-box bg-blue info-box-clickable" title="Ver clientes y usuarios">
        <div class="info-box-icon"><i class="fa-solid fa-users"></i></div>
        <div class="info-box-content">
            <span class="info-box-text">Total Usuarios</span>
            <span class="info-box-number"><?= htmlspecialchars($totalUsers) ?></span>
        </div>
    </a>
    
    <a href="reservas.php?estado=confirmada" class="info-box bg-green info-box-clickable" title="Ver reservas confirmadas">
        <div class="info-box-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
        <div class="info-box-content">
            <span class="info-box-text">Ingresos Totales</span>
            <span class="info-box-number">$<?= number_format($totalIngresos, 2) ?></span>
        </div>
    </a>
    
    <a href="reservas.php" class="info-box bg-teal info-box-clickable" title="Gestionar todas las reservas">
        <div class="info-box-icon"><i class="fa-solid fa-calendar-check"></i></div>
        <div class="info-box-content">
            <span class="info-box-text">Reservas</span>
            <span class="info-box-number"><?= htmlspecialchars($totalReservas) ?></span>
        </div>
    </a>
    
    <a href="reservas.php?estado=pendiente" class="info-box bg-orange info-box-clickable" title="Ver reservas pendientes de atención">
        <div class="info-box-icon"><i class="fa-solid fa-clock"></i></div>
        <div class="info-box-content">
            <span class="info-box-text">Pendientes</span>
            <span class="info-box-number"><?= htmlspecialchars($reservasPendientes) ?></span>
        </div>
    </a>
</div>

<div class="admin-panel">
    <div class="admin-panel-header">
        <h3 class="admin-panel-title"><i class="fa-solid fa-chart-line"></i> Estadísticas Generales</h3>
    </div>
    <div class="admin-panel-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
            <div>
                <h4 style="margin-top:0;">Visitas totales</h4>
                <p style="font-size: 2.5rem; margin: 12px 0 0; color: var(--admin-card-blue); font-weight: bold;"><?= htmlspecialchars($trafficMetrics['totalViews']) ?></p>
            </div>
            <div>
                <h4 style="margin-top:0;">Secciones más populares</h4>
                <ul style="margin: 16px 0 0; padding-left: 20px; color: var(--admin-text-dark);">
                    <?php foreach ($trafficMetrics['topSections'] as $section): ?>
                        <li><strong><?= htmlspecialchars($section['section']) ?>:</strong> <?= htmlspecialchars($section['views']) ?> visitas</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 20px;">
    <!-- Gráfico de reservas -->
    <div class="admin-panel" style="margin-bottom: 0;">
        <div class="admin-panel-header">
            <h3 class="admin-panel-title"><i class="fa-solid fa-chart-pie"></i> Estado de reservas</h3>
        </div>
        <div class="admin-panel-body">
            <canvas id="reservasChart" style="max-height: 250px;"></canvas>
        </div>
    </div>

    <!-- Gráfico de tráfico semanal -->
    <div class="admin-panel" style="margin-bottom: 0;">
        <div class="admin-panel-header">
            <h3 class="admin-panel-title"><i class="fa-solid fa-chart-area"></i> Tráfico semanal</h3>
        </div>
        <div class="admin-panel-body">
            <canvas id="trafficChart" style="max-height: 250px;"></canvas>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 20px;">
    <!-- Gráfico de paquetes populares -->
    <div class="admin-panel" style="margin-bottom: 0;">
        <div class="admin-panel-header">
            <h3 class="admin-panel-title"><i class="fa-solid fa-chart-bar"></i> Paquetes más populares</h3>
        </div>
        <div class="admin-panel-body">
            <canvas id="popularChart" style="max-height: 250px;"></canvas>
        </div>
    </div>

    <!-- Gráfico de leads por estado -->
    <div class="admin-panel" style="margin-bottom: 0;">
        <div class="admin-panel-header">
            <h3 class="admin-panel-title"><i class="fa-solid fa-bullseye"></i> Leads por estado</h3>
        </div>
        <div class="admin-panel-body">
            <canvas id="leadsChart" style="max-height: 250px;"></canvas>
        </div>
    </div>
</div>

<!-- Últimas reservas -->
<div class="admin-panel">
    <div class="admin-panel-header">
        <h3 class="admin-panel-title"><i class="fa-solid fa-list-check"></i> Últimas reservas</h3>
    </div>
    <div class="admin-panel-body">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Cliente</th>
                        <th>Paquete</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimasReservas as $reserva): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($reserva['numero_reserva']) ?></strong></td>
                            <td><?= htmlspecialchars($reserva['nombre_cliente']) ?></td>
                            <td><?= htmlspecialchars($reserva['name']) ?></td>
                            <td>$<?= number_format($reserva['total'], 2) ?></td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($reserva['estado']) ?>">
                                    <?= htmlspecialchars(ucfirst($reserva['estado'])) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($reserva['created_at'])) ?></td>
                            <td>
                                <a href="reservas.php?view=<?= urlencode($reserva['numero_reserva']) ?>" class="btn-action btn-view" title="Ver reserva"><i class="fa-solid fa-eye"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de reservas
    const ctx = document.getElementById('reservasChart').getContext('2d');
    const reservasChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
                labels: ['Confirmadas', 'Pagadas', 'Pendientes', 'Canceladas'],
                datasets: [{
                    data: [
                        <?= htmlspecialchars($reservasConfirmadas) ?>,
                        <?= htmlspecialchars($reservasPagadas) ?>,
                        <?= htmlspecialchars($reservasPendientes) ?>,
                        <?= htmlspecialchars($reservasCanceladas) ?>
                    ],
                    backgroundColor: ['#00a65a', '#0073b7', '#f39c12', '#dd4b39'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });

    // Gráfico de paquetes populares
    const popularCtx = document.getElementById('popularChart').getContext('2d');
    const popularChart = new Chart(popularCtx, {
        type: 'bar',
        data: {
            labels: [<?= implode(', ', array_map(function ($item) { return json_encode($item['name'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); }, $paquetesPopulares)) ?>],
            datasets: [{
                label: 'Nivel de popularidad',
                data: [<?= implode(', ', array_map(function ($item) { return (int)$item['popular']; }, $paquetesPopulares)) ?>],
                backgroundColor: '#00c0ef',
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    // Gráfico de leads por estado
    const leadsCtx = document.getElementById('leadsChart').getContext('2d');
    const leadsChart = new Chart(leadsCtx, {
        type: 'pie',
        data: {
            labels: ['Interesado', 'Contactado', 'Perdido'],
            datasets: [{
                data: [
                    <?= htmlspecialchars($leadsPorEstado['interesado'] ?? 0) ?>,
                    <?= htmlspecialchars($leadsPorEstado['contactado'] ?? 0) ?>,
                    <?= htmlspecialchars($leadsPorEstado['perdido'] ?? 0) ?>
                ],
                backgroundColor: ['#f39c12', '#00a65a', '#dd4b39'],
                borderColor: '#ffffff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' }
            }
        }
    });

    // Gráfico de tráfico semanal
    const trafficCtx = document.getElementById('trafficChart').getContext('2d');
    const trafficChart = new Chart(trafficCtx, {
        type: 'line',
        data: {
            labels: [<?= implode(', ', array_map(function ($fecha) { return json_encode(date('d/m', strtotime($fecha)), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); }, array_keys($traficoPorDia))) ?>],
            datasets: [{
                label: 'Visitas por día',
                data: [<?= implode(', ', array_values($traficoPorDia)) ?>],
                borderColor: '#0073b7',
                backgroundColor: 'rgba(0, 115, 183, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>