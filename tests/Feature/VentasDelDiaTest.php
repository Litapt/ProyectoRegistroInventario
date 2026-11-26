<?php

/*
    Pruebas automáticas de "Imprimir mis ventas del día" (para el corte de caja).
    Cada quien imprime SUS ventas; siempre lo autoriza un gerente o administrador.
    php artisan test — usan una base de datos temporal; NO tocan tus datos reales.
*/

use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\Motivo;
use App\Models\Producto;
use App\Models\Trabajador;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = Trabajador::factory()->administrador()->create(['correoElectronico' => 'admin@prueba.mx', 'nombres' => 'Ana']);
    $this->gerente = Trabajador::factory()->conRol(Trabajador::ROL_GERENTE)->create(['correoElectronico' => 'gerente@prueba.mx']);
    $this->cajero = Trabajador::factory()->conRol(Trabajador::ROL_CAJERO)->create(['nombres' => 'Carla']);
    $this->efectivo = MetodoPago::where('nombre', 'Efectivo')->value('idMetodoPago');
    $this->tarjeta = MetodoPago::where('nombre', '!=', 'Efectivo')->value('idMetodoPago');
    $this->balatas = Producto::factory()->create(['nombre' => 'Balatas', 'precioVentaActual' => 1000, 'existencia' => 50]);
    $this->cliente = Cliente::factory()->create();
    $this->autoriza = ['correo_autoriza' => 'gerente@prueba.mx', 'contrasena_autoriza' => 'password'];
});

function ventaDelTrabajador($test, Trabajador $quien, array $cambios = []): Venta
{
    $test->actingAs($quien);

    return \App\Support\RegistroVenta::registrarVenta(array_merge([
        'cliente' => null, 'pedidos' => [$test->balatas->idProducto => 1], 'descuento' => 0, 'totalEsperado' => null,
        'metodoPago' => $test->efectivo, 'condicion' => 'CONTADO', 'pagoInicial' => 0.0,
    ], $cambios), $quien);   // 1160.00
}

test('sin autorización no se imprime', function () {
    $this->actingAs($this->admin);
    $this->post(route('administrador.ventas.delDia'), ['dia' => now()->toDateString()])
        ->assertRedirect()->assertSessionHas('alerta', fn ($a) => $a[0] === 'danger' && str_contains($a[1], 'gerente o administrador'));
});

test('el administrador imprime SUS ventas del día con totales por método de pago', function () {
    $efectivo = ventaDelTrabajador($this, $this->admin);
    $tarjeta = ventaDelTrabajador($this, $this->admin, ['metodoPago' => $this->tarjeta]);
    $cancelada = ventaDelTrabajador($this, $this->admin);
    \App\Support\RegistroVenta::cancelarVenta($cancelada, Motivo::de(Motivo::CANCELACION)->value('idMotivo'), null, $this->admin, $this->gerente);
    $credito = ventaDelTrabajador($this, $this->admin, ['cliente' => $this->cliente->idCliente, 'condicion' => 'CREDITO', 'pagoInicial' => 160.0]);
    $ajena = ventaDelTrabajador($this, $this->cajero);
    $ayer = ventaDelTrabajador($this, $this->admin);
    $ayer->forceFill(['fechaVenta' => now()->subDay()])->save();
    $ayer->pagos()->update(['fechaPago' => now()->subDay()]);

    $this->actingAs($this->admin);
    $this->post(route('administrador.ventas.delDia'), ['dia' => now()->toDateString()] + $this->autoriza)
        ->assertOk()
        ->assertSee('VENTAS DEL DÍA')->assertSee('Ana')
        ->assertSee($efectivo->folio)->assertSee($tarjeta->folio)->assertSee($credito->folio)
        ->assertSee('Canceladas (1)')->assertSee($cancelada->folio)
        ->assertDontSee($ajena->folio)->assertDontSee($ayer->folio)
        ->assertSee('Ventas (3)')
        ->assertSee('$3,480.00')        // vendido: 1160 × 3 (sin la cancelada)
        ->assertSee('Debe $1,000.00')
        ->assertSee('$1,320.00')        // efectivo cobrado: 1160 + 160 de anticipo
        ->assertSee('Autorizó');
});

test('incluye los abonos que cobró ese día y descuenta las devoluciones', function () {
    $credito = ventaDelTrabajador($this, $this->admin, ['cliente' => $this->cliente->idCliente, 'condicion' => 'CREDITO', 'pagoInicial' => 0.0]);
    $credito->forceFill(['fechaVenta' => now()->subDays(3)])->save();
    \App\Support\RegistroVenta::abonar($credito, 500, $this->efectivo, $this->admin);

    $venta = ventaDelTrabajador($this, $this->admin, ['pedidos' => [$this->balatas->idProducto => 2]]);
    \App\Support\DevolucionVenta::registrar($venta, $this->balatas->idProducto, 1, Motivo::de(Motivo::DEVOLUCION)->value('idMotivo'), null, true, $this->admin, $this->gerente);

    $this->actingAs($this->admin);
    $this->post(route('administrador.ventas.delDia'), ['dia' => now()->toDateString()] + $this->autoriza)
        ->assertSee('Abonos recibidos (1)')->assertSee($credito->folio)
        ->assertSee('Devoluciones (1)')->assertSee('−$1,160.00')
        ->assertSee('$1,660.00');   // efectivo a entregar: 2320 + 500 − 1160
});

test('el gerente y el cajero imprimen las suyas', function () {
    $delGerente = ventaDelTrabajador($this, $this->gerente);
    $delCajero = ventaDelTrabajador($this, $this->cajero);

    $this->actingAs($this->gerente);
    $this->post(route('gerente.ventas.delDia'), $this->autoriza)->assertOk()->assertSee($delGerente->folio)->assertDontSee($delCajero->folio);

    $this->actingAs($this->cajero);
    $this->get(route('cajero.ventas.consultar'))->assertRedirect(route('cajero.inicio'));   // sin turno
    $this->post(route('cajero.ventas.delDia'), $this->autoriza)->assertOk()->assertSee($delCajero->folio)->assertDontSee($delGerente->folio);
});

test('no se puede pedir un día que no ha llegado', function () {
    $this->actingAs($this->admin);
    $this->post(route('administrador.ventas.delDia'), ['dia' => now()->addDay()->toDateString()] + $this->autoriza)
        ->assertSessionHas('alerta', fn ($a) => $a[0] === 'danger');
});
