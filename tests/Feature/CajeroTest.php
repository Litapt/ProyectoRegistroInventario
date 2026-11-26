<?php

/*
    Pruebas automáticas del CAJERO: abrir turno, vender directo, cobrar pedidos con cambio,
    ticket y cancelar. Sólo los pagos directos los hace solo; lo demás lo autoriza un superior.
    php artisan test — usan una base de datos temporal; NO tocan tus datos reales.
*/

use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\MetodoPago;
use App\Models\Motivo;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Trabajador;
use App\Models\TurnoCaja;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->cajero = Trabajador::factory()->conRol(Trabajador::ROL_CAJERO)->create(['nombres' => 'Carla', 'correoElectronico' => 'caja@prueba.mx']);
    $this->gerente = Trabajador::factory()->conRol(Trabajador::ROL_GERENTE)->create(['correoElectronico' => 'gerente@prueba.mx']);
    $this->efectivo = MetodoPago::where('nombre', 'Efectivo')->value('idMetodoPago');
    $this->tarjeta = MetodoPago::where('nombre', '!=', 'Efectivo')->value('idMetodoPago');
    $this->balatas = Producto::factory()->create(['codigoInterno' => 'FR-BOS-001', 'nombre' => 'Balatas', 'precioVentaActual' => 1000, 'existencia' => 10]);
    $this->cliente = Cliente::factory()->create(['nombres' => 'Taller Rivera']);
    $this->vendedor = Trabajador::factory()->conRol(Trabajador::ROL_VENDEDOR)->create(['nombres' => 'Víctor']);
    $this->actingAs($this->cajero);
});

function abrirTurno($test, array $cambios = []): \Illuminate\Testing\TestResponse
{
    return $test->post(route('cajero.turno.abrir'), array_merge(['billetes' => [500 => 2]], $cambios));   // $1,000
}

/* Un pedido como los que arma el vendedor (el cajero no los registra: vende directo) */
function pedidoDeCaja($test, int $cantidad = 1, ?int $cliente = null, float $descuento = 0): Pedido
{
    return \App\Support\RegistroVenta::guardarPedido([
        'cliente' => $cliente, 'pedidos' => [$test->balatas->idProducto => $cantidad], 'descuento' => $descuento, 'totalEsperado' => null,
    ], $test->vendedor);
}

function vender($test, array $cambios = []): \Illuminate\Testing\TestResponse
{
    return $test->post(route('cajero.ventas.store'), array_merge([
        'productos' => [['id' => $test->balatas->idProducto, 'cantidad' => 1]],   // 1000 + IVA = 1160
        'metodoPago' => $test->efectivo, 'recibido' => '1200', 'totalEsperado' => '1160.00',
    ], $cambios));
}

const GERENTE_AUTORIZA = ['correo_autoriza' => 'gerente@prueba.mx', 'contrasena_autoriza' => 'password'];

// ---------- Turno ----------

test('sin turno abierto aparece "Abrir turno" y no puede vender ni ver pedidos', function () {
    $this->get(route('cajero.inicio'))->assertOk()->assertSee('Abrir turno')->assertSee('Fondo autorizado')->assertSee('$1,000.00');
    $this->get(route('cajero.pedidos.index'))->assertRedirect(route('cajero.inicio'));
    vender($this)->assertRedirect(route('cajero.inicio'));
    expect(Venta::count())->toBe(0);
});

test('con el fondo autorizado abre el turno sin pedir autorización', function () {
    abrirTurno($this)->assertSessionHasNoErrors()->assertSessionHas('alerta', fn ($a) => str_contains($a[1], 'Turno abierto'));

    $turno = TurnoCaja::first();
    expect($turno->estado)->toBe(TurnoCaja::ABIERTO)
        ->and((float) $turno->fondoInicial)->toBe(1000.0)
        ->and($turno->fk_idTrabajadorAutorizaApertura)->toBeNull();

    $this->get(route('cajero.inicio'))->assertDontSee('id="modalAbrirTurno"', false)->assertSee($turno->nombre());

    // Un segundo "abrir" no crea otro turno
    abrirTurno($this);
    expect(TurnoCaja::count())->toBe(1);
});

test('con otro fondo necesita la autorización de un gerente y observaciones', function () {
    abrirTurno($this, ['billetes' => [500 => 2, 20 => 3], 'monedas' => '10.50'])
        ->assertSessionHasErrors('turno', null, 'turno');
    expect(TurnoCaja::count())->toBe(0);

    abrirTurno($this, ['billetes' => [500 => 2, 20 => 3], 'monedas' => '10.50',
        'correo_autoriza' => 'gerente@prueba.mx', 'contrasena_autoriza' => 'password'])
        ->assertSessionHasErrors('turno', null, 'turno');   // faltan observaciones

    abrirTurno($this, ['billetes' => [500 => 2, 20 => 3], 'monedas' => '10.50',
        'correo_autoriza' => 'gerente@prueba.mx', 'contrasena_autoriza' => 'password', 'observaciones' => 'Sobró cambio de ayer'])
        ->assertSessionHasNoErrors();

    $turno = TurnoCaja::first();
    expect((float) $turno->fondoInicial)->toBe(1070.5)
        ->and($turno->fk_idTrabajadorAutorizaApertura)->toBe($this->gerente->idTrabajador)
        ->and($turno->conteoApertura['billetes'][20])->toBe(3);
});

test('el fondo autorizado se cambia en Configuración → Ventas', function () {
    $this->actingAs(Trabajador::factory()->administrador()->create());
    $this->put(route('administrador.configuracion.ventas'), [
        'iva' => '16', 'formatoFolio' => \App\Support\Folios::FORMATOS_VENTA[0], 'metodosPago' => 'Efectivo, Tarjeta', 'fondoCaja' => '1500',
    ])->assertSessionHasNoErrors();

    expect(Configuracion::valor('fondoInicialCaja'))->toBe('1500.00');
});

// ---------- Pedidos ----------

// ---------- Venta directa ----------

test('vende directo en efectivo: cambio, inventario y ticket', function () {
    abrirTurno($this);
    $this->get(route('cajero.inicio'))->assertSee('Nueva venta')->assertSee('Monto recibido')->assertSee('Cobrar venta');

    vender($this)->assertSessionHasNoErrors()->assertSessionHas('alerta', fn ($a) => str_contains($a[1], 'Cambio: $40.00'));

    $venta = Venta::first();
    expect($this->balatas->refresh()->existencia)->toBe(9)
        ->and($venta->trabajador->idTrabajador)->toBe($this->cajero->idTrabajador)
        ->and($venta->pagos()->value('referencia'))->toBe('TURNO-' . TurnoCaja::first()->idTurno);

    $this->get(route('cajero.inicio'))->assertSee('data-imprimir-auto', false)->assertSee(route('cajero.ventas.ticket', $venta), false);
    $this->get(route('cajero.ventas.ticket', $venta))->assertSee('Recibido')->assertSee('$1,200.00')->assertSee('$40.00');
});

test('en efectivo tiene que decir con cuánto paga y que alcance', function () {
    abrirTurno($this);
    vender($this, ['recibido' => null])->assertSessionHasErrors('recibido');
    vender($this, ['recibido' => '1000'])->assertSessionHasErrors('recibido');
    expect(Venta::count())->toBe(0)->and($this->balatas->refresh()->existencia)->toBe(10);

    // Con tarjeta no se pide
    vender($this, ['metodoPago' => $this->tarjeta, 'recibido' => null])->assertSessionHasNoErrors();
    expect(Venta::count())->toBe(1);
});

test('un descuento necesita la autorización de un gerente o administrador', function () {
    abrirTurno($this);
    $conDescuento = ['descuento' => 10, 'totalEsperado' => '1044.00', 'recibido' => '1044'];   // 900 + IVA

    vender($this, $conDescuento)->assertSessionHasErrors('autorizacion');
    vender($this, $conDescuento + ['correo_autoriza' => 'gerente@prueba.mx', 'contrasena_autoriza' => 'mala'])->assertSessionHasErrors('autorizacion');
    vender($this, $conDescuento + ['correo_autoriza' => 'caja@prueba.mx', 'contrasena_autoriza' => 'password'])->assertSessionHasErrors('autorizacion');   // otro cajero no autoriza
    expect(Venta::count())->toBe(0);

    vender($this, $conDescuento + GERENTE_AUTORIZA)->assertSessionHasNoErrors();
    expect((float) Venta::first()->total)->toBe(1044.0);
    expect(\App\Models\Bitacora::where('accion', 'CREAR')->where('tabla', 'ventas')->value('fk_idTrabajadorAutoriza'))->toBe($this->gerente->idTrabajador);
});

test('dejar saldo pendiente necesita autorización; pagar todo no', function () {
    abrirTurno($this);
    $aCredito = ['cliente' => $this->cliente->idCliente, 'condicion' => 'CREDITO', 'pagoInicial' => '500', 'recibido' => '500'];

    vender($this, $aCredito)->assertSessionHasErrors('autorizacion');
    vender($this, $aCredito + GERENTE_AUTORIZA)->assertSessionHasNoErrors()
        ->assertSessionHas('alerta', fn ($a) => str_contains($a[1], 'Queda debiendo $660.00'));
    expect(Venta::first()->saldo())->toBe(660.0);

    // "Paga una parte" pero paga todo: es pago directo, no pide nada
    vender($this, ['cliente' => $this->cliente->idCliente, 'condicion' => 'CREDITO', 'pagoInicial' => '1160'])->assertSessionHasNoErrors();
    expect(Venta::count())->toBe(2);
});

// ---------- Pedidos de los vendedores ----------

test('los pedidos de los vendedores se ven en «Pedidos» y no tocan el inventario', function () {
    abrirTurno($this);
    $pedido = pedidoDeCaja($this, 2, $this->cliente->idCliente);

    expect($pedido->estadoPedido)->toBe(Pedido::PENDIENTE)
        ->and((float) $pedido->total)->toBe(2320.0)
        ->and($this->balatas->refresh()->existencia)->toBe(10);

    $this->get(route('cajero.pedidos.index'))
        ->assertSee($pedido->folio)->assertSee('Taller Rivera')->assertSee('Víctor')->assertSee('Pendientes de cobrar (1)')
        ->assertSee(route('cajero.pedidos.cobrar', $pedido), false);
});

test('un pedido pendiente se puede editar', function () {
    abrirTurno($this);
    $pedido = pedidoDeCaja($this);

    $this->get(route('cajero.pedidos.edit', $pedido))->assertOk()->assertSee('Editar pedido ' . $pedido->folio)->assertSee('FR-BOS-001');
    $this->put(route('cajero.pedidos.update', $pedido), [
        'productos' => [['id' => $this->balatas->idProducto, 'cantidad' => 3]],
    ])->assertSessionHasNoErrors();

    expect((float) $pedido->refresh()->total)->toBe(3480.0)->and($pedido->detalles()->value('cantidad'))->toBe(3);
});

test('cobrar en efectivo: descuenta inventario, guarda el cambio y el ticket lo dice', function () {
    abrirTurno($this);
    $pedido = pedidoDeCaja($this);   // $1,160.00

    $this->get(route('cajero.pedidos.cobrar', $pedido))->assertOk()->assertSee('Cobrar pedido ' . $pedido->folio)->assertSee('$1,160.00');

    $this->post(route('cajero.pedidos.pagar', $pedido), [
        'metodoPago' => $this->efectivo, 'recibido' => '1200', 'totalEsperado' => '1160.00',
    ])->assertSessionHasNoErrors()->assertSessionHas('alerta', fn ($a) => str_contains($a[1], 'Cambio: $40.00'));

    $venta = Venta::first();
    $turno = TurnoCaja::first();
    expect($pedido->refresh()->estadoPedido)->toBe(Pedido::CONVERTIDA)
        ->and($pedido->fk_idVenta)->toBe($venta->idVenta)
        ->and($this->balatas->refresh()->existencia)->toBe(9)
        ->and($venta->pagos()->value('referencia'))->toBe('TURNO-' . $turno->idTurno);

    // Al regresar a la lista, el ticket se manda a imprimir solo (sin abrir otra pestaña)
    $this->get(route('cajero.pedidos.index'))->assertSee('data-imprimir-auto', false)->assertSee(route('cajero.ventas.ticket', $venta), false);

    $this->get(route('cajero.ventas.ticket', $venta))
        ->assertSee($turno->nombre())->assertSee('Recibido')->assertSee('$1,200.00')->assertSee('Cambio')->assertSee('$40.00');

    // Ya no se cobra dos veces
    $this->post(route('cajero.pedidos.pagar', $pedido), ['metodoPago' => $this->efectivo, 'recibido' => '1200', 'totalEsperado' => '1160.00'])
        ->assertRedirect(route('cajero.pedidos.index'));
    expect(Venta::count())->toBe(1);

    $this->get(route('cajero.pedidos.index', ['ver' => 'cobrados']))->assertSee($pedido->folio)->assertSee($venta->folio);
});

test('en efectivo, si no alcanza lo recibido no se cobra', function () {
    abrirTurno($this);
    $pedido = pedidoDeCaja($this);

    $this->post(route('cajero.pedidos.pagar', $pedido), ['metodoPago' => $this->efectivo, 'recibido' => '1000', 'totalEsperado' => '1160.00'])
        ->assertSessionHasErrors('recibido');
    $this->post(route('cajero.pedidos.pagar', $pedido), ['metodoPago' => $this->efectivo, 'totalEsperado' => '1160.00'])
        ->assertSessionHasErrors('recibido');

    expect(Venta::count())->toBe(0)->and($pedido->refresh()->estadoPedido)->toBe(Pedido::PENDIENTE)
        ->and($this->balatas->refresh()->existencia)->toBe(10);
});

test('cobrar un pedido con saldo pendiente o con descuento necesita autorización', function () {
    abrirTurno($this);
    $pedido = pedidoDeCaja($this, 1, $this->cliente->idCliente);
    $aCredito = ['metodoPago' => $this->tarjeta, 'condicion' => 'CREDITO', 'pagoInicial' => '500', 'totalEsperado' => '1160.00'];

    $this->post(route('cajero.pedidos.pagar', $pedido), $aCredito)->assertSessionHasErrors('autorizacion');
    $this->post(route('cajero.pedidos.pagar', $pedido), $aCredito + GERENTE_AUTORIZA)
        ->assertSessionHasNoErrors()->assertSessionHas('alerta', fn ($a) => str_contains($a[1], 'Queda debiendo $660.00'));
    expect(Venta::first()->saldo())->toBe(660.0);

    $conDescuento = pedidoDeCaja($this, 1, null, 10);   // 1044.00
    $this->get(route('cajero.pedidos.cobrar', $conDescuento))->assertSee('Este pedido tiene descuento');
    $pago = ['metodoPago' => $this->tarjeta, 'totalEsperado' => '1044.00'];
    $this->post(route('cajero.pedidos.pagar', $conDescuento), $pago)->assertSessionHasErrors('autorizacion');
    $this->post(route('cajero.pedidos.pagar', $conDescuento), $pago + GERENTE_AUTORIZA)->assertSessionHasNoErrors();
    expect(Venta::count())->toBe(2);
});

test('si se acabaron las piezas, no deja cobrar y pide editar', function () {
    abrirTurno($this);
    $pedido = pedidoDeCaja($this, 5);
    $this->balatas->forceFill(['existencia' => 2])->save();

    $this->get(route('cajero.pedidos.cobrar', $pedido))->assertSee('no se puede cobrar así')->assertSee('sólo hay 2');
    $this->post(route('cajero.pedidos.pagar', $pedido), ['metodoPago' => $this->tarjeta, 'totalEsperado' => '5800.00'])
        ->assertSessionHasErrors();
    expect(Venta::count())->toBe(0);
});

test('cancelar un pedido pide motivo y autorización, y deja su comprobante', function () {
    abrirTurno($this);
    $pedido = pedidoDeCaja($this);
    $motivo = Motivo::de(Motivo::CANCELACION)->first();

    $this->patch(route('cajero.pedidos.cancelar', $pedido), [])->assertSessionHasErrors('cancelar')->assertSessionHas('modalCancelarPedido', $pedido->idPedido);
    $this->patch(route('cajero.pedidos.cancelar', $pedido), ['motivo' => $motivo->idMotivo, 'comentario' => 'No regresó'])
        ->assertSessionHasErrors('cancelar');   // sin autorización
    expect($pedido->refresh()->estadoPedido)->toBe(Pedido::PENDIENTE);
    $this->patch(route('cajero.pedidos.cancelar', $pedido), ['motivo' => $motivo->idMotivo, 'comentario' => 'No regresó'] + GERENTE_AUTORIZA)
        ->assertSessionHasNoErrors();

    expect($pedido->refresh()->estadoPedido)->toBe(Pedido::CANCELADO)
        ->and($pedido->observaciones)->toBe($motivo->nombre . ' · No regresó');

    $this->get(route('cajero.pedidos.cancelacion', $pedido))->assertOk()->assertSee('CANCELACIÓN DE PEDIDO')->assertSee('No regresó')->assertSee('Carla');
    $this->get(route('cajero.pedidos.cobrar', $pedido))->assertRedirect(route('cajero.pedidos.index'));
    $this->get(route('cajero.pedidos.index'));
    $this->get(route('cajero.pedidos.index', ['ver' => 'cancelados']))->assertSee($pedido->folio);
    $this->get(route('cajero.pedidos.index'))->assertDontSee('>' . $pedido->folio, false);
});

test('el cajero no entra a las pantallas del administrador; las cotizaciones no salen como pedidos', function () {
    $this->get(route('administrador.ventas.index'))->assertRedirect(route('cajero.inicio'));

    abrirTurno($this);
    $cotizacion = \App\Support\RegistroVenta::guardarCotizacion([
        'cliente' => null, 'pedidos' => [$this->balatas->idProducto => 1], 'descuento' => 0, 'totalEsperado' => null,
    ], $this->cajero);
    $this->get(route('cajero.pedidos.index'))->assertDontSee($cotizacion->folio);
    $this->get(route('cajero.pedidos.cobrar', $cotizacion))->assertNotFound();
});
