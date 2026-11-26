<?php

namespace App\Http\Middleware;

use App\Support\Caja;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
    El cajero no puede cobrar ni registrar pedidos sin abrir su turno (contar su fondo).
    Si no lo ha abierto, lo regresa a su pantalla principal, donde aparece "Abrir turno".
    Uso: ->middleware('turno')
*/
class RequiereTurnoAbierto
{
    public function handle(Request $request, Closure $next): Response
    {
        $turno = $request->user() ? Caja::turnoAbierto($request->user()) : null;

        if (!$turno) {
            return redirect()->route('cajero.inicio')
                ->with('alerta', ['warning', 'Primero abre tu turno: cuenta el fondo de la caja.']);
        }

        $request->attributes->set('turno', $turno);

        return $next($request);
    }
}
