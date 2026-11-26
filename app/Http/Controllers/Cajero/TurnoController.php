<?php

namespace App\Http\Controllers\Cajero;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Support\Autorizacion;
use App\Support\Caja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/*
    Ventana "Abrir turno" (Figma: "Cajero - Inicio" y "Cajero - Autorizar").
    El cajero cuenta su fondo; si no es el autorizado, un gerente o administrador lo autoriza.
*/
class TurnoController extends Controller
{
    public function abrir(Request $request): RedirectResponse
    {
        $volver = redirect()->route('cajero.inicio');

        try {
            $conteo = Caja::leerConteo($request, 'turno');
            $observaciones = mb_substr((string) $request->input('observaciones', ''), 0, 500);

            $autorizo = null;
            if (abs($conteo['total'] - Caja::fondoAutorizado()) >= 0.005) {
                $resultado = Autorizacion::verificar($request, 'correo_autoriza', 'contrasena_autoriza');
                if (is_string($resultado)) {
                    throw ValidationException::withMessages(['turno' => $resultado]);
                }
                $autorizo = $resultado;
            }

            $turno = Caja::abrir($request->user(), $conteo, $autorizo, $observaciones);
        } catch (ValidationException $e) {
            return $volver->withErrors(['turno' => collect($e->errors())->flatten()->unique()->values()->all()], 'turno')
                ->withInput($request->except(['contrasena_autoriza']));
        }

        return $volver->with('alerta', ['success', "Turno abierto ({$turno->nombre()}) con un fondo de "
            . Producto::formatoPrecio($turno->fondoInicial) . '.']);
    }
}
