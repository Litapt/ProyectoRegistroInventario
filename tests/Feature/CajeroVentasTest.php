<?php

/*
    Pruebas automáticas del CAJERO · Ventas y Cotizaciones:
    abonos, devoluciones y cancelaciones con autorización; cotizaciones sin permiso (con descuento, con autorización).
    php artisan test — usan una base de datos temporal; NO tocan tus datos reales.
*/

use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\Motivo;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Solicitud;
use App\Models\Trabajador;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->cajero = Trabajador::factory()->conRol(Trabajador::ROL_CAJERO)->create(['correoElectronico' => 'caja@prueba.mx']);
    Trabajador::factory()->conRol(Trabajador::ROL_GERENTE)->create(['correoElectronico' => 'gerente@prueba.mx']);
    $this->efectivo = MetodoPago::where('nombre', 'Efectivo')->value('idMetodoPago');
    $this->balatas = Producto::factory()->create(['codigoInterno' => 'FR-BOS-001', 'nombre' => 'Balatas', 'precioVentaActual' => 1000, 'existencia' => 10]);
    $this->cliente = Cliente::factory()->create(['nombres' => 'Taller Rivera']);
    $this->actingAs($this->cajero);
    $this->post(route('cajero.turno.abrir'), ['billetes' => [500 => 2]]);
    $this->autoriza = ['correo_autoriza' => 'gerente@prueba.mx', 'contrasena_autoriza' => 'password'];
});

function ventaDeCaja($test, array $cambios = []): Venta
{
    $test->post(route('cajero.ventas.store'), array_merge([
        'productos' => [['id' => $test->balatas->idProducto, 'cantidad' => 2]],   // 2320.00
        'metodoPago' => $test->efectivo, 'recibido' => '2320', 'totalEsperado' => '2320.00',
    ], $cambios))->assertSessionHasNoErrors();

    return Venta::latest('idVenta')->first();
}

// ---------- Ventas ----------

test('el cajero ve sus ventas con ticket, devolución y cancelar', function () {
    $venta = ventaDeCaja($this);
    $this->get(route('cajero.inicio'));   // se "come" el aviso verde

    $this->get(route('cajero.ventas.consultar'))
        ->assertOk()->assertSee($venta->folio)
        ->assertSee(route('cajero.ventas.ticket', $venta), false)
        ->assertSee(route('cajero.ventas.devolucion', $venta), false)
        ->assertSee(route('cajero.ventas.cancelar', $venta), false)
        ->assertDontSee(url('/administrador') . '/', false);
});

test('un abono necesita autorización', function () {
    $venta = ventaDeCaja($this, ['cliente' => $this->cliente->idCliente, 'condicion' => 'CREDITO', 'pagoInicial' => '320', 'recibido' => '320'] + $this->autoriza);
    expect($venta->saldo())->toBe(2000.0);

    $this->get(route('cajero.ventas.consultar', ['saldo' => 1]))->assertSee(route('cajero.ventas.abono', $venta), false)->assertSee('abonoCorreo', false);

    $this->post(route('cajero.ventas.abono', $venta), ['monto' => '500', 'metodoPago' => $this->efectivo])
        ->assertSessionHasErrors('abono')->assertSessionHas('modalAbono', $venta->idVenta);
    expect($venta->refresh()->saldo())->toBe(2000.0);

    $this->post(route('cajero.ventas.abono', $venta), ['monto' => '500', 'metodoPago' => $this->efectivo] + $this->autoriza)
        ->assertSessionHasNoErrors();
    expect($venta->refresh()->saldo())->toBe(1500.0);
});

test('devolución y cancelación necesitan autorización', function () {
    $venta = ventaDeCaja($this);
    $devolver = [
        'producto' => $this->balatas->idProducto, 'cantidad' => 1, 'regresaInventario' => '1',
        'motivo' => Motivo::de(Motivo::DEVOLUCION)->value('idMotivo'),
    ];

    $this->post(route('cajero.ventas.devolucion', $venta), $devolver)->assertSessionHasErrors('devolucion');
    $this->post(route('cajero.ventas.devolucion', $venta), $devolver + $this->autoriza)->assertSessionHasNoErrors();
    $devolucion = Solicitud::where('tipoSolicitud', Solicitud::DEVOLUCION)->first();
    expect($devolucion->cantidad)->toBe(1)->and($this->balatas->refresh()->existencia)->toBe(9);
    $this->get(route('cajero.ventas.devolucion.comprobante', $devolucion))->assertOk();

    $otra = ventaDeCaja($this);
    $cancelar = ['motivo' => Motivo::de(Motivo::CANCELACION)->value('idMotivo')];
    $this->patch(route('cajero.ventas.cancelar', $otra), $cancelar);
    expect($otra->refresh()->estaCancelada())->toBeFalse();
    $this->patch(route('cajero.ventas.cancelar', $otra), $cancelar + $this->autoriza);
    expect($otra->refresh()->estaCancelada())->toBeTrue()->and($this->balatas->refresh()->existencia)->toBe(9);
});

// ---------- Cotizaciones ----------

test('el cajero guarda cotizaciones sin permiso y las cobra', function () {
    $this->post(route('cajero.ventas.cotizacion.store'), [
        'cliente' => $this->cliente->idCliente, 'productos' => [['id' => $this->balatas->idProducto, 'cantidad' => 1]],
    ])->assertSessionHasNoErrors()->assertRedirect(route('cajero.inicio'));

    $cotizacion = Pedido::where('tipoPedido', Pedido::COTIZACION)->first();
    expect($this->balatas->refresh()->existencia)->toBe(10);
    $this->get(route('cajero.inicio'))->assertSee('Cotización ' . $cotizacion->folio)->assertSee(route('cajero.ventas.cotizacion', $cotizacion), false);
    $this->get(route('cajero.ventas.cotizacion', $cotizacion))->assertOk()->assertSee($cotizacion->folio);

    $this->get(route('cajero.ventas.cotizaciones'))->assertSee($cotizacion->folio)
        ->assertSee(route('cajero.inicio', ['cotizacion' => $cotizacion->idPedido]), false);

    $this->get(route('cajero.inicio', ['cotizacion' => $cotizacion->idPedido]))
        ->assertSee('Cobrando la cotización ' . $cotizacion->folio)->assertSee('FR-BOS-001')->assertSee('Taller Rivera');

    $this->post(route('cajero.ventas.store'), [
        'cliente' => $this->cliente->idCliente, 'productos' => [['id' => $this->balatas->idProducto, 'cantidad' => 1]],
        'metodoPago' => $this->efectivo, 'recibido' => '1160', 'totalEsperado' => '1160.00', 'cotizacion' => $cotizacion->idPedido,
    ])->assertSessionHasNoErrors();
    expect($cotizacion->refresh()->estadoPedido)->toBe(Pedido::CONVERTIDA);
});

test('una cotización con descuento necesita autorización (guardarla o sólo mostrarla)', function () {
    $conDescuento = ['productos' => [['id' => $this->balatas->idProducto, 'cantidad' => 1]], 'descuento' => 10];

    $this->post(route('cajero.ventas.cotizacion.store'), $conDescuento)->assertSessionHasErrors('autorizacion');
    $this->post(route('cajero.ventas.cotizacion.vista'), $conDescuento)->assertSessionHasErrors('autorizacion');
    expect(Pedido::count())->toBe(0);

    $this->post(route('cajero.ventas.cotizacion.vista'), $conDescuento + $this->autoriza)->assertOk()->assertSee('$1,044.00');
    $this->post(route('cajero.ventas.cotizacion.store'), $conDescuento + $this->autoriza)->assertSessionHasNoErrors();
    expect((float) Pedido::first()->total)->toBe(1044.0);

    // Sin descuento, mostrarla no pide nada
    $this->post(route('cajero.ventas.cotizacion.vista'), ['productos' => [['id' => $this->balatas->idProducto, 'cantidad' => 1]]])->assertOk();
});

// ---------- Cada cajero sólo ve lo suyo ----------

test('cada cajero sólo ve y toca sus ventas, cotizaciones y pedidos cobrados; el gerente ve todo', function () {
    // Lo de Carla (la cajera de las pruebas)
    $mia = ventaDeCaja($this);
    $this->post(route('cajero.ventas.cotizacion.store'), ['productos' => [['id' => $this->balatas->idProducto, 'cantidad' => 1]]]);
    $miCotizacion = Pedido::where('tipoPedido', Pedido::COTIZACION)->latest('idPedido')->first();

    // Lo de otro cajero
    $otro = Trabajador::factory()->conRol(Trabajador::ROL_CAJERO)->create();
    $this->actingAs($otro);
    $this->post(route('cajero.turno.abrir'), ['billetes' => [500 => 2]]);
    $ajena = ventaDeCaja($this);
    $this->post(route('cajero.ventas.cotizacion.store'), ['productos' => [['id' => $this->balatas->idProducto, 'cantidad' => 3]]]);
    $cotizacionAjena = Pedido::where('tipoPedido', Pedido::COTIZACION)->latest('idPedido')->first();
    $pedido = \App\Support\RegistroVenta::guardarPedido(['cliente' => null, 'pedidos' => [$this->balatas->idProducto => 1], 'descuento' => 0, 'totalEsperado' => null], $otro);
    $this->post(route('cajero.pedidos.pagar', $pedido), ['metodoPago' => $this->efectivo, 'recibido' => '1160', 'totalEsperado' => '1160.00']);
    $pendiente = \App\Support\RegistroVenta::guardarPedido(['cliente' => null, 'pedidos' => [$this->balatas->idProducto => 1], 'descuento' => 0, 'totalEsperado' => null], $otro);

    // De vuelta con Carla
    $this->actingAs($this->cajero);
    $this->get(route('cajero.inicio'));
    $this->get(route('cajero.ventas.consultar'))->assertSee($mia->folio)->assertDontSee($ajena->folio);
    $this->get(route('cajero.ventas.cotizaciones'))->assertSee($miCotizacion->folio)->assertDontSee($cotizacionAjena->folio);
    $this->get(route('cajero.pedidos.index', ['ver' => 'cobrados']))->assertDontSee($pedido->folio)->assertSee('Cobrados (0)');
    $this->get(route('cajero.pedidos.index'))->assertSee($pendiente->folio);   // los pendientes los ve cualquier cajero

    // La dirección de algo ajeno: "no existe"
    $this->get(route('cajero.ventas.ticket', $ajena))->assertNotFound();
    $this->get(route('cajero.ventas.cotizacion', $cotizacionAjena))->assertNotFound();
    $this->post(route('cajero.ventas.abono', $ajena), ['monto' => '1', 'metodoPago' => $this->efectivo] + $this->autoriza)->assertNotFound();
    $this->patch(route('cajero.ventas.cancelar', $ajena), ['motivo' => Motivo::de(Motivo::CANCELACION)->value('idMotivo')] + $this->autoriza)->assertNotFound();
    $this->post(route('cajero.ventas.devolucion', $ajena), [])->assertNotFound();
    $this->get(route('cajero.inicio', ['cotizacion' => $cotizacionAjena->idPedido]))->assertDontSee('Cobrando la cotización');
    expect($ajena->refresh()->estaCancelada())->toBeFalse();

    // El gerente ve todo
    $this->actingAs(Trabajador::where('correoElectronico', 'gerente@prueba.mx')->first());
    $this->get(route('gerente.ventas.consultar'))->assertSee($mia->folio)->assertSee($ajena->folio);
    $this->get(route('gerente.ventas.cotizaciones'))->assertSee($miCotizacion->folio)->assertSee($cotizacionAjena->folio);
});
