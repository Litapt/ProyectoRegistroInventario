<?php

/*
    Pruebas automáticas del CORTE DE CAJA del cajero (Figma: "Cajero - Corte de caja").
    php artisan test — usan una base de datos temporal; NO tocan tus datos reales.
*/

use App\Models\MetodoPago;
use App\Models\Motivo;
use App\Models\Producto;
use App\Models\Trabajador;
use App\Models\TurnoCaja;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->cajero = Trabajador::factory()->conRol(Trabajador::ROL_CAJERO)->create(['nombres' => 'Carla']);
    $this->gerente = Trabajador::factory()->conRol(Trabajador::ROL_GERENTE)->create(['correoElectronico' => 'gerente@prueba.mx']);
    $this->efectivo = MetodoPago::where('nombre', 'Efectivo')->value('idMetodoPago');
    $this->tarjeta = MetodoPago::where('nombre', 'like', '%arjeta%')->value('idMetodoPago');
    $this->balatas = Producto::factory()->create(['precioVentaActual' => 1000, 'existencia' => 20]);
    $this->actingAs($this->cajero);
    $this->post(route('cajero.turno.abrir'), ['billetes' => [500 => 2]]);   // fondo $1,000
    $this->turno = TurnoCaja::first();

    // Una venta en efectivo y una con tarjeta (1160 cada una)
    foreach ([[$this->efectivo, '1160'], [$this->tarjeta, null]] as [$metodo, $recibido]) {
        $this->post(route('cajero.ventas.store'), [
            'productos' => [['id' => $this->balatas->idProducto, 'cantidad' => 1]],
            'metodoPago' => $metodo, 'recibido' => $recibido, 'totalEsperado' => '1160.00',
        ])->assertSessionHasNoErrors();
    }
    $this->autoriza = ['correo_autoriza' => 'gerente@prueba.mx', 'contrasena_autoriza' => 'password'];
    // Efectivo esperado: 1000 + 1160 = 2160  →  4×500 + 1×100 + 1×50 + $10 en monedas
    $this->cuadra = ['billetes' => [500 => 4, 100 => 1, 50 => 1], 'monedas' => '10', 'otros' => [$this->tarjeta => '1160']];
});

test('la pantalla de corte dice lo esperado por método', function () {
    $this->get(route('cajero.corte.index'))
        ->assertOk()->assertSee('Conteo de efectivo')->assertSee('Otros métodos de pago')->assertSee('Resumen del corte')
        ->assertSee('Fondo $1,000.00 + cobrado $1,160.00')
        ->assertSee('$2,160.00')            // efectivo esperado
        ->assertSee('$3,320.00')            // corte esperado (efectivo + tarjeta)
        ->assertSee('Autorización de un superior');
});

test('si cuadra, se guarda sin autorización, cierra el turno y se imprime el comprobante', function () {
    $this->post(route('cajero.corte.store'), $this->cuadra)
        ->assertRedirect(route('cajero.inicio'))
        ->assertSessionHas('alerta', fn ($a) => str_contains($a[1], 'cuadró exacto'));

    $turno = $this->turno->refresh();
    expect($turno->estado)->toBe(TurnoCaja::CERRADO)
        ->and((float) $turno->efectivoContado)->toBe(2160.0)
        ->and((float) $turno->efectivoEsperado)->toBe(2160.0)
        ->and((float) $turno->tarjetaContado)->toBe(1160.0)
        ->and((float) $turno->diferencia)->toBe(0.0)
        ->and($turno->fk_idTrabajadorAutorizaCierre)->toBeNull();

    // Se manda a imprimir solo y luego hay que abrir otro turno
    $this->get(route('cajero.inicio'))
        ->assertSee(route('cajero.corte.comprobante', $turno), false)->assertSee('data-imprimir-auto', false)
        ->assertSee('Abrir turno');
    $this->get(route('cajero.corte.index'))->assertRedirect(route('cajero.inicio'));

    $ventas = Venta::all();
    $this->get(route('cajero.corte.comprobante', $turno))
        ->assertOk()->assertSee('CORTE DE CAJA')->assertSee('CUADRA')
        ->assertSee($ventas[0]->folio)->assertSee($ventas[1]->folio)->assertSee('Ventas (2)');
});

test('si falta dinero, pide autorización de un superior y observaciones', function () {
    $falta = ['billetes' => [500 => 4, 100 => 1], 'monedas' => '10', 'otros' => [$this->tarjeta => '1160']];   // faltan $50

    $this->post(route('cajero.corte.store'), $falta)->assertSessionHasErrors('corte');
    $this->post(route('cajero.corte.store'), $falta + $this->autoriza)->assertSessionHasErrors('corte');   // sin observaciones
    $this->post(route('cajero.corte.store'), $falta + ['correo_autoriza' => 'gerente@prueba.mx', 'contrasena_autoriza' => 'mala', 'observaciones' => 'x'])
        ->assertSessionHasErrors('corte');
    expect($this->turno->refresh()->estado)->toBe(TurnoCaja::ABIERTO);

    $this->post(route('cajero.corte.store'), $falta + $this->autoriza + ['observaciones' => 'Se dio mal un cambio'])
        ->assertSessionHasNoErrors()->assertSessionHas('alerta', fn ($a) => str_contains($a[1], 'faltaron $50.00'));

    $turno = $this->turno->refresh();
    expect((float) $turno->diferencia)->toBe(-50.0)
        ->and($turno->fk_idTrabajadorAutorizaCierre)->toBe($this->gerente->idTrabajador)
        ->and($turno->observacionesCierre)->toBe('Se dio mal un cambio');
    $this->get(route('cajero.corte.comprobante', $turno))->assertSee('FALTAN')->assertSee('Se dio mal un cambio');
});

test('las devoluciones en efectivo se restan de lo esperado; las ventas canceladas no cuentan', function () {
    [$primera, $segunda] = Venta::orderBy('idVenta')->get()->all();
    $this->post(route('cajero.ventas.devolucion', $primera), $this->autoriza + [
        'producto' => $this->balatas->idProducto, 'cantidad' => 1, 'regresaInventario' => '1',
        'motivo' => Motivo::de(Motivo::DEVOLUCION)->value('idMotivo'),
    ])->assertSessionHasNoErrors();
    $this->patch(route('cajero.ventas.cancelar', $segunda), $this->autoriza + ['motivo' => Motivo::de(Motivo::CANCELACION)->value('idMotivo')]);

    $esperado = \App\Support\Caja::esperado($this->turno->refresh());
    expect($esperado['efectivo'])->toBe(1000.0)   // 1000 + 1160 − 1160 de la devolución
        ->and(collect($esperado['otros'])->firstWhere('id', $this->tarjeta)['esperado'])->toBe(0.0);   // la de tarjeta se canceló
});

test('un cajero no ve el comprobante de corte de otro', function () {
    $this->post(route('cajero.corte.store'), $this->cuadra);
    $this->actingAs(Trabajador::factory()->conRol(Trabajador::ROL_CAJERO)->create());
    $this->get(route('cajero.corte.comprobante', $this->turno))->assertNotFound();
});
