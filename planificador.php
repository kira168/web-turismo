<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Planificador de Viajes IA';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    /* Estilos exclusivos para la página del planificador */
    html, body {
        margin: 0;
        height: 100%;
        overflow: hidden; /* Evitamos el scroll de la página completa */
    }

    /* Ocultar el footer y los botones flotantes de la plantilla original */
    .site-footer, .floating-container, .ai-chat-widget {
        display: none !important;
    }

    .planner-container {
        display: flex;
        height: calc(100vh - 70px); /* 70px es un aproximado del alto del header */
        margin-top: 70px;
        background: #f9fafb;
    }

    /* --- PANEL IZQUIERDO: CHAT --- */
    .chat-panel {
        width: 45%;
        min-width: 350px;
        max-width: 550px;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        border-right: 1px solid #e5e7eb;
        height: 100%;
    }

    .chat-header {
        padding: 20px;
        border-bottom: 1px solid #e5e7eb;
        background: #fff;
    }
    
    .chat-header h1 {
        font-size: 1.25rem;
        font-weight: 700;
        margin: 0;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .message {
        max-width: 90%;
        padding: 14px 18px;
        border-radius: 16px;
        line-height: 1.6;
        font-size: 0.95rem;
    }
    
    /* El bot se parece a ChatGPT/Mindtrip, texto alineado a la izquierda sin fondo o fondo muy sutil */
    .message.bot {
        background: transparent;
        color: #1f2937;
        align-self: flex-start;
        padding-left: 0;
        padding-right: 0;
        max-width: 95%;
    }
    
    .message.bot h3 { margin-top: 0; font-size: 1.1rem; font-weight: 700; }
    .message.bot p { margin: 0 0 12px 0; }
    .message.bot p:last-child { margin: 0; }
    .message.bot ul { padding-left: 20px; margin: 12px 0; }
    .message.bot li { margin-bottom: 8px; }
    .message.bot strong { color: var(--text-dark); font-weight: 700; }

    .message.user {
        background: #f3f4f6;
        color: #1f2937;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
        font-weight: 500;
    }

    .chat-input-container {
        padding: 20px;
        background: #fff;
        border-top: 1px solid #e5e7eb;
    }

    .chat-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        background: #f9fafb;
        border: 1px solid #d1d5db;
        border-radius: 30px;
        padding: 10px 20px;
        transition: all 0.3s;
    }
    
    .chat-input-wrapper:focus-within {
        border-color: var(--primary);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.1);
    }

    .chat-input-wrapper input {
        flex: 1;
        border: none;
        background: transparent;
        padding: 8px 0;
        font-size: 1rem;
        outline: none;
    }

    .chat-input-wrapper button {
        background: #1f2937;
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        margin-left: 10px;
    }
    .chat-input-wrapper button:hover {
        background: var(--primary);
        transform: scale(1.05);
    }

    /* --- PANEL DERECHO: MEDIA (IMG + MAPA) --- */
    .media-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        height: 100%;
        background: #e5e7eb;
    }

    .image-section {
        flex: 1.2;
        background-color: #d1d5db;
        background-image: url('https://images.unsplash.com/photo-1526392060635-9d6019884377?q=80&w=2070&auto=format&fit=crop');
        background-size: cover;
        background-position: center;
        position: relative;
        transition: background-image 0.5s ease-in-out;
    }
    
    .image-overlay {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        background: linear-gradient(transparent, rgba(0,0,0,0.7));
        padding: 30px 20px 20px;
        color: white;
    }
    .image-overlay h2 {
        margin: 0;
        font-size: 2rem;
        font-weight: 700;
        text-shadow: 1px 1px 4px rgba(0,0,0,0.8);
    }

    .map-section {
        flex: 1;
        background: #f3f4f6;
        border-top: 4px solid #fff;
    }
    
    .map-section iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    /* --- INDICADOR DE ESCRITURA --- */
    .typing-indicator {
        display: flex;
        gap: 5px;
        padding: 14px 0;
        align-self: flex-start;
        align-items: center;
    }
    .typing-dot {
        width: 8px;
        height: 8px;
        background: #9ca3af;
        border-radius: 50%;
        animation: typing 1.4s infinite ease-in-out both;
    }
    .typing-dot:nth-child(1) { animation-delay: -0.32s; }
    .typing-dot:nth-child(2) { animation-delay: -0.16s; }
    @keyframes typing {
        0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
        40% { transform: scale(1); opacity: 1; }
    }

    /* RESPONSIVE */
    @media (max-width: 900px) {
        .planner-container { flex-direction: column; overflow-y: auto; height: auto; margin-top: 60px;}
        html, body { overflow: auto; }
        .chat-panel { width: 100%; max-width: 100%; height: 60vh; border-right: none; border-bottom: 1px solid #e5e7eb; }
        .media-panel { height: 60vh; flex: none; }
    }
</style>

<div class="planner-container">
    <!-- Panel Izquierdo: Chat -->
    <div class="chat-panel">
        <div class="chat-header">
            <h1>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Descubriendo el Perú
            </h1>
        </div>
        
        <div class="chat-messages" id="mainChatBody">
            <div class="message bot">
                <p>¡Hola! 👋 Soy tu Planificador Inteligente de <strong>Turismo Inkaiko</strong>.</p>
                <p>Puedo ayudarte a diseñar tu viaje perfecto. Dime, <strong>¿qué destino te gustaría explorar o qué tipo de experiencia buscas en Perú?</strong> (Ej: Qué hacer en Arequipa, Viaje a la selva de 3 días...)</p>
            </div>
        </div>
        
        <div class="chat-input-container">
            <div class="chat-input-wrapper">
                <input type="text" id="mainChatInput" placeholder="Pregunta cualquier cosa sobre Perú..." autocomplete="off">
                <button id="sendBtn" aria-label="Enviar mensaje">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px; margin-left: -2px;">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Panel Derecho: Media (IMG + MAPA) -->
    <div class="media-panel">
        <div class="image-section" id="dynamicImage">
            <div class="image-overlay">
                <h2 id="dynamicImageTitle">Turismo Inkaiko - Perú</h2>
            </div>
        </div>
        <div class="map-section">
            <iframe id="dynamicMap" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15886616.480749454!2d-83.9933061214041!3d-9.351656209590748!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9105c850c05914f5%3A0xf29e011279210648!2zUGVyw7o!5e0!3m2!1ses-419!2s!4v1700000000000!5m2!1ses-419!2s" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>
</div>

<!-- Usamos marked.js para renderizar la respuesta de Gemini (listas, negritas) -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputField = document.getElementById('mainChatInput');
    const sendBtn = document.getElementById('sendBtn');
    
    inputField.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') sendMainMessage();
    });
    
    sendBtn.addEventListener('click', sendMainMessage);
});

function sendMainMessage() {
    const input = document.getElementById('mainChatInput');
    const msg = input.value.trim();
    if (!msg) return;
    
    const body = document.getElementById('mainChatBody');
    body.innerHTML += `<div class="message user">${msg}</div>`;
    input.value = '';
    
    // Scroll to bottom
    body.scrollTop = body.scrollHeight;
    
    // Typing indicator
    const typingId = 'typing-' + Date.now();
    body.innerHTML += `
        <div id="${typingId}" class="typing-indicator">
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
        </div>`;
    body.scrollTop = body.scrollHeight;

    // Actualizar imagen y mapa (Lógica visual rápida basada en el texto del usuario)
    updateMediaPanel(msg);

    // Enviar a la IA
    fetch('chatbot.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msg, context: 'planificador_avanzado' })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById(typingId).remove();
        
        let rawReply = data.reply || 'Lo siento, no pude procesar tu solicitud.';
        
        // Formatear markdown (negritas, viñetas) usando marked.js
        let formattedReply = '';
        if (typeof marked !== 'undefined') {
            formattedReply = marked.parse(rawReply);
        } else {
            formattedReply = rawReply.replace(/\n/g, '<br>');
        }
        
        body.innerHTML += `<div class="message bot">${formattedReply}</div>`;
        body.scrollTop = body.scrollHeight;
        
        // Intentar actualizar mapa si la IA nos da pistas en su respuesta
        updateMediaPanel(rawReply); 
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById(typingId).remove();
        body.innerHTML += `<div class="message bot" style="color:#ef4444;">Lo siento, hubo un problema de conexión con el servidor.</div>`;
        body.scrollTop = body.scrollHeight;
    });
}

// Función simple para cambiar la foto y el mapa si mencionan un destino
function updateMediaPanel(text) {
    const lowerText = text.toLowerCase();
    const imageEl = document.getElementById('dynamicImage');
    const titleEl = document.getElementById('dynamicImageTitle');
    const mapEl = document.getElementById('dynamicMap');

    // Catálogo visual rápido de destinos
    const destinations = {
        'arequipa': {
            img: 'https://images.unsplash.com/photo-1580974864459-a5e22ea7c551?q=80&w=2000&auto=format&fit=crop',
            title: 'Arequipa, Ciudad Blanca 🌋',
            map: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d122396.65757788417!2d-71.62121708819543!3d-16.404010214690333!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x91424a487785b9b3%3A0xa3c4a612b9942036!2sArequipa!5e0!3m2!1ses-419!2spe!4v1715000000000!5m2!1ses-419!2spe'
        },
        'colca': {
            img: 'https://images.unsplash.com/photo-1594917531737-0cf7f1e582e0?q=80&w=2000&auto=format&fit=crop',
            title: 'Cañón del Colca 🦅',
            map: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d245842.27411699938!2d-72.03960010903399!3d-15.617300431327464!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9141f23c7b6ff6bf%3A0x2db4e819b168ff0f!2zQ2HDsW9uIGRlbCBDb2xjYQ!5e0!3m2!1ses-419!2spe!4v1715000000000!5m2!1ses-419!2spe'
        },
        'cusco': {
            img: 'https://images.unsplash.com/photo-1587595431973-160d0d94add1?q=80&w=2000&auto=format&fit=crop',
            title: 'Cusco, Capital del Imperio Inca 🏛️',
            map: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d62058.053531316524!2d-72.01524316130099!3d-13.530058567540254!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x916dd5d826598431%3A0x2aa996cc2318315d!2sCusco!5e0!3m2!1ses-419!2spe!4v1715000000000!5m2!1ses-419!2spe'
        },
        'machu picchu': {
            img: 'https://images.unsplash.com/photo-1526392060635-9d6019884377?q=80&w=2000&auto=format&fit=crop',
            title: 'Machu Picchu ⛰️',
            map: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3885.6455110594957!2d-72.5451276241031!3d-13.16314118717013!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x916d9a5f89555555%3A0x3a10370ea4a01a27!2sMachu%20Picchu!5e0!3m2!1ses-419!2spe!4v1715000000000!5m2!1ses-419!2spe'
        },
        'puno': {
            img: 'https://images.unsplash.com/photo-1600325492193-4a601bb97155?q=80&w=2000&auto=format&fit=crop',
            title: 'Puno y el Lago Titicaca 🌊',
            map: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d122692.65171732958!2d-70.10825396557813!3d-15.83907722442436!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x915d691ebddce687%3A0xcda6dc6a0904d9c0!2sPuno!5e0!3m2!1ses-419!2spe!4v1715000000000!5m2!1ses-419!2spe'
        },
        'iquitos': {
            img: 'https://images.unsplash.com/photo-1549429402-9ae126c07a0c?q=80&w=2000&auto=format&fit=crop',
            title: 'Iquitos, Amazonía 🌴',
            map: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d127409.80373801267!2d-73.34863372221293!3d-3.753381483321523!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x91ea11ccf26ea14b%3A0xa195a63901b0b4b2!2sIquitos!5e0!3m2!1ses-419!2spe!4v1715000000000!5m2!1ses-419!2spe'
        },
        'lima': {
            img: 'https://images.unsplash.com/photo-1531968840228-56832dbfa2eb?q=80&w=2000&auto=format&fit=crop',
            title: 'Lima, la Ciudad de los Reyes 🏙️',
            map: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3901.7610115065476!2d-77.04250268461536!3d-12.063014991456637!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9105c8c00c7fb8b7%3A0x6a0c0c0c0c0c0c0!2sLima!5e0!3m2!1ses-419!2spe!4v1715000000000!5m2!1ses-419!2spe'
        }
    };

    let matchedDest = null;
    for (const [key, val] of Object.entries(destinations)) {
        if (lowerText.includes(key)) {
            matchedDest = val;
            break; // Tomamos el primero que coincida
        }
    }

    if (matchedDest) {
        imageEl.style.backgroundImage = `url('${matchedDest.img}')`;
        titleEl.textContent = matchedDest.title;
        mapEl.src = matchedDest.map;
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
