<?php

/*
    Pruebas automáticas de Usuarios del administrador: php artisan test
    Usan una base de datos temporal en memoria; NO tocan tus usuarios reales.
*/

use App\Models\Bitacora;
use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = Trabajador::factory()->administrador()->create([
        'nombres' => 'Jesús', 'apellidoPaterno' => 'García', 'correoElectronico' => 'admin@prueba.mx',
    ]);
});

function datosUsuario(array $cambios = []): array
{
    return array_merge([
        'nombres' => '  Carlos  ',
        'apellidoPaterno' => 'Méndez',
        'apellidoMaterno' => 'Ruiz',
        'rfc' => 'memc900101ab1',
        'correo' => 'Carlos.Mendez@Refaccionaria.MX',
        'telefono' => '613 123 4567',
        'rol' => Rol::idDe(Trabajador::ROL_CAJERO),
        'contrasena' => 'Cajero2026',
        'contrasena_confirmation' => 'Cajero2026',
    ], $cambios);
}

// ---------- Consultar ----------

test('el administrador ve Usuarios en el menú y la lista', function () {
    $cajero = Trabajador::factory()->conRol(Trabajador::ROL_CAJERO)->create(['nombres' => 'Lucía']);

    $this->actingAs($this->admin)->get(route('administrador.usuarios.index'))
        ->assertOk()
        ->assertSee(route('administrador.usuarios.index'), false)
        ->assertSee('Lucía')
        ->assertSee($cajero->codigo())
        ->assertSee('Cajero')
        ->assertDontSee('hashContrasena');
});

test('buscar por nombre o correo', function () {
    Trabajador::factory()->create(['nombres' => 'Lucía', 'correoElectronico' => 'lucia@prueba.mx']);
    Trabajador::factory()->create(['nombres' => 'Pedro', 'correoElectronico' => 'pedro@prueba.mx']);

    $this->actingAs($this->admin)->get(route('administrador.usuarios.index', ['buscar' => 'lucia@']))
        ->assertSee('Lucía')->assertDontSee('pedro@prueba.mx');
});

// ---------- Registrar ----------

test('registrar un usuario limpia los datos, cifra la contraseña y puede iniciar sesión', function () {
    $this->actingAs($this->admin)->post(route('administrador.usuarios.store'), datosUsuario())
        ->assertRedirect(route('administrador.usuarios.index'))
        ->assertSessionHas('alerta', fn ($a) => $a[0] === 'success');

    $usuario = Trabajador::where('correoElectronico', 'carlos.mendez@refaccionaria.mx')->first();
    expect($usuario)->not->toBeNull()
        ->and($usuario->nombres)->toBe('Carlos')
        ->and($usuario->RFC)->toBe('MEMC900101AB1')
        ->and($usuario->nombreRol())->toBe(Trabajador::ROL_CAJERO)
        ->and($usuario->activo)->toBeTrue()
        ->and($usuario->hashContrasena)->not->toBe('Cajero2026')
        ->and(Hash::check('Cajero2026', $usuario->hashContrasena))->toBeTrue();

    // La bitácora nunca guarda la contraseña
    $bitacora = Bitacora::where('accion', 'CREAR')->where('tabla', 'trabajadores')->first();
    expect(json_encode($bitacora->detalle))->not->toContain('Cajero2026');

    auth()->logout();
    $this->post(route('login.entrar'), ['correoElectronico' => 'carlos.mendez@refaccionaria.mx', 'contrasena' => 'Cajero2026'])
        ->assertRedirect(route('cajero.inicio'));
});

test('los datos inválidos se rechazan y la ventana se vuelve a abrir sin la contraseña', function () {
    $this->actingAs($this->admin)->post(route('administrador.usuarios.store'), datosUsuario([
        'nombres' => '',
        'rfc' => '123',
        'correo' => 'no-es-correo',
        'rol' => 999,
        'contrasena' => 'corta',
        'contrasena_confirmation' => 'otra',
    ]))->assertSessionHasErrors(['nombres', 'rfc', 'correo', 'rol', 'contrasena'])
        ->assertSessionHas('modalUsuario', 'registrar')
        ->assertSessionMissing('_old_input.contrasena');

    expect(Trabajador::count())->toBe(1);
});

test('la contraseña necesita letras y números', function () {
    $this->actingAs($this->admin)->post(route('administrador.usuarios.store'), datosUsuario([
        'contrasena' => 'solamenteletras', 'contrasena_confirmation' => 'solamenteletras',
    ]))->assertSessionHasErrors('contrasena');
});

test('no se repiten correo ni RFC', function () {
    Trabajador::factory()->create(['correoElectronico' => 'carlos.mendez@refaccionaria.mx', 'RFC' => 'MEMC900101AB1']);

    $this->actingAs($this->admin)->post(route('administrador.usuarios.store'), datosUsuario())
        ->assertSessionHasErrors(['correo', 'rfc']);
});

test('no se puede dar un rol mandando fk_idRol o activo a escondidas', function () {
    $this->actingAs($this->admin)->post(route('administrador.usuarios.store'), datosUsuario([
        'fk_idRol' => Rol::idDe(Trabajador::ROL_ADMINISTRADOR), 'activo' => 0,
    ]));

    $usuario = Trabajador::where('RFC', 'MEMC900101AB1')->first();
    expect($usuario->nombreRol())->toBe(Trabajador::ROL_CAJERO)->and($usuario->activo)->toBeTrue();
});

// ---------- Editar ----------

test('editar sin contraseña conserva la anterior', function () {
    $usuario = Trabajador::factory()->conRol(Trabajador::ROL_CAJERO)->create();
    $hashAntes = $usuario->hashContrasena;

    $this->actingAs($this->admin)->put(route('administrador.usuarios.update', $usuario), datosUsuario([
        'nombres' => 'Carlos Alberto',
        'rol' => Rol::idDe(Trabajador::ROL_GERENTE),
        'contrasena' => '', 'contrasena_confirmation' => '',
    ]))->assertSessionHasNoErrors()->assertSessionHas('alerta', fn ($a) => $a[0] === 'success');

    $usuario->refresh();
    expect($usuario->nombres)->toBe('Carlos Alberto')
        ->and($usuario->nombreRol())->toBe(Trabajador::ROL_GERENTE)
        ->and($usuario->hashContrasena)->toBe($hashAntes);
});

test('editar con nueva contraseña la cambia y la bitácora no la guarda', function () {
    $usuario = Trabajador::factory()->conRol(Trabajador::ROL_CAJERO)->create();

    $this->actingAs($this->admin)->put(route('administrador.usuarios.update', $usuario), datosUsuario([
        'activo' => '1', 'contrasena' => 'Nueva2026x', 'contrasena_confirmation' => 'Nueva2026x',
    ]))->assertSessionHasNoErrors();

    expect(Hash::check('Nueva2026x', $usuario->refresh()->hashContrasena))->toBeTrue();
    $bitacora = Bitacora::where('accion', 'EDITAR')->where('tabla', 'trabajadores')->first();
    expect($bitacora->detalle['hashContrasena'])->toBe('cambió')
        ->and(json_encode($bitacora->detalle))->not->toContain('Nueva2026x');
});

test('la lista separa activos e inactivos', function () {
    Trabajador::factory()->create(['nombres' => 'Lucía', 'correoElectronico' => 'lucia@prueba.mx']);
    Trabajador::factory()->inactivo()->create(['nombres' => 'Pedro', 'correoElectronico' => 'pedro@prueba.mx']);

    $this->actingAs($this->admin)->get(route('administrador.usuarios.index'))
        ->assertSee('lucia@prueba.mx')->assertDontSee('pedro@prueba.mx')->assertSee('Ver inactivos (1)');

    $this->actingAs($this->admin)->get(route('administrador.usuarios.index', ['ver' => 'inactivos']))
        ->assertSee('pedro@prueba.mx')->assertDontSee('lucia@prueba.mx')->assertSee('Reactivar');
});

test('editar ya no activa ni desactiva aunque se mande "activo"', function () {
    $usuario = Trabajador::factory()->conRol(Trabajador::ROL_CAJERO)->create(['correoElectronico' => 'cajero@prueba.mx']);

    $this->actingAs($this->admin)->put(route('administrador.usuarios.update', $usuario), datosUsuario([
        'correo' => 'cajero@prueba.mx', 'activo' => '0', 'contrasena' => '', 'contrasena_confirmation' => '',
    ]))->assertSessionHasNoErrors();
    expect($usuario->refresh()->activo)->toBeTrue();
});

test('un usuario inactivo no se puede editar', function () {
    $usuario = Trabajador::factory()->inactivo()->create(['nombres' => 'Original']);

    $this->actingAs($this->admin)->put(route('administrador.usuarios.update', $usuario), datosUsuario([
        'contrasena' => '', 'contrasena_confirmation' => '',
    ]))->assertSessionHas('alerta', fn ($a) => $a[0] === 'warning');
    expect($usuario->refresh()->nombres)->toBe('Original');
});

test('el administrador no puede cambiarse el rol', function () {
    Trabajador::factory()->administrador()->create(); // aunque haya otro administrador

    $this->actingAs($this->admin)->put(route('administrador.usuarios.update', $this->admin), datosUsuario([
        'rol' => Rol::idDe(Trabajador::ROL_CAJERO), 'correo' => 'admin@prueba.mx', 'contrasena' => '', 'contrasena_confirmation' => '',
    ]))->assertSessionHasErrors('rol')->assertSessionHas('modalUsuario', 'editar');

    expect($this->admin->refresh()->nombreRol())->toBe(Trabajador::ROL_ADMINISTRADOR);

    // Sí puede cambiar sus otros datos
    $this->actingAs($this->admin)->put(route('administrador.usuarios.update', $this->admin), datosUsuario([
        'nombres' => 'Jesús Enrique', 'correo' => 'admin@prueba.mx',
        'rol' => Rol::idDe(Trabajador::ROL_ADMINISTRADOR),
        'contrasena' => '', 'contrasena_confirmation' => '',
    ]))->assertSessionHasNoErrors();
    expect($this->admin->refresh()->nombres)->toBe('Jesús Enrique');
});

test('a otro administrador sí se le puede cambiar el rol', function () {
    $otroAdmin = Trabajador::factory()->administrador()->create();

    // El otro administrador se puede bajar a cajero porque queda $this->admin
    $this->actingAs($this->admin)->put(route('administrador.usuarios.update', $otroAdmin), datosUsuario([
        'correo' => $otroAdmin->correoElectronico, 'rfc' => $otroAdmin->RFC,
        'rol' => Rol::idDe(Trabajador::ROL_CAJERO), 'activo' => '1',
        'contrasena' => '', 'contrasena_confirmation' => '',
    ]))->assertSessionHasNoErrors();
    expect($otroAdmin->refresh()->nombreRol())->toBe(Trabajador::ROL_CAJERO);
});

// ---------- Eliminar (desactivar) ----------

test('eliminar desactiva al usuario con autorización', function () {
    $usuario = Trabajador::factory()->conRol(Trabajador::ROL_VENDEDOR)->create();
    $gerente = Trabajador::factory()->conRol(Trabajador::ROL_GERENTE)->create(['correoElectronico' => 'gerente@prueba.mx']);

    $this->actingAs($this->admin)->patch(route('administrador.usuarios.desactivar', $usuario), [
        'correo_autoriza' => 'gerente@prueba.mx', 'contrasena_autoriza' => 'password',
    ])->assertSessionHas('alerta', fn ($a) => $a[0] === 'success');

    expect($usuario->refresh()->activo)->toBeFalse()
        ->and(Trabajador::whereKey($usuario->idTrabajador)->exists())->toBeTrue()
        ->and(Bitacora::where('accion', 'DESACTIVAR')->where('tabla', 'trabajadores')->value('fk_idTrabajadorAutoriza'))->toBe($gerente->idTrabajador);
});

test('eliminar con autorización saca al usuario y reactivar lo deja entrar otra vez', function () {
    $usuario = Trabajador::factory()->conRol(Trabajador::ROL_CAJERO)->create(['correoElectronico' => 'cajero@prueba.mx']);
    $autoriza = ['correo_autoriza' => 'admin@prueba.mx', 'contrasena_autoriza' => 'password'];

    $this->actingAs($this->admin)->patch(route('administrador.usuarios.desactivar', $usuario), $autoriza);
    auth()->logout();
    $this->post(route('login.entrar'), ['correoElectronico' => 'cajero@prueba.mx', 'contrasena' => 'password']);
    $this->assertGuest();

    // Reactivar con autorización mala no hace nada
    $this->actingAs($this->admin)->patch(route('administrador.usuarios.reactivar', $usuario), ['correo_autoriza' => 'admin@prueba.mx', 'contrasena_autoriza' => 'mala'])
        ->assertSessionHas('alerta', fn ($a) => $a[0] === 'danger');
    expect($usuario->refresh()->activo)->toBeFalse();

    $this->actingAs($this->admin)->patch(route('administrador.usuarios.reactivar', $usuario), $autoriza)
        ->assertSessionHas('alerta', fn ($a) => $a[0] === 'success');
    expect($usuario->refresh()->activo)->toBeTrue()
        ->and(Bitacora::where('accion', 'REACTIVAR')->where('tabla', 'trabajadores')->exists())->toBeTrue();
});

test('sin autorización no se desactiva', function () {
    $usuario = Trabajador::factory()->create();

    $this->actingAs($this->admin)->patch(route('administrador.usuarios.desactivar', $usuario), [
        'correo_autoriza' => 'admin@prueba.mx', 'contrasena_autoriza' => 'mala',
    ])->assertSessionHas('alerta', fn ($a) => $a[0] === 'danger');

    expect($usuario->refresh()->activo)->toBeTrue();
});

test('nadie puede eliminarse a sí mismo', function () {
    $this->actingAs($this->admin)->patch(route('administrador.usuarios.desactivar', $this->admin), [
        'correo_autoriza' => 'admin@prueba.mx', 'contrasena_autoriza' => 'password',
    ])->assertSessionHas('alerta', fn ($a) => $a[0] === 'warning');

    expect($this->admin->refresh()->activo)->toBeTrue();
});

test('otros roles no entran a Usuarios', function () {
    $gerente = Trabajador::factory()->conRol(Trabajador::ROL_GERENTE)->create();

    $this->actingAs($gerente)->get(route('administrador.usuarios.index'))
        ->assertRedirect(route('gerente.inicio'));
});
