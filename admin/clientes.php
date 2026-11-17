<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_user') {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = trim($_POST['role'] ?? 'cliente');
        
        if ($id) {
            updateUser($id, $name, $email, $phone, $role);
        } else {
            if (!empty($_POST['password'])) {
                $newId = createUser($name, $email, $phone, $_POST['password']);
                if ($role !== 'cliente' && $newId) {
                    updateUser($newId, $name, $email, $phone, $role);
                }
            }
        }
        header('Location: clientes.php');
        exit;
    }
}

if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    if ($deleteId !== (int)$_SESSION['user_id']) {
        deleteUser($deleteId);
    }
    header('Location: clientes.php');
    exit;
}

$pageTitle = 'Gestionar usuarios';
$allUsers = getAllUsers();

$clientes = array_filter($allUsers, fn($u) => $u['role'] === 'cliente');
$admins = array_filter($allUsers, fn($u) => $u['role'] === 'admin');
?>
<?php include __DIR__ . '/includes/admin_header.php'; ?>

<div class="admin-panel">
    <div class="admin-panel-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="admin-panel-title"><i class="fa-solid fa-users"></i> Gestión de Usuarios</h3>
        <button onclick="openUserModal()" class="btn-add"><i class="fa-solid fa-plus"></i> Agregar usuario</button>
    </div>
    <div class="admin-panel-body">
    
        <!-- Modal -->
        <div id="userModal" class="modal-overlay">
            <div class="modal-container">
                <button type="button" class="modal-close" onclick="closeUserModal()">&times;</button>
                <div class="modal-header">
                    <h2 id="userModalTitle">Usuario</h2>
                    <p>Llena los datos del usuario.</p>
                </div>
                <form action="clientes.php" method="post" class="admin-form">
                    <input type="hidden" name="action" value="save_user">
                    <input type="hidden" name="id" id="modalUserId">
                    
                    <div class="modal-form-grid">
                        <div>
                            <label>Nombre</label>
                            <input type="text" name="name" id="modalUserName" required>
                        </div>
                        <div>
                            <label>Email</label>
                            <input type="email" name="email" id="modalUserEmail" required>
                        </div>
                        <div>
                            <label>Teléfono</label>
                            <input type="text" name="phone" id="modalUserPhone">
                        </div>
                        <div>
                            <label>Rol</label>
                            <select name="role" id="modalUserRole" required>
                                <option value="cliente">Cliente</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="full-width" id="passwordGroup">
                            <label>Contraseña (solo para nuevos)</label>
                            <input type="text" name="password" id="modalUserPassword">
                        </div>
                    </div>
                    <button type="submit" class="btn-add" style="margin-top: 20px; width: 100%; justify-content: center;">Guardar usuario</button>
                </form>
            </div>
        </div>

        <h4 style="color: var(--admin-card-blue); margin-top: 0; margin-bottom: 15px;"><i class="fa-solid fa-user-tie"></i> Administradores</h4>
        <div class="table-responsive" style="margin-bottom: 40px;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Fecha de registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?= htmlspecialchars($admin['id']) ?></td>
                            <td><?= htmlspecialchars($admin['name']) ?></td>
                            <td><?= htmlspecialchars($admin['email']) ?></td>
                            <td><?= htmlspecialchars($admin['phone'] ?? 'No registrado') ?></td>
                            <td><?= date('d/m/Y', strtotime($admin['created_at'])) ?></td>
                            <td>
                                <button onclick='openUserModal(<?= json_encode($admin) ?>)' class="btn-action btn-edit" title="Editar"><i class="fa-solid fa-pen"></i></button>
                                <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                <a href="clientes.php?delete=<?= htmlspecialchars($admin['id']) ?>" class="btn-action btn-delete" title="Eliminar" onclick="return confirm('¿Eliminar este administrador?');"><i class="fa-solid fa-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($admins)): ?>
                        <tr><td colspan="6" style="text-align: center;">No hay administradores registrados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h4 style="color: var(--admin-card-green); margin-top: 0; margin-bottom: 15px;"><i class="fa-solid fa-users"></i> Clientes Registrados</h4>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Fecha de registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?= htmlspecialchars($cliente['id']) ?></td>
                            <td><?= htmlspecialchars($cliente['name']) ?></td>
                            <td><?= htmlspecialchars($cliente['email']) ?></td>
                            <td><?= htmlspecialchars($cliente['phone'] ?? 'No registrado') ?></td>
                            <td><?= date('d/m/Y', strtotime($cliente['created_at'])) ?></td>
                            <td>
                                <button onclick='openUserModal(<?= json_encode($cliente) ?>)' class="btn-action btn-edit" title="Editar"><i class="fa-solid fa-pen"></i></button>
                                <a href="clientes.php?delete=<?= htmlspecialchars($cliente['id']) ?>" class="btn-action btn-delete" title="Eliminar" onclick="return confirm('¿Eliminar este cliente?');"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($clientes)): ?>
                        <tr><td colspan="6" style="text-align: center;">No hay clientes registrados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>

<script>
function openUserModal(user = null) {
    if (user) {
        document.getElementById('modalUserId').value = user.id;
        document.getElementById('modalUserName').value = user.name;
        document.getElementById('modalUserEmail').value = user.email;
        document.getElementById('modalUserPhone').value = user.phone || '';
        document.getElementById('modalUserRole').value = user.role;
        document.getElementById('passwordGroup').style.display = 'none';
        document.getElementById('modalUserPassword').required = false;
        document.getElementById('userModalTitle').textContent = 'Editar Usuario';
    } else {
        document.getElementById('modalUserId').value = '';
        document.getElementById('modalUserName').value = '';
        document.getElementById('modalUserEmail').value = '';
        document.getElementById('modalUserPhone').value = '';
        document.getElementById('modalUserRole').value = 'cliente';
        document.getElementById('passwordGroup').style.display = 'block';
        document.getElementById('modalUserPassword').required = true;
        document.getElementById('userModalTitle').textContent = 'Crear Nuevo Usuario';
    }
    document.getElementById('userModal').classList.add('active');
}

function closeUserModal() {
    document.getElementById('userModal').classList.remove('active');
}
</script>