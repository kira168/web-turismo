<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_destino') {
        $data = [
            'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
            'region_id' => (int)$_POST['region_id'],
            'name' => trim($_POST['name'] ?? ''),
            'tag' => trim($_POST['tag'] ?? ''),
            'rating' => trim($_POST['rating'] ?? ''),
            'img' => trim($_POST['img'] ?? ''),
            'packages' => (int)($_POST['packages'] ?? 0)
        ];
        
        if ($data['id']) {
            updateDestino($data);
        } else {
            createDestino($data);
        }
        header('Location: destinos.php');
        exit;
    }
}

if (isset($_GET['delete'])) {
    deleteDestino((int)$_GET['delete']);
    header('Location: destinos.php');
    exit;
}

$pageTitle = 'Destinos';
$destinos = getDestinationsByRegion();
$regiones = getRegions();
?>
<?php include __DIR__ . '/includes/admin_header.php'; ?>

<div class="admin-panel">
    <div class="admin-panel-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="admin-panel-title"><i class="fa-solid fa-earth-americas"></i> Gestionar Destinos</h3>
        <button onclick="openDestinoModal()" class="btn-add"><i class="fa-solid fa-plus"></i> Agregar destino</button>
    </div>
    <div class="admin-panel-body">
    
        <!-- Modal -->
        <div id="destinoModal" class="modal-overlay">
            <div class="modal-container">
                <button type="button" class="modal-close" onclick="closeDestinoModal()">&times;</button>
                <div class="modal-header">
                    <h2 id="destinoModalTitle">Destino</h2>
                    <p>Llena los datos del destino turístico.</p>
                </div>
                <form action="destinos.php" method="post" class="admin-form">
                    <input type="hidden" name="action" value="save_destino">
                    <input type="hidden" name="id" id="modalDestinoId">
                    
                    <div class="modal-form-grid">
                        <div class="full-width">
                            <label>Nombre del destino</label>
                            <input type="text" name="name" id="modalDestinoName" required>
                        </div>
                        <div>
                            <label>Región</label>
                            <select name="region_id" id="modalDestinoRegion" required>
                                <?php foreach ($regiones as $region): ?>
                                    <option value="<?= $region['id'] ?>"><?= htmlspecialchars($region['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Etiqueta (ej. POPULAR)</label>
                            <input type="text" name="tag" id="modalDestinoTag">
                        </div>
                        <div>
                            <label>Calificación (ej. 4.8)</label>
                            <input type="text" name="rating" id="modalDestinoRating">
                        </div>
                        <div>
                            <label>Paquetes disponibles</label>
                            <input type="number" name="packages" id="modalDestinoPackages" value="0" min="0">
                        </div>
                        <div class="full-width">
                            <label>Imagen (URL)</label>
                            <input type="text" name="img" id="modalDestinoImg" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-add" style="margin-top: 20px; width: 100%; justify-content: center;">Guardar destino</button>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Región</th>
                        <th>Etiqueta</th>
                        <th>Calificación</th>
                        <th>Paquetes</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($destinos as $destino): ?>
                        <tr>
                            <td><?= htmlspecialchars($destino['id']) ?></td>
                            <td>
                                <img src="<?= htmlspecialchars($destino['img']) ?>" alt="img" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                            </td>
                            <td><?= htmlspecialchars($destino['name']) ?></td>
                            <td><?= htmlspecialchars($destino['region_name']) ?></td>
                            <td>
                                <?php if ($destino['tag']): ?>
                                    <span class="badge badge-confirmada" style="font-size: 0.7rem; padding: 2px 6px;"><?= htmlspecialchars($destino['tag']) ?></span>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td><i class="fa-solid fa-star" style="color: #f1c40f;"></i> <?= htmlspecialchars($destino['rating']) ?></td>
                            <td><?= htmlspecialchars($destino['packages']) ?></td>
                            <td>
                                <button onclick='openDestinoModal(<?= json_encode($destino) ?>)' class="btn-action btn-edit" title="Editar"><i class="fa-solid fa-pen"></i></button>
                                <a href="destinos.php?delete=<?= htmlspecialchars($destino['id']) ?>" class="btn-action btn-delete" title="Eliminar" onclick="return confirm('¿Eliminar este destino? Todos los paquetes asociados podrían verse afectados.');"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($destinos)): ?>
                        <tr><td colspan="8" style="text-align: center;">No hay destinos registrados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>

<script>
function openDestinoModal(destino = null) {
    if (destino) {
        document.getElementById('modalDestinoId').value = destino.id;
        document.getElementById('modalDestinoName').value = destino.name;
        document.getElementById('modalDestinoRegion').value = destino.region_id;
        document.getElementById('modalDestinoTag').value = destino.tag || '';
        document.getElementById('modalDestinoRating').value = destino.rating || '';
        document.getElementById('modalDestinoPackages').value = destino.packages || 0;
        document.getElementById('modalDestinoImg').value = destino.img || '';
        document.getElementById('destinoModalTitle').textContent = 'Editar Destino';
    } else {
        document.getElementById('modalDestinoId').value = '';
        document.getElementById('modalDestinoName').value = '';
        document.getElementById('modalDestinoRegion').selectedIndex = 0;
        document.getElementById('modalDestinoTag').value = '';
        document.getElementById('modalDestinoRating').value = '';
        document.getElementById('modalDestinoPackages').value = '0';
        document.getElementById('modalDestinoImg').value = '';
        document.getElementById('destinoModalTitle').textContent = 'Agregar Destino';
    }
    document.getElementById('destinoModal').classList.add('active');
}

function closeDestinoModal() {
    document.getElementById('destinoModal').classList.remove('active');
}
</script>
