<?php

namespace App\Http\Controllers\Cajero;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\TurnoCaja;
use App\Support\Autorizacion;
use App\Support\Caja;
use App\Support\ReporteVentas;
use App\Support\Tienda;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/*
    "Hacer corte de caja" (Figma: "Cajero - Corte de caja").

      - Conteo de efectivo por billetes y monedas.
      - Otros métodos de pago: lo que entró por tarjeta, transferencia...
      - Resumen: lo contado, lo esperado (App\Support\Caja::esperado) y la diferencia.
      - Si falta o sobra dinero, lo autoriza un gerente o administrador ("Autorización de un superior").
      - "Guardar corte" cierra el turno y se imprime solo el comprobante con todas las ventas del turno.
*/
class CorteController extends Controller
{
    public function index(Request $request): View
    {
        $turno = $request->attributes->get('turno');

        return view('cajero.corte.index', [
            'turno' => $turno,
            'esperado' => Caja::esperado($turno),
            'billetes' => Caja::BILLETES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var TurnoCaja $turno */
        $turno = $request->attributes->get('turno');

        try {
            $conteo = Caja::leerConteo($request, 'corte');
            $otros = validator($request->all(), [
                'otros' => ['nullable', 'array'],
                'otros.*' => ['nullable', 'numeric', 'min:0', 'max:9999999', 'decimal:0,2'],
            ], [
                'otros.*.numeric' => 'Lo de tarjeta, transferencia u otros debe ser una cantidad en pesos.',
                'otros.*.min' => 'Lo de tarjeta, transferencia u otros no puede ser negativo.',
                'otros.*.decimal' => 'Lo de tarjeta, transferencia u otros puede tener hasta 2 decimales.',
            ])->validate()['otros'] ?? [];

            // Sólo se revisa la autorización si escribió un correo (si no hace falta, se ignora)
            $autorizo = null;
            if (trim((string) $request->input('correo_autoriza')) !== '') {
                $resultado = Autorizacion::verificar($request, 'correo_autoriza', 'contrasena_autoriza');
                if (is_string($resultado)) {
                    throw ValidationException::withMessages(['corte' => $resultado]);
                }
                $autorizo = $resultado;
            }

            $turno = Caja::cerrar($turno, $conteo, $otros, $autorizo, mb_substr((string) $request->input('observaciones', ''), 0, 500));
        } catch (ValidationException $e) {
            return redirect()->route('cajero.corte.index')
                ->withErrors(['corte' => collect($e->errors())->flatten()->unique()->values()->all()])
                ->withInput($request->except('contrasena_autoriza'));
        }

        $diferencia = (float) $turno->diferencia;

        return redirect()->route('cajero.inicio')
            ->with('corteGuardado', $turno->idTurno)
            ->with('alerta', ['success', "Corte del {$turno->nombre()} guardado"
                . (abs($diferencia) < 0.005 ? ': cuadró exacto.' : ($diferencia < 0 ? ': faltaron ' : ': sobraron ') . Producto::formatoPrecio(abs($diferencia)) . '.')
                . ' El turno quedó cerrado.']);
    }

    /* Comprobante del corte (se imprime solo al guardarlo; se puede volver a imprimir) */
    public function comprobante(Request $request, TurnoCaja $turno): View
    {
        abort_unless((int) $turno->fk_idTrabajador === (int) $request->user()->idTrabajador && $turno->estado === TurnoCaja::CERRADO, 404);
        $turno->load(['trabajador.rol', 'autorizoApertura', 'autorizoCierre']);

        return view('cajero.corte.comprobante', ReporteVentas::delPeriodo($turno->trabajador, $turno->fechaApertura, $turno->fechaCierre) + [
            'turno' => $turno,
            'tienda' => Tienda::datos(),
        ]);
    }
}
