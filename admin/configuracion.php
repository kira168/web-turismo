<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$settingsFile = __DIR__ . '/../includes/settings.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_settings') {
        $settings = [
            'company_name' => trim($_POST['company_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'facebook' => trim($_POST['facebook'] ?? ''),
            'instagram' => trim($_POST['instagram'] ?? ''),
            'whatsapp' => trim($_POST['whatsapp'] ?? ''),
            'gemini_api_key' => trim($_POST['gemini_api_key'] ?? '')
        ];
        
        file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
        $successMessage = "¡Configuración guardada exitosamente!";
    }
}

// Load current settings
$settings = [];
if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true) ?: [];
}

$pageTitle = 'Configuración';
?>
<?php include __DIR__ . '/includes/admin_header.php'; ?>

<div class="admin-panel">
    <div class="admin-panel-header">
        <h3 class="admin-panel-title"><i class="fa-solid fa-gear"></i> Configuración del sistema</h3>
    </div>
    <div class="admin-panel-body">
        
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <form action="configuracion.php" method="post" class="admin-form" style="max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <input type="hidden" name="action" value="save_settings">
            
            <h4 style="color: var(--admin-card-blue); margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;"><i class="fa-solid fa-building"></i> Información de Contacto</h4>
            
            <div class="modal-form-grid" style="margin-bottom: 30px;">
                <div class="full-width">
                    <label>Nombre de la Agencia</label>
                    <input type="text" name="company_name" value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>" placeholder="Ej: Turismo Inkaiko">
                </div>
                <div>
                    <label>Teléfono Principal</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($settings['phone'] ?? '') ?>" placeholder="Ej: +51 987654321">
                </div>
                <div>
                    <label>Correo Electrónico</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($settings['email'] ?? '') ?>" placeholder="contacto@agencia.com">
                </div>
                <div class="full-width">
                    <label>Dirección</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($settings['address'] ?? '') ?>" placeholder="Calle principal 123, Cusco">
                </div>
            </div>

            <h4 style="color: var(--admin-card-blue); border-bottom: 1px solid #eee; padding-bottom: 10px;"><i class="fa-solid fa-share-nodes"></i> Redes Sociales</h4>
            
            <div class="modal-form-grid">
                <div>
                    <label>Facebook (URL)</label>
                    <input type="text" name="facebook" value="<?= htmlspecialchars($settings['facebook'] ?? '') ?>" placeholder="https://facebook.com/tu_pagina">
                </div>
                <div>
                    <label>Instagram (URL)</label>
                    <input type="text" name="instagram" value="<?= htmlspecialchars($settings['instagram'] ?? '') ?>" placeholder="https://instagram.com/tu_pagina">
                </div>
                <div>
                    <label>Número de WhatsApp (Solo números)</label>
                    <input type="text" name="whatsapp" value="<?= htmlspecialchars($settings['whatsapp'] ?? '') ?>" placeholder="51987654321">
                </div>
            </div>

            <h4 style="color: var(--admin-card-blue); margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 10px;"><i class="fa-solid fa-robot"></i> Inteligencia Artificial</h4>
            
            <div class="modal-form-grid">
                <div class="full-width">
                    <label>Clave API de Gemini (Google)</label>
                    <input type="text" name="gemini_api_key" value="<?= htmlspecialchars($settings['gemini_api_key'] ?? '') ?>" placeholder="AIzaSy...">
                    <small style="color: #666; display: block; margin-top: 5px;">Esta clave secreta habilita el Asistente Copiloto IA en tu página principal.</small>
                </div>
            </div>

            <div style="text-align: right; margin-top: 30px;">
                <button type="submit" class="btn-add" style="padding: 10px 24px; font-size: 1rem;"><i class="fa-solid fa-floppy-disk"></i> Guardar Cambios</button>
            </div>
        </form>

    </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
