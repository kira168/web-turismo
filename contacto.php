<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = 'Contacto - Turismo Inkaiko';

if (file_exists(__DIR__ . '/includes/functions.php')) {
    include_once __DIR__ . '/includes/functions.php';
}

// VERIFICAR SI EL USUARIO ESTÁ LOGUEADO
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['usuario']) || isset($_SESSION['email']);

// Datos precargados si está logueado
$userName  = $_SESSION['user_name'] ?? $_SESSION['nombre'] ?? '';
$userEmail = $_SESSION['user_email'] ?? $_SESSION['email'] ?? '';
$userPhone = $_SESSION['user_phone'] ?? $_SESSION['telefono'] ?? '';

// PROCESAMIENTO AJAX DEL FORMULARIO DE CONTACTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_contact'])) {
    header('Content-Type: application/json; charset=utf-8');

    if (!$isLoggedIn) {
        echo json_encode(['status' => 'require_auth', 'message' => 'Para enviar tu consulta debes estar registrado o iniciar sesión.']);
        exit;
    }

    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $subject_field = trim($_POST['subject'] ?? 'Consulta Web');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        echo json_encode(['status' => 'error', 'message' => 'Por favor completa los campos obligatorios.']);
        exit;
    }

    $to      = 'kdelgado@turismoinkaiko.com';
    $subject = '=?UTF-8?B?' . base64_encode("📥 $subject_field - Turismo Inkaiko") . '?=';

    $body  = "Hola,\n\n";
    $body .= "Se ha recibido una nueva consulta desde la web:\n\n";
    $body .= "--------------------------------------------------\n";
    $body .= "Nombre: $name\n";
    $body .= "Correo: $email\n";
    $body .= "Teléfono: $phone\n";
    $body .= "Asunto: $subject_field\n";
    $body .= "--------------------------------------------------\n\n";
    $body .= "Mensaje:\n$message\n";

    $headers   = array();
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: text/plain; charset=UTF-8";
    $headers[] = "From: Web Turismo Inkaiko <kdelgado@turismoinkaiko.com>";
    $headers[] = "Reply-To: $name <$email>";

    @mail($to, $subject, $body, implode("\r\n", $headers));

    echo json_encode(['status' => 'success', 'message' => '¡Consulta enviada exitosamente!']);
    exit;
}

// PROCESAMIENTO AJAX DEL REGISTRO RÁPIDO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_register'])) {
    header('Content-Type: application/json; charset=utf-8');

    $regName    = trim($_POST['reg_name'] ?? '');
    $regEmail   = trim($_POST['reg_email'] ?? '');
    $regPhone   = trim($_POST['reg_phone'] ?? '');
    $regPass    = trim($_POST['reg_password'] ?? '');
    $regPassConf = trim($_POST['reg_password_confirm'] ?? '');

    if ($regName === '' || $regEmail === '' || $regPhone === '' || $regPass === '' || $regPassConf === '') {
        echo json_encode(['status' => 'error', 'message' => 'Todos los campos marcados con * son obligatorios.']);
        exit;
    }

    if ($regPass !== $regPassConf) {
        echo json_encode(['status' => 'error', 'message' => 'Las contraseñas ingresadas no coinciden.']);
        exit;
    }

    if (!filter_var($regEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'El correo electrónico no es válido.']);
        exit;
    }

    // Verificar si el usuario ya existe en base de datos
    $existing = getUserByEmail($regEmail);
    if ($existing) {
        echo json_encode(['status' => 'error', 'message' => 'El correo electrónico ya se encuentra registrado.']);
        exit;
    }

    // Crear el usuario de forma persistente y segura
    $newUserId = createUser($regName, $regEmail, $regPhone, $regPass);
    if (!$newUserId) {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo completar el registro. Inténtalo nuevamente.']);
        exit;
    }

    // Iniciar sesión del usuario
    $_SESSION['user_id']    = $newUserId;
    $_SESSION['id']         = $newUserId;
    $_SESSION['user_name']  = $regName;
    $_SESSION['user_email'] = $regEmail;
    $_SESSION['user_phone'] = $regPhone;
    $_SESSION['user_role']  = 'cliente';
    $_SESSION['usuario']    = $regName;
    $_SESSION['email']      = $regEmail;
    $_SESSION['telefono']   = $regPhone;

    echo json_encode(['status' => 'success', 'message' => '¡Registro exitoso!']);
    exit;
}

if (file_exists(__DIR__ . '/includes/header.php')) {
    include_once __DIR__ . '/includes/header.php'; 
}
?>

<style>
.scroll-top, .back-to-top, a[href="#top"], .btn-scroll-top, #scrollTop, .scroll-to-top, .scrollup {
    display: none !important;
}

:root {
    --nk-brand: #0f2a4a;
    --nk-dark: #0a1e36;
    --nk-gold: #d49a00;
    --nk-border: #e2e8f0;
}

.inkaiko-contact-wrapper {
    padding: 35px 0 60px 0;
    color: #1e293b;
}

.inkaiko-header-hero {
    background: rgba(212, 154, 0, 0.06); 
    border-radius: 16px; 
    border: 1.5px solid #d49a00; 
    box-shadow: 0 0 12px rgba(212, 154, 0, 0.12);
    padding: 40px 30px;
    text-align: center;
    margin-bottom: 35px;
}

.inkaiko-badge-clean {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #ffffff;
    border: 1.5px solid #d49a00;
    color: #b45309;
    font-size: 0.95rem;
    font-weight: 800;
    padding: 8px 22px;
    border-radius: 30px;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    margin-bottom: 16px;
}

.inkaiko-header-hero h1 {
    font-size: 2.6rem !important;
    font-weight: 900 !important;
    color: var(--nk-brand) !important;
    margin: 0 0 10px 0 !important;
}

.inkaiko-header-hero h1 span { color: var(--nk-gold); }

.inkaiko-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    align-items: stretch;
}

.inkaiko-col-form { flex: 1 1 540px; }
.inkaiko-col-info { flex: 1 1 380px; }

.inkaiko-card {
    background: #ffffff;
    border: 1px solid var(--nk-border);
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 14px 40px rgba(15, 42, 74, 0.08);
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.inkaiko-card-banner {
    background: rgba(212, 154, 0, 0.06);
    border-bottom: 1px solid rgba(212, 154, 0, 0.2);
    border-top: 4px solid var(--nk-gold);
    margin: -32px -32px 28px -32px;
    padding: 24px 32px;
}

.inkaiko-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #ffffff;
    border: 1px solid rgba(212, 154, 0, 0.25);
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 800;
    color: var(--nk-brand);
    margin-bottom: 12px;
    text-transform: uppercase;
}

.pulse-dot {
    width: 9px;
    height: 9px;
    background-color: #22c55e;
    border-radius: 50%;
    box-shadow: 0 0 6px #22c55e;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { transform: scale(0.95); opacity: 0.8; }
    50% { transform: scale(1.2); opacity: 1; }
    100% { transform: scale(0.95); opacity: 0.8; }
}

.inkaiko-card-banner h2 {
    color: var(--nk-brand) !important;
    font-size: 1.95rem !important;
    font-weight: 900 !important;
    margin: 0 0 6px 0 !important;
}

.inkaiko-card-banner h2 span { color: var(--nk-gold); }

.inkaiko-form-grid {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.inkaiko-row-two-cols {
    display: flex;
    gap: 16px;
}

@media (max-width: 640px) {
    .inkaiko-row-two-cols { flex-direction: column; gap: 16px; }
}

.inkaiko-field-group {
    display: flex;
    flex-direction: column;
    flex: 1;
}

/* Tipografía de Labels Aumentada */
.inkaiko-field-group label {
    font-size: 0.88rem !important;
    font-weight: 800;
    color: var(--nk-brand);
    text-transform: uppercase;
    margin-bottom: 6px;
    letter-spacing: 0.4px;
}

/* Tipografía de Inputs e Inserciones Aumentada */
.inkaiko-field-group input,
.inkaiko-field-group textarea {
    width: 100%;
    background: #f8fafc;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    padding: 13px 18px;
    font-size: 1.05rem !important;
    color: #0f172a;
    box-sizing: border-box;
    transition: all 0.2s ease;
}

.inkaiko-field-group input:focus,
.inkaiko-field-group textarea:focus {
    background: #ffffff;
    border-color: var(--nk-gold);
    box-shadow: 0 0 0 3px rgba(212, 154, 0, 0.15);
    outline: none;
}

.inkaiko-field-group textarea {
    min-height: 120px;
    resize: vertical;
}

button.inkaiko-btn-modern {
    width: 100% !important;
    height: 52px !important;
    background: linear-gradient(135deg, #d49a00 0%, #b88600 100%) !important;
    color: #ffffff !important;
    border: none !important;
    border-radius: 10px !important;
    font-size: 1.05rem !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 10px !important;
    text-decoration: none !important;
    box-shadow: 0 6px 18px rgba(212, 154, 0, 0.28) !important;
    transition: transform 0.15s, box-shadow 0.15s !important;
}

button.inkaiko-btn-modern:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 22px rgba(212, 154, 0, 0.38) !important;
}

/* ESTILOS DE MODAL Y FUENTES AUMENTADAS */
.inkaiko-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(10, 30, 54, 0.75);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 99999;
}

.inkaiko-modal-content {
    background: #ffffff;
    width: 90%;
    max-width: 460px;
    border-radius: 20px;
    padding: 34px 30px;
    position: relative;
    box-shadow: 0 25px 50px -12px rgba(15, 42, 74, 0.25);
    border: 1px solid rgba(226, 232, 240, 0.8);
    -webkit-backface-visibility: hidden;
    backface-visibility: hidden;
    transform: translateZ(0);
    animation: modalFadeIn 0.25s ease-out;
}

@keyframes modalFadeIn {
    from { opacity: 0; transform: translateY(-12px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.inkaiko-modal-close {
    position: absolute;
    top: 16px;
    right: 20px;
    font-size: 26px;
    line-height: 1;
    font-weight: 700;
    color: #94a3b8;
    cursor: pointer;
    transition: color 0.2s;
}

.inkaiko-modal-close:hover {
    color: var(--nk-brand);
}

.inkaiko-icon-badge-modern {
    width: 58px;
    height: 58px;
    background: linear-gradient(135deg, rgba(212, 154, 0, 0.15) 0%, rgba(212, 154, 0, 0.05) 100%);
    border: 1px solid rgba(212, 154, 0, 0.25);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 14px auto;
}

.inkaiko-modal-header {
    text-align: center;
    margin-bottom: 22px;
}

.inkaiko-modal-header h3 {
    color: var(--nk-brand) !important;
    font-size: 1.55rem !important;
    font-weight: 800 !important;
    margin: 0 0 6px 0 !important;
}

.inkaiko-modal-header p {
    color: #64748b !important;
    font-size: 0.98rem !important;
    margin: 0 !important;
}

.input-with-icon {
    position: relative;
    display: flex;
    align-items: center;
}

.input-with-icon .icon-left {
    position: absolute;
    left: 14px;
    color: #94a3b8;
    pointer-events: none;
    transition: color 0.2s;
}

.btn-toggle-password {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    padding: 4px;
    cursor: pointer;
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s;
}

.btn-toggle-password:hover {
    color: var(--nk-brand);
}

.input-field-modern {
    width: 100%;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0 !important;
    border-radius: 10px !important;
    padding: 13px 42px 13px 44px !important;
    font-size: 1.05rem !important;
    color: #0f172a !important;
    box-sizing: border-box;
    transition: all 0.2s ease-in-out !important;
    outline: none !important;
}

.input-field-modern:focus {
    background: #ffffff !important;
    border-color: var(--nk-gold) !important;
    box-shadow: 0 0 0 3px rgba(212, 154, 0, 0.12) !important;
}

.input-with-icon:focus-within .icon-left {
    color: var(--nk-gold);
}

.pass-match-msg {
    font-size: 0.85rem;
    margin-top: 5px;
    font-weight: 600;
    display: none;
}

.pass-match-msg.error { color: #e11d48; display: block; }
.pass-match-msg.success { color: #16a34a; display: block; }

.btn-register-submit {
    width: 100% !important;
    height: 50px !important;
    background: linear-gradient(135deg, #d49a00 0%, #b88600 100%) !important;
    color: #ffffff !important;
    border: none !important;
    border-radius: 10px !important;
    font-size: 1rem !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    cursor: pointer !important;
    box-shadow: 0 4px 14px rgba(212, 154, 0, 0.3) !important;
    transition: transform 0.15s, box-shadow 0.15s !important;
    margin-top: 8px;
}

.btn-register-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(212, 154, 0, 0.4) !important;
}

.inkaiko-action-btns {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.btn-guest-register {
    flex: 1;
    background: linear-gradient(135deg, #d49a00 0%, #b88600 100%);
    color: #ffffff;
    padding: 13px 18px;
    border-radius: 10px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    font-size: 0.98rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    box-shadow: 0 4px 12px rgba(212, 154, 0, 0.25);
    transition: transform 0.15s, box-shadow 0.15s;
}

.btn-guest-register:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(212, 154, 0, 0.35);
}

.btn-guest-login {
    flex: 1;
    background: var(--nk-brand);
    color: #ffffff;
    padding: 13px 18px;
    border-radius: 10px;
    font-weight: 700;
    text-decoration: none;
    font-size: 0.98rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s, transform 0.15s;
}

.btn-guest-login:hover {
    background: var(--nk-dark);
    transform: translateY(-1px);
}

.inkaiko-success-screen {
    display: none;
    text-align: center;
    padding: 30px 10px;
}

.success-icon-box {
    width: 72px;
    height: 72px;
    background: #f0fdf4;
    border: 2px solid #86efac;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px auto;
}

.inkaiko-info-item { display: flex; gap: 16px; margin-bottom: 20px; align-items: flex-start; }
.inkaiko-info-icon-box { width: 46px; height: 46px; background: rgba(212, 154, 0, 0.1); border: 1px solid rgba(212, 154, 0, 0.3); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.inkaiko-info-content label { font-size: 0.92rem; font-weight: 800; color: var(--nk-gold); text-transform: uppercase; display: block; margin-bottom: 2px; }
.inkaiko-info-content a, .inkaiko-info-content span { color: var(--nk-brand); text-decoration: none; font-weight: 700; display: block; line-height: 1.4; font-size: 1.02rem; }
.inkaiko-map-box { margin-top: 15px; border-radius: 12px; overflow: hidden; border: 1.5px solid var(--nk-border); height: 210px; }
.inkaiko-map-box iframe { width: 100%; height: 100%; border: 0; }
.inkaiko-alert-error { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; display: none; font-size: 0.95rem; }
</style>

<div class="inkaiko-contact-wrapper">
    <div class="container">
        
        <div class="inkaiko-header-hero">
            <span class="inkaiko-badge-clean">✨ Soporte Exclusivo</span>
            <h1>Contáctanos en <span>Turismo Inkaiko</span></h1>
            <p>Atención directa para nuestros clientes.</p>
        </div>

        <div class="inkaiko-grid">
            <!-- COLUMNA IZQUIERDA: FORMULARIO DE CONTACTO -->
            <div class="inkaiko-col-form">
                <div class="inkaiko-card">
                    <div id="formContainer">
                        <div class="inkaiko-card-banner">
                            <div class="inkaiko-status-badge">
                                <span class="pulse-dot"></span>
                                Asesores en Línea
                            </div>
                            <h2>¿Tienes alguna consulta? <span>Escríbenos</span></h2>
                            <p>Envía tu consulta directamente a nuestros agentes de reservas.</p>
                        </div>

                        <div id="errorMessage" class="inkaiko-alert-error"></div>

                        <!-- FORMULARIO DE CONTACTO -->
                        <form id="contactForm" class="inkaiko-form-grid">
                            <div class="inkaiko-row-two-cols">
                                <div class="inkaiko-field-group">
                                    <label for="name">NOMBRE Y APELLIDO *</label>
                                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($userName) ?>" placeholder="Ej. Juan Pérez" required>
                                </div>

                                <div class="inkaiko-field-group">
                                    <label for="email">CORREO ELECTRÓNICO *</label>
                                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($userEmail) ?>" placeholder="ejemplo@correo.com" required>
                                </div>
                            </div>

                            <div class="inkaiko-row-two-cols">
                                <div class="inkaiko-field-group">
                                    <label for="phone">TELÉFONO / WHATSAPP *</label>
                                    <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($userPhone) ?>" placeholder="+51 987 654 321" required>
                                </div>

                                <div class="inkaiko-field-group">
                                    <label for="subject">ASUNTO</label>
                                    <input type="text" id="subject" name="subject" placeholder="Ej. Reserva Tour Cusco">
                                </div>
                            </div>

                            <div class="inkaiko-field-group">
                                <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                                    <label for="message">MENSAJE *</label>
                                    <span id="charCounter" style="font-size:14px; color:#94a3b8;">0/500</span>
                                </div>
                                <textarea id="message" name="message" maxlength="500" placeholder="Escribe aquí los detalles de tu consulta..." required></textarea>
                            </div>

                            <button type="submit" id="submitBtn" class="inkaiko-btn-modern">
                                <span id="btnText">Enviar Consulta</span>
                            </button>
                        </form>
                    </div>

                    <div id="successScreen" class="inkaiko-success-screen">
                        <div class="success-icon-box">
                            <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="#166534" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
                        </div>
                        <h3>¡Consulta Recibida!</h3>
                        <p>Hemos recibido tu mensaje correctamente. Tu asesor asignado te responderá en breve.</p>
                        <button type="button" onclick="location.reload()" style="background:transparent; border:1.5px solid var(--nk-gold); color:var(--nk-gold); padding:10px 20px; border-radius:8px; font-weight:700; cursor:pointer;">Enviar otra consulta</button>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: DATOS DE CONTACTO -->
            <div class="inkaiko-col-info">
                <div class="inkaiko-card">
                    <div>
                        <div class="inkaiko-card-banner">
                            <h2>Información de <span>Contacto</span></h2>
                            <p>Canales oficiales de atención.</p>
                        </div>

                        <div class="inkaiko-info-item">
                            <div class="inkaiko-info-icon-box">📞</div>
                            <div class="inkaiko-info-content">
                                <label>TELÉFONOS / WHATSAPP</label>
                                <a href="tel:+51989003025">+51 989 003 025</a>
                                <a href="tel:+51989003031">+51 989 003 031</a>
                            </div>
                        </div>

                        <div class="inkaiko-info-item">
                            <div class="inkaiko-info-icon-box">✉️</div>
                            <div class="inkaiko-info-content">
                                <label>CORREO PRINCIPAL</label>
                                <a href="mailto:kdelgado@turismoinkaiko.com">kdelgado@turismoinkaiko.com</a>
                                <a href="mailto:counter6@turismoinkaiko.com">counter6@turismoinkaiko.com</a>
                                <a href="mailto:lmendoza@turismoinkaiko.com">lmendoza@turismoinkaiko.com</a>
                            </div>
                        </div>

                        <div class="inkaiko-info-item">
                            <div class="inkaiko-info-icon-box">📍</div>
                            <div class="inkaiko-info-content">
                                <label>OFICINA PRINCIPAL</label>
                                <span>Av. José Pardo 610, Miraflores, Perú</span>
                            </div>
                        </div>
                    </div>

                    <div class="inkaiko-map-box">
                        <iframe src="https://maps.google.com/maps?q=Turismo+Inkaiko,+Av.+Jose+Pardo+610,+Miraflores,+Peru&t=&z=16&ie=UTF8&iwloc=&output=embed" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 1: AVISO SI NO ESTÁ LOGUEADO -->
<div id="authPromptModal" class="inkaiko-modal-overlay">
    <div class="inkaiko-modal-content" style="text-align: center;">
        <span class="inkaiko-modal-close" onclick="closeAuthModal()">&times;</span>
        
        <div class="inkaiko-icon-badge-modern">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#d49a00" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>

        <div class="inkaiko-modal-header" style="margin-bottom: 22px;">
            <h3>Registro Requerido</h3>
            <p>Para enviar tu consulta debes contar con una cuenta registrada.</p>
        </div>
        
        <div class="inkaiko-action-btns">
            <button type="button" class="btn-guest-register" onclick="switchToRegForm()">
                <span>✨</span> Crear Cuenta
            </button>
            <a href="/login.php" class="btn-guest-login">Ya tengo cuenta</a>
        </div>
    </div>
</div>

<!-- MODAL 2: FORMULARIO DE REGISTRO RÁPIDO MEJORADO CON CAMPO TELÉFONO -->
<div id="registerModal" class="inkaiko-modal-overlay">
    <div class="inkaiko-modal-content">
        <span class="inkaiko-modal-close" onclick="closeRegisterModal()">&times;</span>
        
        <div class="inkaiko-modal-header">
            <div class="inkaiko-icon-badge-modern">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#d49a00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
            </div>
            <h3>Registro de Cliente</h3>
            <p>Crea tu cuenta para completar el envío de tu consulta.</p>
        </div>
        
        <div id="modalError" class="inkaiko-alert-error"></div>

        <form id="modalRegisterForm" class="inkaiko-form-grid" autocomplete="off">
            <!-- NOMBRE -->
            <div class="inkaiko-field-group">
                <label for="reg_name">Nombre Completo *</label>
                <div class="input-with-icon">
                    <input type="text" id="reg_name" name="reg_name" class="input-field-modern" placeholder="Ej. Juan Pérez" required>
                    <svg class="icon-left" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
            </div>

            <!-- CORREO -->
            <div class="inkaiko-field-group">
                <label for="reg_email">Correo Electrónico *</label>
                <div class="input-with-icon">
                    <input type="email" id="reg_email" name="reg_email" class="input-field-modern" placeholder="ejemplo@correo.com" required>
                    <svg class="icon-left" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                </div>
            </div>

            <!-- TELÉFONO / WHATSAPP AGREGADO -->
            <div class="inkaiko-field-group">
                <label for="reg_phone">Teléfono / WhatsApp *</label>
                <div class="input-with-icon">
                    <input type="tel" id="reg_phone" name="reg_phone" class="input-field-modern" placeholder="+51 987 654 321" required>
                    <svg class="icon-left" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                </div>
            </div>

            <!-- CONTRASEÑA -->
            <div class="inkaiko-field-group">
                <label for="reg_password">Contraseña *</label>
                <div class="input-with-icon">
                    <input type="password" id="reg_password" name="reg_password" class="input-field-modern" placeholder="••••••••" required>
                    <svg class="icon-left" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    
                    <button type="button" class="btn-toggle-password" onclick="togglePassVisibility('reg_password', this)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>

            <!-- CONFIRMAR CONTRASEÑA -->
            <div class="inkaiko-field-group">
                <label for="reg_password_confirm">Confirmar Contraseña *</label>
                <div class="input-with-icon">
                    <input type="password" id="reg_password_confirm" name="reg_password_confirm" class="input-field-modern" placeholder="••••••••" required>
                    <svg class="icon-left" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    
                    <button type="button" class="btn-toggle-password" onclick="togglePassVisibility('reg_password_confirm', this)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
                <span id="passMatchMsg" class="pass-match-msg"></span>
            </div>

            <!-- BOTÓN -->
            <button type="submit" id="modalRegBtn" class="btn-register-submit">
                <span id="modalRegBtnText">Registrarme y Enviar Consulta</span>
            </button>
        </form>
    </div>
</div>

<script>
const messageTextarea = document.getElementById('message');
const charCounter = document.getElementById('charCounter');
if (messageTextarea) {
    messageTextarea.addEventListener('input', () => {
        charCounter.textContent = `${messageTextarea.value.length}/500`;
    });
}

const isUserLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;

const contactForm = document.getElementById('contactForm');
if (contactForm) {
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!isUserLoggedIn) {
            document.getElementById('authPromptModal').style.display = 'flex';
            return;
        }

        sendContactForm();
    });
}

function sendContactForm() {
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const errorMessage = document.getElementById('errorMessage');

    submitBtn.disabled = true;
    btnText.textContent = 'Enviando...';
    errorMessage.style.display = 'none';

    const formData = new FormData(contactForm);
    formData.append('ajax_contact', '1');

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById('formContainer').style.display = 'none';
            document.getElementById('successScreen').style.display = 'block';
        } else {
            errorMessage.innerHTML = '<strong>Atención:</strong> ' + data.message;
            errorMessage.style.display = 'block';
            submitBtn.disabled = false;
            btnText.textContent = 'Enviar Consulta';
        }
    })
    .catch(() => {
        errorMessage.innerHTML = '<strong>Error:</strong> Ocurrió un problema al enviar la consulta.';
        errorMessage.style.display = 'block';
        submitBtn.disabled = false;
        btnText.textContent = 'Enviar Consulta';
    });
}

function closeAuthModal() {
    document.getElementById('authPromptModal').style.display = 'none';
}

function closeRegisterModal() {
    document.getElementById('registerModal').style.display = 'none';
}

function switchToRegForm() {
    closeAuthModal();
    const regForm = document.getElementById('modalRegisterForm');
    if (regForm) {
        regForm.reset();
    }
    const passMatchMsg = document.getElementById('passMatchMsg');
    if (passMatchMsg) {
        passMatchMsg.style.display = 'none';
    }
    document.getElementById('registerModal').style.display = 'flex';
}

// Función para mostrar/ocultar contraseña (Ojo)
function togglePassVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const isPassword = input.type === 'password';
    
    input.type = isPassword ? 'text' : 'password';
    
    btn.innerHTML = isPassword 
        ? `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`
        : `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
}

// Validación en tiempo real de coincidencia de contraseñas
const passInput = document.getElementById('reg_password');
const confirmPassInput = document.getElementById('reg_password_confirm');
const passMatchMsg = document.getElementById('passMatchMsg');

function validatePasswords() {
    if (!confirmPassInput.value) {
        passMatchMsg.style.display = 'none';
        return true;
    }

    if (passInput.value !== confirmPassInput.value) {
        passMatchMsg.textContent = '✕ Las contraseñas no coinciden';
        passMatchMsg.className = 'pass-match-msg error';
        return false;
    } else {
        passMatchMsg.textContent = '✓ Las contraseñas coinciden';
        passMatchMsg.className = 'pass-match-msg success';
        return true;
    }
}

if (passInput && confirmPassInput) {
    passInput.addEventListener('input', validatePasswords);
    confirmPassInput.addEventListener('input', validatePasswords);
}

const modalRegisterForm = document.getElementById('modalRegisterForm');
if (modalRegisterForm) {
    modalRegisterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (passInput.value !== confirmPassInput.value) {
            passMatchMsg.textContent = '✕ Las contraseñas no coinciden';
            passMatchMsg.className = 'pass-match-msg error';
            confirmPassInput.focus();
            return;
        }

        const regBtn = document.getElementById('modalRegBtn');
        const regBtnText = document.getElementById('modalRegBtnText');
        const modalError = document.getElementById('modalError');

        regBtn.disabled = true;
        regBtnText.textContent = 'Registrando...';
        modalError.style.display = 'none';

        const formData = new FormData(modalRegisterForm);
        formData.append('ajax_register', '1');

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                closeRegisterModal();
                // RECARGAR PÁGINA PARA ACTIVAR SESIÓN Y MOSTRAR PERFIL EN EL HEADER
                window.location.reload();
            } else {
                modalError.innerHTML = '<strong>Error:</strong> ' + data.message;
                modalError.style.display = 'block';
                regBtn.disabled = false;
                regBtnText.textContent = 'Registrarme y Enviar Consulta';
            }
        })
        .catch(() => {
            modalError.innerHTML = '<strong>Error:</strong> No se pudo procesar el registro.';
            modalError.style.display = 'block';
            regBtn.disabled = false;
            regBtnText.textContent = 'Registrarme y Enviar Consulta';
        });
    });
}
</script>

<?php 
if (file_exists(__DIR__ . '/includes/footer.php')) {
    include_once __DIR__ . '/includes/footer.php'; 
}
?>