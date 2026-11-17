<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = $pageTitle ?? 'Panel de Control';
$userName = $_SESSION['user_name'] ?? 'Administrador';
$userInitials = strtoupper(substr($userName, 0, 1));
$currentFile = basename($_SERVER['PHP_SELF']);

// Obtener notificaciones no leídas
$pdo = get_db_connection();
$stmt = $pdo->prepare('SELECT COUNT(*) as unread FROM notificaciones WHERE usuario_id = ? AND leida = 0');
$stmt->execute([$_SESSION['user_id']]);
$unreadNotifs = $stmt->fetch()['unread'] ?? 0;

// Obtener últimas 5 notificaciones
$stmt = $pdo->prepare('SELECT * FROM notificaciones WHERE usuario_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$_SESSION['user_id']]);
$recentNotifs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | Admin Turismo Inkaiko</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome para Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos específicos del Admin -->
    <link rel="stylesheet" href="../assets/css/admin-style.css?v=<?= time() ?>">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <a href="dashboard.php" class="sidebar-brand">
            <img src="../assets/images/logo.png?v=<?= time() ?>" alt="Turismo Inkaiko" style="max-height: 40px; margin: 0 10px;">
        </a>
        
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <?= htmlspecialchars($userInitials) ?>
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?= htmlspecialchars($userName) ?></span>
                <span class="sidebar-user-role">Administrador</span>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li>
                <a href="dashboard.php" class="<?= $currentFile === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-gauge-high"></i> <span class="sidebar-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="tours.php" class="<?= $currentFile === 'tours.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-map-location-dot"></i> <span class="sidebar-text">Paquetes (Tours)</span>
                </a>
            </li>
            <li>
                <a href="destinos.php" class="<?= $currentFile === 'destinos.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-earth-americas"></i> <span class="sidebar-text">Destinos</span>
                </a>
            </li>
            <li>
                <a href="reservas.php" class="<?= $currentFile === 'reservas.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-calendar-check"></i> <span class="sidebar-text">Reservas</span>
                </a>
            </li>
            <li>
                <a href="clientes.php" class="<?= $currentFile === 'clientes.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users"></i> <span class="sidebar-text">Clientes</span>
                </a>
            </li>
            <li>
                <a href="leads.php" class="<?= $currentFile === 'leads.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-bullhorn"></i> <span class="sidebar-text">Leads</span>
                </a>
            </li>
            <li>
                <a href="multimedia.php" class="<?= $currentFile === 'multimedia.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-images"></i> <span class="sidebar-text">Multimedia</span>
                </a>
            </li>
            <li>
                <a href="configuracion.php" class="<?= $currentFile === 'configuracion.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-gear"></i> <span class="sidebar-text">Configuración</span>
                </a>
            </li>
            <li>
                <a href="../logout.php">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> <span class="sidebar-text">Cerrar Sesión</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="btn-toggle-sidebar" id="toggleSidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
            <div class="topbar-right" style="display: flex; align-items: center; gap: 15px;">
                <div class="notification-dropdown" style="position: relative;">
                    <button id="bellBtn" style="background: none; border: none; font-size: 1.2rem; color: #666; cursor: pointer; position: relative;">
                        <i class="fa-solid fa-bell"></i>
                        <?php if ($unreadNotifs > 0): ?>
                            <span style="position: absolute; top: -5px; right: -8px; background: #e74c3c; color: white; border-radius: 50%; font-size: 0.7rem; padding: 2px 6px; font-weight: bold;"><?= $unreadNotifs ?></span>
                        <?php endif; ?>
                    </button>
                    <div id="notifPanel" style="display: none; position: absolute; top: 40px; right: 0; width: 320px; background: white; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: 8px; z-index: 1000; text-align: left; border: 1px solid #eaeaea;">
                        <div style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                            <strong style="color: var(--admin-card-blue); font-size: 0.95rem;">Notificaciones</strong>
                            <?php if ($unreadNotifs > 0): ?>
                                <button onclick="markAllAsRead()" style="background: none; border: none; color: #3498db; cursor: pointer; font-size: 0.8rem; font-weight: 500;">Marcar leídas</button>
                            <?php endif; ?>
                        </div>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php if (empty($recentNotifs)): ?>
                                <div style="padding: 20px; text-align: center; color: #888; font-size: 0.9rem;">No tienes notificaciones</div>
                            <?php else: ?>
                                <?php foreach ($recentNotifs as $n): ?>
                                    <div style="padding: 12px 15px; border-bottom: 1px solid #f5f5f5; background: <?= $n['leida'] ? '#fff' : '#f4faff' ?>;">
                                        <h5 style="margin: 0 0 5px; font-size: 0.85rem; color: #333;"><i class="fa-solid <?= $n['leida'] ? 'fa-envelope-open' : 'fa-envelope' ?>" style="color: <?= $n['leida'] ? '#aaa' : '#3498db' ?>; margin-right: 5px;"></i> <?= htmlspecialchars($n['titulo']) ?></h5>
                                        <p style="margin: 0; font-size: 0.8rem; color: #666;"><?= htmlspecialchars($n['mensaje']) ?></p>
                                        <small style="color: #aaa; font-size: 0.7rem; margin-top: 5px; display: block;"><?= htmlspecialchars($n['created_at']) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div style="padding: 10px; text-align: center; border-top: 1px solid #eee; background: #fafafa; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
                            <a href="reservas.php" style="color: var(--admin-card-blue); font-size: 0.85rem; text-decoration: none; font-weight: 500;">Ir a panel de reservas <i class="fa-solid fa-arrow-right" style="margin-left: 5px; font-size: 0.8rem;"></i></a>
                        </div>
                    </div>
                </div>
                <a href="../index.php" target="_blank" class="btn-logout" style="color: var(--admin-card-blue);">
                    <i class="fa-solid fa-globe"></i> Ver sitio web
                </a>
                <a href="../logout.php" class="btn-logout">
                    <i class="fa-solid fa-power-off"></i> Salir
                </a>
            </div>
        </header>

        <!-- Content Area -->
        <main class="admin-content">
            <div class="content-header">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </div>

<script>
document.getElementById('bellBtn').addEventListener('click', function(e) {
    e.stopPropagation();
    const panel = document.getElementById('notifPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
});

window.addEventListener('click', function(e) {
    if (!e.target.closest('.notification-dropdown')) {
        document.getElementById('notifPanel').style.display = 'none';
    }
});

function markAllAsRead() {
    fetch('ajax/mark_notifications_read.php', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(err => console.error('Error al marcar notificaciones:', err));
}
</script>
