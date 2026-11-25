<?php

/*
    Pruebas automáticas de "Mi perfil": php artisan test
    Usan una base de datos temporal en memoria; NO tocan tus usuarios reales.
*/

use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('todos los roles ven su perfil desde el menú del usuario', function (string $rol) {
    $yo = Trabajador::factory()->conRol($rol)->create(['nombres' => 'Laura', 'correoElectronico' => 'laura@prueba.mx']);

    $this->actingAs($yo)->get(route($yo->rutaInicio()))->assertSee(route('perfil'), false);
    $this->actingAs($yo)->get(route('perfil'))
        ->assertOk()->assertSee('Mi perfil')->assertSee('laura@prueba.mx')->assertSee($yo->codigo())
        ->assertDontSee('hashContrasena');
})->with([Trabajador::ROL_ADMINISTRADOR, Trabajador::ROL_GERENTE, Trabajador::ROL_CAJERO, Trabajador::ROL_VENDEDOR, Trabajador::ROL_ALMACENISTA]);

test('sin sesión manda al inicio de sesión', function () {
    $this->get(route('perfil'))->assertRedirect(route('login'));
});

test('editar mis datos (sin cambiar el correo no pide contraseña)', function () {
    $yo = Trabajador::factory()->conRol(Trabajador::ROL_CAJERO)->create(['correoElectronico' => 'yo@prueba.mx']);

    $this->actingAs($yo)->put(route('perfil.datos'), [
        'nombres' => '  Laura  Elena ', 'apellidoPaterno' => 'Ramírez', 'apellidoMaterno' => '',
        'correo' => 'yo@prueba.mx', 'telefono' => '613 123 4567',
    ])->assertRedirect(route('perfil'))->assertSessionHasNoErrors();

    $yo->refresh();
    expect($yo->nombres)->toBe('Laura Elena')->and($yo->apellidoMaterno)->toBeNull()->and($yo->telefono)->toBe('613 123 4567');
});

test('cambiar el correo pide mi contraseña y no deja usar el de otro', function () {
    $yo = Trabajador::factory()->create(['correoElectronico' => 'yo@prueba.mx']);
    Trabajador::factory()->create(['correoElectronico' => 'otro@prueba.mx']);
    $base = ['nombres' => $yo->nombres, 'apellidoPaterno' => $yo->apellidoPaterno];

    $this->actingAs($yo)->put(route('perfil.datos'), $base + ['correo' => 'nuevo@prueba.mx'])
        ->assertSessionHasErrorsIn('datos', 'contrasenaConfirmar');
    $this->actingAs($yo)->put(route('perfil.datos'), $base + ['correo' => 'nuevo@prueba.mx', 'contrasenaConfirmar' => 'mala'])
        ->assertSessionHasErrorsIn('datos', 'contrasenaConfirmar')->assertSessionMissing('_old_input.contrasenaConfirmar');
    $this->actingAs($yo)->put(route('perfil.datos'), $base + ['correo' => 'otro@prueba.mx', 'contrasenaConfirmar' => 'password'])
        ->assertSessionHasErrorsIn('datos', 'correo');

    $this->actingAs($yo)->put(route('perfil.datos'), $base + ['correo' => 'Nuevo@Prueba.MX', 'contrasenaConfirmar' => 'password'])
        ->assertSessionHasNoErrors();
    expect($yo->refresh()->correoElectronico)->toBe('nuevo@prueba.mx');
});

test('no se puede cambiar el rol, el RFC ni activo desde el perfil', function () {
    $yo = Trabajador::factory()->conRol(Trabajador::ROL_VENDEDOR)->create(['correoElectronico' => 'yo@prueba.mx', 'RFC' => 'AAAA900101AA1']);

    $this->actingAs($yo)->put(route('perfil.datos'), [
        'nombres' => 'Yo', 'apellidoPaterno' => 'Mismo', 'correo' => 'yo@prueba.mx',
        'fk_idRol' => 1, 'rol' => 1, 'RFC' => 'ZZZZ900101ZZ9', 'activo' => 0,
    ]);

    $yo->refresh();
    expect($yo->nombreRol())->toBe(Trabajador::ROL_VENDEDOR)->and($yo->RFC)->toBe('AAAA900101AA1')->and($yo->activo)->toBeTrue();
});

test('cambiar mi contraseña', function () {
    $yo = Trabajador::factory()->conRol(Trabajador::ROL_ALMACENISTA)->create();

    $this->actingAs($yo)->put(route('perfil.contrasena'), [
        'contrasenaActual' => 'mala', 'contrasenaNueva' => 'Nueva2026x', 'contrasenaNueva_confirmation' => 'Nueva2026x',
    ])->assertSessionHasErrorsIn('contrasena', 'contrasenaActual')
        ->assertSessionMissing('_old_input.contrasenaActual')->assertSessionMissing('_old_input.contrasenaNueva');

    $this->actingAs($yo)->put(route('perfil.contrasena'), [
        'contrasenaActual' => 'password', 'contrasenaNueva' => 'corta', 'contrasenaNueva_confirmation' => 'corta',
    ])->assertSessionHasErrorsIn('contrasena', 'contrasenaNueva');

    $this->actingAs($yo)->put(route('perfil.contrasena'), [
        'contrasenaActual' => 'password', 'contrasenaNueva' => 'Nueva2026x', 'contrasenaNueva_confirmation' => 'Nueva2026x',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('Nueva2026x', $yo->refresh()->hashContrasena))->toBeTrue();
});

test('subir, cambiar y quitar mi foto de perfil', function () {
    $yo = Trabajador::factory()->conRol(Trabajador::ROL_CAJERO)->create();

    // Sólo imágenes PNG/JPG de hasta 2 MB.
    // (Las imágenes de prueba son PNG: el PHP de php.new no trae soporte para generar JPG)
    $this->actingAs($yo)->post(route('perfil.foto'), ['foto' => UploadedFile::fake()->create('foto.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrorsIn('foto', 'foto');
    $this->actingAs($yo)->post(route('perfil.foto'), ['foto' => UploadedFile::fake()->image('foto.png', 200, 200)->size(3000)])
        ->assertSessionHasErrorsIn('foto', 'foto');

    $this->actingAs($yo)->post(route('perfil.foto'), ['foto' => UploadedFile::fake()->image('foto.png', 200, 200)])
        ->assertSessionHasNoErrors();
    $primera = $yo->refresh()->foto;
    expect($primera)->toStartWith('imagenes/perfiles/foto-')->and(File::exists(public_path($primera)))->toBeTrue();

    // Sale en el menú de arriba
    $this->actingAs($yo)->get(route('perfil'))->assertSee($primera);

    // Cambiarla borra la anterior
    $this->actingAs($yo)->post(route('perfil.foto'), ['foto' => UploadedFile::fake()->image('otra.png', 120, 120)]);
    $segunda = $yo->refresh()->foto;
    expect(File::exists(public_path($primera)))->toBeFalse()->and(File::exists(public_path($segunda)))->toBeTrue();

    // Quitarla
    $this->actingAs($yo)->delete(route('perfil.foto.quitar'))->assertSessionHas('alerta');
    expect($yo->refresh()->foto)->toBeNull()->and(File::exists(public_path($segunda)))->toBeFalse();
});
