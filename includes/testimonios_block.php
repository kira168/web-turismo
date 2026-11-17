<?php
// Obtener los primeros 6 testimonios desde tu base de datos
$testimonios = getTestimonios(6);
?>

<?php if (!empty($testimonios)): ?>
<section class="reviews-section-machu py-5">
    <div class="container">
        <div class="text-center mb-4">
            <span class="badge bg-primary text-uppercase px-3 py-2">Experiencias Reales</span>
            <h2 class="mt-2 fw-bold">Lo que dicen nuestros viajeros</h2>
            <p class="text-muted">Conoce las historias de quienes ya disfrutaron de nuestros destinos.</p>
        </div>

        <div class="row g-4">
            <?php foreach ($testimonios as $testimonio): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0 p-3">
                        <div class="text-warning mb-2">
                            <?php 
                            $stars = (int)($testimonio['stars'] ?? 5);
                            echo str_repeat('★', $stars);
                            ?>
                        </div>
                        <p class="card-text text-secondary">
                            "<?= htmlspecialchars($testimonio['text']) ?>"
                        </p>
                        <div class="d-flex align-items-center mt-auto pt-3">
                            <?php if (!empty($testimonio['avatar'])): ?>
                                <img src="<?= htmlspecialchars($testimonio['avatar']) ?>" alt="<?= htmlspecialchars($testimonio['name']) ?>" class="rounded-circle me-3" width="50" height="50">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                    <?= strtoupper(substr($testimonio['name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <strong class="d-block text-dark"><?= htmlspecialchars($testimonio['name']) ?></strong>
                                <small class="text-muted"><?= htmlspecialchars($testimonio['role'] ?? 'Viajero') ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>