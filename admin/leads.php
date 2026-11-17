<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leadId = (int)($_POST['lead_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $newStatus = $_POST['new_status'] ?? 'interesado';
        $pdo = get_db_connection();
        $stmt = $pdo->prepare('UPDATE leads SET estado = ? WHERE id = ?');
        $stmt->execute([$newStatus, $leadId]);
    } elseif ($action === 'add_note') {
        $note = trim($_POST['note'] ?? '');
        if ($note !== '') {
            $pdo = get_db_connection();
            $stmt = $pdo->prepare('UPDATE leads SET notas = CONCAT(IFNULL(notas, ""), ?) WHERE id = ?');
            $stmt->execute(["\n" . date('Y-m-d H:i') . ": " . $note, $leadId]);
        }
    }
    header('Location: leads.php');
    exit;
}

$pageTitle = 'Leads';
$leads = getLeads();
?>
<?php include __DIR__ . '/includes/admin_header.php'; ?>

<div class="admin-panel">
    <div class="admin-panel-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="admin-panel-title"><i class="fa-solid fa-bullhorn"></i> Leads de reserva</h3>
        <p style="margin: 0; font-size: 0.9rem;">Total de leads: <strong><?= count($leads) ?></strong></p>
    </div>
    <div class="admin-panel-body">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Paquete</th>
                        <th>Estado</th>
                        <th>Notas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td><?= htmlspecialchars($lead['id']) ?></td>
                            <td><?= htmlspecialchars($lead['nombre'] ?? 'Sin nombre') ?></td>
                            <td><?= htmlspecialchars($lead['email'] ?? 'No registrado') ?></td>
                            <td><?= htmlspecialchars($lead['telefono'] ?? 'No registrado') ?></td>
                            <td><?= htmlspecialchars($lead['paquete_name'] ?? 'General') ?></td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="lead_id" value="<?= htmlspecialchars($lead['id']) ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <select name="new_status" onchange="this.form.submit()" style="padding: 4px; border-radius: 6px; border: 1px solid var(--border);">
                                        <option value="interesado" <?= $lead['estado'] === 'interesado' ? 'selected' : '' ?>>Interesado</option>
                                        <option value="contactado" <?= $lead['estado'] === 'contactado' ? 'selected' : '' ?>>Contactado</option>
                                        <option value="perdido" <?= $lead['estado'] === 'perdido' ? 'selected' : '' ?>>Perdido</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <details style="max-width: 200px;">
                                    <summary>Ver notas</summary>
                                    <p style="margin: 8px 0; font-size: 0.9rem; white-space: pre-line;"><?= htmlspecialchars($lead['notas'] ?? 'Sin notas') ?></p>
                                    <form method="post" style="margin-top: 8px;">
                                        <input type="hidden" name="lead_id" value="<?= htmlspecialchars($lead['id']) ?>">
                                        <input type="hidden" name="action" value="add_note">
                                        <input type="text" name="note" placeholder="Agregar nota" style="width: 100%; padding: 4px; border-radius: 4px; border: 1px solid var(--border);">
                                        <button type="submit" class="btn-action btn-edit" style="width: auto; padding: 4px 10px; font-size: 0.8rem; margin-top: 4px;">Agregar</button>
                                    </form>
                                </details>
                            </td>
                            <td>
                                <?php if (!empty($lead['telefono'])): ?>
                                    <a href="https://wa.me/<?= htmlspecialchars(ltrim($lead['telefono'], '+')) ?>?text=Hola%20vi%20que%20estás%20interesado%20en%20nuestros%20paquetes" target="_blank" class="btn-action btn-add" style="width: auto; padding: 6px 12px; font-size: 0.85rem;"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/admin_footer.php'; ?>
