<!-- Modal de Registro / Login -->
<div id="authModal" class="auth-modal-overlay" style="display:none;">
    <div class="auth-modal-content">
        <button class="auth-modal-close" onclick="closeAuthModal()">&times;</button>
        
        <h2>Crea tu Cuenta</h2>
        <p>Regístrate para reservar y gestionar tus viajes.</p>

        <div id="modalAlert" style="display:none; padding:10px; border-radius:6px; margin-bottom:15px; font-size:14px;"></div>

        <form id="modalRegisterForm" action="registro.php" method="POST">
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" name="name" required placeholder="Tu nombre y apellido">
            </div>

            <div class="form-group">
                <label>Teléfono / Celular</label>
                <input type="tel" name="phone" required placeholder="Ej: 987654321">
            </div>

            <div class="form-group">
                <label>Correo Electrónico</label>
                <input type="email" name="email" required placeholder="tu@email.com">
            </div>

            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" required placeholder="******">
            </div>

            <button type="submit" id="btnSubmitModal" class="btn-submit-modal">Completar Registro</button>
        </form>
    </div>
</div>

<style>
.auth-modal-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.6);
    display: flex; align-items: center; justify-content: center;
    z-index: 999999;
}
.auth-modal-content {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    max-width: 420px;
    width: 90%;
    position: relative;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}
.auth-modal-close {
    position: absolute;
    top: 10px; right: 15px;
    border: none; background: none;
    font-size: 24px; cursor: pointer;
}
.form-group { margin-bottom: 15px; }
.form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
.form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
.btn-submit-modal {
    width: 100%; padding: 12px;
    background: #c69214; color: #fff;
    border: none; border-radius: 6px;
    font-weight: bold; cursor: pointer; font-size: 16px;
}
.btn-submit-modal:hover { background: #a3760d; }
</style>

<script>
function openAuthModal() {
    const modal = document.getElementById('authModal');
    if (modal) modal.style.display = 'flex';
}

function closeAuthModal() {
    const modal = document.getElementById('authModal');
    if (modal) modal.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('modalRegisterForm');
    
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('btnSubmitModal');
            const alertBox = document.getElementById('modalAlert');
            
            if (btn) {
                btn.disabled = true;
                btn.innerText = 'Procesando...';
            }

            const formData = new FormData(this);

            fetch('registro.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.require_verification && data.redirect) {
                        if (alertBox) {
                            alertBox.style.display = 'block';
                            alertBox.style.background = '#dcfce7';
                            alertBox.style.color = '#15803d';
                            alertBox.innerText = '¡Código enviado! Redirigiendo a verificación...';
                        }
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 600);
                    } else {
                        if (alertBox) {
                            alertBox.style.display = 'block';
                            alertBox.style.background = '#d4edda';
                            alertBox.style.color = '#155724';
                            alertBox.innerText = '¡Registro exitoso! Redirigiendo...';
                        }
                        setTimeout(() => {
                            window.location.reload();
                        }, 800);
                    }
                } else {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerText = 'Completar Registro';
                    }
                    const errorMsg = data.message || 'Error al procesar el registro.';
                    if (alertBox) {
                        alertBox.style.display = 'block';
                        alertBox.style.background = '#f8d7da';
                        alertBox.style.color = '#721c24';
                        alertBox.innerText = errorMsg;
                    } else {
                        alert(errorMsg);
                    }
                }
            })
            .catch(err => {
                console.error('Error:', err);
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = 'Completar Registro';
                }
                if (alertBox) {
                    alertBox.style.display = 'block';
                    alertBox.style.background = '#f8d7da';
                    alertBox.style.color = '#721c24';
                    alertBox.innerText = 'Respuesta inesperada del servidor. Revisa registro.php';
                }
            });
        });
    }
});
</script>