# Refaccionaria · Empezamos de 0 (base refaccionaria2)

Qué trae esta versión:

- Un solo inicio de sesión para los 5 roles. Al entrar, cada quien llega a su pantalla.
- Administrador y gerente usan menú lateral. Cajero, vendedor y almacenista usan solo la barra de arriba.
- Cada rol tiene su carpeta: `app/Http/Controllers/<Rol>` y `resources/views/<rol>`.
- Permisos por rol (tablas `permisos` y `rolPermisos`).
- Comando para crear al primer administrador.

## 1. Borra lo viejo de tu proyecto

Abre PowerShell **dentro de la carpeta del proyecto**. Pega esto tal cual: solo borra los archivos de la lista.

```powershell
$viejos = @(
  "app\Http\Controllers\Administrador",
  "app\Http\Controllers\Login\RegistroController.php",
  "app\Models\Cliente.php", "app\Models\DetalleVenta.php", "app\Models\MovimientoStock.php",
  "app\Models\Pieza.php", "app\Models\Venta.php",
  "database\factories\ClienteFactory.php", "database\factories\PiezaFactory.php",
  "database\migrations\0001_01_01_000000_crear_tabla_trabajadores.php",
  "database\migrations\2026_11_17_000001_crear_tabla_piezas.php",
  "database\migrations\2026_11_18_000001_crear_tablas_clientes_y_ventas.php",
  "resources\views\administrador", "resources\views\login",
  "resources\views\partials\encabezado.blade.php", "resources\views\layouts\administrador.blade.php",
  "tests\Feature\ExistenciasTest.php", "tests\Feature\VentasClientesTest.php",
  "public\css\administrador", "public\javascript\administrador"
)
foreach ($v in $viejos) { if (Test-Path $v) { Remove-Item $v -Recurse -Force; "Borrado: $v" } }
```

**No borres** estas migraciones, porque son de Laravel:
- `0001_01_01_000001_create_cache_table.php`
- `0001_01_01_000002_create_jobs_table.php`

## 2. Copia los archivos nuevos

Copia todo lo que viene junto a este LEEME dentro de tu proyecto. Cuando Windows pregunte, elige **Reemplazar**.

## 3. Cambia el `.env`

Busca estas líneas y déjalas así:

```ini
DB_DATABASE=refaccionaria2
APP_LOCALE=es
AUTH_MODEL=App\Models\Trabajador
```

## 4. Crea las tablas de Laravel

```powershell
php artisan migrate
```

Este comando crea solo las tablas de Laravel: `sessions`, `cache` y `jobs`. Las 23 tablas tuyas ya existen, así que se salta su migración.

## 5. Crea al primer administrador

```powershell
php artisan trabajadores:crear
```

Te pide nombre, apellidos, RFC, teléfono y correo. Luego eliges el rol **ADMINISTRADOR** y escribes la contraseña, que no se ve mientras la escribes.

## 6. Pruébalo

```powershell
composer run dev
```

Abre http://localhost:8000 y entra con el correo y la contraseña del paso 5.

Las pruebas automáticas se corren así (no tocan tu base):

```powershell
php artisan test
```

## Qué sigue

Programar cada módulo con las tablas nuevas. Primero **Productos**.
