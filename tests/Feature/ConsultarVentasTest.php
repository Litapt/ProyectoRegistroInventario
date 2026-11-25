<?php

/*
    Pruebas automáticas de Consultar ventas y Cancelar venta del administrador: php artisan test
    Usan una base de datos temporal en memoria; NO tocan tus ventas ni tu inventario reales.
*/

use App\Models\Bitacora;
use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\Motivo;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Trabajador;
use App\Models\Venta;
use App\Support\RegistroVenta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = Trabajador::factory()->administrador()->create(['correoElectronico' => 'admin@prueba.mx']);
    $this->gerente = Trabajador::factory()->conRol(Trabajador::ROL_GERENTE)->create(['correoElectronico' => 'gerente@prueba.mx']);
    $this->efectivo = MetodoPago::where('nombre', 'Efectivo')->value('idMetodoPago');
    $this->balatas = Producto::factory()->create(['codigoInterno' => 'FR-BOS-001', 'nombre' => 'Balatas delanteras', 'precioVentaActual' => 850, 'existencia' => 10]);
    $this->filtro = Producto::factory()->create(['codigoInterno' => 'FA-MAN-024', 'nombre' => 'Filtro de aceite', 'precioVentaActual' => 145, 'existencia' => 10]);
    $this->motivo = Motivo::de(Motivo::CANCELACION)->value('idMotivo');
});

function venderAhora($test, array $cantidades, ?Cliente $cliente = null, ?Trabajador $quien = null): Venta
{
    $pedidos = [];
    foreach ($cantidades as $id => $cantidad) {
        $pedidos[$id] = $cantidad;
    }

    $test->actingAs($quien ?? $test->admin); // la bitácora anota a quien tiene la sesión

    return RegistroVenta::registrarVenta([
        'cliente' => $cliente?->idCliente, 'pedidos' => $pedidos, 'descuento' => 0,
        'metodoPago' => $test->efectivo, 'totalEsperado' => null,
    ], $quien ?? $test->admin);
}

function datosCancelar($test, array $cambios = []): array
{
    return array_merge([
        'motivo' => $test->motivo, 'comentario' => 'Se llevó otra marca',
        'correo_autoriza' => 'gerente@prueba.mx', 'contrasena_autoriza' => 'password',
    ], $cambios);
}

// ---------- Consultar ----------

test('la pestaña Consultar ventas lista las ventas con su total', function () {
    $maria = Cliente::factory()->create(['nombres' => 'María López']);
    $venta = venderAhora($this, [$this->balatas->idProducto => 1], $maria);

    $this->actingAs($this->admin)->get(route('administrador.ventas.consultar'))
        ->assertOk()
        ->assertSee($venta->folio)->assertSee('María López')->assertSee('$986.00')->assertSee('Completada')
        ->assertSee(route('administrador.ventas.cancelar', $venta), false);
});

test('los filtros se combinan: folio, cliente, trabajador, estado y fechas', function () {
    $maria = Cliente::factory()->create(['nombres' => 'María López', 'telefono' => '613 123 4567']);
    $cajero = Trabajador::factory()->conRol(Trabajador::ROL_CAJERO)->create();
    $v1 = venderAhora($this, [$this->balatas->idProducto => 1], $maria);
    $v2 = venderAhora($this, [$this->filtro->idProducto => 1], null, $cajero);
    $v2->fechaVenta = now()->subDays(10);
    $v2->save();

    $ver = fn (array $f) => $this->actingAs($this->admin)->get(route('administrador.ventas.consultar', $f));

    $ver(['folio' => substr($v1->folio, -4)])->assertSee($v1->folio)->assertDontSee($v2->folio);
    $ver(['cliente' => '6131234'])->assertSee($v1->folio)->assertDontSee($v2->folio);
    $ver(['cliente' => 'público'])->assertSee($v2->folio)->assertDontSee($v1->folio);
    $ver(['trabajador' => $cajero->idTrabajador])->assertSee($v2->folio)->assertDontSee($v1->folio);
    $ver(['desde' => now()->toDateString(), 'hasta' => now()->toDateString()])->assertSee($v1->folio)->assertDontSee($v2->folio);
    $ver(['ver' => 'canceladas'])->assertDontSee($v1->folio)->assertDontSee($v2->folio)->assertSee('No hay ventas canceladas');
    $ver(['desde' => 'no-es-fecha'])->assertOk()->assertSee($v1->folio);
});

// ---------- Cancelar ----------

test('cancelar con motivo y autorización regresa las piezas y guarda todo', function () {
    $venta = venderAhora($this, [$this->balatas->idProducto => 2, $this->filtro->idProducto => 3]);
    expect($this->balatas->refresh()->existencia)->toBe(8);

    $this->actingAs($this->admin)
        ->from(route('administrador.ventas.consultar'))
        ->patch(route('administrador.ventas.cancelar', $venta), datosCancelar($this))
        ->assertRedirect(route('administrador.ventas.consultar'))
        ->assertSessionHas('alerta', fn ($a) => $a[0] === 'success');

    $venta->refresh();
    expect($venta->estadoVenta)->toBe(Venta::CANCELADA)
        ->and($venta->fechaCancelacion)->not->toBeNull()
        ->and($venta->fk_idTrabajadorCancelacion)->toBe($this->admin->idTrabajador)
        ->and($venta->fk_idMotivoCancelacion)->toBe($this->motivo)
        ->and($venta->textoCancelacion())->toContain('Se llevó otra marca')
        ->and($this->balatas->refresh()->existencia)->toBe(10)
        ->and($this->filtro->refresh()->existencia)->toBe(10)
        ->and(MovimientoInventario::where('tipoMovimiento', MovimientoInventario::CANCELACION_VENTA)->count())->toBe(2)
        ->and(Bitacora::where('accion', 'CANCELAR')->value('fk_idTrabajadorAutoriza'))->toBe($this->gerente->idTrabajador)
        ->and(Venta::count())->toBe(1);   // no se borra
});

test('sin autorización válida no se cancela y la ventana vuelve a abrirse', function () {
    $venta = venderAhora($this, [$this->balatas->idProducto => 1]);
    $cajero = Trabajador::factory()->conRol(Trabajador::ROL_CAJERO)->create(['correoElectronico' => 'cajero@prueba.mx']);

    foreach ([datosCancelar($this, ['contrasena_autoriza' => 'mala']), datosCancelar($this, ['correo_autoriza' => 'cajero@prueba.mx'])] as $intento) {
        $this->actingAs($this->admin)->from(route('administrador.ventas.consultar'))
            ->patch(route('administrador.ventas.cancelar', $venta), $intento)
            ->assertSessionHasErrors('cancelar')
            ->assertSessionHas('modalCancelarVenta', $venta->idVenta)
            ->assertSessionMissing('_old_input.contrasena_autoriza');
    }

    expect($venta->refresh()->estadoVenta)->toBe(Venta::COMPLETADA)
        ->and($this->balatas->refresh()->existencia)->toBe(9);
});

test('el motivo es obligatorio y debe ser de cancelación', function () {
    $venta = venderAhora($this, [$this->balatas->idProducto => 1]);
    $motivoAjuste = Motivo::de(Motivo::AJUSTE)->value('idMotivo');

    foreach (['', $motivoAjuste] as $motivo) {
        $this->actingAs($this->admin)->patch(route('administrador.ventas.cancelar', $venta), datosCancelar($this, ['motivo' => $motivo]))
            ->assertSessionHasErrors('cancelar');
    }
    expect($venta->refresh()->estadoVenta)->toBe(Venta::COMPLETADA);
});

test('una venta cancelada no se cancela dos veces', function () {
    $venta = venderAhora($this, [$this->balatas->idProducto => 1]);
    $this->actingAs($this->admin)->patch(route('administrador.ventas.cancelar', $venta), datosCancelar($this));
    $this->actingAs($this->admin)->patch(route('administrador.ventas.cancelar', $venta), datosCancelar($this))
        ->assertSessionHas('alerta', fn ($a) => $a[0] === 'warning');

    expect($this->balatas->refresh()->existencia)->toBe(10)   // no se sumó dos veces
        ->and(MovimientoInventario::where('tipoMovimiento', MovimientoInventario::CANCELACION_VENTA)->count())->toBe(1);
});

test('el ticket de una venta cancelada lo dice con su motivo', function () {
    $venta = venderAhora($this, [$this->balatas->idProducto => 1]);
    $this->actingAs($this->admin)->patch(route('administrador.ventas.cancelar', $venta), datosCancelar($this));

    $this->actingAs($this->admin)->get(route('administrador.ventas.ticket', $venta))
        ->assertOk()->assertSee('CANCELADA')->assertSee('Se llevó otra marca');

    // La lista normal sólo muestra completadas; las canceladas van en "Ver canceladas"
    $otra = venderAhora($this, [$this->filtro->idProducto => 1]);
    $this->actingAs($this->admin)->get(route('administrador.ventas.consultar'))
        ->assertSee($otra->folio)->assertDontSee($venta->folio)->assertSee('Ver canceladas (1)');
    $this->actingAs($this->admin)->get(route('administrador.ventas.consultar', ['ver' => 'canceladas']))
        ->assertSee($venta->folio)->assertDontSee($otra->folio)->assertSee('Cancelada')
        ->assertDontSee(route('administrador.ventas.cancelar', $venta), false);
});

test('otros roles no entran a consultar ni cancelar ventas del administrador', function () {
    $venta = venderAhora($this, [$this->balatas->idProducto => 1]);

    $this->actingAs($this->gerente)->get(route('administrador.ventas.consultar'))->assertRedirect(route('gerente.inicio'));
    $this->actingAs($this->gerente)->patch(route('administrador.ventas.cancelar', $venta), datosCancelar($this))
        ->assertRedirect(route('gerente.inicio'));
    expect($venta->refresh()->estadoVenta)->toBe(Venta::COMPLETADA);
});
