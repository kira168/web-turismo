SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS regiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'cliente') DEFAULT 'cliente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS destinos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    region_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    tag VARCHAR(50),
    rating DECIMAL(2,1),
    img VARCHAR(255),
    packages INT DEFAULT 0,
    description TEXT
);

CREATE TABLE IF NOT EXISTS paquetes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    destino VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    duration INT,
    durationLabel VARCHAR(50),
    price DECIMAL(10,2) NOT NULL,
    oldPrice DECIMAL(10,2),
    promo BOOLEAN DEFAULT FALSE,
    discount INT DEFAULT 0,
    img VARCHAR(255),
    rating DECIMAL(2,1),
    reviews INT DEFAULT 0,
    popular BOOLEAN DEFAULT FALSE,
    priceLabel VARCHAR(50),
    description TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS paquete_imagenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paquete_id INT NOT NULL,
    img_url VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS paquete_incluye (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paquete_id INT NOT NULL,
    included_item VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS paquete_no_incluye (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paquete_id INT NOT NULL,
    not_included_item VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS tarifas_hoteles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paquete_id INT NOT NULL,
    hotel VARCHAR(150) NOT NULL,
    simple DECIMAL(10,2),
    doble DECIMAL(10,2),
    triple DECIMAL(10,2)
);

CREATE TABLE IF NOT EXISTS page_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_url VARCHAR(255) NOT NULL,
    view_date DATE NOT NULL,
    views INT DEFAULT 1,
    UNIQUE KEY unique_page_date (page_url, view_date)
);

CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(25),
    message TEXT,
    status ENUM('new', 'contacted', 'converted', 'closed') DEFAULT 'new',
    usuario_id INT,
    paquete_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS itinerario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paquete_id INT NOT NULL,
    day INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT
);

CREATE TABLE IF NOT EXISTS testimonios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(100),
    stars INT DEFAULT 5,
    text TEXT,
    avatar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS multimedia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    img VARCHAR(255) NOT NULL,
    title VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_reserva VARCHAR(20) UNIQUE NOT NULL,
    usuario_id INT,
    nombre_cliente VARCHAR(100) NOT NULL,
    email_cliente VARCHAR(150) NOT NULL,
    telefono VARCHAR(25) NOT NULL,
    paquete_id INT NOT NULL,
    fecha_salida DATE NOT NULL,
    cantidad_personas INT DEFAULT 1,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'confirmada', 'pagada', 'cancelada', 'completada') DEFAULT 'pendiente',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_id VARCHAR(255),
    payment_method VARCHAR(50),
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS resenas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    paquete_id INT NOT NULL,
    calificacion INT,
    comentario TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review (usuario_id, paquete_id)
);

CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    mensaje TEXT,
    tipo VARCHAR(50),
    leida BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO usuarios (name, email, password_hash, role) VALUES
('Administrador', 'admin@turismoinkaiko.com', '$2y$10$O9oKO6pJcnwdxCr7EuJeKOqW9E.dGEEA8x0X3p0qJK8q0h3S6sLAK', 'admin');

INSERT INTO regiones (name) VALUES
('Costa'),
('Sierra'),
('Selva');

INSERT INTO destinos (region_id, name, tag, rating, img, packages, description) VALUES
(2, 'Cusco', 'Historia y cultura', '4.9', 'https://images.unsplash.com/photo-1508830524289-0adcbe822b40?w=1200&q=80', 8, 'Ciudad imperial con ruinas incas, mercados y gastronomía andina.'),
(2, 'Machu Picchu', 'Maravilla ancestral', '5.0', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?w=1200&q=80', 12, 'La ciudad perdida de los incas, un destino imperdible para viajeros.'),
(1, 'Lima', 'Costa moderna', '4.5', 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=1200&q=80', 5, 'Capital con playas, gastronomía premium y vida nocturna vibrante.'),
(3, 'Iquitos', 'Aventura selvática', '4.7', 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?w=1200&q=80', 4, 'Puerta de entrada a la Amazonía peruana y sus experiencias únicas.');

INSERT INTO paquetes (id, name, destino, category, duration, durationLabel, price, oldPrice, promo, discount, img, rating, reviews, popular, priceLabel, description, notes) VALUES
(1, 'Aventura en Machu Picchu', 'Machu Picchu', 'Arqueológico', 4, '4 días / 3 noches', 540.00, 620.00, TRUE, 13, 'https://images.unsplash.com/photo-1500534623283-312aade485b7?w=1200&q=80', '4.9', 120, 1, '$540', 'Visita guiada al Santuario Histórico de Machu Picchu con tren y hotel incluido.', 'Lleva ropa cómoda y protector solar.'),
(2, 'Cusco y Valle Sagrado', 'Cusco', 'Cultural', 5, '5 días / 4 noches', 720.00, 780.00, TRUE, 8, 'https://images.unsplash.com/photo-1483683804023-6ccdb62f86ef?w=1200&q=80', '4.8', 98, 1, '$720', 'Explora los templos, mercados y tradiciones del Valle Sagrado.', 'Incluye desayuno diario y transporte local.'),
(3, 'Escapada a Lima', 'Lima', 'Urbano', 3, '3 días / 2 noches', 220.00, 260.00, FALSE, 0, 'https://images.unsplash.com/photo-1501594907352-04cda38ebc29?w=1200&q=80', '4.6', 45, 0, '$220', 'Tour gastronómico y recorrido por los mejores barrios de Lima.', 'No incluye vuelos.'),
(4, 'Aventura en la Amazonía', 'Iquitos', 'Naturaleza', 6, '6 días / 5 noches', 860.00, 950.00, TRUE, 10, 'https://images.unsplash.com/photo-1495567720989-cebdbdd97913?w=1200&q=80', '4.7', 67, 1, '$860', 'Expedición por la selva con lodge, paseos en bote y observación de fauna.', 'Recomendado para viajeros con espíritu aventurero.');

INSERT INTO paquete_imagenes (paquete_id, img_url) VALUES
(1, 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?w=1200&q=80'),
(1, 'https://images.unsplash.com/photo-1529429617124-3da9d69a6b45?w=1200&q=80'),
(2, 'https://images.unsplash.com/photo-1483683804023-6ccdb62f86ef?w=1200&q=80'),
(2, 'https://images.unsplash.com/photo-1504257432389-52343af06ae3?w=1200&q=80'),
(3, 'https://images.unsplash.com/photo-1501594907352-04cda38ebc29?w=1200&q=80'),
(3, 'https://images.unsplash.com/photo-1494526585095-c41746248156?w=1200&q=80'),
(4, 'https://images.unsplash.com/photo-1495567720989-cebdbdd97913?w=1200&q=80'),
(4, 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?w=1200&q=80');

INSERT INTO paquete_incluye (paquete_id, included_item) VALUES
(1, 'Entradas a Machu Picchu'),
(1, 'Alojamiento 3 noches en hotel 3 estrellas'),
(1, 'Desayunos incluidos'),
(1, 'Transporte en tren ida y vuelta'),
(2, 'Guía turístico en español'),
(2, 'Entradas a Ollantaytambo y Pisac'),
(2, 'Traslados aeropuerto - hotel - tours'),
(3, 'Tour gastronómico con degustaciones'),
(3, 'Visita a Miraflores y Barranco'),
(4, 'Excursiones por la Amazonía'),
(4, 'Alojamiento en ecolodge'),
(4, 'Paseos en bote y avistamiento de fauna');

INSERT INTO paquete_no_incluye (paquete_id, not_included_item) VALUES
(1, 'Bebidas no alcohólicas'),
(1, 'Propinas'),
(2, 'Vuelos internacionales'),
(3, 'Comidas especiales fuera del tour'),
(4, 'Seguro de viaje');

INSERT INTO tarifas_hoteles (paquete_id, hotel, simple, doble, triple) VALUES
(1, 'Hotel Andino', 90.00, 120.00, 150.00),
(2, 'Hotel Colonial', 110.00, 140.00, 170.00),
(3, 'Hotel Boutique Lima', 75.00, 95.00, 120.00),
(4, 'Lodge Amazonas', 130.00, 160.00, 190.00);

INSERT INTO itinerario (paquete_id, day, title, description) VALUES
(1, 1, 'Llegada a Cusco', 'Recepción en aeropuerto y traslado al hotel, tiempo libre para aclimatación.'),
(1, 2, 'Tour arqueológico', 'Visita a Sacsayhuamán, Qenqo y transporte a Aguas Calientes.'),
(1, 3, 'Machu Picchu', 'Visita guiada a Machu Picchu y tiempo libre para explorar.'),
(1, 4, 'Regreso a casa', 'Desayuno y traslado al aeropuerto de Cusco.'),
(2, 1, 'Cusco histórico', 'Recorrido por la Plaza de Armas y museos inolvidables.'),
(2, 2, 'Valle Sagrado', 'Visita a Pisac y Ollantaytambo con mercado tradicional.'),
(2, 3, 'Maras y Moray', 'Exploración de las salineras y terrazas agrícolas.'),
(2, 4, 'Tiempo libre', 'Opcional: clase de cocina o visita a artistas locales.'),
(2, 5, 'Salida', 'Traslado al aeropuerto de Cusco.'),
(3, 1, 'Llegada a Lima', 'Recepción y tour por Miraflores.'),
(3, 2, 'Barranco y sabores', 'Recorrido por el barrio bohemio y degustación local.'),
(3, 3, 'Rumbo a casa', 'Desayuno y traslado al aeropuerto.'),
(4, 1, 'Iquitos y Amazonía', 'Llegada a Iquitos y traslado al lodge.'),
(4, 2, 'Río Amazonas', 'Paseo en bote y avistamiento de fauna.'),
(4, 3, 'Aldea nativa', 'Visita cultural a una comunidad local.'),
(4, 4, 'Senderismo', 'Caminata por la selva y observación de aves.'),
(4, 5, 'Descanso', 'Tiempo libre en el lodge.'),
(4, 6, 'Regreso', 'Traslado al aeropuerto de Iquitos.');

INSERT INTO testimonios (name, role, stars, text, avatar) VALUES
('María Pérez', 'Viajeras felices', 5, 'Excelente atención y un itinerario muy bien organizado. ¡Repetiría seguro!', 'https://images.unsplash.com/photo-1502685104226-ee32379fefbe?w=200&q=80'),
('Juan Díaz', 'Aventurero', 5, 'Machu Picchu fue impresionante y el guía explicó todo con claridad.', 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=200&q=80'),
('Ana Torres', 'Familia', 4, 'La experiencia en Lima fue muy completa y la comida deliciosa.', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=200&q=80'),
('Ricardo Gómez', 'Naturalista', 5, 'La expedición en la selva superó mis expectativas. Muy recomendable.', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=200&q=80');

INSERT INTO multimedia (img, title) VALUES
('https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?w=1200&q=80', 'Machu Picchu al amanecer'),
('https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=1200&q=80', 'Gastronomía en Lima'),
('https://images.unsplash.com/photo-1483683804023-6ccdb62f86ef?w=1200&q=80', 'Valle Sagrado'),
('https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?w=1200&q=80', 'Aventura en la Amazonía');
