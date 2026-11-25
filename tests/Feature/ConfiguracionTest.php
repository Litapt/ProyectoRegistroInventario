<?php

/*
    Pruebas automáticas de Configuración: php artisan test
    Usan una base de datos temporal en memoria; NO tocan tu configuración real.
*/

use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\Marca;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Trabajador;
use App\Support\Folios;
use App\Support\Tienda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    Tienda::olvidar();
    $this->admin = Trabajador::factory()->administrador()->create(['correoElectronico' => 'admin@prueba.mx']);
    $this->actingAs($this->admin);
});

afterEach(function () {
    // Se borran los logos que subieron las pruebas
    $logo = Configuracion::valor('tienda.logo');
    if ($logo && str_starts_with($logo, 'imagenes/tienda/')) {
        File::delete(public_path($logo));
    }
    Tienda::olvidar();
});

function datosTienda(array $cambios = []): array
{
    return array_merge([
        'nombre' => 'Refaccionaria El Güero',
        'rfc' => 'rag-900101-ab1',
        'direccion' => 'Blvd. Olachea 123, Ciudad Constitución, BCS',
        'telefono' => '613 132 0000',
        'pie' => 'Gracias por su preferencia',
    ], $cambios);
}

test('el administrador ve Configuración en el menú con sus 5 secciones', function () {
    $this->get(route('administrador.configuracion.index'))
        ->assertOk()
        ->assertSee(route('administrador.configuracion.index'), false)
        ->assertSee('Datos de la tienda')->assertSee('Inventario')->assertSee('Notificaciones')->assertSee('Seguridad')
        ->assertSee('VENTA-' . now()->year . '-0001');
});

test('datos de la tienda: se guardan y salen arriba y en el ticket', function () {
    $this->put(route('administrador.configuracion.tienda'), datosTienda())
        ->assertRedirect()
        ->assertSessionHas('alerta', fn ($a) => $a[0] === 'success');

    expect(Configuracion::valor('tienda.nombreComercial'))->toBe('Refaccionaria El Güero')
        ->and(Configuracion::valor('tienda.rfc'))->toBe('RAG900101AB1')
        ->and(Configuracion::where('clave', 'tienda.rfc')->value('fk_idTrabajadorActualizacion'))->toBe($this->admin->idTrabajador);

    Tienda::olvidar();
    $this->get(route('administrador.inicio'))->assertSee('Refaccionaria El Güero');
});

test('datos inválidos de la tienda se rechazan sólo en su sección', function () {
    $this->put(route('administrador.configuracion.tienda'), datosTienda(['nombre' => '', 'rfc' => '123']))
        ->assertSessionHasErrorsIn('tienda', ['nombre', 'rfc']);

    expect(Configuracion::valor('tienda.nombreComercial'))->toBeNull();
});

test('el logo: sólo PNG/JPG de hasta 2 MB; se guarda en imagenes/tienda y se puede quitar', function () {
    $this->put(route('administrador.configuracion.tienda'), datosTienda(['logo' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf')]))
        ->assertSessionHasErrorsIn('tienda', 'logo');
    $this->put(route('administrador.configuracion.tienda'), datosTienda(['logo' => UploadedFile::fake()->image('logo.png')->size(3000)]))
        ->assertSessionHasErrorsIn('tienda', 'logo');

    $this->put(route('administrador.configuracion.tienda'), datosTienda(['logo' => UploadedFile::fake()->image('logo.png', 64, 64)]))
        ->assertSessionHasNoErrors();
    $ruta = Configuracion::valor('tienda.logo');
    expect($ruta)->toStartWith('imagenes/tienda/logo-')->and(File::exists(public_path($ruta)))->toBeTrue();

    $this->put(route('administrador.configuracion.tienda'), datosTienda(['quitarLogo' => '1']));
    expect(Configuracion::valor('tienda.logo'))->toBeNull()->and(File::exists(public_path($ruta)))->toBeFalse();
});

test('inventario: stock mínimo y listas con comas (crear, desactivar, reactivar)', function () {
    $frenos = Categoria::where('nombre', 'Frenos')->first();
    $producto = Producto::factory()->create(['fk_idCategoria' => $frenos->idCategoria]);

    // Se quita "Frenos", se agrega "Baterías"; "filtros" en minúsculas no se duplica
    $todas = Categoria::activas()->pluck('nombre')->reject(fn ($n) => $n === 'Frenos')->implode(', ');
    $this->put(route('administrador.configuracion.inventario'), [
        'stockMinimo' => 5,
        'categorias' => $todas . ', Baterías,  filtros , Baterías',
        'marcas' => Marca::activas()->pluck('nombre')->implode(', ') . ', Optima',
    ])->assertSessionHasNoErrors();

    expect(Configuracion::valor('stockMinimoDefecto'))->toBe('5')
        ->and($frenos->refresh()->activo)->toBeFalse()
        ->and(Categoria::where('nombre', 'Baterías')->count())->toBe(1)
        ->and(Categoria::whereRaw('LOWER(nombre) = ?', ['filtros'])->count())->toBe(1)
        ->and(Marca::where('nombre', 'Optima')->value('activo'))->toBeTrue()
        ->and($producto->refresh()->fk_idCategoria)->toBe($frenos->idCategoria);   // el producto la conserva

    // Se vuelve a escribir "Frenos": se reactiva (no se crea otra)
    $this->put(route('administrador.configuracion.inventario'), [
        'stockMinimo' => 5, 'categorias' => 'Frenos, Baterías', 'marcas' => 'Bosch',
    ])->assertSessionHasNoErrors();
    expect($frenos->refresh()->activo)->toBeTrue()->and(Categoria::where('nombre', 'Frenos')->count())->toBe(1);
});

test('inventario: listas vacías o nombres muy largos se rechazan', function () {
    $this->put(route('administrador.configuracion.inventario'), ['stockMinimo' => -1, 'categorias' => ' , ', 'marcas' => 'Bosch'])
        ->assertSessionHasErrorsIn('inventario');
    $this->put(route('administrador.configuracion.inventario'), ['stockMinimo' => 3, 'categorias' => str_repeat('x', 81), 'marcas' => 'Bosch'])
        ->assertSessionHasErrorsIn('inventario', 'categorías');

    expect(Categoria::activas()->count())->toBe(11);
});

test('ventas: IVA, formato de folio y métodos de pago', function () {
    $this->put(route('administrador.configuracion.ventas'), [
        'iva' => '8', 'formatoFolio' => 'Venta #{0000}', 'metodosPago' => 'Efectivo, Tarjeta, Vales',
    ])->assertSessionHasNoErrors();

    expect(Configuracion::valor('iva'))->toBe('8')
        ->and(DB::table('folios')->where('tipoDocumento', 'VENTA')->value('formato'))->toBe('Venta #{0000}')
        ->and(MetodoPago::activos()->pluck('nombre')->all())->toBe(['Efectivo', 'Tarjeta', 'Vales'])
        ->and(MetodoPago::where('nombre', 'Transferencia')->value('activo'))->toBeFalse();

    DB::transaction(fn () => expect(Folios::siguiente('VENTA'))->toBe('Venta #0001'));

    $this->put(route('administrador.configuracion.ventas'), ['iva' => '150', 'formatoFolio' => 'X-{0000}', 'metodosPago' => ''])
        ->assertSessionHasErrorsIn('ventas', ['iva', 'formatoFolio', 'metodosPago']);
});

test('al regresar a un formato de folio anterior nunca se repite un folio', function () {
    DB::table('ventas')->insert([
        'folio' => 'Venta #0001', 'fk_idTrabajador' => $this->admin->idTrabajador, 'fechaVenta' => now(),
        'subtotal' => 1, 'impuestosTotal' => 0, 'total' => 1,
    ]);
    DB::table('folios')->where('tipoDocumento', 'VENTA')->update(['formato' => 'Venta #{0000}', 'ultimoNumero' => 0]);

    DB::transaction(fn () => expect(Folios::siguiente('VENTA'))->toBe('Venta #0002'));
});

test('notificaciones: el correo es obligatorio si se activan los avisos por correo', function () {
    $this->put(route('administrador.configuracion.notificaciones'), [
        'alertasStockBajo' => '0', 'notificacionesCorreo' => '1', 'correoNotificaciones' => '',
    ])->assertSessionHasErrorsIn('notificaciones', 'correoNotificaciones');

    $this->put(route('administrador.configuracion.notificaciones'), [
        'alertasStockBajo' => '0', 'notificacionesCorreo' => '1', 'correoNotificaciones' => 'Avisos@Tienda.MX',
    ])->assertSessionHasNoErrors();

    expect(Configuracion::valor('alertasStockBajo'))->toBe('0')
        ->and(Configuracion::valor('correoNotificaciones'))->toBe('avisos@tienda.mx');
});

test('seguridad: cambiar mi contraseña pide la actual y nunca la guarda en la sesión', function () {
    $this->put(route('administrador.configuracion.seguridad'), [
        'minutosCierre' => 30, 'contrasenaActual' => 'mala', 'contrasenaNueva' => 'Nueva2026x', 'contrasenaNueva_confirmation' => 'Nueva2026x',
    ])->assertSessionHasErrorsIn('seguridad', 'contrasenaActual')
        ->assertSessionMissing('_old_input.contrasenaActual')
        ->assertSessionMissing('_old_input.contrasenaNueva');

    $this->put(route('administrador.configuracion.seguridad'), [
        'minutosCierre' => 30, 'contrasenaActual' => 'password', 'contrasenaNueva' => 'Nueva2026x', 'contrasenaNueva_confirmation' => 'Nueva2026x',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('Nueva2026x', $this->admin->refresh()->hashContrasena))->toBeTrue()
        ->and(Configuracion::valor('minutosCierreSesion'))->toBe('30');

    // Sólo el tiempo, sin tocar la contraseña
    $this->put(route('administrador.configuracion.seguridad'), ['minutosCierre' => 0])->assertSessionHasNoErrors();
    expect(Configuracion::valor('minutosCierreSesion'))->toBe('0');
});

test('la sesión se cierra sola después de los minutos sin actividad', function () {
    Configuracion::guardar('minutosCierreSesion', '15');

    $this->get(route('administrador.inicio'))->assertOk();
    $this->travel(16)->minutes();
    $this->get(route('administrador.inicio'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('alerta', fn ($a) => str_contains($a[1], '15 minutos'));
    $this->assertGuest();
});

test('con "Nunca" la sesión no se cierra', function () {
    Configuracion::guardar('minutosCierreSesion', '0');

    $this->get(route('administrador.inicio'))->assertOk();
    $this->travel(300)->minutes();
    $this->get(route('administrador.inicio'))->assertOk();
});

test('otros roles no entran a Configuración', function () {
    $this->actingAs(Trabajador::factory()->conRol(Trabajador::ROL_GERENTE)->create())
        ->get(route('administrador.configuracion.index'))
        ->assertRedirect(route('gerente.inicio'));
});
