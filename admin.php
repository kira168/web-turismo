<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Reservas';
$reservas = getReservations();
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<section class="section">
    <div class="container section-header">
        <h1>Reservas recientes</h1>
        <p>Revisa todas las reservas realizadas en el sistema y su estado actual.</p>
    </div>
    <div class="container admin-card">
        <?php if (empty($reservas)): ?>
            <p>No hay reservas registradas todavía.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Reserva</th>
                        <th>Cliente</th>
                        <th>Paquete</th>
                        <th>Salida</th>
                        <th>Personas</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Creada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $reserva): ?>
                        <tr>
                            <td><?= htmlspecialchars($reserva['id']) ?></td>
                            <td><?= htmlspecialchars($reserva['numero_reserva']) ?></td>
                            <td><?= htmlspecialchars($reserva['nombre_cliente']) ?> <br><small><?= htmlspecialchars($reserva['email_cliente']) ?></small></td>
                            <td><?= htmlspecialchars($reserva['paquete']) ?></td>
                            <td><?= htmlspecialchars($reserva['fecha_salida']) ?></td>
                            <td><?= htmlspecialchars($reserva['cantidad_personas']) ?></td>
                            <td>$<?= number_format($reserva['total'], 2) ?></td>
                            <td><span class="admin-status <?= htmlspecialchars($reserva['estado']) ?>"><?= htmlspecialchars(ucfirst($reserva['estado'])) ?></span></td>
                            <td><?= htmlspecialchars($reserva['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>