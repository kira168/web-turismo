<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Nosotros - Turismo Inkaiko Group';

if (function_exists('recordPageView')) {
    recordPageView('Nosotros', 'AboutUs');
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<style>
.scroll-top, .back-to-top, a[href="#top"], .btn-scroll-top, #scrollTop, .scroll-to-top, .scrollup {
    display: none !important;
}

:root {
    --nk-primary: #0f2942;
    --nk-gold: #d4a017;
    --nk-gold-light: rgba(212, 160, 23, 0.18);
    --nk-bg-light: #f8fafc;
}

.nk-dyn-wrapper {
    width: 100%;
    margin: 0;
    padding: 0 0 60px 0;
    background: #ffffff;
    color: #1e293b;
    box-sizing: border-box;
}

/* 1. HERO LUMINOSO SIN IMÁGENES DE FONDO */
.nk-about-hero-clean {
    position: relative;
    width: 100%;
    background: #ffffff;
    padding: 40px 0 35px 0;
    border-bottom: 1px solid #f1f5f9;
}

.nk-hero-p-grid {
    display: grid;
    grid-template-columns: 0.95fr 1.05fr;
    gap: 36px;
    align-items: stretch;
}

.nk-hero-p-left {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
}

.nk-hero-p-badge {
    background: #fef9c3;
    border: 1px solid #d4a017;
    color: #854d0e;
    font-size: 0.82rem;
    font-weight: 800;
    padding: 7px 18px;
    border-radius: 50px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 16px;
    display: inline-flex;
    align-items: center;
}

.nk-hero-p-left h1 {
    font-size: 2.85rem;
    font-weight: 800;
    color: var(--nk-primary) !important;
    margin-bottom: 16px;
    line-height: 1.15;
    letter-spacing: -0.5px;
}

.nk-hero-p-left p {
    font-size: 1.1rem;
    line-height: 1.65;
    color: #475569;
    margin-bottom: 26px;
}

.nk-btn-navy {
    background: var(--nk-primary);
    color: #ffffff !important;
    padding: 13px 26px;
    border-radius: 8px;
    font-size: 0.98rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.25s ease;
    display: inline-block;
}

.nk-btn-navy:hover {
    background: #1e3a5f;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(15, 41, 66, 0.2);
}

/* TARJETA DERECHA EXTENDIDA */
.nk-hero-p-card {
    background: #ffffff;
    border: 1.5px solid rgba(212, 160, 23, 0.35);
    border-radius: 18px;
    padding: 28px 32px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 18px;
}

.nk-p-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    text-align: center;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 18px;
}

.nk-p-stat {
    display: flex;
    flex-direction: column;
}

.nk-p-stat strong {
    font-size: 2.1rem;
    font-weight: 800;
    color: var(--nk-gold);
    line-height: 1;
    margin-bottom: 4px;
}

.nk-p-stat span {
    font-size: 0.78rem;
    font-weight: 800;
    color: var(--nk-primary);
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.nk-p-features {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.nk-p-feat-item {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f8fafc;
    padding: 13px 15px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
}

.nk-p-feat-icon {
    width: 38px;
    height: 38px;
    min-width: 38px;
    background: #ffffff;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    box-shadow: 0 2px 5px rgba(0,0,0,0.04);
    border: 1px solid #cbd5e1;
}

.nk-p-feat-info strong {
    display: block;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--nk-primary);
    line-height: 1.25;
    margin-bottom: 3px;
}

.nk-p-feat-info span {
    font-size: 0.82rem;
    color: #64748b;
    line-height: 1.25;
    display: block;
}

/* CONTENEDOR PRINCIPAL */
.nk-dyn-container {
    max-width: 1320px !important;
    margin: 35px auto 0 auto !important;
    padding: 0 24px !important;
    box-sizing: border-box;
}

/* 2. MISIÓN Y VISIÓN */
.nk-dyn-mv-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 28px;
    margin-bottom: 50px;
}

.nk-dyn-card-vertical {
    background: #ffffff;
    border-radius: 18px;
    border: 1.5px solid var(--nk-gold-light);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: all 0.3s ease;
}

.nk-dyn-card-vertical:hover {
    border-color: var(--nk-gold);
    box-shadow: 0 8px 22px rgba(212, 160, 23, 0.12);
    transform: translateY(-3px);
}

.nk-dyn-img-top {
    width: 100%;
    height: 270px;
    overflow: hidden;
    background: #f1f5f9;
}

.nk-dyn-img-top img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.nk-dyn-card-body {
    padding: 26px 30px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    flex-grow: 1;
}

.nk-dyn-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
}

.nk-dyn-icon {
    width: 40px;
    height: 40px;
    min-width: 40px;
    background: rgba(212, 160, 23, 0.12);
    border: 1.5px solid var(--nk-gold);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.nk-dyn-card-body h2 {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--nk-primary);
    margin: 0;
    line-height: 1.2;
}

.nk-dyn-card-body p {
    font-size: 1rem;
    line-height: 1.6;
    color: #334155;
    margin: 0;
}

.nk-mv-list {
    list-style: none;
    padding: 0;
    margin: 6px 0 0 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.nk-mv-list li {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--nk-primary);
    display: flex;
    align-items: center;
    gap: 8px;
}

/* 3. VALORES INSTITUCIONALES */
.nk-dyn-section-title {
    text-align: center;
    margin-bottom: 30px;
}

.nk-dyn-section-title h2 {
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--nk-primary);
}

.nk-dyn-valores-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 50px;
}

.nk-dyn-valor-box {
    background: #ffffff;
    border-radius: 14px;
    padding: 24px 20px;
    border: 1.5px solid var(--nk-gold-light);
    text-align: center;
    transition: all 0.3s ease;
}

.nk-dyn-valor-box:hover {
    border-color: var(--nk-gold);
    transform: translateY(-3px);
}

.nk-dyn-v-icon {
    font-size: 2.2rem;
    margin-bottom: 10px;
    display: inline-block;
}

.nk-dyn-valor-box h3 {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--nk-primary);
    margin-bottom: 8px;
}

.nk-dyn-valor-box p {
    font-size: 0.95rem;
    color: #475569;
    line-height: 1.5;
    margin: 0;
}

/* Estilos para Sección Políticas & PDFs */
.nk-docs-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 50px;
}

.nk-doc-card {
    background: #ffffff;
    border: 1.5px solid var(--nk-gold-light);
    border-radius: 16px;
    padding: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    transition: all 0.3s ease;
}

.nk-doc-card:hover {
    border-color: var(--nk-gold);
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(212, 160, 23, 0.12);
}

.nk-doc-info {
    display: flex;
    align-items: center;
    gap: 16px;
}

.nk-doc-icon {
    font-size: 2.5rem;
    line-height: 1;
}

.nk-doc-text h3 {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--nk-primary);
    margin: 0 0 4px 0;
}

.nk-doc-text p {
    font-size: 0.88rem;
    color: #64748b;
    margin: 0;
}

/* DISEÑO DEL BOTÓN DE DESCARGA */
.nk-doc-btn {
    background: linear-gradient(135deg, #d4a017 0%, #b8860b 100%);
    color: #ffffff !important;
    padding: 12px 22px;
    border-radius: 10px;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    font-size: 0.9rem;
    font-weight: 800;
    letter-spacing: 0.5px;
    text-decoration: none;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 14px rgba(212, 160, 23, 0.35);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.nk-doc-btn:hover {
    background: linear-gradient(135deg, #0f2942 0%, #1e3a5f 100%);
    color: #ffffff !important;
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 6px 18px rgba(15, 41, 66, 0.35);
}

/* 4. RECONOCIMIENTOS */
.nk-dyn-premios-section {
    margin-bottom: 50px;
}

.nk-dyn-premios-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.nk-dyn-premio-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1.5px solid var(--nk-gold-light);
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.nk-dyn-premio-card:hover {
    border-color: var(--nk-gold);
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(212, 160, 23, 0.15);
}

.nk-dyn-premio-img-wrapper {
    position: relative;
    width: 100%;
    height: 220px;
    background: #f1f5f9;
}

.nk-dyn-premio-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.nk-dyn-premio-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex-grow: 1;
}

.nk-dyn-premio-body h3 {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--nk-primary);
    margin: 0;
    line-height: 1.35;
}

.nk-dyn-premio-body p {
    font-size: 0.95rem;
    color: #475569;
    line-height: 1.5;
    margin: 0;
}

/* 5. SEDE CENTRAL & EQUIPO DIRECTIVO */
.nk-dyn-info-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 30px 36px;
    border: 1.5px solid var(--nk-gold-light);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.03);
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.nk-dyn-info-header h3 {
    font-size: 2rem;
    font-weight: 800;
    color: var(--nk-primary);
    margin: 0 0 4px 0;
}

.nk-dyn-info-header p {
    color: #64748b;
    font-size: 1.05rem;
    margin: 0;
}

.nk-team-section-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.nk-dyn-c-card {
    background: var(--nk-bg-light);
    border-radius: 14px;
    padding: 22px;
    border: 1px solid #cbd5e1;
    border-left: 5px solid var(--nk-gold);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.25s ease;
    min-height: 170px;
    box-sizing: border-box;
}

.nk-dyn-c-card:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    background: #ffffff;
    transform: translateY(-2px);
}

.nk-dyn-c-card label {
    font-size: 0.82rem;
    font-weight: 800;
    color: var(--nk-gold);
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 6px;
    display: block;
}

.nk-dyn-c-card strong {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--nk-primary);
    margin-bottom: 14px;
    display: block;
    line-height: 1.3;
}

.nk-dyn-c-detail {
    display: flex;
    flex-direction: column;
    gap: 8px;
    font-size: 0.95rem;
    color: #334155;
}

.nk-dyn-c-detail a {
    color: var(--nk-primary);
    text-decoration: none;
    font-weight: 700;
}

.nk-dyn-c-detail a:hover {
    color: var(--nk-gold);
}

.nk-bottom-split {
    display: grid;
    grid-template-columns: 1.35fr 0.65fr;
    gap: 24px;
    align-items: stretch;
}

.nk-office-banner {
    background: var(--nk-bg-light);
    border: 1px solid #cbd5e1;
    border-left: 5px solid var(--nk-primary);
    border-radius: 14px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.nk-office-info label {
    font-size: 0.82rem;
    font-weight: 800;
    color: var(--nk-gold);
    text-transform: uppercase;
}

.nk-office-info strong {
    font-size: 1.3rem;
    font-weight: 800;
    color: var(--nk-primary);
    display: block;
    margin: 2px 0 6px 0;
}

.nk-office-details {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 0.95rem;
    color: #334155;
    font-weight: 600;
}

.nk-map-preview-box {
    position: relative;
    width: 400px;
    height: 150px;
    max-width: 100%;
    border-radius: 12px;
    overflow: hidden;
    border: 1.5px solid var(--nk-gold);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
    flex-shrink: 0;
}

.nk-map-preview-box iframe {
    width: 100%;
    height: 100%;
    border: 0;
}

.nk-map-overlay-link {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 41, 66, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-weight: 800;
    font-size: 0.92rem;
    text-decoration: none;
    text-shadow: 0 2px 4px rgba(0,0,0,0.8);
}

.nk-dyn-badges-card {
    background: var(--nk-bg-light);
    border-radius: 14px;
    padding: 18px 22px;
    border: 1.5px solid var(--nk-gold-light);
    display: flex;
    flex-direction: column;
    gap: 12px;
    justify-content: center;
}

.nk-dyn-badges-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.nk-dyn-badges-card h4 {
    color: var(--nk-primary);
    font-size: 1.1rem;
    font-weight: 800;
    margin: 0;
}

/* BOTÓN DE CONTACTAR */
.nk-map-direct-btn {
    background: linear-gradient(135deg, #d4a017 0%, #b8860b 100%);
    color: #ffffff !important;
    padding: 10px 22px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 800;
    letter-spacing: 0.8px;
    text-decoration: none;
    display: inline-block;
    white-space: nowrap;
    text-align: center;
    transition: all 0.25s ease;
    box-shadow: 0 4px 12px rgba(212, 160, 23, 0.35);
    flex-shrink: 0;
}

.nk-map-direct-btn:hover {
    background: linear-gradient(135deg, #0f2942 0%, #1e3a5f 100%);
    color: #ffffff !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(15, 41, 66, 0.3);
}

.nk-dyn-logos-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.nk-dyn-logo-box {
    background: #ffffff;
    border: 1px solid var(--nk-gold-light);
    border-radius: 8px;
    padding: 8px 10px;
    flex: 1;
    text-align: center;
    font-weight: 800;
    font-size: 0.9rem;
    color: var(--nk-primary);
}

/* RESPONSIVE */
@media (max-width: 1024px) {
    .nk-hero-p-grid, .nk-team-section-grid, .nk-bottom-split { grid-template-columns: 1fr; }
    .nk-p-features { grid-template-columns: 1fr; }
}

@media (max-width: 992px) {
    .nk-dyn-mv-section, .nk-docs-grid { grid-template-columns: 1fr; }
    .nk-dyn-premios-grid, .nk-dyn-valores-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 640px) {
    .nk-p-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .nk-office-banner { flex-direction: column; }
    .nk-map-preview-box { width: 100%; height: 180px; }
    .nk-doc-card { flex-direction: column; text-align: center; }
    .nk-doc-info { flex-direction: column; }
}
</style>

<div class="nk-dyn-wrapper">
    <!-- HERO -->
    <section class="nk-about-hero-clean">
        <div class="nk-dyn-container" style="margin-top: 0 !important;">
            <div class="nk-hero-p-grid">
                <!-- Izquierda -->
                <div class="nk-hero-p-left">
                    <span class="nk-hero-p-badge">
                        AGENCIA OPERADORA OFICIAL
                    </span>
                    <h1>TURISMO INKAIKO </h1>
                    <p>Más de cuatro décadas diseñando viajes culturales, vivenciales y de aventura en todo el Perú. Garantizamos respaldo institucional, atención personalizada y guías cualificados en cada itinerario.</p>
                    
                    <div class="nk-hero-p-btns">
                        <a href="contacto.php" class="nk-btn-navy">Contactar con la Agencia →</a>
                    </div>
                </div>

                <!-- Derecha -->
                <div class="nk-hero-p-card">
                    <div class="nk-p-stats-grid">
                        <div class="nk-p-stat">
                            <strong>40+</strong>
                            <span>Años Trayectoria</span>
                        </div>
                        <div class="nk-p-stat">
                            <strong>100%</strong>
                            <span>Guías Oficiales</span>
                        </div>
                        <div class="nk-p-stat">
                            <strong>24/7</strong>
                            <span>Soporte en Ruta</span>
                        </div>
                        <div class="nk-p-stat">
                            <strong>Lima</strong>
                            <span>Miraflores HQ</span>
                        </div>
                    </div>

                    <div class="nk-p-features">
                        <div class="nk-p-feat-item">
                            <div class="nk-p-feat-icon">🏛️</div>
                            <div class="nk-p-feat-info">
                                <strong>Operación Certificada</strong>
                                <span>MINCETUR, APAVIT e IATA.</span>
                            </div>
                        </div>

                        <div class="nk-p-feat-item">
                            <div class="nk-p-feat-icon">🌐</div>
                            <div class="nk-p-feat-info">
                                <strong>Red B2B Internacional</strong>
                                <span>Alianzas con agencias globales.</span>
                            </div>
                        </div>

                        <div class="nk-p-feat-item">
                            <div class="nk-p-feat-icon">📍</div>
                            <div class="nk-p-feat-info">
                                <strong>Cobertura Nacional</strong>
                                <span>Cusco, Puno, Arequipa y más.</span>
                            </div>
                        </div>

                        <div class="nk-p-feat-item">
                            <div class="nk-p-feat-icon">👥</div>
                            <div class="nk-p-feat-info">
                                <strong>Atención Bilingüe</strong>
                                <span>Personal especializado.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="nk-dyn-container">
        <!-- MISIÓN Y VISIÓN -->
        <section class="nk-dyn-mv-section">
            <div class="nk-dyn-card-vertical">
                <div class="nk-dyn-img-top">
                    <img src="assets/images/turismo-inkaiko-mision.jpg" 
                         onerror="this.src='turismo-inkaiko-mision.jpg'" 
                         alt="Misión Turismo Inkaiko" 
                         style="object-position: center 15%;" />
                </div>
                <div class="nk-dyn-card-body">
                    <div class="nk-dyn-card-header">
                        <div class="nk-dyn-icon">🌄</div>
                        <h2>Nuestra Misión</h2>
                    </div>
                    <p>Brindar experiencias únicas y auténticas que transmitan la esencia del Perú de ayer y hoy, generando un impacto cultural positivo en cada viajero.</p>
                    <ul class="nk-mv-list">
                        <li><span>✔</span> Operación personalizada y segura</li>
                        <li><span>✔</span> Promoción del turismo sostenible</li>
                    </ul>
                </div>
            </div>

            <div class="nk-dyn-card-vertical">
                <div class="nk-dyn-img-top">
                    <img src="assets/images/turismo-inkaiko-vision.jpg" 
                         onerror="this.src='turismo-inkaiko-vision.jpg'" 
                         alt="Visión Turismo Inkaiko" 
                         style="object-position: center 10%;" />
                </div>
                <div class="nk-dyn-card-body">
                    <div class="nk-dyn-card-header">
                        <div class="nk-dyn-icon">🌅</div>
                        <h2>Nuestra Visión</h2>
                    </div>
                    <p>Ser líder referente en la operación de servicios turísticos en América Latina, manteniendo la excelencia operativa dentro del Grupo Andino.</p>
                    <ul class="nk-mv-list">
                        <li><span>✔</span> Líderes en turismo receptivo</li>
                        <li><span>✔</span> Calidad y estándares internacionales</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- VALORES -->
        <section>
            <div class="nk-dyn-section-title">
                <h2>Nuestros Valores</h2>
            </div>
            
            <div class="nk-dyn-valores-grid">
                <div class="nk-dyn-valor-box">
                    <div class="nk-dyn-v-icon">🤝</div>
                    <h3>Responsabilidad</h3>
                    <p>Cumplimiento total de cada servicio acordado.</p>
                </div>
                <div class="nk-dyn-valor-box">
                    <div class="nk-dyn-v-icon">🏛️</div>
                    <h3>Respeto Cultural</h3>
                    <p>Preservación activa del patrimonio peruano.</p>
                </div>
                <div class="nk-dyn-valor-box">
                    <div class="nk-dyn-v-icon">🌱</div>
                    <h3>Solidaridad</h3>
                    <p>Fomento de oportunidades en zonas rurales.</p>
                </div>
                <div class="nk-dyn-valor-box">
                    <div class="nk-dyn-v-icon">🌍</div>
                    <h3>Compromiso</h3>
                    <p>Sostenibilidad en el marco de la Carta de Río.</p>
                </div>
            </div>
        </section>

        <!-- DOCUMENTOS Y POLÍTICAS REGULATORIAS CON BOTONES ESTILIZADOS -->
        <section>
            <div class="nk-dyn-section-title">
                <h2>Políticas y Cumplimiento Normativo</h2>
            </div>
            <div class="nk-docs-grid">
                <div class="nk-doc-card">
                    <div class="nk-doc-info">
                        <div class="nk-doc-icon">🔒</div>
                        <div class="nk-doc-text">
                            <h3>Protección de Datos Personales</h3>
                            <p>Políticas de tratamiento y protección de la información del cliente.</p>
                        </div>
                    </div>
                    <a href="assets/PDF/ley-proteccion-datos-personales.pdf" download="Ley-Proteccion-Datos-Personales.pdf" class="nk-doc-btn">Descargar PDF 📥</a>
                </div>

                <div class="nk-doc-card">
                    <div class="nk-doc-info">
                        <div class="nk-doc-icon">🛡️</div>
                        <div class="nk-doc-text">
                            <h3>Prevención ESNNA</h3>
                            <p>Código de conducta contra la explotación sexual de NNA en el turismo.</p>
                        </div>
                    </div>
                    <a href="assets/PDF/2018-Prevencion-ESNNA-Turismo-Viajes.pdf" download="Prevencion-ESNNA-Turismo-Viajes.pdf" class="nk-doc-btn">Descargar PDF 📥</a>
                </div>
            </div>
        </section>

        <!-- RECONOCIMIENTOS -->
        <section class="nk-dyn-premios-section">
            <div class="nk-dyn-section-title">
                <h2>Reconocimientos & Trayectoria Gremial</h2>
            </div>

            <div class="nk-dyn-premios-grid">
                <div class="nk-dyn-premio-card">
                    <div class="nk-dyn-premio-img-wrapper">
                        <img src="assets/images/APAVIP.JPG" onerror="this.src='APAVIP.JPG'" alt="Reconocimiento APAVIT" class="nk-dyn-premio-img" />
                    </div>
                    <div class="nk-dyn-premio-body">
                        <h3>Liderazgo y Participación Directiva</h3>
                        <p>Reconocimiento por nuestra activa representación y participación institucional en el gremio de APAVIT Perú.</p>
                    </div>
                </div>

                <div class="nk-dyn-premio-card">
                    <div class="nk-dyn-premio-img-wrapper">
                        <img src="assets/images/feria-apavir.jpg" onerror="this.src='feria-apavir.jpg'" alt="Feria de Turismo APAVIT" class="nk-dyn-premio-img" />
                    </div>
                    <div class="nk-dyn-premio-body">
                        <h3>Ferias & Eventos del Sector</h3>
                        <p>Presencia constante como operadores receptivos clave en las ferias nacionales e internacionales de turismo.</p>
                    </div>
                </div>

                <div class="nk-dyn-premio-card">
                    <div class="nk-dyn-premio-img-wrapper">
                        <img src="assets/images/premio.jpg" onerror="this.src='premio.jpg'" alt="Premio a la Excelencia Turística" class="nk-dyn-premio-img" />
                    </div>
                    <div class="nk-dyn-premio-body">
                        <h3>Premio a la Excelencia Operativa</h3>
                        <p>Distinción por más de 40 años dedicados al desarrollo, calidad e innovación del turismo en el Perú.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- SEDE CENTRAL -->
        <section class="nk-dyn-info-card">
            <div class="nk-dyn-info-header">
                <h3>Sede Central & Equipo Directivo</h3>
                <p>Contactos directos de nuestra plana de gestión y atención administrativa.</p>
            </div>
            
            <div class="nk-team-section-grid">
                <div class="nk-dyn-c-card">
                    <div>
                        <label>Gerente General</label>
                        <strong>Lic. Hilda Paragulla de Delgado</strong>
                    </div>
                    <div class="nk-dyn-c-detail">
                        <div>📱 <a href="tel:989003025">989 003 025</a></div>
                        <div>✉️ <a href="mailto:kdelgado@turismoinkaiko.net">kdelgado@turismoinkaiko.net</a></div>
                    </div>
                </div>

                <div class="nk-dyn-c-card">
                    <div>
                        <label>Jefe de Turismo Receptivo</label>
                        <strong>Aldo Dolci Botto</strong>
                    </div>
                    <div class="nk-dyn-c-detail">
                        <div>📱 <a href="tel:989003024">989 003 024</a></div>
                        <div>✉️ <a href="mailto:counter6@turismoinkaiko.net">counter6@turismoinkaiko.net</a></div>
                    </div>
                </div>

                <div class="nk-dyn-c-card">
                    <div>
                        <label>Supervisor General</label>
                        <strong>Luis Mendoza Portilla</strong>
                    </div>
                    <div class="nk-dyn-c-detail">
                        <div>📱 <a href="tel:989003022">989 003 022</a></div>
                        <div>✉️ <a href="mailto:lmendoza@turismoinkaiko.net">lmendoza@turismoinkaiko.net</a></div>
                    </div>
                </div>
            </div>

            <div class="nk-bottom-split">
                <div class="nk-office-banner">
                    <div class="nk-office-info">
                        <label>Oficina Principal</label>
                        <strong>Miraflores, Lima</strong>
                        <div class="nk-office-details">
                            <span>📍 Av. José Pardo 610, TS/21</span>
                            <span>🕒 Lun - Vie: 09:00 – 18:00</span>
                        </div>
                    </div>

                    <div class="nk-map-preview-box">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3900.7328122391696!2d-77.034375!3d-12.116892!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9105c81f33f67557%3A0x6fb86fa91ad448!2sAv.%20Jos%C3%A9%20Pardo%20610%2C%20Miraflores%2015074!5e0!3m2!1ses!2spe!4v1700000000000" 
                            loading="lazy"
                            allowfullscreen>
                        </iframe>
                        <a href="https://maps.google.com/?q=Av.+Jos%C3%A9+Pardo+610+Miraflores+Lima" target="_blank" rel="noopener noreferrer" class="nk-map-overlay-link">
                            🗺️ Ver Mapa Completo ↗
                        </a>
                    </div>
                </div>

                <div class="nk-dyn-badges-card">
                    <div class="nk-dyn-badges-header">
                        <h4>Acreditaciones Oficiales</h4>
                        <a href="contacto.php" class="nk-map-direct-btn">CONTACTAR</a>
                    </div>
                    <div class="nk-dyn-logos-row">
                        <div class="nk-dyn-logo-box">APAVIT</div>
                        <div class="nk-dyn-logo-box">CANATUR</div>
                        <div class="nk-dyn-logo-box">IATA</div>
                        <div class="nk-dyn-logo-box">COTAL</div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>