# CLAUDE.md

## Sobre el proyecto
Aplicación web para gestionar el inventario de una refaccionaria en Ciudad Constitución, Baja California Sur.

## Estado actual
- El proyecto empieza desde cero: todavía no hay carpetas ni código.
- Ya existe el diseño de las pantallas; úsalo como guía para construir la interfaz.
- Soy principiante en programación: explícame cada paso con palabras sencillas y dime para qué sirve cada archivo que crees.

## Tecnologías
- Lenguajes: PHP, JavaScript, HTML5, CSS
- Framework de interfaz: Bootstrap
- Base de datos: MySQL
- Gestor de paquetes: ninguno por ahora (no usar npm ni Composer sin preguntar)

## Entorno de desarrollo
- Servidor local: EasyPHP Devserver (Apache + PHP + MySQL). El proyecto vive dentro de la carpeta web de EasyPHP y se abre desde el navegador en `http://127.0.0.1/` más el nombre de la carpeta del proyecto.
- Base de datos: se administra con MySQL Workbench.
- No uses XAMPP, `php -S` ni otro servidor; todo se prueba en EasyPHP.

## Comandos importantes
- Correr en desarrollo: iniciar Apache y MySQL desde el panel de EasyPHP y abrir el proyecto en el navegador.
- Revisar sintaxis de un archivo PHP: `php -l ruta/al/archivo.php` (usando el PHP que trae EasyPHP).
- Tests: todavía no hay pruebas automatizadas.
- Lint: todavía no hay linter configurado.
- Compilar: no aplica (PHP no se compila).

<!-- TODO: anota aquí la ruta de la carpeta web de EasyPHP y el nombre de la base de datos. -->

## Estructura de carpetas
Esta es la estructura que vamos a usar. Créala poco a poco, conforme se necesite, no toda de golpe.
- `public/` → archivos accesibles desde el navegador (index.php, css, js, imágenes)
- `includes/` → conexión a la base de datos y funciones compartidas
- `database/` → scripts SQL para crear tablas y datos de ejemplo

## Convenciones de código
- Nombres de variables, funciones y tablas en inglés; comentarios en español.
- Indentación de 4 espacios en PHP y 2 espacios en HTML, CSS y JavaScript.
- Consultas a MySQL siempre con PDO y sentencias preparadas; nunca concatenar datos del usuario en el SQL.
- Escapar todo lo que se muestre en pantalla con `htmlspecialchars()`.
- Usar las clases de Bootstrap antes de escribir CSS propio.

## Reglas de trabajo
- Después de cambiar un archivo PHP, revisa su sintaxis con `php -l` antes de decir que terminaste.
- Haz cambios pequeños y explica qué cambiaste y por qué.
- Pregunta antes de instalar dependencias, borrar archivos o modificar la estructura de la base de datos.
- Si propones cambios a la base de datos, dame el script SQL para ejecutarlo en MySQL Workbench y guárdalo también en `database/`.
- Nunca subas ni muestres contraseñas, claves ni el archivo de configuración con los datos de conexión a MySQL (por ejemplo `.env` o `config.php`).
- Si algo de este archivo no coincide con el código real, avísame en lugar de adivinar.

## Cosas a tener en cuenta
- Los precios y existencias son datos sensibles del negocio: no los borres ni los reinicies en pruebas.
<!-- TODO: agrega aquí archivos que no se deben tocar, reglas del negocio (por ejemplo, cómo se calcula el stock mínimo), etc. -->
- Colores para mantener consistencia: (FFFFFF, 4F46E5, 9CA3AF, 111827, 374151, 6B7280, D1D5DB, F3F4F6).
- Organiza las interfaces dentro de las carpetas que corresponden, si algo es para el administrador, debe de ir en la carpeta de administador, no debe de jalar nada de la carpeta de almacenista ni hacer referencia a lo que el vendedor tiene a no ser que se trate de intercambio de datos a través de la base de datos o la funcionalidad del servidor, de ahí en fuera, no puede pasar de una interfaz de administrador a una de vendedor o algo así.
- Las imágenes se van a guardar en la carpeta de imágenes y solamente ahí.
- En todas las interfaces en las que se muestre un elemento que diga "Marca de agua del equipo", debe de sustituirse por la verdadera imagen del equipo, que se encuentra en la ruta C:\Program Files (x86)\EasyPHP-Devserver-17\eds-www\RegistroInventario\imagenes y se llama ImagenEquipoNF-192.
