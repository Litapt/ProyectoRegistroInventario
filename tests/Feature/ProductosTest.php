<?php

/*
    Pruebas automáticas de Productos del administrador: php artisan test
    Usan una base de datos temporal en memoria y una carpeta temporal para las fotos:
    NO tocan tu MySQL ni tu carpeta public.
*/

use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\MarcaVehiculo;
use App\Models\ModeloVehiculo;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Las fotos de prueba se guardan en una carpeta temporal, no en tu public/
    $this->carpetaPublica = sys_get_temp_dir() . '/refaccionaria-pruebas-' . uniqid();
    File::ensureDirectoryExists($this->carpetaPublica . '/imagenes/productos');
    $this->app->usePublicPath($this->carpetaPublica);

    $this->admin = Trabajador::factory()->administrador()->create(['correoElectronico' => 'admin@prueba.mx']);
    $this->frenos = Categoria::firstWhere('nombre', 'Frenos');
    $this->bosch = Marca::firstWhere('nombre', 'Bosch');
});

afterEach(function () {
    File::deleteDirectory($this->carpetaPublica);
});

/* id del modelo de vehículo, ej. modeloId('Nissan', 'Versa') (los pone la migración) */
function modeloId(string $marca, string $modelo): int
{
    return ModeloVehiculo::where('nombre', $modelo)
        ->where('fk_idMarcaVehiculo', MarcaVehiculo::where('nombre', $marca)->value('idMarcaVehiculo'))
        ->value('idModeloVehiculo');
}

function datosProducto(array $cambios = []): array
{
    return array_merge([
        'codigo' => 'fr-bos-001',
        'nombre' => 'Balatas delanteras',
        'categoria' => Categoria::firstWhere('nombre', 'Frenos')->idCategoria,
        'marca' => Marca::firstWhere('nombre', 'Bosch')->idMarca,
        'precioCompra' => '580',
        'precioVenta' => '850.50',
        'stockInicial' => '4',
        'stockMinimo' => '10',
        'ubicacion' => ' a-01-03 ',
        'vehiculos' => [
            ['modelo' => modeloId('Nissan', 'Versa'), 'desde' => '2012', 'hasta' => '2019'],
        ],
    ], $cambios);
}

// ---------- Consultar ----------

test('la migración deja categorías y marcas para elegir', function () {
    expect($this->frenos)->not->toBeNull()
        ->and($this->bosch)->not->toBeNull()
        ->and(Categoria::count())->toBeGreaterThan(5);
});

test('el administrador ve Productos en el menú y la lista', function () {
    Producto::factory()->create(['nombre' => 'Filtro de aire Mann']);
    Producto::factory()->desactivado()->create(['nombre' => 'Producto viejo']);

    $this->actingAs($this->admin)->get(route('administrador.productos.index'))
        ->assertOk()
        ->assertSee('Menú consulta')
        ->assertSee(route('administrador.productos.index'), false)
        ->assertSee('Filtro de aire Mann')
        ->assertDontSee('Producto viejo')
        ->assertSee('Universal'); // sin vehículos compatibles
});

test('buscar por nombre o código y filtrar por estado de stock', function () {
    Producto::factory()->create(['nombre' => 'Bujía iridium', 'codigoInterno' => 'BU-NGK-018', 'existencia' => 6, 'stockMinimo' => 15]);
    Producto::factory()->create(['nombre' => 'Filtro de aceite', 'codigoInterno' => 'FA-MAN-024', 'existencia' => 0]);
    Producto::factory()->create(['nombre' => 'Amortiguador', 'codigoInterno' => 'AM-MON-036', 'existencia' => 24, 'stockMinimo' => 5]);

    $ruta = fn (array $filtros) => route('administrador.productos.index', $filtros);

    $this->actingAs($this->admin)->get($ruta(['buscar' => 'ngk']))
        ->assertSee('Bujía iridium')->assertDontSee('Filtro de aceite');

    $this->actingAs($this->admin)->get($ruta(['estado' => 'bajo']))
        ->assertSee('Bujía iridium')->assertDontSee('Amortiguador')->assertDontSee('Filtro de aceite');

    $this->actingAs($this->admin)->get($ruta(['estado' => 'agotado']))
        ->assertSee('Filtro de aceite')->assertSee('0 · Agotado')->assertDontSee('Bujía iridium');

    $this->actingAs($this->admin)->get($ruta(['estado' => 'disponible']))
        ->assertSee('Amortiguador')->assertSee('24 · Disponible')->assertDontSee('Bujía iridium');
});

test('filtrar por modelo de vehículo', function () {
    $versa = Producto::factory()->create(['nombre' => 'Balatas para Versa']);
    $versa->compatibilidades()->create(['fk_idModeloVehiculo' => modeloId('Nissan', 'Versa'), 'anioDesde' => 2012]);
    Producto::factory()->create(['nombre' => 'Filtro universal']);

    $this->actingAs($this->admin)->get(route('administrador.productos.index', ['vehiculo' => modeloId('Nissan', 'Versa')]))
        ->assertSee('Balatas para Versa')
        ->assertSee('Nissan Versa')
        ->assertDontSee('Filtro universal');
});

test('la migración deja marcas y modelos de vehículo para elegir', function () {
    expect(MarcaVehiculo::count())->toBeGreaterThan(10)
        ->and(modeloId('Chevrolet', 'Aveo'))->toBeGreaterThan(0);
});

// ---------- Registrar ----------

test('registrar un producto guarda todo, su vehículo y el stock inicial en el historial', function () {
    $this->actingAs($this->admin)->post(route('administrador.productos.store'), datosProducto())
        ->assertRedirect(route('administrador.productos.index'))
        ->assertSessionHas('alerta', ['success', 'Producto «Balatas delanteras» registrado correctamente.']);

    $producto = Producto::with(['ubicacion', 'compatibilidades'])->firstWhere('codigoInterno', 'FR-BOS-001');

    expect($producto)->not->toBeNull()
        ->and($producto->existencia)->toBe(4)
        ->and((string) $producto->precioVentaActual)->toBe('850.50')
        ->and($producto->stockMinimo)->toBe(10)
        ->and($producto->ubicacion->codigoUbicacion)->toBe('A-01-03')
        ->and($producto->compatibilidades)->toHaveCount(1)
        ->and($producto->compatibilidades->first()->anios())->toBe('2012–2019')
        ->and($producto->estadoStock())->toBe(Producto::ESTADO_BAJO);

    $movimiento = MovimientoInventario::first();
    expect($movimiento->tipoMovimiento)->toBe('ENTRADA')
        ->and($movimiento->cantidad)->toBe(4)
        ->and($movimiento->fk_idTrabajador)->toBe($this->admin->idTrabajador);

    expect(Bitacora::where('accion', 'CREAR')->where('idRegistro', $producto->idProducto)->exists())->toBeTrue();
});

test('sin ubicación ni stock mínimo usa "sin asignar" y el mínimo de Configuración', function () {
    $this->actingAs($this->admin)->post(route('administrador.productos.store'), datosProducto([
        'ubicacion' => '', 'stockMinimo' => '', 'stockInicial' => '0', 'vehiculos' => [],
    ]));

    $producto = Producto::firstWhere('codigoInterno', 'FR-BOS-001');
    expect($producto->ubicacion->codigoUbicacion)->toBe('SIN-ASIGNAR')
        ->and($producto->stockMinimo)->toBeNull()
        ->and($producto->stockMinimoEfectivo())->toBe(10)
        ->and($producto->estadoStock())->toBe(Producto::ESTADO_AGOTADO)
        ->and(MovimientoInventario::count())->toBe(0); // con stock 0 no hay entrada
});

test('no se puede repetir el código y la ventana se vuelve a abrir', function () {
    Producto::factory()->create(['codigoInterno' => 'FR-BOS-001']);

    $this->actingAs($this->admin)->post(route('administrador.productos.store'), datosProducto())
        ->assertSessionHasErrors('codigo')
        ->assertSessionHas('modalProducto', 'registrar');

    expect(Producto::count())->toBe(1);
});

test('los datos inválidos se rechazan', function () {
    $this->actingAs($this->admin)->post(route('administrador.productos.store'), datosProducto([
        'codigo' => 'FR BOS 001',      // con espacios
        'nombre' => '',
        'categoria' => '99999',
        'precioCompra' => '-5',
        'precioVenta' => '10.555',     // 3 decimales
        'stockInicial' => '2.5',
        'vehiculos' => [
            ['modelo' => modeloId('Nissan', 'Versa'), 'desde' => '2019', 'hasta' => '2012'], // años al revés
            ['modelo' => '999999', 'desde' => '2010', 'hasta' => ''],                        // modelo que no existe
        ],
    ]))->assertSessionHasErrors(['codigo', 'nombre', 'categoria', 'precioCompra', 'precioVenta', 'stockInicial', 'vehiculos.0.hasta', 'vehiculos.1.modelo']);

    expect(Producto::count())->toBe(0);
});

test('la foto se guarda con un nombre al azar', function () {
    $this->actingAs($this->admin)->post(route('administrador.productos.store'), datosProducto([
        'imagen' => UploadedFile::fake()->image('balatas.png', 200, 200),
    ]))->assertSessionHasNoErrors();

    $producto = Producto::first();
    expect($producto->imagen)->toEndWith('.png')->not->toBe('balatas.png')
        ->and(File::exists(public_path('imagenes/productos/' . $producto->imagen)))->toBeTrue();
})->skip(fn () => !function_exists('imagecreatetruecolor'), 'Tu PHP no tiene la extensión GD');

test('un archivo que no es imagen se rechaza', function () {
    $this->actingAs($this->admin)->post(route('administrador.productos.store'), datosProducto([
        'imagen' => UploadedFile::fake()->create('factura.pdf', 100, 'application/pdf'),
    ]))->assertSessionHasErrors('imagen');

    $this->actingAs($this->admin)->post(route('administrador.productos.store'), datosProducto([
        'imagen' => UploadedFile::fake()->createWithContent('script.php', '<?php echo "hola";'),
    ]))->assertSessionHasErrors('imagen');

    expect(Producto::count())->toBe(0);
});

// ---------- Editar ----------

test('editar cambia los datos y los vehículos pero NO el stock', function () {
    $producto = Producto::factory()->create(['codigoInterno' => 'FR-BOS-001', 'existencia' => 4]);
    $producto->compatibilidades()->create(['fk_idModeloVehiculo' => modeloId('Ford', 'Fiesta'), 'anioDesde' => 2010]);

    $this->actingAs($this->admin)->put(route('administrador.productos.update', $producto), datosProducto([
        'nombre' => 'Balatas delanteras cerámicas',
        'precioVenta' => '999',
        'stockInicial' => '500', // aunque lo manden, se ignora
        'vehiculos' => [
            ['modelo' => modeloId('Nissan', 'Versa'), 'desde' => '2012', 'hasta' => '2019'],
            ['modelo' => modeloId('Nissan', 'March'), 'desde' => '2011', 'hasta' => ''],
        ],
    ]))->assertRedirect(route('administrador.productos.index'));

    $producto->refresh()->load('compatibilidades');
    expect($producto->nombre)->toBe('Balatas delanteras cerámicas')
        ->and((string) $producto->precioVentaActual)->toBe('999.00')
        ->and($producto->existencia)->toBe(4)
        ->and($producto->compatibilidades->map(fn ($c) => $c->vehiculo())->all())->toBe(['Nissan Versa', 'Nissan March'])
        ->and($producto->compatibilidades->last()->anios())->toBe('2011 en adelante');

    $bitacora = Bitacora::where('accion', 'EDITAR')->first();
    expect($bitacora->detalle['nombre']['despues'])->toBe('Balatas delanteras cerámicas');
});

test('al editar se puede dejar el mismo código', function () {
    $producto = Producto::factory()->create(['codigoInterno' => 'FR-BOS-001']);

    $this->actingAs($this->admin)->put(route('administrador.productos.update', $producto), datosProducto())
        ->assertSessionHasNoErrors();
});

test('editar con errores vuelve a abrir la ventana de ese producto', function () {
    $producto = Producto::factory()->create();

    $this->actingAs($this->admin)->put(route('administrador.productos.update', $producto), datosProducto(['nombre' => '']))
        ->assertSessionHasErrors('nombre')
        ->assertSessionHas('modalProducto', 'editar')
        ->assertSessionHas('modalProductoId', $producto->idProducto);
});

// ---------- Desactivar y reactivar ----------

test('eliminar desactiva el producto con la autorización del administrador', function () {
    $producto = Producto::factory()->create(['nombre' => 'Balatas delanteras']);

    $this->actingAs($this->admin)->patch(route('administrador.productos.desactivar', $producto), [
        'correo' => 'admin@prueba.mx',
        'contrasena' => 'password',
    ])->assertSessionHas('alerta', fn ($alerta) => $alerta[0] === 'success');

    expect($producto->refresh()->activo)->toBeFalse()
        ->and(Producto::count())->toBe(1); // no se borró

    $bitacora = Bitacora::where('accion', 'DESACTIVAR')->first();
    expect($bitacora->fk_idTrabajadorAutoriza)->toBe($this->admin->idTrabajador);

    // Ya no aparece en la lista normal, pero sí en "Ver desactivados"
    // (se busca por código porque el aviso verde todavía dice el nombre)
    $this->actingAs($this->admin)->get(route('administrador.productos.index'))->assertDontSee($producto->codigoInterno);
    $this->actingAs($this->admin)->get(route('administrador.productos.index', ['ver' => 'desactivados']))->assertSee($producto->codigoInterno);
});

test('un gerente también puede autorizar', function () {
    $producto = Producto::factory()->create();
    Trabajador::factory()->conRol(Trabajador::ROL_GERENTE)->create(['correoElectronico' => 'gerente@prueba.mx']);

    $this->actingAs($this->admin)->patch(route('administrador.productos.desactivar', $producto), [
        'correo' => 'gerente@prueba.mx', 'contrasena' => 'password',
    ])->assertSessionHas('alerta', fn ($alerta) => $alerta[0] === 'success');

    expect($producto->refresh()->activo)->toBeFalse();
});

test('con contraseña equivocada o sin ser gerente o administrador no se desactiva', function () {
    $producto = Producto::factory()->create();
    Trabajador::factory()->create(['correoElectronico' => 'vendedor@prueba.mx']); // vendedor
    Trabajador::factory()->conRol(Trabajador::ROL_GERENTE)->inactivo()->create(['correoElectronico' => 'exgerente@prueba.mx']);

    foreach ([
        ['admin@prueba.mx', 'incorrecta'],
        ['vendedor@prueba.mx', 'password'],
        ['exgerente@prueba.mx', 'password'],
    ] as [$correo, $contrasena]) {
        $this->actingAs($this->admin)->patch(route('administrador.productos.desactivar', $producto), [
            'correo' => $correo, 'contrasena' => $contrasena,
        ])->assertSessionHas('alerta', fn ($alerta) => $alerta[0] === 'danger');
    }

    expect($producto->refresh()->activo)->toBeTrue();
});

test('después de 5 autorizaciones fallidas se bloquea aunque la contraseña sea correcta', function () {
    $producto = Producto::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($this->admin)->patch(route('administrador.productos.desactivar', $producto), [
            'correo' => 'admin@prueba.mx', 'contrasena' => 'incorrecta',
        ]);
    }

    $this->actingAs($this->admin)->patch(route('administrador.productos.desactivar', $producto), [
        'correo' => 'admin@prueba.mx', 'contrasena' => 'password',
    ])->assertSessionHas('alerta', fn ($alerta) => str_starts_with($alerta[1], 'Demasiados intentos'));

    expect($producto->refresh()->activo)->toBeTrue();
});

test('reactivar también pide la autorización', function () {
    $producto = Producto::factory()->desactivado()->create();

    // Sin correo ni contraseña: no se reactiva
    $this->actingAs($this->admin)->patch(route('administrador.productos.reactivar', $producto))
        ->assertSessionHasErrors(['correo', 'contrasena']);
    expect($producto->refresh()->activo)->toBeFalse();

    $this->actingAs($this->admin)->patch(route('administrador.productos.reactivar', $producto), [
        'correo' => 'admin@prueba.mx', 'contrasena' => 'password',
    ]);

    expect($producto->refresh()->activo)->toBeTrue();

    $bitacora = Bitacora::where('accion', 'REACTIVAR')->first();
    expect($bitacora->fk_idTrabajadorAutoriza)->toBe($this->admin->idTrabajador);
});

test('un producto desactivado no se puede editar', function () {
    $producto = Producto::factory()->desactivado()->create(['nombre' => 'Original']);

    $this->actingAs($this->admin)->put(route('administrador.productos.update', $producto), datosProducto(['nombre' => 'Cambiado']))
        ->assertSessionHas('alerta', fn ($alerta) => $alerta[0] === 'warning');

    expect($producto->refresh()->nombre)->toBe('Original');
});

// ---------- Acceso ----------

test('otros roles no entran a los productos del administrador', function () {
    $vendedor = Trabajador::factory()->create();

    $this->actingAs($vendedor)->get(route('administrador.productos.index'))
        ->assertRedirect(route('vendedor.inicio'));

    $this->actingAs($vendedor)->post(route('administrador.productos.store'), datosProducto())
        ->assertRedirect(route('vendedor.inicio'));

    expect(Producto::count())->toBe(0);
});
