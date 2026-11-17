## Plan: Aplicación web de turismo en PHP puro

TL;DR - Crear una app PHP desde cero usando MySQL, con páginas públicas para mostrar destinos, paquetes y reservas, enlazadas a tu base de datos `turismo_inkaiko`.

**Pasos**
1. Preparar la estructura del proyecto y la base de datos.
   - Crear carpetas: `includes/`, `assets/css/`, `assets/js/`, `sql/`.
   - Guardar el script SQL completo en `sql/turismo_inkaiko.sql`.
   - Importar `sql/turismo_inkaiko.sql` en MySQL y verificar que la base de datos `turismo_inkaiko` se crea correctamente.

2. Crear configuración y conexión a MySQL.
   - `includes/config.php` para datos de conexión (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
   - `includes/db.php` con una clase o función `get_db_connection()` que devuelva un objeto PDO.
   - `includes/functions.php` con funciones reutilizables para consultar paquetes, destinos, testimonios y crear reservas.

3. Desarrollar páginas públicas principales.
   - `index.php`: Landing page con diseño de hero + buscador + destinos populares + tours destacados + multimedia + testimonios.
   - `destinos.php`: Listado de destinos por región con cards e imágenes, usando `regiones` y `destinos`.
   - `paquete.php`: Página de detalle de paquete que recibe `id` o `slug`, muestra imágenes, descripción, itinerario, incluye/no incluye, tarifas de hoteles y formulario de reserva.
   - `reserva.php`: Procesa formulario de reserva, valida entrada, crea registro en `reservas`, y muestra confirmación.

4. Implementar la parte visual.
   - `assets/css/style.css`: Estilos inspirados en los diseños adjuntos (paleta clara, tarjetas, botones rojos, secciones blancas con sombras suaves).
   - `assets/js/main.js`: Interacciones simples para abrir/cerrar menú, filtros y validaciones básicas del formulario.
   - Usar HTML/PHP semántico para que el diseño sea fiel al ejemplo: hero grande, cards de tours, estadísticas, galería, testimonios.

5. Agregar funciones de búsqueda y filtrado básicos.
   - Búsqueda por texto en `index.php` o `destinos.php` que filtre paquetes/destinos.
   - Filtro opcional por región o categoría si lo deseas.

6. Verificar funcionalidad básica.
   - Abrir `index.php` en servidor local PHP y verificar carga de datos.
   - Abrir `paquete.php?id=<n>` para varios paquetes.
   - Enviar reserva y comprobar registro en tabla `reservas`.

**Archivos relevantes**
- `sql/turismo_inkaiko.sql` — el script completo de creación e inserción de datos.
- `includes/config.php` — configuración de conexión a MySQL.
- `includes/db.php` — conexión PDO y manejo de errores.
- `includes/functions.php` — consultas SQL reutilizables.
- `index.php` — página principal pública.
- `destinos.php` — listado de destinos.
- `paquete.php` — detalle del paquete.
- `reserva.php` — procesar reservas.
- `assets/css/style.css` — estilos del sitio.
- `assets/js/main.js` — scripts ligeros.

**Verificación**
1. Importar el SQL en MySQL y verificar que hay tablas `paquetes`, `destinos`, `reservas`, etc.
2. Ejecutar el proyecto en un servidor local PHP (XAMPP, WAMP o PHP integrado) y abrir `index.php`.
3. Revisar que los datos del paquete y destino aparezcan correctamente.
4. Enviar una reserva de prueba y confirmar que se guarda en `reservas`.

**Decisiones**
- Usar PHP puro en lugar de framework, según tu preferencia.
- Iniciar con páginas públicas y reserva; el panel administrador queda para una segunda fase si lo deseas.
- Reusar los datos reales en la base de datos para mostrar contenido dinámico.

**Consideraciones**
1. Si quieres, puedo detallar el siguiente paso y escribir el primer conjunto de archivos PHP y CSS completo.
2. Si necesitas autenticación o panel administrativo más adelante, lo agregamos en fase 2.
3. Si prefieres un diseño aún más cercano a las capturas, definiré secciones exactas y componentes CSS.
