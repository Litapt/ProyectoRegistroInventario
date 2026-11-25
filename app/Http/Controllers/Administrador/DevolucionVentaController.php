<?php

namespace App\Http\Controllers\Administrador;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use App\Models\Motivo;
use App\Models\Producto;
use App\Models\Solicitud;
use App\Models\Venta;
use App\Support\Autorizacion;
use App\Support\DevolucionVenta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/*
    "Registrar devolución" (botón de Consultar ventas). La lógica está en App\Support\DevolucionVenta.
    Pide la autorización de un gerente o administrador porque se regresa dinero.
*/
class DevolucionVentaController extends Controller
{
    public function store(Request $request, Venta $venta): RedirectResponse
    {
        $volver = redirect()->to(url()->previous(route('administrador.ventas.consultar')));

        try {
            $datos = validator($request->all(), [
                'producto' => ['required', 'integer'],
                'cantidad' => ['required', 'integer', 'min:1', 'max:9999'],
                'motivo' => ['required', 'integer', Rule::exists('motivos', 'idMotivo')
                    ->where('categoriaMotivo', Motivo::DEVOLUCION)->where('activo', true)],
                'comentario' => ['nullable', 'string', 'max:500'],
            ], [
                'producto.required' => 'Elige el producto que se devuelve.',
                'cantidad.required' => 'Escribe cuántas piezas se devuelven.',
                'cantidad.min' => 'Se debe devolver al menos 1 pieza.',
                'cantidad.integer' => 'La cantidad debe ser un número entero.',
                'motivo.required' => 'Elige el motivo de la devolución.',
                'motivo.exists' => 'Elige un motivo de la lista.',
                'comentario.max' => 'El comentario puede tener hasta 500 caracteres.',
            ])->validate();

            $autorizo = Autorizacion::verificar($request, 'correo_autoriza', 'contrasena_autoriza');
            if (is_string($autorizo)) {
                throw ValidationException::withMessages(['devolucion' => $autorizo]);
            }

            $devolucion = DevolucionVenta::registrar(
                $venta,
                (int) $datos['producto'],
                (int) $datos['cantidad'],
                (int) $datos['motivo'],
                $datos['comentario'] ?? null,
                $request->boolean('regresaInventario'),
                $request->user(),
                $autorizo,
            );
        } catch (ValidationException $e) {
            return $volver
                ->withErrors(['devolucion' => collect($e->errors())->flatten()->unique()->values()->all()])
                ->withInput($request->except(['contrasena_autoriza']))
                ->with('modalDevolucion', $venta->idVenta);
        }

        return $volver
            ->with('alerta', ['success', "Devolución {$devolucion->folio} registrada. Reembolso al cliente: "
                . Producto::formatoPrecio($devolucion->reembolso()) . '.'])
            ->with('devolucionRegistrada', $devolucion->idSolicitud);
    }

    /* Comprobante para imprimir (se imprime directo, sin abrir pestaña) */
    public function comprobante(Solicitud $solicitud): View
    {
        abort_unless($solicitud->tipoSolicitud === Solicitud::DEVOLUCION, 404);
        $solicitud->load(['venta.cliente', 'producto', 'motivoDe', 'solicitante', 'responsable']);

        return view('administrador.ventas.comprobanteDevolucion', [
            'devolucion' => $solicitud,
            'tienda' => \App\Support\Tienda::datos(),
        ]);
    }
}
