<?php

/*
    Pruebas automáticas de Registrar venta del administrador: php artisan test
    Usan una base de datos temporal en memoria; NO tocan tus ventas ni tu inventario reales.
*/

use App\Models\Bitacora;
use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Trabajador;
use App\Models\Venta;
use App\Support\CalculoVenta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = Trabajador::factory()->administrador()->create();
    $this->efectivo = MetodoPago::where('nombre', 'Efectivo')->value('idMetodoPago');

    // Los del diseño de Figma: subtotal $1,880.00 (precios sin IVA)
    $this->balatas = Producto::factory()->create(['codigoInterno' => 'FR-BOS-001', 'nombre' => 'Balatas delanteras', 'precioVentaActual' => 850, 'existencia' => 10]);
    $this->filtro = Producto::factory()->create(['codigoInterno' => 'FA-MAN-024', 'nombre' => 'Filtro de aceite', 'precioVentaActual' => 145, 'existencia' => 10]);
    $this->bujia = Producto::factory()->create(['codigoInterno' => 'BU-NGK-018', 'nombre' => 'Bujía iridium', 'precioVentaActual' => 185, 'existencia' => 4]);
});

function carritoFigma($test, array $cambios = []): array
{
    return array_merge([
        'productos' => [
            ['id' => $test->balatas->idProducto, 'cantidad' => 1],
            ['id' => $test->filtro->idProducto, 'cantidad' => 2],
            ['id' => $test->bujia->idProducto, 'cantidad' => 4],
        ],
        'descuento' => 0,
        'metodoPago' => $test->efectivo,
    ], $cambios);
}

// ---------- Cuentas ----------

test('las cuentas del diseño: $1,880 + IVA = $2,180.80', function () {
    $c = CalculoVenta::calcular([
        ['producto' => $this->balatas, 'cantidad' => 1],
        ['producto' => $this->filtro, 'cantidad' => 2],
        ['producto' => $this->bujia, 'cantidad' => 4],
    ], 0);

    expect($c['subtotal'])->toBe(1880.0)->and($c['iva'])->toBe(300.8)->and($c['total'])->toBe(2180.8)->and($c['piezas'])->toBe(7);
});

test('el descuento va antes del IVA y los renglones suman exacto', function () {
    $c = CalculoVenta::calcular([
        ['producto' => $this->balatas, 'cantidad' => 1],
        ['producto' => $this->filtro, 'cantidad' => 2],
        ['producto' => $this->bujia, 'cantidad' => 4],
    ], 12.5);

    // 1880 × 12.5 % = 235.00 → base 1645.00 → IVA 263.20 → total 1908.20
    expect($c['descuento'])->toBe(235.0)->and($c['iva'])->toBe(263.2)->and($c['total'])->toBe(1908.2)
        ->and(round(array_sum(array_column($c['renglones'], 'descuento')), 2))->toBe(235.0)
        ->and(round(array_sum(array_column($c['renglones'], 'total')), 2))->toBe(1908.2);
});

test('un descuento que no divide exacto no pierde centavos', function () {
    $tres = [
        ['producto' => Producto::factory()->create(['precioVentaActual' => 0.33]), 'cantidad' => 1],
        ['producto' => Producto::factory()->create(['precioVentaActual' => 0.33]), 'cantidad' => 1],
        ['producto' => Producto::factory()->create(['precioVentaActual' => 0.34]), 'cantidad' => 1],
    ];
    $c = CalculoVenta::calcular($tres, 33.33);

    expect(round(array_sum(array_column($c['renglones'], 'descuento')), 2))->toBe($c['descuento'])
        ->and(round($c['subtotal'] - $c['descuento'] + $c['iva'], 2))->toBe($c['total']);
});

// ---------- Pantalla y buscadores ----------

test('el administrador ve Ventas en el menú y la pantalla', function () {
    $this->actingAs($this->admin)->get(route('administrador.ventas.index'))
        ->assertOk()
        ->assertSee(route('administrador.ventas.index'), false)
        ->assertSee('Resumen de venta')
        ->assertSee('Efectivo');
});

test('buscar productos por nombre, código o marca; los desactivados no salen', function () {
    Producto::factory()->desactivado()->create(['nombre' => 'Balatas viejas']);

    $r = $this->actingAs($this->admin)->getJson(route('administrador.ventas.buscarProductos', ['q' => 'balatas']))->assertOk()->json();
    expect($r)->toHaveCount(1)
        ->and($r[0]['codigo'])->toBe('FR-BOS-001')
        ->and($r[0]['precio'])->toEqual(850)
        ->and($r[0]['existencia'])->toBe(10);

    expect($this->actingAs($this->admin)->getJson(route('administrador.ventas.buscarProductos', ['q' => 'BU-NGK']))->json())->toHaveCount(1);
});

test('buscar clientes por nombre o teléfono; los desactivados no salen', function () {
    Cliente::factory()->create(['nombres' => 'María López', 'telefono' => '613 123 4567']);
    Cliente::factory()->desactivado()->create(['nombres' => 'María Vieja']);

    $r = $this->actingAs($this->admin)->getJson(route('administrador.ventas.buscarClientes', ['q' => 'maría']))->json();
    expect($r)->toHaveCount(1)->and($r[0]['nombre'])->toBe('María López');
    expect($this->actingAs($this->admin)->getJson(route('administrador.ventas.buscarClientes', ['q' => '6131234']))->json())->toHaveCount(1);

    // "Consultar cliente" con la caja vacía: los últimos registrados (sólo activos)
    expect($this->actingAs($this->admin)->getJson(route('administrador.ventas.buscarClientes', ['q' => '']))->json())->toHaveCount(1);
});

test('registrar un cliente desde Ventas responde en JSON', function () {
    $this->actingAs($this->admin)->postJson(route('administrador.clientes.store'), ['nombre' => 'Pedro Ruiz', 'telefono' => '6131112222'])
        ->assertCreated()->assertJson(['nombre' => 'Pedro Ruiz', 'codigo' => 'CLI-00001']);

    $this->actingAs($this->admin)->postJson(route('administrador.clientes.store'), ['nombre' => '', 'telefono' => '1'])
        ->assertStatus(422)->assertJsonStructure(['errores']);
});

// ---------- Registrar venta ----------

test('registrar la venta del diseño: folio, totales, renglones, pago, inventario y movimientos', function () {
    $cliente = Cliente::factory()->create();

    $this->actingAs($this->admin)->post(route('administrador.ventas.store'), carritoFigma($this, [
        'cliente' => $cliente->idCliente, 'totalEsperado' => '2180.80',
    ]))->assertRedirect(route('administrador.ventas.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('alerta', fn ($a) => $a[0] === 'success' && str_contains($a[1], 'V-' . now()->year . '-0001'));

    $venta = Venta::with(['detalles', 'pagos'])->first();
    expect($venta->folio)->toBe('V-' . now()->year . '-0001')
        ->and($venta->fk_idCliente)->toBe($cliente->idCliente)
        ->and($venta->fk_idTrabajador)->toBe($this->admin->idTrabajador)
        ->and($venta->estadoVenta)->toBe(Venta::COMPLETADA)
        ->and((float) $venta->subtotal)->toBe(1880.0)
        ->and((float) $venta->impuestosTotal)->toBe(300.8)
        ->and((float) $venta->total)->toBe(2180.8)
        ->and($venta->detalles)->toHaveCount(3)
        ->and((float) $venta->pagos->first()->monto)->toBe(2180.8)
        ->and($venta->pagos->first()->fk_idMetodoPago)->toBe($this->efectivo);

    expect($this->balatas->refresh()->existencia)->toBe(9)
        ->and($this->filtro->refresh()->existencia)->toBe(8)
        ->and($this->bujia->refresh()->existencia)->toBe(0);

    $movimiento = MovimientoInventario::where('fk_idProducto', $this->bujia->idProducto)->first();
    expect($movimiento->tipoMovimiento)->toBe(MovimientoInventario::VENTA)
        ->and($movimiento->existenciaAnterior)->toBe(4)
        ->and($movimiento->existenciaNueva)->toBe(0)
        ->and($movimiento->referenciaId)->toBe($venta->idVenta)
        ->and(Bitacora::where('tabla', 'ventas')->exists())->toBeTrue();
});

test('sin cliente queda como Público en general y el folio avanza', function () {
    foreach ([1, 2] as $i) {
        $this->actingAs($this->admin)->post(route('administrador.ventas.store'), [
            'productos' => [['id' => $this->filtro->idProducto, 'cantidad' => 1]], 'metodoPago' => $this->efectivo,
        ])->assertSessionHasNoErrors();
    }

    expect(Venta::pluck('folio')->all())->toBe(['V-' . now()->year . '-0001', 'V-' . now()->year . '-0002'])
        ->and(Venta::first()->nombreCliente())->toBe('Público en general');
});

test('el precio lo pone el servidor, no la pantalla', function () {
    $this->actingAs($this->admin)->post(route('administrador.ventas.store'), [
        'productos' => [['id' => $this->balatas->idProducto, 'cantidad' => 1, 'precio' => 1]],
        'metodoPago' => $this->efectivo,
    ])->assertSessionHasNoErrors();

    expect((float) Venta::first()->subtotal)->toBe(850.0);
});

test('si el total que vio la persona no cuadra, no se guarda', function () {
    $this->actingAs($this->admin)->post(route('administrador.ventas.store'), carritoFigma($this, ['totalEsperado' => '100.00']))
        ->assertSessionHasErrors('totalEsperado');

    expect(Venta::count())->toBe(0)->and($this->balatas->refresh()->existencia)->toBe(10);
});

test('no se vende más de lo que hay y no se guarda nada a medias', function () {
    $this->actingAs($this->admin)->post(route('administrador.ventas.store'), [
        'productos' => [
            ['id' => $this->balatas->idProducto, 'cantidad' => 1],
            ['id' => $this->bujia->idProducto, 'cantidad' => 5],   // sólo hay 4
        ],
        'metodoPago' => $this->efectivo,
    ])->assertSessionHasErrors('productos')
        ->assertSessionHasInput('productos');

    expect(Venta::count())->toBe(0)
        ->and($this->balatas->refresh()->existencia)->toBe(10)
        ->and(MovimientoInventario::count())->toBe(0)
        ->and(\Illuminate\Support\Facades\DB::table('folios')->where('tipoDocumento', 'VENTA')->value('ultimoNumero'))->toBe(0);
});

test('datos inválidos: sin productos, cantidades malas, repetidos, descuento > 100, sin método de pago', function () {
    $this->actingAs($this->admin)->post(route('administrador.ventas.store'), [])
        ->assertSessionHasErrors(['productos', 'metodoPago']);

    $this->actingAs($this->admin)->post(route('administrador.ventas.store'), [
        'productos' => [
            ['id' => $this->balatas->idProducto, 'cantidad' => 0],
            ['id' => $this->balatas->idProducto, 'cantidad' => 1],
        ],
        'descuento' => 150,
        'metodoPago' => 999,
    ])->assertSessionHasErrors(['productos.0.cantidad', 'productos.0.id', 'descuento', 'metodoPago']);

    expect(Venta::count())->toBe(0);
});

test('un producto desactivado o un cliente desactivado no se pueden vender', function () {
    $this->balatas->activo = false;
    $this->balatas->save();

    $this->actingAs($this->admin)->post(route('administrador.ventas.store'), [
        'productos' => [['id' => $this->balatas->idProducto, 'cantidad' => 1]], 'metodoPago' => $this->efectivo,
    ])->assertSessionHasErrors('productos');

    $this->actingAs($this->admin)->post(route('administrador.ventas.store'), [
        'cliente' => Cliente::factory()->desactivado()->create()->idCliente,
        'productos' => [['id' => $this->filtro->idProducto, 'cantidad' => 1]], 'metodoPago' => $this->efectivo,
    ])->assertSessionHasErrors('cliente');

    expect(Venta::count())->toBe(0);
});

test('con un error, la pantalla vuelve a armar la lista de productos', function () {
    $this->actingAs($this->admin)->post(route('administrador.ventas.store'), carritoFigma($this, ['metodoPago' => 999]));

    $this->actingAs($this->admin)->get(route('administrador.ventas.index'))
        ->assertSee('FR-BOS-001')->assertSee('BU-NGK-018');
});

test('el ticket muestra la venta con sus precios guardados', function () {
    $this->actingAs($this->admin)->post(route('administrador.ventas.store'), carritoFigma($this));
    $venta = Venta::first();

    // Aunque el precio cambie después, el ticket no cambia
    $this->balatas->precioVentaActual = 999;
    $this->balatas->save();

    $this->actingAs($this->admin)->get(route('administrador.ventas.ticket', $venta))
        ->assertOk()->assertSee($venta->folio)->assertSee('$850.00')->assertSee('$2,180.80')->assertSee('Efectivo');
});

// ---------- Cotizaciones ----------

test('mostrar cotización no guarda nada ni toca el inventario', function () {
    $this->actingAs($this->admin)->post(route('administrador.ventas.cotizacion.vista'), carritoFigma($this, ['descuento' => 10]))
        ->assertOk()->assertSee('Vista previa')->assertSee('Balatas delanteras')->assertSee('−$188.00');

    expect(Pedido::count())->toBe(0)->and(Venta::count())->toBe(0)->and($this->balatas->refresh()->existencia)->toBe(10);
});

test('convertir a cotización la guarda con folio y vigencia, sin tocar el inventario', function () {
    // Se queda en Ventas con el aviso para imprimirla (no cambia de página)
    $this->actingAs($this->admin)->post(route('administrador.ventas.cotizacion.store'), carritoFigma($this))
        ->assertRedirect(route('administrador.ventas.index'))
        ->assertSessionHas('cotizacionGuardada');

    $cotizacion = Pedido::with('detalles')->first();
    expect($cotizacion->tipoPedido)->toBe(Pedido::COTIZACION)
        ->and($cotizacion->folio)->toBe('COT-' . now()->year . '-0001')
        ->and((float) $cotizacion->total)->toBe(2180.8)
        ->and($cotizacion->detalles)->toHaveCount(3)
        ->and($cotizacion->vigencia->toDateString())->toBe(now()->addDays(Pedido::DIAS_VIGENCIA)->toDateString())
        ->and($this->bujia->refresh()->existencia)->toBe(4)
        ->and(Venta::count())->toBe(0);

    $this->actingAs($this->admin)->get(route('administrador.ventas.index'))
        ->assertSee('Imprimir cotización')->assertSee(route('administrador.ventas.cotizacion', $cotizacion), false);
    $this->actingAs($this->admin)->get(route('administrador.ventas.cotizacion', $cotizacion))
        ->assertOk()->assertSee($cotizacion->folio)->assertSee('$2,180.80');
});

test('una cotización sí puede llevar más piezas de las que hay', function () {
    $this->actingAs($this->admin)->post(route('administrador.ventas.cotizacion.store'), [
        'productos' => [['id' => $this->bujia->idProducto, 'cantidad' => 50]],
    ])->assertSessionHasNoErrors();

    expect(Pedido::count())->toBe(1);
});

test('otros roles no entran a las ventas del administrador', function () {
    $this->actingAs(Trabajador::factory()->conRol(Trabajador::ROL_CAJERO)->create())
        ->get(route('administrador.ventas.index'))
        ->assertRedirect(route('cajero.inicio'));
});
