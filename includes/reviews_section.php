<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Comprobación flexible de sesión activa
$isUserLoggedIn = !empty($_SESSION['user_id']) || !empty($_SESSION['usuario_id']) || !empty($_SESSION['id']);

// CONSULTA DIRECTA A LA BASE DE DATOS
global $pdo;
$opiniones = [];

if (isset($pdo)) {
    try {
        $stmt = $pdo->query("
            SELECT 
                o.id,
                COALESCE(o.rating, o.calificacion, 5) AS calificacion,
                COALESCE(o.opinion, o.comentario, '') AS comentario,
                o.fecha,
                COALESCE(
                    NULLIF(u.nombre_completo, ''), 
                    NULLIF(u.nombre, ''), 
                    NULLIF(u.usuario_nombre, ''),
                    'Viajero Inkaiko'
                ) AS nombre
            FROM opiniones o
            LEFT JOIN usuarios u ON o.user_id = u.id
            ORDER BY o.fecha DESC, o.id DESC
        ");
        $opiniones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $opiniones = [];
    }
}

$total_opiniones = count($opiniones);
$suma_calificaciones = array_sum(array_column($opiniones, 'calificacion'));
$promedio = $total_opiniones > 0 ? round($suma_calificaciones / $total_opiniones, 1) : 5.0;
?>

<div id="reviews-section" class="reviews-wrapper">

  <!-- ENCABEZADO Y BOTÓN DE ACCIÓN -->
  <div class="reviews-header">
    <div class="reviews-summary">
      <h2>Opiniones de Clientes</h2>
      <div class="rating-badge">
        <span class="rating-number"><?= $promedio ?></span>
        <div class="stars-outer">
          <div class="stars-inner" style="width: <?= ($promedio / 5) * 100 ?>%;">★★★★★</div>
        </div>
        <span class="rating-count">(<?= $total_opiniones ?> valoraciones)</span>
      </div>
    </div>

    <div class="reviews-action">
      <?php if ($isUserLoggedIn): ?>
        <button type="button" class="btn-primary" onclick="toggleOpinionForm()">Escribir una opinión</button>
      <?php else: ?>
        <button type="button" class="btn-primary" onclick="openAuthModal()">Escribir una opinión</button>
      <?php endif; ?>
    </div>
  </div>

  <!-- FORMULARIO DE PUBLICACIÓN DE OPINIÓN (SI TIENE SESIÓN) -->
  <?php if ($isUserLoggedIn): ?>
    <div id="formOpinionContainer" class="opinion-form-container" style="display: none;">
      <h3>Deja tu opinión, <?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['nombre'] ?? $_SESSION['nombre_completo'] ?? 'Cliente') ?></h3>
      <form action="guardar_opinion.php" method="POST" id="formOpinionDirecto">
        <input type="hidden" name="paquete_id" value="<?= htmlspecialchars($_GET['id'] ?? $paquete['id'] ?? 0) ?>">
        
        <div class="form-group">
          <label for="calificacion">Puntuación:</label>
          <select name="calificacion" id="calificacion" required>
            <option value="5">⭐⭐⭐⭐⭐ (5/5)</option>
            <option value="4">⭐⭐⭐⭐ (4/5)</option>
            <option value="3">⭐⭐⭐ (3/5)</option>
            <option value="2">⭐⭐ (2/5)</option>
            <option value="1">⭐ (1/5)</option>
          </select>
        </div>
        <div class="form-group">
          <label for="comentario">Comentario:</label>
          <textarea name="comentario" id="comentario" rows="4" required placeholder="Escribe tu opinión sobre el servicio..."></textarea>
        </div>
        <button type="submit" class="btn-submit">Publicar Opinión</button>
      </form>
    </div>
  <?php endif; ?>

  <!-- LISTA DE OPINIONES -->
  <div class="reviews-list">
    <?php if (!empty($opiniones)): ?>
      <?php foreach ($opiniones as $rev): ?>
        <div class="review-card">
          <div class="review-card-header">
            <strong><?= htmlspecialchars($rev['nombre'] ?? 'Viajero') ?></strong>
            <span class="review-date"><?= !empty($rev['fecha']) ? date("d/m/Y", strtotime($rev['fecha'])) : date("d/m/Y") ?></span>
          </div>
          <div class="review-stars">
            <?= str_repeat('★', (int)($rev['calificacion'] ?? 5)) . str_repeat('☆', 5 - (int)($rev['calificacion'] ?? 5)) ?>
          </div>
          <p class="review-text"><?= nl2br(htmlspecialchars($rev['comentario'] ?? '')) ?></p>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="text-align: center; color: #777; padding: 20px;">Aún no hay opiniones. ¡Sé el primero en compartir tu experiencia!</p>
    <?php endif; ?>
  </div>

  <!-- MODAL DE REGISTRO E INICIO DE SESIÓN -->
  <div id="authModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
      <button type="button" class="modal-close" onclick="closeAuthModal()">&times;</button>

      <div class="modal-tabs">
        <button type="button" class="tab-btn active" id="tabBtnRegister" onclick="switchTab('register')">Registrarse</button>
        <button type="button" class="tab-btn" id="tabBtnLogin" onclick="switchTab('login')">Ya tengo cuenta</button>
      </div>

      <!-- CONTENEDOR DE ERRORES -->
      <div id="modalErrorContainer" class="alert-error" style="display: none;"></div>

      <!-- FORMULARIO DE REGISTRO -->
      <form id="formRegistroReviewDirecto" class="auth-form" action="registro.php" method="POST">
        <div class="form-group">
          <label>NOMBRE COMPLETO *</label>
          <input type="text" name="nombre_completo" required placeholder="Ej. Juan Pérez">
        </div>
        <div class="form-group">
          <label>CORREO ELECTRÓNICO *</label>
          <input type="email" name="correo" required placeholder="tu@email.com">
        </div>
        <div class="form-group">
          <label>TELÉFONO / WHATSAPP *</label>
          <!-- Se envían ambos campos para compatibilidad con backend -->
          <input type="tel" name="telefono" required placeholder="95284784">
          <input type="hidden" name="celular" id="hiddenCelular">
        </div>
        <div class="form-group">
          <label>CONTRASEÑA *</label>
          <input type="password" name="password" required placeholder="******">
        </div>
        <div class="form-group">
          <label>CONFIRMAR CONTRASEÑA *</label>
          <input type="password" name="confirm_password" required placeholder="******">
        </div>
        <button type="submit" id="btnSubmitReviewReg" class="btn-submit btn-gold">REGISTRARME Y DEJAR OPINIÓN</button>
      </form>

      <!-- FORMULARIO DE LOGIN -->
      <form id="formLoginReviewDirecto" class="auth-form" action="login.php" method="POST" style="display: none;">
        <div class="form-group">
          <label>CORREO ELECTRÓNICO *</label>
          <input type="email" name="email" required placeholder="tu@email.com">
        </div>
        <div class="form-group">
          <label>CONTRASEÑA *</label>
          <input type="password" name="password" required placeholder="******">
        </div>
        <button type="submit" id="btnSubmitReviewLog" class="btn-submit btn-gold">INICIAR SESIÓN</button>
      </form>
    </div>
  </div>

</div>

<style>
.reviews-wrapper { max-width: 850px; margin: 30px auto; font-family: sans-serif; color: #333; }
.reviews-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 25px; }
.rating-badge { display: flex; align-items: center; gap: 10px; }
.rating-number { font-size: 28px; font-weight: bold; }
.stars-outer { position: relative; font-size: 20px; color: #ccc; display: inline-block; }
.stars-inner { position: absolute; top: 0; left: 0; white-space: nowrap; overflow: hidden; color: #ffc107; }

.btn-primary, .btn-submit { background: #0066cc; color: #fff; border: none; padding: 12px 20px; border-radius: 6px; cursor: pointer; font-weight: 700; width: auto; }
.btn-gold { background: #c59114; color: #fff; width: 100%; border-radius: 25px; margin-top: 10px; font-size: 14px; text-transform: uppercase; }
.btn-gold:hover { background: #a3760f; }

.opinion-form-container { background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0; margin-bottom: 25px; }
.reviews-list { display: flex; flex-direction: column; gap: 15px; }
.review-card { background: #fff; padding: 18px; border: 1px solid #e9e9e9; border-radius: 8px; }
.review-card-header { display: flex; justify-content: space-between; margin-bottom: 5px; }
.review-date { font-size: 12px; color: #888; }
.review-stars { color: #ffc107; margin-bottom: 8px; }

.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); display: flex; justify-content: center; align-items: center; z-index: 9999; }
.modal-content { background: #ffffff; padding: 30px; border-radius: 12px; width: 100%; max-width: 420px; position: relative; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
.modal-close { position: absolute; top: 12px; right: 15px; border: none; background: transparent; font-size: 24px; cursor: pointer; color: #999; }
.modal-tabs { display: flex; border-bottom: 2px solid #eee; margin-bottom: 20px; }
.tab-btn { flex: 1; padding: 10px; border: none; background: transparent; cursor: pointer; font-weight: 700; color: #777; font-size: 13px; }
.tab-btn.active { color: #c59114; border-bottom: 2px solid #c59114; margin-bottom: -2px; }

.form-group { margin-bottom: 15px; text-align: left; }
.form-group label { display: block; margin-bottom: 6px; font-size: 11px; font-weight: 700; color: #444; letter-spacing: 0.5px; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-size: 14px; }

.alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 6px; font-size: 13px; margin-bottom: 15px; text-align: center; }
</style>

<script>
function openAuthModal() {
  document.getElementById('authModal').style.display = 'flex';
}

function closeAuthModal() {
  document.getElementById('authModal').style.display = 'none';
}

function toggleOpinionForm() {
  const container = document.getElementById('formOpinionContainer');
  if (container) {
    const isHidden = container.style.display === 'none' || container.style.display === '';
    container.style.display = isHidden ? 'block' : 'none';
    if (isHidden) {
      container.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  } else {
    openAuthModal();
  }
}

function switchTab(tab) {
  const formLogin = document.getElementById('formLoginReviewDirecto');
  const formRegister = document.getElementById('formRegistroReviewDirecto');
  const tabLogin = document.getElementById('tabBtnLogin');
  const tabRegister = document.getElementById('tabBtnRegister');
  const errContainer = document.getElementById('modalErrorContainer');

  if (errContainer) errContainer.style.display = 'none';

  if (tab === 'login') {
    formLogin.style.display = 'block';
    formRegister.style.display = 'none';
    tabLogin.classList.add('active');
    tabRegister.classList.remove('active');
  } else {
    formLogin.style.display = 'none';
    formRegister.style.display = 'block';
    tabRegister.classList.add('active');
    tabLogin.classList.remove('active');
  }
}

document.addEventListener('DOMContentLoaded', function() {
  if (window.location.hash === '#write-review') {
    toggleOpinionForm();
  }

  const errContainer = document.getElementById('modalErrorContainer');

  // REGISTRO DESDE EL MODAL
  const formReg = document.getElementById('formRegistroReviewDirecto');
  const btnSubmitReg = document.getElementById('btnSubmitReviewReg');
  if (formReg) {
    formReg.addEventListener('submit', function(e) {
      e.preventDefault();
      errContainer.style.display = 'none';
      btnSubmitReg.disabled = true;
      btnSubmitReg.textContent = 'PROCESANDO...';

      // Sincronizar el campo teléfono con celular
      const telInput = this.querySelector('input[name="telefono"]');
      const celInput = document.getElementById('hiddenCelular');
      if (telInput && celInput) {
        celInput.value = telInput.value;
      }

      const formData = new FormData(this);

      fetch('registro.php', {
        method: 'POST',
        headers: { 
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        btnSubmitReg.disabled = false;
        btnSubmitReg.textContent = 'REGISTRARME Y DEJAR OPINIÓN';
        
        if (data.success || data.status === 'success') {
          window.location.hash = 'write-review';
          window.location.reload();
        } else {
          const msg = data.message || (data.errors ? data.errors.join('<br>') : 'Error en el registro.');
          errContainer.innerHTML = msg;
          errContainer.style.display = 'block';
        }
      })
      .catch(err => {
        console.error('Error registrando:', err);
        btnSubmitReg.disabled = false;
        btnSubmitReg.textContent = 'REGISTRARME Y DEJAR OPINIÓN';
        errContainer.innerHTML = 'Error de conexión con el servidor. Se enviará de forma estándar...';
        errContainer.style.display = 'block';
        // Respaldo: si el servidor falla en JSON, enviar el formulario de manera nativa
        setTimeout(() => { formReg.submit(); }, 1200);
      });
    });
  }

  // INICIO DE SESIÓN DESDE EL MODAL
  const formLog = document.getElementById('formLoginReviewDirecto');
  const btnSubmitLog = document.getElementById('btnSubmitReviewLog');
  if (formLog) {
    formLog.addEventListener('submit', function(e) {
      e.preventDefault();
      errContainer.style.display = 'none';
      btnSubmitLog.disabled = true;
      btnSubmitLog.textContent = 'VERIFICANDO...';

      fetch('login.php', {
        method: 'POST',
        headers: { 
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: new FormData(this)
      })
      .then(res => res.json())
      .then(data => {
        btnSubmitLog.disabled = false;
        btnSubmitLog.textContent = 'INICIAR SESIÓN';
        if (data.success || data.status === 'success') {
          window.location.hash = 'write-review';
          window.location.reload();
        } else {
          errContainer.innerHTML = (data.errors ? data.errors.join('<br>') : data.message) || 'Credenciales incorrectas.';
          errContainer.style.display = 'block';
        }
      })
      .catch(err => {
        console.error('Error en login:', err);
        btnSubmitLog.disabled = false;
        btnSubmitLog.textContent = 'INICIAR SESIÓN';
        errContainer.innerHTML = 'Error al procesar el inicio de sesión.';
        errContainer.style.display = 'block';
      });
    });
  }
});
</script>