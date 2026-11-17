<?php 
if (file_exists(__DIR__ . '/includes/functions.php')) {
    include_once __DIR__ . '/includes/functions.php';
}
?>

<!-- Carga Leaflet CSS y JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<style>
    /* Contenedor del widget flotante */
    .copiloto-widget-container {
        position: fixed !important;
        bottom: 95px !important;
        right: 25px !important;
        z-index: 9999999 !important;
        font-family: Arial, sans-serif !important;
    }

    .copiloto-widget-container * {
        box-sizing: border-box !important;
    }

    /* Botón Flotante de IA */
    .copiloto-toggle-btn {
        width: 60px !important;
        height: 60px !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #00c6ff, #0072ff) !important;
        color: #ffffff !important;
        border: none !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3) !important;
        cursor: pointer !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 26px !important;
        transition: transform 0.2s ease !important;
        margin-left: auto !important;
    }

    .copiloto-toggle-btn:hover {
        transform: scale(1.08) !important;
    }

    /* Ventana del Chat Redimensionable */
    .copiloto-window {
        display: none;
        position: relative !important;
        width: 360px;
        min-width: 280px;
        max-width: calc(100vw - 30px);
        height: 520px;
        min-height: 380px;
        max-height: calc(100vh - 120px);
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.25);
        overflow: hidden;
        flex-direction: column;
        margin-bottom: 12px;
        border: 1px solid rgba(0,0,0,0.1);
    }

    .copiloto-window.active {
        display: flex !important;
    }

    /* BORDES Y ESQUINAS SENSIBLES PARA REDIMENSIONAR (RESIZERS) */
    .c-resizer {
        position: absolute !important;
        z-index: 10000000 !important;
    }
    .c-resizer-top { top: 0; left: 0; width: 100%; height: 6px; cursor: ns-resize; }
    .c-resizer-bottom { bottom: 0; left: 0; width: 100%; height: 6px; cursor: ns-resize; }
    .c-resizer-left { top: 0; left: 0; width: 6px; height: 100%; cursor: ew-resize; }
    .c-resizer-right { top: 0; right: 0; width: 6px; height: 100%; cursor: ew-resize; }

    .c-resizer-top-left { top: 0; left: 0; width: 12px; height: 12px; cursor: nwse-resize; }
    .c-resizer-top-right { top: 0; right: 0; width: 12px; height: 12px; cursor: nesw-resize; }
    .c-resizer-bottom-left { bottom: 0; left: 0; width: 12px; height: 12px; cursor: nesw-resize; }
    .c-resizer-bottom-right { bottom: 0; right: 0; width: 12px; height: 12px; cursor: nwse-resize; }

    .c-resizer:hover {
        background: rgba(212, 175, 55, 0.3) !important;
    }

    /* Estilos del contenido */
    .copiloto-header {
        padding: 12px 16px;
        background: #D4AF37;
        color: #ffffff;
        font-weight: 600;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }

    .copiloto-close-btn {
        background: none !important;
        border: none !important;
        color: #fff !important;
        font-size: 20px !important;
        cursor: pointer !important;
    }

    .copiloto-messages {
        flex: 1;
        padding: 12px;
        overflow-y: auto !important;
        background: #fdfdfd;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .c-message {
        max-width: 85%;
        padding: 10px 14px;
        border-radius: 14px;
        font-size: 0.88rem;
        line-height: 1.4;
        word-break: break-word;
    }

    .c-message a {
        color: #0066cc;
        text-decoration: underline;
        font-weight: bold;
    }

    .c-user {
        background: #e6f2ff;
        color: #004085;
        margin-left: auto;
        border-bottom-right-radius: 2px;
    }

    .c-bot {
        background: #fff4d1;
        color: #5a4a0f;
        margin-right: auto;
        border-bottom-left-radius: 2px;
    }

    .typing-indicator {
        display: none;
        padding: 6px 12px;
        background: #eee;
        border-radius: 10px;
        font-size: 0.8rem;
        color: #666;
        width: fit-content;
    }

    .copiloto-input-area {
        padding: 8px 10px;
        background: #fff;
        border-top: 1px solid #eee;
        display: flex;
        gap: 8px;
        flex-shrink: 0;
    }

    .copiloto-input {
        flex: 1;
        padding: 8px 14px;
        border: 1px solid #ddd;
        border-radius: 20px;
        outline: none;
        font-size: 0.88rem;
    }

    .copiloto-send-btn {
        background: #D4AF37 !important;
        color: white !important;
        border: none !important;
        width: 36px !important;
        height: 36px !important;
        border-radius: 50% !important;
        cursor: pointer !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 0.9rem !important;
        flex-shrink: 0;
    }

    .copiloto-media-panel {
        height: 140px !important;
        min-height: 140px !important;
        width: 100% !important;
        background: #e5e7eb;
        border-top: 1px solid #ddd;
        position: relative !important;
        flex-shrink: 0;
    }

    #copiloto-map-box {
        width: 100% !important;
        height: 100% !important;
    }
</style>

<div class="copiloto-widget-container">
    <div class="copiloto-window" id="copilotoWindowBox">
        <!-- 8 CAPAS DE REDIMENSIONADO EN BORDES Y ESQUINAS -->
        <div class="c-resizer c-resizer-top"></div>
        <div class="c-resizer c-resizer-right"></div>
        <div class="c-resizer c-resizer-bottom"></div>
        <div class="c-resizer c-resizer-left"></div>
        <div class="c-resizer c-resizer-top-left"></div>
        <div class="c-resizer c-resizer-top-right"></div>
        <div class="c-resizer c-resizer-bottom-left"></div>
        <div class="c-resizer c-resizer-bottom-right"></div>

        <div class="copiloto-header">
            <span>Viajero Inkaiko IA</span>
            <button type="button" class="copiloto-close-btn" onclick="copilotoToggle(event)">&times;</button>
        </div>
        
        <div class="copiloto-messages" id="copilotoMsgBox">
            <div class="c-message c-bot">
                Hola viajero! Que destino de Peru te gustaria explorar hoy?
            </div>
            <div class="typing-indicator" id="copilotoTyping">Buscando informacion...</div>
        </div>

        <div class="copiloto-input-area">
            <input type="text" id="copilotoInputField" class="copiloto-input" placeholder="Escribe tu destino (Ej: Cusco)..." onkeydown="copilotoKeyCheck(event)">
            <button type="button" class="copiloto-send-btn" onclick="copilotoSend(event)">></button>
        </div>

        <div class="copiloto-media-panel">
            <div id="copiloto-map-box"></div>
        </div>
    </div>

    <!-- Botón Flotante -->
    <button type="button" class="copiloto-toggle-btn" onclick="copilotoToggle(event)" title="Viajero IA">
        IA
    </button>
</div>

<script>
    var cMapObj = null;
    var cMapMarker = null;

    function copilotoToggle(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        var win = document.getElementById('copilotoWindowBox');
        if (!win) return;

        if (win.classList.contains('active')) {
            win.classList.remove('active');
        } else {
            win.classList.add('active');
            copilotoInitMap();
        }
    }

    function copilotoInitMap() {
        if (!cMapObj && typeof L !== 'undefined') {
            try {
                cMapObj = L.map('copiloto-map-box', { zoomControl: false }).setView([-9.189967, -75.015152], 5);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(cMapObj);
                cMapMarker = L.marker([-9.189967, -75.015152]).addTo(cMapObj).bindPopup('<b>Peru</b>').openPopup();
            } catch(e) {
                console.error("Error al inicializar Leaflet:", e);
            }
        }
        if (cMapObj) {
            setTimeout(function() { cMapObj.invalidateSize(); }, 300);
        }
    }

    function copilotoMoveMap(lat, lng, zoom, title) {
        if (!cMapObj) copilotoInitMap();
        if (cMapObj && lat && lng) {
            setTimeout(function() {
                cMapObj.invalidateSize();
                cMapObj.flyTo([lat, lng], zoom || 12, { animate: true });
                if (cMapMarker) cMapObj.removeLayer(cMapMarker);
                cMapMarker = L.marker([lat, lng]).addTo(cMapObj).bindPopup('<b>' + (title || 'Destino') + '</b>').openPopup();
            }, 150);
        }
    }

    function copilotoKeyCheck(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            e.stopPropagation();
            copilotoSend(e);
        }
    }

    function copilotoAppendMsg(text, type) {
        var box = document.getElementById('copilotoMsgBox');
        var typing = document.getElementById('copilotoTyping');
        if (!box) return;

        var msg = document.createElement('div');
        msg.className = 'c-message ' + type;
        
        var html = text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>')
            .replace(/\n/g, '<br>');

        msg.innerHTML = html;

        if (typing && typing.parentNode === box) {
            box.insertBefore(msg, typing);
        } else {
            box.appendChild(msg);
        }
        box.scrollTop = box.scrollHeight;
    }

    function copilotoSend(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        var input = document.getElementById('copilotoInputField');
        if (!input) return;

        var text = input.value.trim();
        if (!text) return;

        copilotoAppendMsg(text, 'c-user');
        input.value = '';

        var typing = document.getElementById('copilotoTyping');
        if (typing) typing.style.display = 'block';

        fetch('copiloto_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: text })
        })
        .then(function(res) {
            if (!res.ok) throw new Error("Error HTTP " + res.status);
            return res.json();
        })
        .then(function(data) {
            if (typing) typing.style.display = 'none';

            if (data && data.message) {
                copilotoAppendMsg(data.message, 'c-bot');

                if (data.map_location && data.map_location.lat && data.map_location.lng) {
                    copilotoMoveMap(
                        data.map_location.lat,
                        data.map_location.lng,
                        data.map_location.zoom || 12,
                        data.map_location.title || 'Destino'
                    );
                }
            } else {
                copilotoAppendMsg("Disculpa, podrías repetirme tu consulta?", 'c-bot');
            }
        })
        .catch(function(err) {
            if (typing) typing.style.display = 'none';
            console.error("Error en copiloto_api:", err);
            copilotoAppendMsg("Tuvimos un problema de conexion. Intenta de nuevo.", 'c-bot');
        });
    }

    /* LÓGICA DE REDIMENSIONADO MULTI-BORDE EN TIEMPO REAL */
    document.addEventListener('DOMContentLoaded', function() {
        var win = document.getElementById('copilotoWindowBox');
        if (!win) return;

        var resizers = win.querySelectorAll('.c-resizer');
        var currentResizer = null;
        var startX = 0, startY = 0, startW = 0, startH = 0;

        resizers.forEach(function(resizer) {
            resizer.addEventListener('mousedown', function(e) {
                e.preventDefault();
                e.stopPropagation();
                currentResizer = resizer;

                startX = e.clientX;
                startY = e.clientY;
                startW = win.offsetWidth;
                startH = win.offsetHeight;

                document.addEventListener('mousemove', handleMouseMove);
                document.addEventListener('mouseup', handleMouseUp);
                document.body.style.userSelect = 'none';
            });
        });

        function handleMouseMove(e) {
            if (!currentResizer) return;

            var dx = e.clientX - startX;
            var dy = e.clientY - startY;

            // Arriba
            if (currentResizer.classList.contains('c-resizer-top') || 
                currentResizer.classList.contains('c-resizer-top-left') || 
                currentResizer.classList.contains('c-resizer-top-right')) {
                var newH = startH - dy;
                if (newH >= 380 && newH <= (window.innerHeight - 120)) {
                    win.style.height = newH + 'px';
                }
            }

            // Abajo
            if (currentResizer.classList.contains('c-resizer-bottom') || 
                currentResizer.classList.contains('c-resizer-bottom-left') || 
                currentResizer.classList.contains('c-resizer-bottom-right')) {
                var newH = startH + dy;
                if (newH >= 380 && newH <= (window.innerHeight - 120)) {
                    win.style.height = newH + 'px';
                }
            }

            // Izquierda
            if (currentResizer.classList.contains('c-resizer-left') || 
                currentResizer.classList.contains('c-resizer-top-left') || 
                currentResizer.classList.contains('c-resizer-bottom-left')) {
                var newW = startW - dx;
                if (newW >= 280 && newW <= (window.innerWidth - 30)) {
                    win.style.width = newW + 'px';
                }
            }

            // Derecha
            if (currentResizer.classList.contains('c-resizer-right') || 
                currentResizer.classList.contains('c-resizer-top-right') || 
                currentResizer.classList.contains('c-resizer-bottom-right')) {
                var newW = startW + dx;
                if (newW >= 280 && newW <= (window.innerWidth - 30)) {
                    win.style.width = newW + 'px';
                }
            }

            // Reajustar mapa Leaflet
            if (cMapObj) {
                cMapObj.invalidateSize();
            }
        }

        function handleMouseUp() {
            currentResizer = null;
            document.removeEventListener('mousemove', handleMouseMove);
            document.removeEventListener('mouseup', handleMouseUp);
            document.body.style.userSelect = '';
        }
    });
</script>