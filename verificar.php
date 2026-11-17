<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/functions.php';

// Si ya tiene sesión activa y verificada, redirigir
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Verificar Cuenta - Turismo Inkaiko';
$email = strtolower(trim($_GET['email'] ?? $_POST['email'] ?? ''));
$error_message = '';
$success_message = '';

if (empty($email)) {
    header('Location: login.php');
    exit;
}

// Procesar Acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'verify';

    if ($action === 'resend') {
        $resendResult = resendVerificationCode($email);
        if ($resendResult['success']) {
            $success_message = $resendResult['message'];
        } else {
            $error_message = $resendResult['message'];
        }
    } else {
        // Verificar código
        $code = trim($_POST['code'] ?? '');
        // Eliminar posibles espacios o guiones
        $code = preg_replace('/[^0-9]/', '', $code);

        if (empty($code) || strlen($code) !== 6) {
            $error_message = 'Por favor ingresa el código numérico completo de 6 dígitos.';
        } else {
            $verifyResult = verifyUserCode($email, $code);
            if ($verifyResult['success']) {
                $user = $verifyResult['user'];
                $userName = $user['name'] ?? 'Cliente';

                // Iniciar sesión automáticamente
                $_SESSION['user_id']        = $user['id'];
                $_SESSION['usuario_id']     = $user['id'];
                $_SESSION['id']             = $user['id'];
                $_SESSION['user_name']      = $userName;
                $_SESSION['nombre']         = $userName;
                $_SESSION['usuario_nombre'] = $userName;
                $_SESSION['user_email']     = $user['email'];
                $_SESSION['user_phone']     = $user['phone'] ?? '';
                $_SESSION['user_role']      = $user['role'] ?? 'cliente';

                $redirectTo = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : 'mi-cuenta.php?status=verified';
                header('Location: ' . $redirectTo);
                exit;
            } else {
                $error_message = $verifyResult['message'];
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<style>
.verify-wrapper {
    padding: 120px 20px 80px;
    background: #f8fafc;
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.verify-card {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(15, 42, 74, 0.08);
    border: 1px solid #e2e8f0;
    max-width: 520px;
    width: 100%;
    padding: 40px 35px;
    text-align: center;
    box-sizing: border-box;
    position: relative;
}
.verify-icon-box {
    width: 75px;
    height: 75px;
    border-radius: 50%;
    background: #fef9c3;
    color: #ca8a04;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 20px;
    box-shadow: 0 4px 15px rgba(202, 138, 4, 0.15);
}
.verify-card h1 {
    font-size: 1.8rem;
    color: #0f2a4a;
    font-weight: 800;
    margin: 0 0 10px;
}
.verify-card p.subtitle {
    font-size: 0.95rem;
    color: #64748b;
    margin: 0 0 25px;
    line-height: 1.6;
}
.verify-card p.subtitle strong {
    color: #0f2a4a;
    word-break: break-all;
}
.code-input-container {
    margin: 25px 0;
}
.code-input-field {
    width: 100%;
    max-width: 280px;
    padding: 14px 10px;
    font-size: 2.2rem;
    font-weight: 800;
    font-family: 'Courier New', Courier, monospace;
    letter-spacing: 12px;
    text-align: center;
    border: 2px solid #cbd5e1;
    border-radius: 12px;
    outline: none;
    color: #0f2a4a;
    background: #fdfdfd;
    transition: all 0.2s ease;
    box-sizing: border-box;
}
.code-input-field:focus {
    border-color: #d4a017;
    box-shadow: 0 0 0 4px rgba(212, 160, 23, 0.2);
    background: #ffffff;
}
.btn-verify-submit {
    width: 100%;
    background: #0f2a4a;
    color: #ffffff;
    border: none;
    padding: 14px;
    border-radius: 10px;
    font-size: 1.05rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s, transform 0.1s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-verify-submit:hover {
    background: #d4a017;
    color: #0f2a4a;
    transform: translateY(-2px);
}
.alert-box {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 0.92rem;
    display: flex;
    align-items: center;
    gap: 10px;
    text-align: left;
}
.alert-box.error {
    background: #fef2f2;
    border: 1px solid #fee2e2;
    color: #b91c1c;
}
.alert-box.success {
    background: #dcfce7;
    border: 1px solid #bbf7d0;
    color: #166534;
}
.resend-box {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #f1f5f9;
    font-size: 0.9rem;
    color: #64748b;
}
.btn-resend-link {
    background: none;
    border: none;
    color: #d4a017;
    font-weight: 700;
    cursor: pointer;
    text-decoration: underline;
    padding: 0;
    font-size: 0.9rem;
}
.btn-resend-link:hover {
    color: #a3760d;
}
.security-hint {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 14px;
    margin-top: 20px;
    font-size: 0.82rem;
    color: #64748b;
    text-align: left;
    line-height: 1.5;
}
</style>

<div class="verify-wrapper">
    <div class="verify-card">
        
        <div class="verify-icon-box">
            <i class="fa-solid fa-envelope-circle-check"></i>
        </div>

        <h1>Verifica tu Correo</h1>
        <p class="subtitle">
            Hemos enviado un código de seguridad de 6 dígitos a:<br>
            <strong><?= htmlspecialchars($email) ?></strong>
        </p>

        <?php if (!empty($error_message)): ?>
            <div class="alert-box error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error_message) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert-box success">
                <i class="fa-solid fa-circle-check"></i>
                <span><?= htmlspecialchars($success_message) ?></span>
            </div>
        <?php endif; ?>

        <form action="verificar.php" method="POST" autocomplete="off">
            <input type="hidden" name="action" value="verify">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($_GET['redirect_to'] ?? $_POST['redirect_to'] ?? 'mi-cuenta.php') ?>">

            <div class="code-input-container">
                <label for="verification_code" style="display:block; font-size:0.85rem; font-weight:700; color:#475569; margin-bottom:8px; text-transform:uppercase;">
                    Ingresa los 6 dígitos aquí:
                </label>
                <input type="text" 
                       id="verification_code" 
                       name="code" 
                       class="code-input-field" 
                       placeholder="••••••" 
                       maxlength="6" 
                       pattern="[0-9]{6}" 
                       inputmode="numeric" 
                       autocomplete="one-time-code" 
                       required 
                       autofocus>
            </div>

            <button type="submit" class="btn-verify-submit">
                <i class="fa-solid fa-check"></i> Activar y Entrar
            </button>
        </form>

        <div class="resend-box">
            ¿No te llegó el código?
            <form action="verificar.php" method="POST" style="display:inline;">
                <input type="hidden" name="action" value="resend">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                <button type="submit" class="btn-resend-link">Reenviar código</button>
            </form>
            <br>
            <span style="display:block; margin-top:8px;">
                ¿Correo equivocado? <a href="registro.php" style="color:#0f2a4a; font-weight:600; text-decoration:none;">Registrar con otro correo</a>
            </span>
        </div>

        <div class="security-hint">
            <i class="fa-solid fa-circle-info" style="color:#d4a017;"></i>
            <strong>Recomendación:</strong> El código suele llegar en pocos segundos. Si no lo encuentras en tu bandeja principal, revisa la carpeta de <em>Spam</em> o <em>Promociones</em>.
        </div>

    </div>
</div>

<script>
// Auto-filtro para solo permitir números en el input
document.getElementById('verification_code').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
