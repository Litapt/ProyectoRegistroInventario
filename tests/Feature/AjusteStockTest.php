<?php

/*
    Pruebas automáticas del Ajuste de stock (entradas y egresos): php artisan test
    Usan una base de datos temporal en memoria; NO tocan tu inventario real.
*/

use App\Models\AjusteInventario;
use App\Models\Motivo;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Trabajador;
use App\Support\Folios;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = Trabajador::factory()->administrador()->create();
    $this->producto = Producto::factory()->create(['nombre' => 'Balatas delanteras', 'existencia' => 24, 'stockMinimo' => 5]);
    $this->motivo = Motivo::de(Motivo::AJUSTE)->first()->idMotivo; // "Llegada de mercancía"
});

function ajustar($test, array $datos)
{
    return $test->actingAs($test->admin)->post(route('administrador.productos.ajuste.store', $test->producto), $datos);
}

test('la pantalla de ajuste se abre con los datos del producto', function () {
    $this->actingAs($this->admin)->get(route('administrador.productos.ajuste', $this->producto))
        ->assertOk()
        ->assertSee('Registrar ajuste de stock')
        ->assertSee('Balatas delanteras')
        ->assertSee('Llegada de mercancía')
        ->assertSee('data-stock="24"', false);
});

test('una entrada suma, guarda el ajuste con folio y el movimiento', function () {
    ajustar($this, ['tipo' => 'ENTRADA', 'cantidad' => '12', 'motivo' => $this->motivo, 'referencia' => 'FAC-2026-0184'])
        ->assertRedirect(route('administrador.productos.index'))
        ->assertSessionHas('alerta', fn ($a) => $a[0] === 'success' && str_contains($a[1], 'pasó de 24 a 36'));

    expect($this->producto->refresh()->existencia)->toBe(36);

    $ajuste = AjusteInventario::first();
    expect($ajuste->folio)->toBe('AJ-' . now()->year . '-0001')
        ->and($ajuste->estado)->toBe('APLICADO')
        ->and($ajuste->existenciaSistema)->toBe(24)
        ->and($ajuste->referencia)->toBe('FAC-2026-0184')
        ->and($ajuste->fk_idTrabajadorAutoriza)->toBe($this->admin->idTrabajador);

    $movimiento = MovimientoInventario::first();
    expect($movimiento->tipoMovimiento)->toBe('ENTRADA')
        ->and($movimiento->existenciaAnterior)->toBe(24)
        ->and($movimiento->existenciaNueva)->toBe(36)
        ->and($movimiento->referenciaTipo)->toBe('AJUSTE')
        ->and($movimiento->referenciaId)->toBe($ajuste->idAjuste);
});

test('un egreso resta y exige la especificación', function () {
    // Sin especificación: se rechaza y no cambia nada
    ajustar($this, ['tipo' => 'SALIDA', 'cantidad' => '4', 'motivo' => $this->motivo])
        ->assertSessionHasErrors('especificacion');
    expect($this->producto->refresh()->existencia)->toBe(24)
        ->and(AjusteInventario::count())->toBe(0);

    ajustar($this, ['tipo' => 'SALIDA', 'cantidad' => '4', 'motivo' => $this->motivo, 'especificacion' => 'Mantenimiento del taller'])
        ->assertSessionHasNoErrors();

    expect($this->producto->refresh()->existencia)->toBe(20)
        ->and(MovimientoInventario::first()->tipoMovimiento)->toBe('SALIDA')
        ->and(MovimientoInventario::first()->observaciones)->toBe('Mantenimiento del taller');
});

test('no se puede retirar más de lo que hay', function () {
    ajustar($this, ['tipo' => 'SALIDA', 'cantidad' => '25', 'motivo' => $this->motivo, 'especificacion' => 'Prueba'])
        ->assertSessionHasErrors('cantidad');

    expect($this->producto->refresh()->existencia)->toBe(24)
        ->and(AjusteInventario::count())->toBe(0)
        ->and(MovimientoInventario::count())->toBe(0)
        ->and(DB::table('folios')->where('tipoDocumento', 'AJUSTE')->value('ultimoNumero'))->toBe(0); // el folio no se gastó
});

test('los datos inválidos se rechazan', function () {
    ajustar($this, ['tipo' => 'REGALO', 'cantidad' => '0', 'motivo' => '99999'])
        ->assertSessionHasErrors(['tipo', 'cantidad', 'motivo']);

    // Un motivo que no es de ajuste (es de cancelación) tampoco sirve
    $deCancelacion = Motivo::de(Motivo::CANCELACION)->first()->idMotivo;
    ajustar($this, ['tipo' => 'ENTRADA', 'cantidad' => '1', 'motivo' => $deCancelacion])
        ->assertSessionHasErrors('motivo');

    expect($this->producto->refresh()->existencia)->toBe(24);
});

test('los folios siguen la numeración y no se repiten', function () {
    ajustar($this, ['tipo' => 'ENTRADA', 'cantidad' => '1', 'motivo' => $this->motivo]);
    ajustar($this, ['tipo' => 'ENTRADA', 'cantidad' => '1', 'motivo' => $this->motivo]);

    expect(AjusteInventario::pluck('folio')->all())->toBe(['AJ-' . now()->year . '-0001', 'AJ-' . now()->year . '-0002']);
});

test('el folio vuelve a 1 al cambiar de año', function () {
    DB::table('folios')->where('tipoDocumento', 'AJUSTE')->update(['ultimoNumero' => 57, 'anio' => now()->year - 1]);

    expect(DB::transaction(fn () => Folios::siguiente('AJUSTE')))->toBe('AJ-' . now()->year . '-0001');
});

test('el administrador puede elegir otro formato de folio', function () {
    DB::table('folios')->where('tipoDocumento', 'VENTA')->update(['formato' => 'Venta #{0000}']);

    expect(DB::transaction(fn () => Folios::siguiente('VENTA')))->toBe('Venta #0001');
});

test('un producto desactivado no se puede ajustar', function () {
    $this->producto->activo = false;
    $this->producto->save();

    $this->actingAs($this->admin)->get(route('administrador.productos.ajuste', $this->producto))
        ->assertRedirect(route('administrador.productos.index'));

    ajustar($this, ['tipo' => 'ENTRADA', 'cantidad' => '5', 'motivo' => $this->motivo])
        ->assertSessionHasErrors('cantidad');

    expect($this->producto->refresh()->existencia)->toBe(24);
});

test('los movimientos aparecen en la pantalla del producto', function () {
    ajustar($this, ['tipo' => 'ENTRADA', 'cantidad' => '6', 'motivo' => $this->motivo]);

    $this->actingAs($this->admin)->get(route('administrador.productos.ajuste', $this->producto))
        ->assertSee('Últimos movimientos')
        ->assertSee('+6')
        ->assertSee('24 → 30');
});

test('otros roles no pueden ajustar desde la carpeta del administrador', function () {
    $almacenista = Trabajador::factory()->conRol(Trabajador::ROL_ALMACENISTA)->create();

    $this->actingAs($almacenista)->post(route('administrador.productos.ajuste.store', $this->producto), [
        'tipo' => 'ENTRADA', 'cantidad' => '5', 'motivo' => $this->motivo,
    ])->assertRedirect(route('almacenista.inicio'));

    expect($this->producto->refresh()->existencia)->toBe(24);
});
