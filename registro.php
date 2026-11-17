<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/functions.php';

// Detectar si la petición viene vía AJAX
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
          || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

// Si ya tiene sesión activa y viene por GET, redirigir
if (isset($_SESSION['user_id']) && !$isAjax && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Crear cuenta';
$errors = [];
$name = '';
$email = '';
$phone = '';

// Procesar registro cuando se envía por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['nombre_completo'] ?? $_POST['name'] ?? '');
    $phone    = trim($_POST['celular'] ?? $_POST['phone'] ?? '');
    $email    = trim($_POST['correo'] ?? $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Validaciones
    if (empty($name)) {
        $errors[] = 'El nombre completo es obligatorio.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El correo electrónico no es válido.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
    }
    if (!empty($confirm) && $password !== $confirm) {
        $errors[] = 'Las contraseñas no coinciden.';
    }

    if (empty($errors)) {
        $regResult = createUserPendingVerification($name, $email, $phone, $password);
        if ($regResult['success']) {
            $redirectTo = $_POST['redirect_to'] ?? 'mi-cuenta.php';
            $verifyUrl = 'verificar.php?email=' . urlencode($email) . '&redirect_to=' . urlencode($redirectTo);

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success'              => true,
                    'require_verification' => true,
                    'email'                => $email,
                    'message'              => '¡Código enviado! Revisa tu correo electrónico para activar tu cuenta.',
                    'redirect'             => $verifyUrl
                ]);
                exit;
            }

            header('Location: ' . $verifyUrl);
            exit;
        } else {
            $errors[] = $regResult['message'] ?? 'No se pudo procesar el registro. Inténtalo de nuevo.';
        }
    }

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => implode(' ', $errors),
            'errors'  => $errors
        ]);
        exit;
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<style>
.auth-section {
    padding: 120px 20px 80px;
    background: #f8fafc;
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.auth-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    max-width: 480px;
    width: 100%;
    padding: 35px 30px;
    box-sizing: border-box;
}
.auth-card h1 {
    font-size: 2rem;
    font-weight: 800;
    color: #0f2a4a;
    margin: 0 0 8px;
    text-align: center;
}
.auth-card p.subtitle {
    font-size: 0.95rem;
    color: #64748b;
    margin: 0 0 25px;
    text-align: center;
    line-height: 1.5;
}
.form-group {
    margin-bottom: 18px;
}
.form-group label {
    display: block;
    font-size: 0.88rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}
.form-group input {
    width: 100%;
    padding: 12px 14px;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.95rem;
    color: #1e293b;
    box-sizing: border-box;
    transition: border-color 0.2s;
}
.form-group input:focus {
    outline: none;
    border-color: #d4a017;
    box-shadow: 0 0 0 3px rgba(212, 160, 23, 0.15);
}
.btn-auth-primary {
    width: 100%;
    background: #0f2a4a;
    color: #ffffff;
    border: none;
    padding: 14px;
    border-radius: 8px;
    font-size: 1.05rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s, transform 0.1s;
    margin-top: 10px;
}
.btn-auth-primary:hover {
    background: #d4a017;
    color: #0f2a4a;
    transform: translateY(-2px);
}
.alert-error {
    background: #fef2f2;
    border: 1px solid #fee2e2;
    color: #b91c1c;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 0.9rem;
}
.alert-error ul {
    margin: 0;
    padding-left: 20px;
}
.switch-auth {
    text-align: center;
    margin-top: 24px;
    font-size: 0.92rem;
    color: #64748b;
}
.switch-auth a {
    color: #d4a017;
    font-weight: 700;
    text-decoration: none;
}
.switch-auth a:hover {
    text-decoration: underline;
}
</style>

<section class="section auth-section">
    <div class="auth-card">
        <h1>Crear cuenta</h1>
        <p class="subtitle">Regístrate con tu correo real. Te enviaremos un código de seguridad de 6 dígitos para verificar tu cuenta.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="registro.php" method="POST" autocomplete="off">
            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($_GET['redirect_to'] ?? $_POST['redirect_to'] ?? 'index.php') ?>">

            <div class="form-group">
                <label for="reg_nombre">Nombre y Apellido *</label>
                <input type="text" id="reg_nombre" name="nombre_completo" value="<?= htmlspecialchars($name) ?>" placeholder="Ej: Juan Pérez" required>
            </div>

            <div class="form-group">
                <label for="reg_email">Correo Electrónico *</label>
                <input type="email" id="reg_email" name="correo" value="<?= htmlspecialchars($email) ?>" placeholder="tu@email.com" required>
            </div>

            <div class="form-group">
                <label for="reg_phone">Celular / WhatsApp *</label>
                <input type="tel" id="reg_phone" name="celular" value="<?= htmlspecialchars($phone) ?>" placeholder="+51 987654321" required>
            </div>

            <div class="form-group">
                <label for="reg_password">Contraseña (Mín. 6 caracteres) *</label>
                <input type="password" id="reg_password" name="password" placeholder="••••••••" required>
            </div>

            <div class="form-group">
                <label for="reg_confirm">Confirmar Contraseña *</label>
                <input type="password" id="reg_confirm" name="confirm_password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-auth-primary">Registrarse Ahora</button>
        </form>

        <p class="switch-auth">
            ¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a>
        </p>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
