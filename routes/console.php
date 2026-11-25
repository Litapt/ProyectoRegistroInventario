<?php

use App\Models\Rol;
use App\Models\Trabajador;
use Illuminate\Support\Facades\Artisan;

/*
    Registra un trabajador desde la terminal: php artisan trabajadores:crear
    Sirve sobre todo para el PRIMER administrador, porque todavía no hay
    nadie que pueda darlo de alta desde la aplicación.
    La contraseña se escribe aquí y se guarda cifrada: nunca queda en un archivo.
*/
Artisan::command('trabajadores:crear', function () {
    $roles = Rol::where('activo', true)->orderBy('idRol')->pluck('nombre')->all();

    if (!$roles) {
        $this->error('No hay roles en la base de datos. Revisa que corriste el script o php artisan migrate.');
        return 1;
    }

    $nombres = trim((string) $this->ask('Nombre(s)'));
    $apellidoP = trim((string) $this->ask('Apellido paterno'));
    $apellidoM = trim((string) $this->ask('Apellido materno (Enter si no tiene)'));
    $rfc = mb_strtoupper(trim((string) $this->ask('RFC')));
    $telefono = trim((string) $this->ask('Teléfono (Enter si no quieres ponerlo)'));
    $correo = mb_strtolower(trim((string) $this->ask('Correo electrónico')));
    $rol = $this->choice('Rol', $roles, 0);
    // secret() no muestra lo que escribes
    $contra = (string) $this->secret('Contraseña (mínimo 8 caracteres, con letras y números; no se ve al escribirla)');
    $confirmacion = (string) $this->secret('Repite la contraseña');

    $errores = [];

    if ($nombres === '' || $apellidoP === '' || $rfc === '' || $correo === '' || $contra === '') {
        $errores[] = 'Nombre, apellido paterno, RFC, correo y contraseña son obligatorios.';
    }

    if ($rfc !== '' && !preg_match('/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/u', $rfc)) {
        $errores[] = 'El RFC no tiene un formato válido.';
    }

    if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo electrónico no tiene un formato válido.';
    }

    if (mb_strlen($contra) < 8 || !preg_match('/[A-Za-z]/', $contra) || !preg_match('/[0-9]/', $contra)) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres, con letras y números.';
    }

    if ($contra !== $confirmacion) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (Trabajador::where('correoElectronico', $correo)->exists()) {
        $errores[] = 'El correo electrónico ya está registrado.';
    }

    if (Trabajador::where('RFC', $rfc)->exists()) {
        $errores[] = 'El RFC ya está registrado.';
    }

    if ($errores) {
        foreach ($errores as $error) {
            $this->error($error);
        }
        return 1;
    }

    $trabajador = new Trabajador([
        'nombres' => $nombres,
        'apellidoPaterno' => $apellidoP,
        'apellidoMaterno' => $apellidoM !== '' ? $apellidoM : null,
        'RFC' => $rfc,
        'telefono' => $telefono !== '' ? $telefono : null,
        'correoElectronico' => $correo,
        'hashContrasena' => $contra, // se cifra sola por el cast "hashed" del modelo
    ]);
    // El rol y el estado se asignan aparte porque no están en $fillable
    $trabajador->fk_idRol = Rol::idDe($rol);
    $trabajador->activo = true;
    $trabajador->save();

    $this->info("Listo: {$trabajador->nombreCompleto()} quedó registrado como {$rol}.");
    return 0;
})->purpose('Registra un trabajador (por ejemplo, el primer administrador)');
