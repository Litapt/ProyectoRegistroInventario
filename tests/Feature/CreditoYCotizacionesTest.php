<?php

/*
    Pruebas automáticas de: ventas a crédito (pago de una parte + abonos) y cotizaciones guardadas.
    php artisan test — usan una base de datos temporal; NO tocan tus datos reales.
*/

use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Trabajador;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = Trabajador::factory()->administrador()->create(['correoElectronico' => 'admin@prueba.mx']);
    $this->actingAs($this->admin);
    $this->efectivo = MetodoPago::where('nombre', 'Efectivo')->value('idMetodoPago');
    $this->balatas = Producto::factory()->create(['codigoInterno' => 'FR-BOS-001', 'nombre' => 'Balatas', 'precioVentaActual' => 1000, 'existencia' => 10]);
    $this->cliente = Cliente::factory()->create(['nombres' => 'Taller Rivera']);
});

function ventaCredito($test, array $cambios = []): \Illuminate\Testing\TestResponse
{
    return $test->post(route('administrador.ventas.store'), array_merge([
        'cliente' => $test->cliente->idCliente,
        'productos' => [['id' => $test->balatas->idProducto, 'cantidad' => 1]],   // 1000 + IVA = 1160
        'metodoPago' => $test->efectivo,
        'condicion' => 'CREDITO',
        'pagoInicial' => '400',
    ], $cambios));
}

// ---------- Crédito ----------

test('pagar una parte deja la venta a crédito con su saldo', function () {
    ventaCredito($this)->assertSessionHasNoErrors()
        ->assertSessionHas('alerta', fn ($a) => str_contains($a[1], 'queda debiendo $760.00'));

    $venta = Venta::first();
    expect($venta->condicionPago)->toBe(Venta::CREDITO)
        ->and($venta->estadoVenta)->toBe(Venta::COMPLETADA)
        ->and($venta->pagado())->toBe(400.0)
        ->and($venta->saldo())->toBe(760.0)
        ->and($this->balatas->refresh()->existencia)->toBe(9);   // la mercancía sí sale
});

test('se puede dejar todo pendiente (sin anticipo)', function () {
    ventaCredito($this, ['pagoInicial' => '0'])->assertSessionHasNoErrors();
    expect(Venta::first()->pagos()->count())->toBe(0)->and(Venta::first()->saldo())->toBe(1160.0);
});

test('sin cliente registrado no se puede dejar saldo', function () {
    ventaCredito($this, ['cliente' => null])->assertSessionHasErrors('condicion');
    expect(Venta::count())->toBe(0)->and($this->balatas->refresh()->existencia)->toBe(10);
});

test('si "paga una parte" pero paga todo, queda de contado', function () {
    ventaCredito($this, ['pagoInicial' => '1160'])->assertSessionHasNoErrors();
    expect(Venta::first()->condicionPago)->toBe(Venta::CONTADO)->and(Venta::first()->saldo())->toBe(0.0);
});

test('abonos hasta liquidar; no se abona de más', function () {
    ventaCredito($this);
    $venta = Venta::first();

    $this->post(route('administrador.ventas.abono', $venta), ['monto' => '800', 'metodoPago' => $this->efectivo])
        ->assertSessionHasErrors('abono')->assertSessionHas('modalAbono', $venta->idVenta);

    $this->post(route('administrador.ventas.abono', $venta), ['monto' => '500', 'metodoPago' => $this->efectivo])
        ->assertSessionHas('alerta', fn ($a) => str_contains($a[1], 'Todavía debe $260.00'));
    $this->post(route('administrador.ventas.abono', $venta), ['monto' => '260', 'metodoPago' => $this->efectivo])
        ->assertSessionHas('alerta', fn ($a) => str_contains($a[1], 'liquidada'));

    expect($venta->refresh()->saldo())->toBe(0.0)->and($venta->pagos()->count())->toBe(3);

    $this->post(route('administrador.ventas.abono', $venta), ['monto' => '1', 'metodoPago' => $this->efectivo])
        ->assertSessionHasErrors('abono');
});

test('Consultar muestra lo que debe y filtra las que tienen saldo', function () {
    ventaCredito($this);
    $this->post(route('administrador.ventas.store'), [
        'productos' => [['id' => $this->balatas->idProducto, 'cantidad' => 1]], 'metodoPago' => $this->efectivo,
    ]);
    [$aCredito, $deContado] = Venta::orderBy('idVenta')->get()->all();
    $this->get(route('administrador.ventas.index'));   // se "come" el aviso verde

    $this->get(route('administrador.ventas.consultar'))
        ->assertSee('Debe $760.00')->assertSee('Con saldo (1)')
        ->assertSee(route('administrador.ventas.abono', $aCredito), false)
        ->assertDontSee(route('administrador.ventas.abono', $deContado), false);

    $this->get(route('administrador.ventas.consultar', ['saldo' => 1]))
        ->assertSee($aCredito->folio)->assertDontSee($deContado->folio);
});

test('el ticket dice el saldo pendiente', function () {
    ventaCredito($this);
    $this->get(route('administrador.ventas.ticket', Venta::first()))->assertSee('SALDO PENDIENTE')->assertSee('$760.00');
});

test('con saldo no se registra devolución; editar no puede bajar de lo ya pagado', function () {
    ventaCredito($this, ['pagoInicial' => '1000']);
    $venta = Venta::first();

    $this->post(route('administrador.ventas.devolucion', $venta), [
        'producto' => $this->balatas->idProducto, 'cantidad' => 1,
        'motivo' => \App\Models\Motivo::de('DEVOLUCION')->value('idMotivo'),
        'correo_autoriza' => 'admin@prueba.mx', 'contrasena_autoriza' => 'password',
    ])->assertSessionHasErrors('devolucion');

    // 100 % de descuento: el total sería 0, menor a los $1,000 pagados
    $this->put(route('administrador.ventas.editar', $venta), [
        'productos' => [['id' => $this->balatas->idProducto, 'cantidad' => 1]], 'descuento' => 100,
        'motivo' => 'prueba', 'accion' => 'autorizar', 'correo_autoriza' => 'admin@prueba.mx', 'contrasena_autoriza' => 'password',
    ])->assertSessionHasErrors('editar');
    expect((float) $venta->refresh()->total)->toBe(1160.0);
});

// ---------- Cotizaciones ----------

test('las cotizaciones guardadas se ven en su pestaña y se pueden cobrar', function () {
    $this->post(route('administrador.ventas.cotizacion.store'), [
        'cliente' => $this->cliente->idCliente,
        'productos' => [['id' => $this->balatas->idProducto, 'cantidad' => 2]],
        'descuento' => 10,
    ]);
    $cotizacion = Pedido::first();
    $this->get(route('administrador.ventas.consultar'));   // se "come" el aviso verde

    $this->get(route('administrador.ventas.cotizaciones'))
        ->assertOk()->assertSee($cotizacion->folio)->assertSee('Taller Rivera')
        ->assertSee(route('administrador.ventas.index', ['cotizacion' => $cotizacion->idPedido]), false);

    // "Cobrar": Registrar venta llega con sus productos, cliente y descuento
    $this->get(route('administrador.ventas.index', ['cotizacion' => $cotizacion->idPedido]))
        ->assertSee('Cobrando la cotización ' . $cotizacion->folio)
        ->assertSee('FR-BOS-001')->assertSee('Taller Rivera')
        ->assertSee('value="10"', false)
        ->assertSee('name="cotizacion" value="' . $cotizacion->idPedido . '"', false);

    // Al registrar la venta, la cotización queda cobrada y ligada a la venta
    $this->post(route('administrador.ventas.store'), [
        'cliente' => $this->cliente->idCliente,
        'productos' => [['id' => $this->balatas->idProducto, 'cantidad' => 2]],
        'descuento' => 10, 'metodoPago' => $this->efectivo, 'cotizacion' => $cotizacion->idPedido,
    ])->assertSessionHasNoErrors();

    expect($cotizacion->refresh()->estadoPedido)->toBe(Pedido::CONVERTIDA)
        ->and($cotizacion->fk_idVenta)->toBe(Venta::first()->idVenta);

    $this->get(route('administrador.ventas.cotizaciones'))->assertDontSee($cotizacion->folio);
    $this->get(route('administrador.ventas.cotizaciones', ['ver' => 'cobradas']))->assertSee($cotizacion->folio)->assertSee(Venta::first()->folio);
});

test('una cotización vencida se ve como vencida', function () {
    $this->post(route('administrador.ventas.cotizacion.store'), ['productos' => [['id' => $this->balatas->idProducto, 'cantidad' => 1]]]);
    $this->travel(20)->days();
    $this->withSession(['ultimaActividad' => now()->timestamp]);   // pasaron 20 días: que no cierre la sesión por inactividad

    $this->get(route('administrador.ventas.cotizaciones'))->assertSee('Venció');
});
