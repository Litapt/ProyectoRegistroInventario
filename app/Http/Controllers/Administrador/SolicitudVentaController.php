<?php

namespace App\Http\Controllers\Administrador;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Solicitud;
use App\Models\Venta;
use App\Support\Autorizacion;
use App\Support\EdicionVenta;
use App\Support\RegistroVenta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/*
    Editar una venta por SOLICITUD (Figma: "Modal · Editar venta" y "Solicitudes pendientes").
    La lógica está en App\Support\EdicionVenta.
*/
class SolicitudVentaController extends Controller
{
    /*
        Ventana "Editar venta" → dos botones:
          accion=autorizar → un gerente o administrador pone sus datos y se aplica ya;
          accion=solicitud → queda pendiente en la pestaña "Solicitudes".
    */
    public function editar(Request $request, Venta $venta): RedirectResponse
    {
        $volver = redirect()->to(url()->previous(route('administrador.ventas.consultar')));
        $autorizarAhora = $request->input('accion') === 'autorizar';

        try {
            $datos = RegistroVenta::validar($request, false);

            $motivo = trim((string) $request->input('motivo'));
            if ($motivo === '' || mb_strlen($motivo) > 500) {
                throw ValidationException::withMessages(['editar' => 'Escribe por qué se edita la venta (hasta 500 caracteres).']);
            }

            $autorizo = null;
            if ($autorizarAhora) {
                $autorizo = Autorizacion::verificar($request, 'correo_autoriza', 'contrasena_autoriza');
                if (is_string($autorizo)) {
                    throw ValidationException::withMessages(['editar' => $autorizo]);
                }
            }

            $solicitud = EdicionVenta::solicitar($venta, $datos, $motivo, $request->user(), $autorizo);
        } catch (ValidationException $e) {
            return $volver
                ->withErrors(['editar' => collect($e->errors())->flatten()->unique()->values()->all()])
                ->withInput($request->except(['contrasena_autoriza']))
                ->with('modalEditarVenta', $venta->idVenta);
        }

        $venta->refresh();

        return $volver->with('alerta', $autorizo
            ? ['success', "La venta {$venta->folio} se actualizó (solicitud {$solicitud->folio}). Nuevo total: " . Producto::formatoPrecio($venta->total) . '.']
            : ['success', "Se guardó la solicitud {$solicitud->folio}. La venta cambiará cuando un gerente o administrador la acepte en Ventas → Solicitudes."]);
    }

    /* Pestaña "Solicitudes": pendientes (y aparte, las ya resueltas) */
    public function index(Request $request): View
    {
        $verResueltas = $request->query('ver') === 'resueltas';

        $solicitudes = Solicitud::with(['venta.cliente', 'solicitante.rol', 'responsable'])
            ->where('tipoSolicitud', Solicitud::EDITAR_VENTA)
            ->when($verResueltas,
                fn ($q) => $q->where('estado', '!=', Solicitud::PENDIENTE)->orderByDesc('fechaResolucion'),
                fn ($q) => $q->where('estado', Solicitud::PENDIENTE)->orderBy('fechaSolicitud'))
            ->orderByDesc('idSolicitud')
            ->paginate(10)->withQueryString();

        // Nombres de productos y clientes para "Ver cambios"
        $propuestas = $solicitudes->getCollection()->map(fn ($s) => $s->datosPropuestos ?? []);
        $idsProductos = $propuestas->flatMap(fn ($p) => collect($p['productos'] ?? [])->merge($p['antes']['productos'] ?? [])->pluck('id'))->unique();
        $idsClientes = $propuestas->flatMap(fn ($p) => [$p['cliente'] ?? null, $p['antes']['cliente'] ?? null])->filter()->unique();

        return view('administrador.ventas.solicitudes', [
            'solicitudes' => $solicitudes,
            'verResueltas' => $verResueltas,
            'totalPendientes' => Solicitud::pendientes()->where('tipoSolicitud', Solicitud::EDITAR_VENTA)->count(),
            'productos' => Producto::whereKey($idsProductos)->get()->keyBy('idProducto'),
            'clientes' => Cliente::whereKey($idsClientes)->get()->keyBy('idCliente'),
        ]);
    }

    /* "Aceptar": se aplica la edición con quien tiene la sesión como responsable */
    public function aprobar(Request $request, Solicitud $solicitud): RedirectResponse
    {
        try {
            EdicionVenta::aprobar($solicitud, $request->user());
        } catch (ValidationException $e) {
            return redirect()->route('administrador.ventas.solicitudes')
                ->with('alerta', ['danger', "No se pudo aplicar {$solicitud->folio}: " . collect($e->errors())->flatten()->first()]);
        }

        return redirect()->route('administrador.ventas.solicitudes')
            ->with('alerta', ['success', "Solicitud {$solicitud->folio} aceptada: la venta {$solicitud->venta?->folio} ya tiene los cambios."]);
    }

    public function rechazar(Request $request, Solicitud $solicitud): RedirectResponse
    {
        $request->validate(['comentario' => ['nullable', 'string', 'max:500']]);

        try {
            EdicionVenta::rechazar($solicitud, $request->user(), $request->input('comentario'));
        } catch (ValidationException $e) {
            return redirect()->route('administrador.ventas.solicitudes')
                ->with('alerta', ['warning', collect($e->errors())->flatten()->first()]);
        }

        return redirect()->route('administrador.ventas.solicitudes')
            ->with('alerta', ['success', "Solicitud {$solicitud->folio} rechazada: la venta no cambió."]);
    }
}
