<?php

/*
    Pruebas automáticas del Inicio del administrador: php artisan test
    Usan una base de datos temporal en memoria; NO tocan tus ventas reales.
*/

use App\Models\Producto;
use App\Models\Trabajador;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = Trabajador::factory()->administrador()->create();
    $this->actingAs($this->admin);
});

function ventaDe($test, float $total, $fecha, string $estado = 'COMPLETADA'): void
{
    static $n = 0;
    $n++;
    DB::table('ventas')->insert([
        'folio' => 'T-' . $n, 'fk_idTrabajador' => $test->admin->idTrabajador, 'fechaVenta' => $fecha,
        'estadoVenta' => $estado, 'subtotal' => $total, 'impuestosTotal' => 0, 'total' => $total,
    ]);
}

test('las tarjetas cuentan las ventas del día, sin canceladas, y comparan con ayer', function () {
    ventaDe($this, 1000, now()->setTime(10, 0));
    ventaDe($this, 500, now()->setTime(11, 0));
    ventaDe($this, 9999, now()->setTime(12, 0), Venta::CANCELADA);
    ventaDe($this, 1000, now()->subDay()->setTime(10, 0));

    $this->get(route('administrador.inicio'))
        ->assertOk()
        ->assertSee('$1,500.00')
        ->assertSee('+50%')              // 1500 contra 1000
        ->assertSee('Ventas realizadas')
        ->assertDontSee('$9,999.00');
});

test('la gráfica muestra los 7 días y el total de la semana', function () {
    ventaDe($this, 200, now()->subDays(6)->setTime(9, 0));
    ventaDe($this, 300, now()->setTime(9, 0));
    ventaDe($this, 5000, now()->subDays(7)->setTime(9, 0));   // semana anterior: no cuenta

    $html = $this->get(route('administrador.inicio'))->assertSee('$500.00')->assertSee('Hoy')->getContent();
    expect(substr_count($html, 'class="grafica-columna"'))->toBe(7);
});

test('se puede ver otro día; una fecha futura o inválida muestra hoy', function () {
    ventaDe($this, 750, now()->subDays(3)->setTime(9, 0));

    $this->get(route('administrador.inicio', ['dia' => now()->subDays(3)->format('Y-m-d')]))
        ->assertSee('Así le fue')->assertSee('$750.00');
    $this->get(route('administrador.inicio', ['dia' => now()->addDays(3)->format('Y-m-d')]))->assertSee('Así va tu refaccionaria hoy');
    $this->get(route('administrador.inicio', ['dia' => 'mañana']))->assertOk()->assertSee('Así va tu refaccionaria hoy');
});

test('productos por reponer y su buscador', function () {
    $a = Producto::factory()->create(['nombre' => 'Balatas', 'stockMinimo' => 10]);
    $a->existencia = 4; $a->save();
    $b = Producto::factory()->create(['nombre' => 'Bobina', 'stockMinimo' => 3]);
    $b->existencia = 0; $b->save();
    $c = Producto::factory()->create(['nombre' => 'Amortiguador', 'stockMinimo' => 3]);
    $c->existencia = 50; $c->save();

    $this->get(route('administrador.inicio'))
        ->assertSeeInOrder(['Bobina', 'Balatas'])->assertDontSee('Amortiguador')
        ->assertSee('1 producto con stock bajo')->assertSee('1 producto agotado');

    $this->get(route('administrador.inicio', ['buscar' => 'bobi']))->assertSee('Bobina')->assertDontSee('>Balatas<', false);
});
