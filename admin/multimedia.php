<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Multimedia';
$editingItem = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $img = trim($_POST['img'] ?? '');
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;

    if ($id) {
        updateMultimedia(['id' => $id, 'title' => $title, 'img' => $img]);
    } else {
        createMultimedia(['title' => $title, 'img' => $img]);
    }
    header('Location: multimedia.php');
    exit;
}

if (isset($_GET['delete'])) {
    deleteMultimedia((int)$_GET['delete']);
    header('Location: multimedia.php');
    exit;
}

$items = getMultimedia(50);
?>
<?php include __DIR__ . '/includes/admin_header.php'; ?>

<div class="admin-panel">
    <div class="admin-panel-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="admin-panel-title"><i class="fa-solid fa-images"></i> Gestión de Multimedia</h3>
        <button onclick="openMediaModal('', '', '')" class="btn-add"><i class="fa-solid fa-plus"></i> Agregar elemento</button>
    </div>
    <div class="admin-panel-body">

        <!-- Modal Multimedia -->
        <div id="mediaModal" class="modal-overlay">
            <div class="modal-container">
                <button type="button" class="modal-close" onclick="closeMediaModal()">&times;</button>
                <div class="modal-header">
                    <h2 id="mediaModalTitle">Agregar nuevo elemento</h2>
                    <p id="mediaModalSubtitle">Sube o edita la imagen o video para tu galería.</p>
                </div>
                <form action="multimedia.php" method="post" class="admin-form">
                    <input type="hidden" name="id" id="mediaModalId">
                    <label>Título</label>
                    <input type="text" name="title" id="mediaModalTitleInput" required>
                    <label>URL de imagen o video</label>
                    <input type="text" name="img" id="mediaModalImgInput" required>
                    <button type="submit" class="btn-add" style="margin-top: 20px; width: 100%; justify-content: center;">Guardar elemento</button>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>URL</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['id']) ?></td>
                            <td><?= htmlspecialchars($item['title']) ?></td>
                            <td><a href="<?= htmlspecialchars($item['img']) ?>" target="_blank" rel="noopener noreferrer">Ver recurso</a></td>
                            <td>
                                <button onclick='openMediaModal(<?= (int)$item['id'] ?>, <?= htmlspecialchars(json_encode($item['title']), ENT_QUOTES, "UTF-8") ?>, <?= htmlspecialchars(json_encode($item['img']), ENT_QUOTES, "UTF-8") ?>)' class="btn-action btn-edit" title="Editar"><i class="fa-solid fa-pen"></i></button>
                                <a href="multimedia.php?delete=<?= htmlspecialchars($item['id']) ?>" class="btn-action btn-delete" title="Eliminar" onclick="return confirm('¿Eliminar este elemento multimedia?');"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>

<script>
function openMediaModal(id, title, img) {
    document.getElementById('mediaModalId').value = id;
    document.getElementById('mediaModalTitleInput').value = title;
    document.getElementById('mediaModalImgInput').value = img;
    document.getElementById('mediaModalTitle').textContent = id ? 'Editar elemento' : 'Agregar nuevo elemento';
    document.getElementById('mediaModalSubtitle').textContent = id ? 'Modifica la información de tu galería.' : 'Sube o edita la imagen o video para tu galería.';
    document.getElementById('mediaModal').classList.add('active');
}

function closeMediaModal() {
    document.getElementById('mediaModal').classList.remove('active');
}
</script>
