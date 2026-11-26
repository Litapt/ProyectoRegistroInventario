<?php

use App\Http\Middleware\CierreSesionPorInactividad;
use App\Http\Middleware\RequierePermiso;
use App\Http\Middleware\RequiereRol;
use App\Http\Middleware\RequiereTurnoAbierto;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Nombres cortos para las rutas:
        //   ->middleware('rol:ADMINISTRADOR')          quién entra a una sección
        //   ->middleware('permiso:ventas.cancelar')    quién puede hacer una acción
        //   ->middleware('turno')                      el cajero ya abrió su turno de caja
        $middleware->alias([
            'rol' => RequiereRol::class,
            'permiso' => RequierePermiso::class,
            'turno' => RequiereTurnoAbierto::class,
        ]);

        // Cierra la sesión si no se usa en X minutos (Configuración → Seguridad)
        $middleware->web(append: [CierreSesionPorInactividad::class]);

        // Sin sesión, cualquier página protegida manda al inicio de sesión
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Si alguien con la sesión abierta entra al inicio de sesión, lo manda a su pantalla
        $middleware->redirectUsersTo(function (Request $request) {
            $rutaInicio = Auth::user()?->rutaInicio();

            if ($rutaInicio !== null) {
                return route($rutaInicio);
            }

            // Su rol no tiene pantalla: se cierra la sesión para que vea el inicio de sesión
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Cuando un formulario regresa con errores, estas contraseñas NUNCA se guardan en la sesión
        $exceptions->dontFlash([
            'contrasena', 'contrasena_confirmation', 'contrasena_autoriza',
            'contrasenaActual', 'contrasenaNueva', 'contrasenaNueva_confirmation', 'contrasenaConfirmar',
        ]);
    })->create();
