<?php
if (session_status() === PHP_SESSION_NONE) {
    ob_start();
    session_start();
}

require_once __DIR__ . '/includes/functions.php';

// Detectar si la petición viene vía AJAX / Fetch (como desde el modal)
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
          || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

// Si ya tiene sesión activa y no es una petición AJAX, redirigir
if (isset($_SESSION['user_id']) && !$isAjax) {
    if (ob_get_level() > 0) ob_end_clean();
    header('Location: index.php');
    exit;
}

$pageTitle = 'Iniciar sesión';
$errors = [];
$email = '';

// Procesar el formulario cuando se envía por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Acepta tanto 'email' como 'correo' para evitar incompatibilidades
    $email = trim($_POST['email'] ?? $_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validaciones básicas
    if ($email === '') {
        $errors[] = 'El correo electrónico es obligatorio.';
    }
    if ($password === '') {
        $errors[] = 'La contraseña es obligatoria.';
    }

    if (empty($errors)) {
        // Obtener datos del usuario desde la base de datos
        $user = getUserByEmail($email);

        // Verificar contraseña
        if ($user && password_verify($password, $user['password_hash'])) {
            // Verificar si la cuenta completó su activación por correo
            if (isset($user['email_verified']) && (int)$user['email_verified'] === 0) {
                $verifyLink = 'verificar.php?email=' . urlencode($user['email']);
                $errors[] = 'Tu cuenta aún no está activada. <a href="' . $verifyLink . '" style="color:#d4a017; font-weight:700; text-decoration:underline;">Ingresa tu código de 6 dígitos aquí</a>.';
                
                if ($isAjax) {
                    if (ob_get_level() > 0) ob_end_clean();
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success'              => false,
                        'require_verification' => true,
                        'redirect'             => $verifyLink,
                        'message'              => 'Tu cuenta aún no está activada. Por favor verifica tu correo.'
                    ]);
                    exit;
                }
            } else {
            $userName = $user['name'] ?? $user['nombre'] ?? $user['nombre_completo'] ?? 'Cliente';

            // Registrar datos esenciales en la sesión (Estandarizado para todo el sitio)
            $_SESSION['user_id']        = $user['id'];
            $_SESSION['usuario_id']     = $user['id'];
            $_SESSION['id']             = $user['id'];

            $_SESSION['user_name']      = $userName;
            $_SESSION['nombre']         = $userName;
            $_SESSION['usuario_nombre'] = $userName;
            $_SESSION['user_role']      = $user['role'] ?? $user['rol'] ?? 'cliente';

            // Si la petición viene desde el modal (AJAX)
            if ($isAjax) {
                session_write_close();
                if (ob_get_level() > 0) ob_end_clean();
                
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true, 
                    'message' => 'Sesión iniciada correctamente',
                    'user' => [
                        'id' => $_SESSION['user_id'],
                        'name' => $_SESSION['user_name']
                    ]
                ]);
                exit;
            }

            // Si es un submit estándar, redirigir
            if (!empty($_POST['redirect_to'])) {
                $redirectUrl = $_POST['redirect_to'];
            } else {
                $redirectUrl = ($_SESSION['user_role'] === 'admin') ? 'admin/dashboard.php' : 'index.php';
            }
            if (ob_get_level() > 0) ob_end_clean();
            header('Location: ' . $redirectUrl);
            exit;
            } // Fin de validación email_verified
        } else {
            $errors[] = 'Correo electrónico o contraseña incorrectos.';
        }
    }

    // Si hubo errores y la petición es AJAX (Modal)
    if ($isAjax) {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false, 
            'errors' => $errors,
            'message' => implode(' ', $errors)
        ]);
        exit;
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<section class="section auth-section">
    <div class="container auth-card">
        <h1>Iniciar sesión</h1>
        <p>Accede a tu cuenta para ver tu historial de reservas, gestionar tus viajes y compartir tus opiniones.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <!-- Campo oculto para redirigir al paquete de origen al iniciar sesión -->
            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($_POST['redirect_to'] ?? $_SERVER['HTTP_REFERER'] ?? 'index.php') ?>">

            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required placeholder="tu@email.com">

            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required placeholder="Tu contraseña">

            <button type="submit" class="button button-primary" style="width: 100%; margin-top: 10px;">Iniciar sesión</button>
        </form>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-top: 20px; font-size: 0.82rem; color: #475569; text-align: left;">
            <strong style="color: #0f2a4a; display: block; margin-bottom: 4px;"><i class="fa-solid fa-key" style="color: #d4a017;"></i> Cuentas demostrativas de prueba:</strong>
            <div><strong>Admin:</strong> <code>admin@turismoinkaiko.com</code> / <code>admin123</code></div>
            <div><strong>Cliente:</strong> <code>cesar@gmail.com</code> / <code>123456</code></div>
        </div>

        <p style="text-align: center; margin-top: 20px;">
            ¿No tienes cuenta? <a href="registro.php" style="color: var(--primary); font-weight: 700;">Regístrate aquí</a>
        </p>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>