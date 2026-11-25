<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/*
    Revisa que el rol del trabajador tenga un PERMISO (tabla rolPermisos).
    Protege ACCIONES sueltas, por ejemplo cancelar una venta.
    Se pone DESPUÉS de 'rol:...', que ya revisó la sesión.

    En routes/web.php:
        ->middleware('permiso:ventas.cancelar')
*/
class RequierePermiso
{
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        $trabajador = Auth::user();

        if (!$trabajador) {
            return redirect()->route('login')->with('alerta', ['warning', 'Tu sesión expiró o no has iniciado sesión.']);
        }

        if (!$trabajador->puede($permiso)) {
            return redirect()
                ->route($trabajador->rutaInicio() ?? 'login')
                ->with('alerta', ['warning', 'Tu rol no tiene permiso para hacer eso.']);
        }

        return $next($request);
    }
}
