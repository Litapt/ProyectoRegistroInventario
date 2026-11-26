<?php

/*
    Pruebas automáticas de Registrar devolución: php artisan test
    Usan una base de datos temporal en memoria; NO tocan tus ventas ni tu inventario reales.
*/

use App\Models\Bitacora;
use App\Models\MetodoPago;
use App\Models\Motivo;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Solicitud;
use App\Models\Trabajador;
use App\Support\RegistroVenta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = Trabajador::factory()->administrador()->create(['correoElectronico' => 'admin@prueba.mx']);
    $this->gerente = Trabajador::factory()->conRol(Trabajador::ROL_GERENTE)->create(['correoElectronico' => 'gerente@prueba.mx']);
    $this->filtro = Producto::factory()->create(['codigoInterno' => 'FA-MAN-024', 'nombre' => 'Filtro de aceite', 'precioVentaActual' => 145, 'existencia' => 10]);
    $this->balatas = Producto::factory()->create(['codigoInterno' => 'FR-BOS-001', 'nombre' => 'Balatas delanteras', 'precioVentaActual' => 850, 'existencia' => 10]);
    $this->noCompatible = Motivo::de(Motivo::DEVOLUCION)->where('nombre', 'like', '%compatible%')->value('idMotivo');
    $this->danado = Motivo::de(Motivo::DEVOLUCION)->where('nombre', 'like', '%dañado%')->value('idMotivo');

    // 3 filtros con 10 % de descuento: 435 − 43.50 = 391.50 + IVA 62.64 = 454.14 (151.38 por pieza)
    $this->actingAs($this->admin);
    $this->venta = RegistroVenta::registrarVenta([
        'cliente' => null,
        'pedidos' => [$this->filtro->idProducto => 3, $this->balatas->idProducto => 1],
        'descuento' => 10,
        'metodoPago' => MetodoPago::where('nombre', 'Efectivo')->value('idMetodoPago'),
        'totalEsperado' => null,
    ], $this->admin);
});

function devolver($test, array $cambios = []): array
{
    return array_merge([
        'producto' => $test->filtro->idProducto,
        'cantidad' => 1,
        'motivo' => $test->noCompatible,
        'comentario' => 'Era para otro modelo',
        'regresaInventario' => '1',
        'correo_autoriza' => 'gerente@prueba.mx',
        'contrasena_autoriza' => 'password',
    ], $cambios);
}

test('devolver piezas en buen estado: regresan al inventario y se calcula el reembolso', function () {
    $this->from(route('administrador.ventas.consultar'))
        ->post(route('administrador.ventas.devolucion', $this->venta), devolver($this))
        ->assertRedirect(route('administrador.ventas.consultar'))
        ->assertSessionHas('alerta', fn ($a) => $a[0] === 'success' && str_contains($a[1], '$151.38'));

    $d = Solicitud::where('tipoSolicitud', Solicitud::DEVOLUCION)->first();
    expect($d->estado)->toBe(Solicitud::APROBADA)
        ->and($d->cantidad)->toBe(1)
        ->and($d->reembolso())->toBe(151.38)
        ->and($d->fk_idTrabajadorResponsable)->toBe($this->gerente->idTrabajador)
        ->and($this->filtro->refresh()->existencia)->toBe(8)    // 10 − 3 + 1
        ->and(MovimientoInventario::where('tipoMovimiento', MovimientoInventario::DEVOLUCION)->count())->toBe(1)
        ->and(Bitacora::where('accion', 'DEVOLUCION')->exists())->toBe(true)
        ->and((float) $this->venta->refresh()->total)->toBe(1341.54);  // la venta no cambia
});

test('piezas dañadas: no regresan al inventario', function () {
    $this->post(route('administrador.ventas.devolucion', $this->venta), devolver($this, [
        'motivo' => $this->danado, 'regresaInventario' => null,
    ]))->assertSessionHasNoErrors();

    expect($this->filtro->refresh()->existencia)->toBe(7)
        ->and(MovimientoInventario::where('tipoMovimiento', MovimientoInventario::DEVOLUCION)->count())->toBe(0);
});

test('no se devuelven más de las vendidas y la última devolución cuadra los centavos', function () {
    $this->post(route('administrador.ventas.devolucion', $this->venta), devolver($this, ['cantidad' => 2]))->assertSessionHasNoErrors();
    $this->post(route('administrador.ventas.devolucion', $this->venta), devolver($this, ['cantidad' => 2]))
        ->assertSessionHasErrors('devolucion')->assertSessionHas('modalDevolucion', $this->venta->idVenta);
    $this->post(route('administrador.ventas.devolucion', $this->venta), devolver($this, ['cantidad' => 1]))->assertSessionHasNoErrors();

    $reembolsos = Solicitud::where('tipoSolicitud', Solicitud::DEVOLUCION)->get()->sum(fn ($s) => $s->reembolso());
    $totalLinea = (float) $this->venta->detalles()->where('fk_idProducto', $this->filtro->idProducto)->value('totalLinea');
    expect(round($reembolsos, 2))->toBe($totalLinea);

    $this->post(route('administrador.ventas.devolucion', $this->venta), devolver($this))
        ->assertSessionHasErrors('devolucion');
});

test('sin autorización, sin motivo o con un producto que no es de la venta: no se guarda', function () {
    $otro = Producto::factory()->create();
    foreach ([
        devolver($this, ['contrasena_autoriza' => 'mala']),
        devolver($this, ['motivo' => '']),
        devolver($this, ['motivo' => Motivo::de(Motivo::CANCELACION)->value('idMotivo')]),
        devolver($this, ['producto' => $otro->idProducto]),
        devolver($this, ['cantidad' => 0]),
    ] as $intento) {
        $this->post(route('administrador.ventas.devolucion', $this->venta), $intento)
            ->assertSessionHasErrors('devolucion')
            ->assertSessionMissing('_old_input.contrasena_autoriza');
    }

    expect(Solicitud::count())->toBe(0)->and($this->filtro->refresh()->existencia)->toBe(7);
});

test('al cancelar una venta con devolución sólo regresan las piezas que el cliente no devolvió', function () {
    $this->post(route('administrador.ventas.devolucion', $this->venta), devolver($this, ['cantidad' => 2]));
    expect($this->filtro->refresh()->existencia)->toBe(9);

    $this->patch(route('administrador.ventas.cancelar', $this->venta), [
        'motivo' => Motivo::de(Motivo::CANCELACION)->value('idMotivo'),
        'correo_autoriza' => 'gerente@prueba.mx', 'contrasena_autoriza' => 'password',
    ])->assertSessionHas('alerta', fn ($a) => $a[0] === 'success');

    expect($this->filtro->refresh()->existencia)->toBe(10)    // 9 + la 1 que quedaba
        ->and($this->balatas->refresh()->existencia)->toBe(10);
});

test('una venta con devolución ya no se edita y lo muestra en la lista', function () {
    $this->post(route('administrador.ventas.devolucion', $this->venta), devolver($this));

    $this->get(route('administrador.ventas.consultar'))
        ->assertSee('Con devolución')
        ->assertDontSee(route('administrador.ventas.editar', $this->venta), false);

    $this->put(route('administrador.ventas.editar', $this->venta), [
        'productos' => [['id' => $this->filtro->idProducto, 'cantidad' => 1]], 'motivo' => 'x', 'accion' => 'solicitud',
    ])->assertSessionHasErrors('editar');
});

test('el comprobante se puede imprimir', function () {
    $this->post(route('administrador.ventas.devolucion', $this->venta), devolver($this));
    $d = Solicitud::first();

    $this->get(route('administrador.ventas.devolucion.comprobante', $d))
        ->assertOk()->assertSee('COMPROBANTE DE DEVOLUCIÓN')->assertSee($d->folio)->assertSee('$151.38')
        ->assertSee('regresaron al inventario');
});

test('hay motivos de devolución de sobra y los de piezas malas no regresan al inventario por defecto', function () {
    expect(Motivo::de(Motivo::DEVOLUCION)->count())->toBeGreaterThanOrEqual(11);

    $html = $this->get(route('administrador.ventas.consultar'))->assertSee('Pieza equivocada')->getContent();
    expect($html)->toMatch('/data-danado="1">\s*Producto defectuoso/')
        ->and($html)->toMatch('/data-danado="">\s*El cliente compró de más/');
});

test('otros roles no registran devoluciones del administrador', function () {
    $this->actingAs($this->gerente)->post(route('administrador.ventas.devolucion', $this->venta), devolver($this))
        ->assertRedirect(route('gerente.inicio'));
    expect(Solicitud::count())->toBe(0);
});

test('las devoluciones tienen su propia lista en Consultar, separadas de las ventas', function () {
    $this->post(route('administrador.ventas.devolucion', $this->venta), devolver($this));
    $this->post(route('administrador.ventas.devolucion', $this->venta), devolver($this, [
        'producto' => $this->balatas->idProducto, 'motivo' => $this->danado, 'regresaInventario' => '0',
    ]));
    [$primera, $segunda] = Solicitud::where('tipoSolicitud', Solicitud::DEVOLUCION)->orderBy('idSolicitud')->get()->all();
    $this->get(route('administrador.ventas.index'));   // se "come" el aviso verde

    // Realizadas: la venta sí, las devoluciones no (sólo una liga "Con devolución")
    $this->get(route('administrador.ventas.consultar'))
        ->assertSee($this->venta->folio)->assertSee('Devoluciones (2)')
        ->assertDontSee('>' . $primera->folio . '<', false)->assertDontSee('>' . $segunda->folio . '<', false)->assertDontSee('Reembolsado:');

    // Devoluciones: cada una con su venta, producto, reembolso y si regresó al inventario
    $this->get(route('administrador.ventas.consultar', ['ver' => 'devoluciones']))
        ->assertOk()
        ->assertSee($primera->folio)->assertSee($segunda->folio)->assertSee($this->venta->folio)
        ->assertSee('1 × Filtro de aceite')->assertSee('1 × Balatas delanteras')
        ->assertSee('Regresó')->assertSee('Dañada')
        ->assertSee('Reembolsado:')
        ->assertSee(route('administrador.ventas.devolucion.comprobante', $primera), false);

    // Los filtros también sirven aquí (por folio de devolución)
    $this->get(route('administrador.ventas.consultar', ['ver' => 'devoluciones', 'folio' => $segunda->folio]))
        ->assertSee($segunda->folio)->assertDontSee($primera->folio);
});
