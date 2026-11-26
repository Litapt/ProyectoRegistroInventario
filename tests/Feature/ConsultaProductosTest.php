<?php

/*
    Pruebas automáticas de "Consultar productos" del cajero (Figma: "Cajero - Menú consulta").
    php artisan test — usan una base de datos temporal; NO tocan tus datos reales.
*/

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->cajero = Trabajador::factory()->conRol(Trabajador::ROL_CAJERO)->create();
    $this->frenos = Categoria::query()->value('idCategoria');
    $this->otraCategoria = Categoria::query()->where('idCategoria', '!=', $this->frenos)->value('idCategoria');
    $this->balatas = Producto::factory()->create(['codigoInterno' => 'FR-BOS-001', 'nombre' => 'Balatas delanteras', 'precioVentaActual' => 850,
        'existencia' => 3, 'stockMinimo' => 5, 'fk_idCategoria' => $this->frenos]);
    $this->filtro = Producto::factory()->create(['codigoInterno' => 'FA-MAN-024', 'nombre' => 'Filtro de aceite', 'existencia' => 0, 'fk_idCategoria' => $this->otraCategoria]);
    $this->bateria = Producto::factory()->create(['codigoInterno' => 'BA-LTH-012', 'nombre' => 'Batería 12 V', 'existencia' => 18, 'fk_idCategoria' => $this->otraCategoria]);
    Producto::factory()->desactivado()->create(['codigoInterno' => 'XX-OLD-001', 'nombre' => 'Balatas viejas']);
    $this->actingAs($this->cajero);
});

test('el cajero busca productos (sin los desactivados) con precio y estado de stock', function () {
    $this->getJson(route('cajero.productos.consultar', ['buscar' => 'balatas']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('productos.0.codigo', 'FR-BOS-001')
        ->assertJsonPath('productos.0.estado', Producto::ESTADO_BAJO)
        ->assertJsonPath('productos.0.precio', '$850.00')
        ->assertJsonPath('productos.0.precioConIva', '$986.00')
        ->assertJsonPath('productos.0.vehiculo', 'Universal');
});

test('filtra por estado de stock y categoría', function () {
    $this->getJson(route('cajero.productos.consultar', ['estado' => Producto::ESTADO_AGOTADO]))
        ->assertJsonPath('total', 1)->assertJsonPath('productos.0.codigo', 'FA-MAN-024');

    $codigos = collect($this->getJson(route('cajero.productos.consultar', ['categoria' => $this->otraCategoria]))->json('productos'))->pluck('codigo');
    expect($codigos->all())->toContain('FA-MAN-024', 'BA-LTH-012')->not->toContain('FR-BOS-001');
});

test('la ventana está en todas las pantallas del cajero, una sola vez', function () {
    $this->post(route('cajero.turno.abrir'), ['billetes' => [500 => 2]]);
    foreach (['cajero.inicio', 'cajero.ventas.consultar', 'cajero.ventas.cotizaciones', 'cajero.pedidos.index', 'cajero.corte.index'] as $ruta) {
        $html = $this->get(route($ruta))->assertOk()->assertSee('Consultar productos')->getContent();
        expect(substr_count($html, 'id="modalConsultaProductos"'))->toBe(1);
    }
});

test('sólo el cajero entra a su consulta', function () {
    $this->actingAs(Trabajador::factory()->administrador()->create());
    $this->get(route('cajero.productos.consultar'))->assertRedirect();
});
