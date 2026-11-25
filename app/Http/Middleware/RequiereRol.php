<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/*
    Revisa en cada petición que el trabajador haya iniciado sesión,
    que siga activo y que tenga uno de los roles permitidos.
    Protege las CARPETAS de cada rol (/administrador, /gerente...).

    En routes/web.php:
        ->middleware('rol:ADMINISTRADOR')
        ->middleware('rol:ADMINISTRADOR,GERENTE')
*/
class RequiereRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Laravel vuelve a leer al trabajador de la base en cada petición,
        // así que "activo" y el rol siempre están al día
        $trabajador = Auth::user();

        if (!$trabajador) {
            return $this->denegar('Tu sesión expiró o no has iniciado sesión.');
        }

        if (!$trabajador->activo) {
            $this->cerrarSesion($request);
            return $this->denegar('El usuario no está activo.');
        }

        if (!$trabajador->tieneRol(...$roles)) {
            $rutaInicio = $trabajador->rutaInicio();

            if ($rutaInicio === null) {
                $this->cerrarSesion($request);
                return $this->denegar('No tienes permiso para acceder a esa página.');
            }

            // Lo regresa a su propia pantalla
            return redirect()
                ->route($rutaInicio)
                ->with('alerta', ['warning', 'No tienes permiso para acceder a esa página.']);
        }

        return $next($request);
    }

    private function denegar(string $mensaje): Response
    {
        return redirect()->route('login')->with('alerta', ['warning', $mensaje]);
    }

    private function cerrarSesion(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
