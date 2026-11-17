<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Editar Tour';
$packageId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$package = $packageId ? getPackageById($packageId) : null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id' => isset($_POST['id']) ? (int)$_POST['id'] : null,
        'name' => trim($_POST['name'] ?? ''),
        'destino' => trim($_POST['destino'] ?? ''),
        'category' => trim($_POST['category'] ?? ''),
        'duration' => (int)($_POST['duration'] ?? 0),
        'durationLabel' => trim($_POST['durationLabel'] ?? ''),
        'price' => (float)($_POST['price'] ?? 0),
        'oldPrice' => (float)($_POST['oldPrice'] ?? 0),
        'promo' => isset($_POST['promo']) ? 1 : 0,
        'discount' => (int)($_POST['discount'] ?? 0),
        'img' => trim($_POST['img'] ?? ''),
        'rating' => trim($_POST['rating'] ?? ''),
        'reviews' => (int)($_POST['reviews'] ?? 0),
        'popular' => (int)($_POST['popular'] ?? 0),
        'priceLabel' => trim($_POST['priceLabel'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'notes' => trim($_POST['notes'] ?? ''),
    ];

    if ($data['name'] === '') {
        $errors[] = 'El nombre del paquete es obligatorio.';
    }
    if ($data['destino'] === '') {
        $errors[] = 'El destino es obligatorio.';
    }
    if ($data['price'] <= 0) {
        $errors[] = 'El precio debe ser mayor a cero.';
    }
    if ($data['img'] === '') {
        $errors[] = 'La imagen de portada es obligatoria.';
    }

    if (empty($errors)) {
        $savedId = savePackage($data);
        header('Location: tours.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | Turismo Inkaiko</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a href="../index.php" class="brand">Turismo <span>Inkaiko</span></a>
        <nav class="site-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="tours.php">Tours</a>
            <a href="multimedia.php">Multimedia</a>
            <a href="leads.php">Leads</a>
            <a href="clientes.php">Clientes</a>
            <a href="reservas.php">Reservas</a>
            <a href="../logout.php">Salir</a>
        </nav>
    </div>
</header>
<main>
<section class="section">
    <div class="container">
        <h1><?= $package ? 'Editar tour' : 'Crear tour nuevo' ?></h1>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="admin-card" style="padding: 24px;">
            <form action="edit_tour.php<?= $package ? '?id=' . htmlspecialchars($package['id']) : '' ?>" method="post" class="admin-form">
                <?php if ($package): ?>
                    <input type="hidden" name="id" value="<?= htmlspecialchars($package['id']) ?>">
                <?php endif; ?>
                <label>Nombre del paquete</label>
                <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? $package['name'] ?? '') ?>" required>

                <label>Destino</label>
                <input type="text" name="destino" value="<?= htmlspecialchars($_POST['destino'] ?? $package['destino'] ?? '') ?>" required>

                <label>Categoría</label>
                <input type="text" name="category" value="<?= htmlspecialchars($_POST['category'] ?? $package['category'] ?? '') ?>" required>

                <label>Duración</label>
                <input type="number" name="duration" value="<?= htmlspecialchars($_POST['duration'] ?? $package['duration'] ?? '') ?>" min="1" required>

                <label>Etiqueta de duración</label>
                <input type="text" name="durationLabel" value="<?= htmlspecialchars($_POST['durationLabel'] ?? $package['durationLabel'] ?? '') ?>" required>

                <label>Precio</label>
                <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($_POST['price'] ?? $package['price'] ?? '') ?>" required>

                <label>Precio anterior</label>
                <input type="number" step="0.01" name="oldPrice" value="<?= htmlspecialchars($_POST['oldPrice'] ?? $package['oldPrice'] ?? '') ?>">

                <label>Imagen principal (URL)</label>
                <input type="text" name="img" value="<?= htmlspecialchars($_POST['img'] ?? $package['img'] ?? '') ?>" required>

                <label>Rating</label>
                <input type="text" name="rating" value="<?= htmlspecialchars($_POST['rating'] ?? $package['rating'] ?? '') ?>">

                <label>Reseñas</label>
                <input type="number" name="reviews" value="<?= htmlspecialchars($_POST['reviews'] ?? $package['reviews'] ?? 0) ?>" min="0">

                <label>Popularidad</label>
                <input type="number" name="popular" value="<?= htmlspecialchars($_POST['popular'] ?? $package['popular'] ?? 0) ?>" min="0">

                <label>Etiqueta de precio</label>
                <input type="text" name="priceLabel" value="<?= htmlspecialchars($_POST['priceLabel'] ?? $package['priceLabel'] ?? '') ?>">

                <label>Descripción</label>
                <textarea name="description" rows="4"><?= htmlspecialchars($_POST['description'] ?? $package['description'] ?? '') ?></textarea>

                <label>Notas internas</label>
                <textarea name="notes" rows="3"><?= htmlspecialchars($_POST['notes'] ?? $package['notes'] ?? '') ?></textarea>

                <div style="display: flex; gap: 16px; align-items: center; margin-top: 16px;">
                    <label><input type="checkbox" name="promo" value="1" <?= isset($_POST['promo']) ? 'checked' : ($package && $package['promo'] ? 'checked' : '') ?>> En oferta</label>
                    <label>Descuento (%)</label>
                    <input type="number" name="discount" value="<?= htmlspecialchars($_POST['discount'] ?? $package['discount'] ?? 0) ?>" min="0" max="100">
                </div>

                <button type="submit" class="button button-primary" style="margin-top: 24px;">Guardar tour</button>
            </form>
        </div>
    </div>
</section>
</main>
<footer class="site-footer">
    <div class="container">
        <p>Panel de administración &copy; <?= date('Y') ?> Turismo Inkaiko</p>
    </div>
</footer>
</body>
</html>
