<?php

namespace App\Http\Controllers\Administrador;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\MetodoPago;
use App\Models\Motivo;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Trabajador;
use App\Models\Venta;
use App\Support\Autorizacion;
use App\Support\RegistroVenta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/*
    Ventas del administrador (Figma Page 2: "Ventas · Registrar venta").
    Las cuentas y el guardado están en App\Support\RegistroVenta y App\Support\CalculoVenta.
*/
class VentaController extends Controller
{
    /* Pestaña "Registrar venta" */
    public function index(): View
    {
        $ventaRegistrada = session('ventaRegistrada') ? Venta::find(session('ventaRegistrada')) : null;
        $clienteAnterior = old('cliente') ? Cliente::activos()->find(old('cliente')) : null;

        return view('administrador.ventas.index', [
            'metodosPago' => MetodoPago::activos()->get(),
            'iva' => (float) Configuracion::valor('iva', '16'),
            'carritoAnterior' => RegistroVenta::carritoDesde(old('productos')),
            'clienteAnterior' => $clienteAnterior ? $this->datosCliente($clienteAnterior) : null,
            'ventaRegistrada' => $ventaRegistrada,
        ]);
    }

    /* Pestaña "Consultar ventas": filtros combinables */
    public function consultar(Request $request): View
    {
        // Igual que en Usuarios y Clientes: las canceladas van aparte ("Ver canceladas")
        $verCanceladas = $request->query('ver') === 'canceladas';

        $filtros = [
            'folio' => mb_substr(trim((string) $request->query('folio', '')), 0, 30),
            'cliente' => mb_substr(trim((string) $request->query('cliente', '')), 0, 60),
            'trabajador' => (int) $request->query('trabajador', 0),
            'desde' => $this->fecha($request->query('desde')),
            'hasta' => $this->fecha($request->query('hasta')),
        ];

        $consulta = Venta::query()
            ->when($filtros['folio'] !== '', fn ($q) => $q->where('folio', 'like', '%' . $filtros['folio'] . '%'))
            ->when($filtros['cliente'] !== '', function ($q) use ($filtros) {
                // "público" encuentra las ventas sin cliente
                if (in_array(mb_strtolower($filtros['cliente']), ['público', 'publico', 'público en general', 'publico en general'], true)) {
                    return $q->whereNull('fk_idCliente');
                }

                return $q->whereHas('cliente', fn ($c) => $c->buscar($filtros['cliente']));
            })
            ->when($filtros['trabajador'] > 0, fn ($q) => $q->where('fk_idTrabajador', $filtros['trabajador']))
            ->when($filtros['desde'], fn ($q) => $q->where('fechaVenta', '>=', $filtros['desde']->copy()->startOfDay()))
            ->when($filtros['hasta'], fn ($q) => $q->where('fechaVenta', '<=', $filtros['hasta']->copy()->endOfDay()));

        // Lo vendido (sin canceladas) y cuántas canceladas hay, con los mismos filtros
        $totalVendido = (clone $consulta)->where('estadoVenta', Venta::COMPLETADA)->sum('total');
        $totalCanceladas = (clone $consulta)->where('estadoVenta', Venta::CANCELADA)->count();

        $ventas = $consulta
            ->where('estadoVenta', $verCanceladas ? Venta::CANCELADA : Venta::COMPLETADA)
            ->with(['cliente', 'trabajador.rol', 'pagos.metodo', 'detalles.producto', 'motivoDeCancelacion', 'trabajadorCancelacion', 'solicitudPendiente', 'devoluciones.producto', 'devoluciones.motivoDe'])
            ->orderByDesc('fechaVenta')->orderByDesc('idVenta')
            ->paginate(10)->withQueryString();

        return view('administrador.ventas.consultar', [
            'ventas' => $ventas,
            'filtros' => $filtros,
            'hayFiltros' => $filtros['folio'] !== '' || $filtros['cliente'] !== '' || $filtros['trabajador'] > 0
                || $filtros['desde'] || $filtros['hasta'],
            'verCanceladas' => $verCanceladas,
            'totalCanceladas' => $totalCanceladas,
            'totalVendido' => (float) $totalVendido,
            'trabajadores' => Trabajador::orderBy('nombres')->orderBy('apellidoPaterno')->get(),
            'motivosCancelacion' => Motivo::de(Motivo::CANCELACION)->get(),
            'ventaACancelar' => session('modalCancelarVenta') ? Venta::find(session('modalCancelarVenta')) : null,
            'edicionReabierta' => $this->edicionReabierta(),
            'motivosDevolucion' => Motivo::de(Motivo::DEVOLUCION)->get(),
            'ventaADevolver' => session('modalDevolucion') ? Venta::with(['detalles.producto', 'devoluciones'])->find(session('modalDevolucion')) : null,
            'devolucionRegistrada' => session('devolucionRegistrada') ? \App\Models\Solicitud::with(['producto', 'venta'])->find(session('devolucionRegistrada')) : null,
        ]);
    }

    /* Ventana "Cancelar venta" (con motivo y autorización) */
    public function cancelar(Request $request, Venta $venta): RedirectResponse
    {
        $volver = fn () => redirect()->to(url()->previous(\App\Support\Ruta::a('ventas.consultar')));

        if ($venta->estaCancelada()) {
            return $volver()->with('alerta', ['warning', "La venta {$venta->folio} ya estaba cancelada."]);
        }

        $datos = validator($request->all(), [
            'motivo' => ['required', 'integer', Rule::exists('motivos', 'idMotivo')
                ->where('categoriaMotivo', Motivo::CANCELACION)->where('activo', true)],
            'comentario' => ['nullable', 'string', 'max:500'],
        ], [
            'motivo.required' => 'Elige el motivo de la cancelación.',
            'motivo.exists' => 'Elige un motivo de la lista.',
            'comentario.max' => 'El comentario puede tener hasta 500 caracteres.',
        ]);

        if ($datos->fails()) {
            return $this->regresarACancelar($volver(), $venta, $datos->errors()->all());
        }

        $autorizo = Autorizacion::verificar($request, 'correo_autoriza', 'contrasena_autoriza');
        if (is_string($autorizo)) {
            return $this->regresarACancelar($volver(), $venta, [$autorizo]);
        }

        try {
            RegistroVenta::cancelarVenta($venta, (int) $request->input('motivo'), $request->input('comentario'), $request->user(), $autorizo);
        } catch (ValidationException $e) {
            return $volver()->with('alerta', ['warning', collect($e->errors())->flatten()->first()]);
        }

        return $volver()
            ->with('alerta', ['success', "La venta {$venta->folio} se canceló y sus piezas regresaron al inventario."]);
    }

    /* Buscador de clientes (lo llama ventas.js mientras se escribe o con "Consultar cliente") */
    public function buscarClientes(Request $request): JsonResponse
    {
        $texto = mb_substr(trim((string) $request->query('q', '')), 0, 60);

        // Sin texto ("Consultar cliente" con la caja vacía): los últimos registrados
        $clientes = $texto === ''
            ? Cliente::activos()->orderByDesc('idCliente')->limit(8)->get()
            : (mb_strlen($texto) < 2 ? collect() : Cliente::activos()->buscar($texto)->orderBy('nombres')->limit(8)->get());

        return response()->json($clientes->map(fn ($c) => $this->datosCliente($c))->values());
    }

    /* Buscador de refacciones: por nombre, código, código de barras, número de parte o marca */
    public function buscarProductos(Request $request): JsonResponse
    {
        $texto = mb_substr(trim((string) $request->query('q', '')), 0, 60);
        if (mb_strlen($texto) < 2) {
            return response()->json([]);
        }

        $productos = Producto::with(['marca', 'compatibilidades.modelo.marca'])
            ->activos()
            ->where(function ($q) use ($texto) {
                $q->where('nombre', 'like', "%{$texto}%")
                    ->orWhere('codigoInterno', 'like', "%{$texto}%")
                    ->orWhere('codigoBarras', $texto)
                    ->orWhere('numeroParteFabricante', 'like', "%{$texto}%")
                    ->orWhereHas('marca', fn ($m) => $m->where('nombre', 'like', "%{$texto}%"));
            })
            ->orderByDesc('existencia')->orderBy('nombre')
            ->limit(10)->get();

        return response()->json($productos->map(fn ($p) => RegistroVenta::datosProducto($p))->values());
    }

    /* Botón "Registrar venta" */
    public function store(Request $request): RedirectResponse
    {
        $datos = RegistroVenta::validar($request, true);
        $venta = RegistroVenta::registrarVenta($datos, $request->user());

        return redirect()
            ->to(\App\Support\Ruta::a('ventas.index'))
            ->with('alerta', ['success', "Venta {$venta->folio} registrada por " . Producto::formatoPrecio($venta->total) . '. El inventario ya se actualizó.'])
            ->with('ventaRegistrada', $venta->idVenta);
    }

    /* Ticket para imprimir */
    public function ticket(Venta $venta): View
    {
        $venta->load(['detalles.producto', 'pagos.metodo', 'cliente', 'trabajador', 'motivoDeCancelacion']);

        return view('administrador.ventas.ticket', [
            'venta' => $venta,
            'tienda' => $this->datosTienda(),
        ]);
    }

    /* Botón "Mostrar cotización": se abre en otra pestaña y NO se guarda nada */
    public function vistaCotizacion(Request $request): View
    {
        $datos = RegistroVenta::validar($request, false);

        return view('administrador.ventas.cotizacion', [
            'folio' => null,
            'fecha' => now(),
            'vigencia' => now()->addDays(Pedido::DIAS_VIGENCIA),
            'cliente' => $datos['cliente'] ? Cliente::find($datos['cliente'])?->nombre() : null,
            'atendio' => $request->user()->nombreCompleto(),
            'cuentas' => RegistroVenta::previa($datos),
            'tienda' => $this->datosTienda(),
        ]);
    }

    /* Botón "Convertir a cotización": se guarda con folio y se abre para imprimir */
    public function guardarCotizacion(Request $request): RedirectResponse
    {
        $datos = RegistroVenta::validar($request, false);
        $cotizacion = RegistroVenta::guardarCotizacion($datos, $request->user());

        return redirect()->to(\App\Support\Ruta::a('ventas.cotizacion', $cotizacion));
    }

    public function cotizacion(Pedido $pedido): View
    {
        abort_unless($pedido->tipoPedido === Pedido::COTIZACION, 404);
        $pedido->load(['detalles.producto', 'cliente', 'trabajador']);

        $cuentas = [
            'renglones' => $pedido->detalles->map(fn ($d) => [
                'producto' => $d->producto,
                'cantidad' => $d->cantidad,
                'precio' => (float) $d->precioUnitario,
                'subtotal' => (float) $d->subtotal,
                'descuento' => (float) $d->descuento,
                'tasa' => (float) $d->tasaIVA,
                'total' => (float) $d->totalLinea,
            ])->all(),
            'subtotal' => (float) $pedido->subtotal,
            'descuento' => (float) $pedido->descuentoTotal,
            'iva' => (float) $pedido->impuestosTotal,
            'total' => (float) $pedido->total,
            'piezas' => (int) $pedido->detalles->sum('cantidad'),
        ];

        return view('administrador.ventas.cotizacion', [
            'folio' => $pedido->folio,
            'fecha' => $pedido->fechaPedido,
            'vigencia' => $pedido->vigencia,
            'cliente' => $pedido->cliente?->nombre(),
            'atendio' => $pedido->trabajador?->nombreCompleto(),
            'cuentas' => $cuentas,
            'tienda' => $this->datosTienda(),
        ]);
    }

    // ---------------------------------------------------------------------

    /* Si "Editar venta" regresó con un error: la venta y lo que se había escrito */
    private function edicionReabierta(): ?array
    {
        $venta = session('modalEditarVenta') ? Venta::find(session('modalEditarVenta')) : null;
        if (!$venta) {
            return null;
        }

        $pedidos = collect(old('productos', []))
            ->filter(fn ($r) => is_array($r) && isset($r['id']))
            ->mapWithKeys(fn ($r) => [(int) $r['id'] => max(1, (int) ($r['cantidad'] ?? 1))])
            ->all();

        return [
            'url' => \App\Support\Ruta::a('ventas.editar', $venta),
            'datos' => $venta->datosParaEditar(
                $pedidos ?: null,
                old('cliente') ? (int) old('cliente') : null,
                is_numeric(old('descuento')) ? min(100, max(0, round((float) old('descuento'), 2))) : null,
            ),
        ];
    }

    private function regresarACancelar(RedirectResponse $destino, Venta $venta, array $errores): RedirectResponse
    {
        return $destino
            ->withErrors(['cancelar' => $errores])
            ->withInput($this->sinContrasena())
            ->with('modalCancelarVenta', $venta->idVenta);
    }

    private function sinContrasena(): array
    {
        return request()->except(['contrasena_autoriza']);
    }

    /* "2026-11-24" → fecha; cualquier otra cosa → null */
    private function fecha(mixed $texto): ?Carbon
    {
        if (!is_string($texto) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $texto)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $texto);
        } catch (\Throwable) {
            return null;
        }
    }

    private function datosCliente(Cliente $cliente): array
    {
        return [
            'id' => $cliente->idCliente,
            'nombre' => $cliente->nombre(),
            'codigo' => $cliente->codigo(),
            'telefono' => $cliente->telefono,
        ];
    }

    /* Encabezado de tickets y cotizaciones (se llena en Configuración → Datos de la tienda) */
    private function datosTienda(): array
    {
        return \App\Support\Tienda::datos();
    }
}
