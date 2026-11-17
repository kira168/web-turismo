# Turismo Inkaiko - Sistema Completo de Turismo en PHP

## 📋 Descripción
Aplicación web completa de turismo desarrollada en **PHP puro** con las siguientes características:

### 🌐 Módulo Cliente (Pública)
- ✅ Visualizar tours y destinos
- ✅ Buscar y filtrar paquetes
- ✅ Registrarse e iniciar sesión
- ✅ Realizar reservas
- ✅ Ver historial de reservas
- ✅ Notificaciones personalizadas
- ✅ Dejar reseñas y calificaciones
- ✅ Página de contacto con envío de email

### 💻 Módulo Administrador (Privada)
- ✅ Dashboard con estadísticas
- ✅ Gráficos de reservas
- ✅ Gestionar tours/paquetes
- ✅ Gestionar clientes
- ✅ Gestionar reservas (cambiar estado)
- ✅ Ver ingresos totales

## 🚀 Instalación

### 1. Preparar base de datos
```bash
# Importar el archivo SQL
mysql -u root turismo_inkaiko < sql/turismo_inkaiko.sql
```

### 2. Configurar conexión BD
Editar `includes/config.php` con tus datos:
```php
'host' => '127.0.0.1',
'dbname' => 'turismo_inkaiko',
'user' => 'root',
'pass' => 'tu_contraseña',
```

### 3. Iniciar servidor local
```bash
# En la carpeta del proyecto
php -S localhost:8000
```

### 4. Acceder a la aplicación
- **Cliente:** http://localhost:8000
- **Admin:** http://localhost:8000/admin/dashboard.php

## 👤 Credenciales de prueba

### Usuario Cliente
- Email: `cliente@turismoinkaiko.com`
- Contraseña: `cliente123`
(Crear nuevo usuario en registro.php)

### Usuario Admin
- Email: `admin@turismoinkaiko.com`
- Contraseña: `admin123`

## � Configuración de pagos con Stripe

### Instalación
```bash
# Instalar Stripe PHP SDK
composer require stripe/stripe-php
```

### Configuración
1. **Crear cuenta en Stripe:**
   - Ve a [Stripe Dashboard](https://dashboard.stripe.com/)
   - Activa modo de pruebas (test mode)

2. **Obtener claves API:**
   - Publishable key: `pk_test_...`
   - Secret key: `sk_test_...`

3. **Configurar en archivos:**
   ```php
   // En reserva.php y confirmacion.php
   \Stripe\Stripe::setApiKey('sk_test_YOUR_SECRET_KEY');
   
   // En reserva.php (JavaScript)
   const stripe = Stripe('pk_test_YOUR_PUBLISHABLE_KEY');
   ```

4. **Configurar webhook:**
   - En Stripe Dashboard > Webhooks
   - URL: `https://tu-dominio.com/stripe-webhook.php`
   - Eventos: `payment_intent.succeeded`, `payment_intent.payment_failed`
   - Copiar "Webhook signing secret" a `stripe-webhook.php`

## �📁 Estructura de carpetas
```
turismo3/
├── index.php                 # Página principal
├── destinos.php             # Listado de destinos
├── paquetes.php             # Catálogo de paquetes
├── paquete.php              # Detalle del paquete
├── reserva.php              # Procesar reserva
├── login.php                # Iniciar sesión
├── registro.php             # Crear cuenta
├── logout.php               # Cerrar sesión
├── mi-cuenta.php            # Perfil y historial
├── contacto.php             # Formulario de contacto
├── about.php                # Información sobre nosotros
│
├── admin/                   # Panel administrativo
│   ├── dashboard.php        # Dashboard principal
│   ├── tours.php            # Gestionar tours
│   ├── clientes.php         # Gestionar clientes
│   └── reservas.php         # Gestionar reservas
│
├── includes/
│   ├── config.php           # Configuración BD
│   ├── db.php               # Conexión PDO
│   ├── functions.php        # Funciones reutilizables
│   ├── header.php           # Encabezado (nav, sesión)
│   └── footer.php           # Pie de página
│
├── assets/
│   ├── css/
│   │   └── style.css        # Estilos principales
│   └── js/
│       └── main.js          # JavaScript
│
└── sql/
    └── turismo_inkaiko.sql  # Script de BD
```

## 🔧 Funcionalidades principales

### Autenticación y Usuarios
- Registro con email y contraseña
- Hashing de contraseñas con bcrypt
- Sesiones seguras en PHP
- Roles: cliente y admin

### Reservas
- Formulario de reserva completo
- Generación de número único
- Historial de reservas por usuario
- Estados: pendiente, confirmada, cancelada, completada

### Notificaciones
- Notificaciones de bienvenida
- Notificaciones de reserva
- Sistema extensible para más tipos

### Reseñas (Próximo)
- Calificación de 1-5 estrellas
- Comentarios de usuarios
- Una reseña por usuario por paquete

### Admin Dashboard
- Estadísticas en tiempo real
- Gráficos con Chart.js
- Total de usuarios, reservas e ingresos
- Control de estado de reservas

## 🎨 Diseño
- Responsivo (mobile-first)
- Variables CSS personalizadas
- Colores coherentes (rojo turismo: #be0d2d)
- Tipografía clara y legible

## 📝 Notas de desarrollo

### Extensiones futuras
1. Integración de pagos (Stripe, PayPal)
2. Sistema de descuentos y promociones
3. Email automático de confirmación
4. Exportar reportes a PDF
5. Galería de fotos ampliada
6. Sistema de comentarios en tours

### Variables de sesión
```php
$_SESSION['user_id']      // ID del usuario
$_SESSION['user_name']    // Nombre del usuario
$_SESSION['user_role']    // Rol (cliente/admin)
```

### Funciones importantes
- `createUser()` - Crear nuevo usuario
- `createReservation()` - Crear reserva
- `createNotification()` - Crear notificación
- `getReservations()` - Obtener todas las reservas
- `getAllUsers()` - Obtener clientes

## ⚠️ Seguridad
- ✅ Hashing de contraseñas
- ✅ Validación de entrada
- ✅ Protección de páginas admin
- ✅ Escapado de salida HTML
---

Desarrollado con PHP puro. Sin frameworks externos.
