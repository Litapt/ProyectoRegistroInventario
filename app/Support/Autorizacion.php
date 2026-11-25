<?php

namespace App\Support;

use App\Models\Trabajador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/*
    Revisa el correo y la contraseña que se escriben en las ventanas de "Autorización".
    Regla del equipo: desactivar o reactivar (y otras acciones delicadas) sólo se hace
    con la autorización de un GERENTE o un ADMINISTRADOR activo.

    Uso en un controlador:
        $resultado = Autorizacion::verificar($request);
        if (is_string($resultado)) { ...mostrar $resultado como error... }
        $autorizo = $resultado; // el Trabajador que autorizó
*/
class Autorizacion
{
    // Intentos equivocados antes de bloquear, y cuánto dura el bloqueo
    private const MAX_INTENTOS = 5;
    private const SEGUNDOS_BLOQUEO = 300;

    public const ROLES_QUE_AUTORIZAN = [Trabajador::ROL_ADMINISTRADOR, Trabajador::ROL_GERENTE];

    /* Campos del formulario y sus reglas (correo y contrasena) */
    public const REGLAS = [
        'correo' => ['required', 'email', 'max:120'],
        'contrasena' => ['required', 'string', 'max:255'],
    ];

    public const CAMPOS = [
        'correo' => 'correo del gerente o administrador',
        'contrasena' => 'contraseña',
    ];

    /*
        Regresa el Trabajador que autorizó, o un texto con el error.
        $campoCorreo / $campoContrasena: cómo se llaman los campos en el formulario
        (en Clientes son "correo_autoriza" para no confundirse con el correo del cliente).
    */
    public static function verificar(Request $request, string $campoCorreo = 'correo', string $campoContrasena = 'contrasena'): Trabajador|string
    {
        $correo = trim((string) $request->input($campoCorreo));
        $contrasena = (string) $request->input($campoContrasena);

        if ($correo === '' || $contrasena === '') {
            return 'Escribe el correo y la contraseña de un gerente o administrador para autorizar.';
        }

        // El bloqueo es por la persona que tiene la sesión abierta
        $llave = 'autorizar|' . auth()->id();

        if (RateLimiter::tooManyAttempts($llave, self::MAX_INTENTOS)) {
            $minutos = (int) ceil(RateLimiter::availableIn($llave) / 60);
            return "Demasiados intentos de autorización. Espera {$minutos} minuto(s).";
        }

        $autorizo = Trabajador::with('rol')
            ->where('correoElectronico', $correo)
            ->where('activo', true)
            ->first();

        $valida = $autorizo
            && Hash::check($contrasena, $autorizo->hashContrasena)
            && $autorizo->tieneRol(...self::ROLES_QUE_AUTORIZAN);

        if (!$valida) {
            RateLimiter::hit($llave, self::SEGUNDOS_BLOQUEO);
            // Mismo mensaje en todos los casos: así no se sabe qué correos existen
            return 'No se pudo autorizar: el correo o la contraseña son incorrectos, o esa persona no es gerente ni administrador.';
        }

        RateLimiter::clear($llave);

        return $autorizo;
    }
}
