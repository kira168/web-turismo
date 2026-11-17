<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/functions.php';

$userId = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? null;

// Si no hay sesión activa, redirigir a login
if (!$userId) {
    header("Location: login.php?redirect_to=mi-cuenta.php");
    exit();
}

$pageTitle = 'Mi Cuenta - Turismo Inkaiko';
$pdo = get_db_connection();
$error_message = '';
$success_message = '';

// Procesar actualización de datos del perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $nuevoNombre = trim($_POST['name'] ?? $_POST['nombre'] ?? '');
    $nuevoTelefono = trim($_POST['phone'] ?? $_POST['telefono'] ?? '');

    if (empty($nuevoNombre)) {
        $error_message = "El nombre no puede estar vacío.";
    } else {
        try {
            $stmtUpdate = $pdo->prepare("UPDATE usuarios SET name = ?, phone = ? WHERE id = ?");
            if ($stmtUpdate->execute([$nuevoNombre, $nuevoTelefono, $userId])) {
                $_SESSION['user_name']      = $nuevoNombre;
                $_SESSION['nombre']         = $nuevoNombre;
                $_SESSION['usuario_nombre'] = $nuevoNombre;
                $_SESSION['user_phone']     = $nuevoTelefono;
                $_SESSION['telefono']       = $nuevoTelefono;
                $success_message = "¡Tu perfil se ha actualizado correctamente!";
            } else {
                $error_message = "No se pudo actualizar el perfil. Intenta nuevamente.";
            }
        } catch (PDOException $e) {
            $error_message = "Error en el servidor al actualizar perfil: " . $e->getMessage();
        }
    }
}

// Cargar datos actualizados del usuario
try {
    $stmt = $pdo->prepare("SELECT id, name, email, phone, role, created_at FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
} catch (PDOException $e) {
    $error_message = "Error al cargar la información del perfil.";
    $userData = [
        'name' => $_SESSION['user_name'] ?? 'Cliente',
        'email' => $_SESSION['user_email'] ?? '',
        'phone' => $_SESSION['user_phone'] ?? '',
        'role' => 'cliente',
        'created_at' => date('Y-m-d H:i:s')
    ];
}

// Cargar reservas del usuario (por ID o por Email)
$userEmail = $userData['email'] ?? '';
try {
    $stmtRes = $pdo->prepare("
        SELECT r.*, p.name AS paquete_nombre, p.img AS paquete_img 
        FROM reservas r 
        LEFT JOIN paquetes p ON r.paquete_id = p.id 
        WHERE r.usuario_id = ? OR (r.email_cliente = ? AND ? != '')
        ORDER BY r.created_at DESC
    ");
    $stmtRes->execute([$userId, $userEmail, $userEmail]);
    $misReservas = $stmtRes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $misReservas = [];
}

include __DIR__ . '/includes/header.php';
?>

<style>
.account-wrapper {
    padding: 120px 20px 80px;
    background: #f8fafc;
    min-height: 85vh;
}
.account-container {
    max-width: 1150px;
    margin: 0 auto;
}
.account-header-hero {
    background: linear-gradient(135deg, #0f2a4a 0%, #1e3a5f 100%);
    border-radius: 16px;
    padding: 30px 35px;
    color: #ffffff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 35px;
    box-shadow: 0 8px 25px rgba(15, 42, 74, 0.15);
}
.account-user-badge {
    display: flex;
    align-items: center;
    gap: 18px;
}
.account-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #d49a00;
    color: #0f2a4a;
    font-size: 1.6rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid rgba(255,255,255,0.3);
}
.account-title-box h1 {
    font-size: 1.8rem;
    margin: 0 0 5px;
    font-weight: 800;
}
.account-title-box p {
    margin: 0;
    color: #cbd5e1;
    font-size: 0.95rem;
}
.account-grid {
    display: grid;
    grid-template-columns: 360px 1fr;
    gap: 30px;
}
@media (max-width: 900px) {
    .account-grid {
        grid-template-columns: 1fr;
    }
}
.account-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
}
.account-card h2 {
    font-size: 1.3rem;
    color: #0f2a4a;
    font-weight: 700;
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 10px;
}
.account-card h2 i {
    color: #d49a00;
}
.field-group {
    margin-bottom: 18px;
}
.field-group label {
    display: block;
    font-size: 0.88rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 6px;
}
.field-group input {
    width: 100%;
    padding: 11px 14px;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.95rem;
    box-sizing: border-box;
    outline: none;
    transition: border-color 0.2s;
}
.field-group input:focus {
    border-color: #d49a00;
    box-shadow: 0 0 0 3px rgba(212, 154, 0, 0.15);
}
.field-group input:disabled {
    background: #f1f5f9;
    color: #94a3b8;
    cursor: not-allowed;
}
.btn-save-account {
    width: 100%;
    background: #0f2a4a;
    color: #fff;
    border: none;
    padding: 12px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-save-account:hover {
    background: #d49a00;
    color: #0f2a4a;
}
.alert-box {
    padding: 14px 18px;
    border-radius: 8px;
    margin-bottom: 25px;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 10px;
}
.alert-box.success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}
.alert-box.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}
.reservas-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.92rem;
}
.reservas-table th {
    background: #f8fafc;
    color: #475569;
    font-weight: 600;
    text-align: left;
    padding: 12px 14px;
    border-bottom: 2px solid #e2e8f0;
}
.reservas-table td {
    padding: 14px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    vertical-align: middle;
}
.badge-status {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
}
.badge-status.pendiente { background: #fef3c7; color: #92400e; }
.badge-status.confirmada { background: #dcfce7; color: #166534; }
.badge-status.pagada { background: #e0e7ff; color: #3730a3; }
.badge-status.cancelada { background: #fee2e2; color: #991b1b; }
.badge-status.completada { background: #e2e8f0; color: #334155; }
</style>

<div class="account-wrapper">
    <div class="account-container">

        <!-- Banner de Encabezado -->
        <div class="account-header-hero">
            <div class="account-user-badge">
                <div class="account-avatar">
                    <?= strtoupper(substr($userData['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="account-title-box">
                    <h1><?= htmlspecialchars($userData['name'] ?? 'Mi Cuenta') ?></h1>
                    <p><i class="fa-solid fa-envelope" style="color: #d49a00;"></i> <?= htmlspecialchars($userData['email'] ?? '') ?> · Rol: <strong><?= ucfirst(htmlspecialchars($userData['role'] ?? 'Cliente')) ?></strong></p>
                </div>
            </div>

            <div style="display: flex; gap: 10px;">
                <?php if (($userData['role'] ?? '') === 'admin'): ?>
                    <a href="admin/dashboard.php" style="background: #d49a00; color: #0f2a4a; padding: 10px 18px; border-radius: 8px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-gauge-high"></i> Panel Admin
                    </a>
                <?php endif; ?>
                <a href="logout.php" style="background: rgba(255,255,255,0.15); color: #fff; padding: 10px 18px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-power-off"></i> Cerrar Sesión
                </a>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert-box success">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert-box error">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="account-grid">
            
            <!-- Columna Izquierda: Datos del Perfil -->
            <div class="account-card">
                <h2><i class="fa-solid fa-user-gear"></i> Datos del Perfil</h2>

                <form action="mi-cuenta.php" method="POST">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="field-group">
                        <label for="acc_name">Nombre Completo *</label>
                        <input type="text" id="acc_name" name="name" value="<?= htmlspecialchars($userData['name'] ?? '') ?>" required>
                    </div>

                    <div class="field-group">
                        <label for="acc_email">Correo Electrónico</label>
                        <input type="email" id="acc_email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" disabled title="El correo electrónico no puede ser modificado">
                        <small style="color: #94a3b8; display: block; margin-top: 4px;">Identificador permanente de tu cuenta.</small>
                    </div>

                    <div class="field-group">
                        <label for="acc_phone">Teléfono / WhatsApp</label>
                        <input type="tel" id="acc_phone" name="phone" value="<?= htmlspecialchars($userData['phone'] ?? '') ?>" placeholder="+51 987654321">
                    </div>

                    <div class="field-group">
                        <label>Fecha de Registro</label>
                        <p style="margin: 0; font-size: 0.95rem; color: #64748b;">
                            <?= !empty($userData['created_at']) ? date('d/m/Y H:i', strtotime($userData['created_at'])) : 'No disponible' ?>
                        </p>
                    </div>

                    <button type="submit" class="btn-save-account">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
                    </button>
                </form>
            </div>

            <!-- Columna Derecha: Mis Reservas -->
            <div class="account-card">
                <h2><i class="fa-solid fa-calendar-check"></i> Mis Reservas de Tours</h2>

                <?php if (empty($misReservas)): ?>
                    <div style="text-align: center; padding: 40px 20px; color: #64748b;">
                        <i class="fa-solid fa-plane-departure" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px; display: block;"></i>
                        <h3 style="font-size: 1.2rem; color: #0f2a4a; margin: 0 0 8px;">Aún no tienes reservas registradas</h3>
                        <p style="margin: 0 0 20px;">Explora nuestros paquetes turísticos y vive una experiencia inolvidable en el Perú.</p>
                        <a href="destinos.php" style="background: #0f2a4a; color: #fff; padding: 10px 22px; border-radius: 8px; text-decoration: none; font-weight: 700; display: inline-block;">
                            Explorar Destinos
                        </a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="reservas-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Tour / Paquete</th>
                                    <th>Fecha Salida</th>
                                    <th>Personas</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($misReservas as $res): ?>
                                    <tr>
                                        <td><strong style="color: #0f2a4a;"><?= htmlspecialchars($res['numero_reserva']) ?></strong></td>
                                        <td>
                                            <strong><?= htmlspecialchars($res['paquete_nombre'] ?? 'Tour Inkaiko') ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($res['fecha_salida']) ?></td>
                                        <td style="text-align: center;"><?= htmlspecialchars($res['cantidad_personas']) ?></td>
                                        <td><strong><?= formatPrice($res['total']) ?></strong></td>
                                        <td>
                                            <span class="badge-status <?= htmlspecialchars($res['estado']) ?>">
                                                <?= ucfirst(htmlspecialchars($res['estado'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
