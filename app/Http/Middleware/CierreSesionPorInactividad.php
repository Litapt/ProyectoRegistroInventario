<?php

namespace App\Http\Middleware;

use App\Models\Configuracion;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/*
    Cierre de sesión automático (Configuración → Seguridad → "Cierre de sesión automático").
    Si alguien deja la sesión abierta sin usarla más de esos minutos, al siguiente clic
    se le cierra y vuelve al inicio de sesión. 0 = nunca.
*/
class CierreSesionPorInactividad
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $minutos = (int) Configuracion::valor('minutosCierreSesion', '15');
            $ultima = (int) $request->session()->get('ultimaActividad', 0);

            if ($minutos > 0 && $ultima > 0 && now()->timestamp - $ultima > $minutos * 60) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $mensaje = "Tu sesión se cerró porque pasaron más de {$minutos} minutos sin actividad.";

                return $request->expectsJson()
                    ? response()->json(['message' => $mensaje], 401)
                    : redirect()->route('login')->with('alerta', ['warning', $mensaje]);
            }

            $request->session()->put('ultimaActividad', now()->timestamp);
        }

        return $next($request);
    }
}
