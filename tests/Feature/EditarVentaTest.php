<?php

/*
    Pruebas automáticas de Editar venta por solicitud: php artisan test
    Usan una base de datos temporal en memoria; NO tocan tus ventas ni tu inventario reales.
*/

use App\Models\Bitacora;
use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\MetodoPago;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Solicitud;
use App\Models\Trabajador;
use App\Models\Venta;
use App\Support\RegistroVenta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = Trabajador::factory()->administrador()->create(['correoElectronico' => 'admin@prueba.mx']);
    $this->gerente = Trabajador::factory()->conRol(Trabajador::ROL_GERENTE)->create(['correoElectronico' => 'gerente@prueba.mx']);
    $this->balatas = Producto::factory()->create(['codigoInterno' => 'FR-BOS-001', 'nombre' => 'Balatas delanteras', 'precioVentaActual' => 850, 'existencia' => 10]);
    $this->filtro = Producto::factory()->create(['codigoInterno' => 'FA-MAN-024', 'nombre' => 'Filtro de aceite', 'precioVentaActual' => 145, 'existencia' => 10]);
    $this->bujia = Producto::factory()->create(['codigoInterno' => 'BU-NGK-018', 'nombre' => 'Bujía iridium', 'precioVentaActual' => 185, 'existencia' => 4]);

    // Venta: 1 balatas + 2 filtros = $1,140 + IVA = $1,322.40
    $this->actingAs($this->admin);
    $this->venta = RegistroVenta::registrarVenta([
        'cliente' => null,
        'pedidos' => [$this->balatas->idProducto => 1, $this->filtro->idProducto => 2],
        'descuento' => 0,
        'metodoPago' => MetodoPago::where('nombre', 'Efectivo')->value('idMetodoPago'),
        'totalEsperado' => null,
    ], $this->admin);
});

function edicion($test, array $productos, array $cambios = []): array
{
    return array_merge([
        'productos' => collect($productos)->map(fn ($c, $id) => ['id' => $id, 'cantidad' => $c])->values()->all(),
        'motivo' => 'El cliente cambió de opinión',
        'accion' => 'autorizar',
        'correo_autoriza' => 'gerente@prueba.mx',
        'contrasena_autoriza' => 'password',
    ], $cambios);
}

test('con autorización se aplica ya: renglones, inventario, total, pago y bitácora', function () {
    $maria = Cliente::factory()->create(['nombres' => 'María López']);

    // Se quita un filtro, se quitan las balatas y se agregan 3 bujías; cambia el cliente
    $this->from(route('administrador.ventas.consultar'))
        ->put(route('administrador.ventas.editar', $this->venta), edicion($this, [
            $this->filtro->idProducto => 1, $this->bujia->idProducto => 3,
        ], ['cliente' => $maria->idCliente]))
        ->assertRedirect(route('administrador.ventas.consultar'))
        ->assertSessionHas('alerta', fn ($a) => $a[0] === 'success' && str_contains($a[1], 'se actualizó'));

    $venta = $this->venta->refresh();
    // (145 + 555) = 700 + IVA 112 = 812
    expect((float) $venta->total)->toBe(812.0)
        ->and($venta->fk_idCliente)->toBe($maria->idCliente)
        ->and($venta->detalles()->count())->toBe(2)
        ->and((float) $venta->pagos()->first()->monto)->toBe(812.0)
        ->and($this->balatas->refresh()->existencia)->toBe(10)   // regresaron
        ->and($this->filtro->refresh()->existencia)->toBe(9)     // 10 - 2 + 1
        ->and($this->bujia->refresh()->existencia)->toBe(1);     // 4 - 3

    $solicitud = Solicitud::first();
    expect($solicitud->estado)->toBe(Solicitud::APROBADA)
        ->and($solicitud->folio)->toBe('SOL-' . now()->year . '-0001')
        ->and($solicitud->fk_idTrabajadorResponsable)->toBe($this->gerente->idTrabajador)
        ->and(Bitacora::where('accion', 'EDITAR')->where('tabla', 'ventas')->value('fk_idTrabajadorAutoriza'))->toBe($this->gerente->idTrabajador)
        ->and(MovimientoInventario::where('observaciones', 'like', 'Edición%')->count())->toBe(3);
});

test('los productos que ya estaban conservan su precio; los nuevos usan el de hoy', function () {
    $this->balatas->forceFill(['precioVentaActual' => 999])->save();
    $this->bujia->forceFill(['precioVentaActual' => 200])->save();

    $this->put(route('administrador.ventas.editar', $this->venta), edicion($this, [
        $this->balatas->idProducto => 2, $this->filtro->idProducto => 2, $this->bujia->idProducto => 1,
    ]))->assertSessionHasNoErrors();

    $precio = fn ($p) => (float) DetalleVenta::where('fk_idVenta', $this->venta->idVenta)->where('fk_idProducto', $p->idProducto)->value('precioUnitarioVenta');
    expect($precio($this->balatas))->toBe(850.0)->and($precio($this->bujia))->toBe(200.0);
});

test('se puede cambiar el % de descuento (y también sólo eso)', function () {
    // Sólo el descuento: 10 % de 1140 = 114 → 1026 + IVA 164.16 = 1190.16
    $this->put(route('administrador.ventas.editar', $this->venta), edicion($this, [
        $this->balatas->idProducto => 1, $this->filtro->idProducto => 2,
    ], ['descuento' => 10]))->assertSessionHasNoErrors();

    $venta = $this->venta->refresh();
    expect((float) $venta->descuentoTotal)->toBe(114.0)->and((float) $venta->total)->toBe(1190.16)
        ->and($this->balatas->refresh()->existencia)->toBe(9);   // el inventario no se movió

    // Si no se manda, queda en 0 (la pantalla siempre lo manda); y se puede quitar
    $this->put(route('administrador.ventas.editar', $venta), edicion($this, [
        $this->balatas->idProducto => 1, $this->filtro->idProducto => 2,
    ], ['descuento' => 0]))->assertSessionHasNoErrors();
    expect((float) $venta->refresh()->total)->toBe(1322.4);

    // Más de 100 % no
    $this->put(route('administrador.ventas.editar', $venta), edicion($this, [$this->balatas->idProducto => 1], ['descuento' => 150]))
        ->assertSessionHasErrors('editar');
});

test('la solicitud pendiente guarda el descuento y al aceptarla se aplica', function () {
    $this->put(route('administrador.ventas.editar', $this->venta), edicion($this, [
        $this->balatas->idProducto => 1, $this->filtro->idProducto => 2,
    ], ['descuento' => 25, 'accion' => 'solicitud']));

    $this->get(route('administrador.ventas.solicitudes'))->assertSee('25 %');
    $this->patch(route('administrador.ventas.solicitudes.aprobar', Solicitud::first()));

    // 25 % de 1140 = 285 → 855 + IVA 136.80 = 991.80
    expect((float) $this->venta->refresh()->total)->toBe(991.8);
});

test('sin autorización queda pendiente y la venta no cambia hasta que la aceptan', function () {
    $this->put(route('administrador.ventas.editar', $this->venta), edicion($this, [$this->balatas->idProducto => 2], [
        'accion' => 'solicitud', 'correo_autoriza' => '', 'contrasena_autoriza' => '',
    ]))->assertSessionHas('alerta', fn ($a) => str_contains($a[1], 'Se guardó la solicitud'));

    $solicitud = Solicitud::first();
    expect($solicitud->estado)->toBe(Solicitud::PENDIENTE)
        ->and((float) $this->venta->refresh()->total)->toBe(1322.4)
        ->and($this->balatas->refresh()->existencia)->toBe(9);

    // Aparece en la pestaña con su contador y la venta dice "Edición pendiente"
    $this->get(route('administrador.ventas.solicitudes'))->assertOk()->assertSee($solicitud->folio)->assertSee('El cliente cambió de opinión');
    $this->get(route('administrador.ventas.consultar'))->assertSee('Edición pendiente')
        ->assertDontSee(route('administrador.ventas.editar', $this->venta), false);

    // No se puede pedir otra mientras esté pendiente
    $this->put(route('administrador.ventas.editar', $this->venta), edicion($this, [$this->balatas->idProducto => 3]))
        ->assertSessionHasErrors('editar');

    // Se acepta: ahora sí cambia (2 balatas = 1700 + IVA = 1972)
    $this->patch(route('administrador.ventas.solicitudes.aprobar', $solicitud))
        ->assertSessionHas('alerta', fn ($a) => $a[0] === 'success');
    expect($solicitud->refresh()->estado)->toBe(Solicitud::APROBADA)
        ->and($solicitud->fk_idTrabajadorResponsable)->toBe($this->admin->idTrabajador)
        ->and((float) $this->venta->refresh()->total)->toBe(1972.0)
        ->and($this->balatas->refresh()->existencia)->toBe(8)
        ->and($this->filtro->refresh()->existencia)->toBe(10);

    // No se acepta dos veces
    $this->patch(route('administrador.ventas.solicitudes.aprobar', $solicitud))
        ->assertSessionHas('alerta', fn ($a) => $a[0] === 'danger');
    expect($this->balatas->refresh()->existencia)->toBe(8);
});

test('rechazar deja la venta igual y guarda el comentario', function () {
    $this->put(route('administrador.ventas.editar', $this->venta), edicion($this, [$this->balatas->idProducto => 2], ['accion' => 'solicitud']));
    $solicitud = Solicitud::first();

    $this->patch(route('administrador.ventas.solicitudes.rechazar', $solicitud), ['comentario' => 'No lo pidió así'])
        ->assertSessionHas('alerta', fn ($a) => $a[0] === 'success');

    expect($solicitud->refresh()->estado)->toBe(Solicitud::RECHAZADA)
        ->and($solicitud->comentario)->toBe('No lo pidió así')
        ->and((float) $this->venta->refresh()->total)->toBe(1322.4);

    $this->get(route('administrador.ventas.solicitudes'))->assertSee('No hay solicitudes pendientes');
    $this->get(route('administrador.ventas.solicitudes', ['ver' => 'resueltas']))->assertSee($solicitud->folio)->assertSee('Rechazada');
});

test('si al aceptar ya no alcanza el inventario, no se aplica', function () {
    $this->put(route('administrador.ventas.editar', $this->venta), edicion($this, [
        $this->balatas->idProducto => 1, $this->filtro->idProducto => 2, $this->bujia->idProducto => 4,
    ], ['accion' => 'solicitud']));
    $solicitud = Solicitud::first();

    $this->bujia->existencia = 2;   // mientras tanto se vendieron
    $this->bujia->save();

    $this->patch(route('administrador.ventas.solicitudes.aprobar', $solicitud))
        ->assertSessionHas('alerta', fn ($a) => $a[0] === 'danger' && str_contains($a[1], 'Bujía'));
    expect($solicitud->refresh()->estado)->toBe(Solicitud::PENDIENTE)
        ->and($this->bujia->refresh()->existencia)->toBe(2);
});

test('autorización mala, sin motivo, sin cambios o sin stock: no se guarda y la ventana vuelve a abrirse', function () {
    $intentos = [
        edicion($this, [$this->balatas->idProducto => 2], ['contrasena_autoriza' => 'mala']),
        edicion($this, [$this->balatas->idProducto => 2], ['motivo' => '']),
        edicion($this, [$this->balatas->idProducto => 1, $this->filtro->idProducto => 2]),   // igual que antes
        edicion($this, [$this->bujia->idProducto => 5]),                                     // sólo hay 4
        edicion($this, []),                                                                  // sin productos
    ];

    foreach ($intentos as $intento) {
        $this->from(route('administrador.ventas.consultar'))
            ->put(route('administrador.ventas.editar', $this->venta), $intento)
            ->assertSessionHasErrors('editar')
            ->assertSessionHas('modalEditarVenta', $this->venta->idVenta)
            ->assertSessionMissing('_old_input.contrasena_autoriza');
    }

    expect(Solicitud::count())->toBe(0)->and((float) $this->venta->refresh()->total)->toBe(1322.4);

    // La ventana se arma con lo último que se escribió
    $this->put(route('administrador.ventas.editar', $this->venta), edicion($this, [$this->bujia->idProducto => 5]));
    $this->get(route('administrador.ventas.consultar'))->assertSee('BU-NGK-018');
});

test('una venta cancelada no se edita', function () {
    $this->venta->forceFill(['estadoVenta' => Venta::CANCELADA])->save();

    $this->put(route('administrador.ventas.editar', $this->venta), edicion($this, [$this->balatas->idProducto => 2]))
        ->assertSessionHasErrors('editar');
    expect(Solicitud::count())->toBe(0);
});

test('otros roles no entran a las solicitudes del administrador', function () {
    $this->actingAs($this->gerente)->get(route('administrador.ventas.solicitudes'))->assertRedirect(route('gerente.inicio'));
});
