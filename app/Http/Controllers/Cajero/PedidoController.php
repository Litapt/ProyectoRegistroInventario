<?php

namespace App\Http\Controllers\Cajero;

use App\Http\Controllers\Administrador\VentaController;
use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\MetodoPago;
use App\Models\Motivo;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Trabajador;
use App\Models\TurnoCaja;
use App\Support\Caja;
use App\Models\Venta;
use App\Support\RegistroVenta;
use App\Support\Tienda;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/*
    Pedidos del CAJERO (Figma: "Cajero - Principal", "Detalles Pedido", "Cobrar", "Imprimir Ticket").

    - Los pedidos los arma el vendedor; aquí se ven, se editan antes de cobrar, se cobran o se cancelan.
      Un pedido NO descuenta inventario; se descuenta al cobrarlo.
    - Cobrar: precios de hoy, método de pago, con cuánto paga y el cambio. Se vuelve venta.
    - Cancelar: un pedido sin cobrar se cancela con su motivo (no movió dinero ni piezas).
    Lo que se guarda lo hace App\Support\RegistroVenta (el mismo código que el administrador).
*/
class PedidoController extends Controller
{
    /* Lista de pedidos: pendientes de cobrar, cobrados y cancelados (Figma: "Cajero - Principal") */
    private const VISTAS = ['pendientes' => Pedido::PENDIENTE, 'cobrados' => Pedido::CONVERTIDA, 'cancelados' => Pedido::CANCELADO];

    public function index(Request $request): View
    {
        $vista = array_key_exists($request->query('ver'), self::VISTAS) ? $request->query('ver') : 'pendientes';

        $filtros = [
            'folio' => mb_substr(trim((string) $request->query('folio', '')), 0, 30),
            'cliente' => mb_substr(trim((string) $request->query('cliente', '')), 0, 60),
            'trabajador' => (int) $request->query('trabajador', 0),
        ];

        $consulta = Pedido::query()->where('tipoPedido', Pedido::PEDIDO)
            ->when($filtros['folio'] !== '', fn ($q) => $q->where('folio', 'like', '%' . $filtros['folio'] . '%'))
            ->when($filtros['cliente'] !== '', function ($q) use ($filtros) {
                if (in_array(mb_strtolower($filtros['cliente']), ['público', 'publico', 'público en general', 'publico en general'], true)) {
                    return $q->whereNull('fk_idCliente');
                }

                return $q->whereHas('cliente', fn ($c) => $c->buscar($filtros['cliente']));
            })
            ->when($filtros['trabajador'] > 0, fn ($q) => $q->where('fk_idTrabajador', $filtros['trabajador']));

        /*
            Los pendientes los ve cualquier cajero (alguien los tiene que cobrar);
            los cobrados y cancelados, sólo los que cobró o canceló él.
        */
        $yo = $request->user()->idTrabajador;
        $cancelados = Bitacora::where('tabla', 'pedidos')->where('accion', 'CANCELAR')->where('fk_idTrabajador', $yo)->select('idRegistro');
        $suyos = fn ($q, $estado) => match ($estado) {
            Pedido::CONVERTIDA => $q->whereHas('venta', fn ($v) => $v->where('fk_idTrabajador', $yo)),
            Pedido::CANCELADO => $q->whereIn('idPedido', $cancelados),
            default => $q,
        };

        $totales = collect(self::VISTAS)->map(fn ($estado) => $suyos((clone $consulta)->where('estadoPedido', $estado), $estado)->count());
        $consulta = $suyos($consulta, self::VISTAS[$vista]);

        $pedidos = $consulta->where('estadoPedido', self::VISTAS[$vista])
            ->with(['cliente', 'trabajador.rol', 'detalles.producto', 'venta'])
            ->when($vista === 'pendientes',
                fn ($q) => $q->orderBy('fechaPedido')->orderBy('idPedido'),          // el que llegó primero, primero
                fn ($q) => $q->orderByDesc('fechaPedido')->orderByDesc('idPedido'))
            ->paginate(10)->withQueryString();

        $turno = $request->attributes->get('turno');

        return view('cajero.pedidos.index', [
            'vista' => $vista,
            'pedidos' => $pedidos,
            'totales' => $totales,
            'filtros' => $filtros,
            'hayFiltros' => $filtros['folio'] !== '' || $filtros['cliente'] !== '' || $filtros['trabajador'] > 0,
            'trabajadores' => Trabajador::with('rol')->where('activo', true)->orderBy('nombres')->get(),
            'turno' => $turno,
            'motivosCancelacion' => Motivo::de(Motivo::CANCELACION)->get(),
            'pedidoACancelar' => session('modalCancelarPedido') ? Pedido::find(session('modalCancelarPedido')) : null,
            'ventaCobrada' => session('ventaCobrada') ? Venta::with('pagos')->find(session('ventaCobrada')) : null,
            'pedidoCancelado' => session('pedidoCancelado') ? Pedido::find(session('pedidoCancelado')) : null,
        ]);
    }

    /* Editar un pedido que todavía no se cobra */
    public function edit(Request $request, Pedido $pedido): View|RedirectResponse
    {
        if ($respuesta = $this->sinCobrar($pedido)) {
            return $respuesta;
        }

        return $this->formulario($request, $pedido);
    }

    public function update(Request $request, Pedido $pedido): RedirectResponse
    {
        if ($respuesta = $this->sinCobrar($pedido)) {
            return $respuesta;
        }

        $datos = $this->datosPedido($request);
        $pedido = RegistroVenta::guardarPedido($datos, $request->user(), $pedido);

        return redirect()->route('cajero.pedidos.index')
            ->with('alerta', ['success', "Pedido {$pedido->folio} actualizado. Nuevo total: " . Producto::formatoPrecio($pedido->total) . '.']);
    }

    /* Pantalla "Cobrar": el pedido con precios de hoy + el importe */
    public function cobrar(Request $request, Pedido $pedido): View|RedirectResponse
    {
        if ($respuesta = $this->sinCobrar($pedido)) {
            return $respuesta;
        }

        $pedido->load(['detalles.producto', 'cliente', 'trabajador']);
        $cuentas = null;
        $problema = null;
        try {
            $cuentas = RegistroVenta::previa($this->datosParaCobrar($pedido) + ['totalEsperado' => null]);
            // Que haya piezas para todo (el inventario se descuenta al cobrar)
            foreach ($cuentas['renglones'] as $r) {
                if ($r['cantidad'] > $r['producto']->existencia) {
                    $problema = "De «{$r['producto']->nombre}» sólo hay {$r['producto']->existencia} en existencia. Edita el pedido para poder cobrarlo.";
                }
            }
        } catch (ValidationException $e) {
            $problema = collect($e->errors())->flatten()->first() . ' Edita el pedido para poder cobrarlo.';
        }

        $metodos = MetodoPago::activos()->get();

        return view('cajero.pedidos.cobrar', [
            'pedido' => $pedido,
            'cuentas' => $cuentas,
            'problema' => $problema,
            'cambioDePrecio' => $cuentas && abs($cuentas['total'] - (float) $pedido->total) >= 0.005,
            'metodosPago' => $metodos,
            'idEfectivo' => $this->idEfectivo($metodos),
            'turno' => $request->attributes->get('turno'),
            'conDescuento' => (float) $pedido->descuentoTotal > 0,
        ]);
    }

    /* Botón "Proceder con el pago" */
    public function pagar(Request $request, Pedido $pedido): RedirectResponse
    {
        if ($respuesta = $this->sinCobrar($pedido)) {
            return $respuesta;
        }

        /** @var TurnoCaja $turno */
        $turno = $request->attributes->get('turno');
        $metodos = MetodoPago::activos()->get();
        $esEfectivo = (int) $request->input('metodoPago') === $this->idEfectivo($metodos);

        $entrada = validator($request->all(), [
            'metodoPago' => ['required', 'integer', Rule::exists('metodosPago', 'idMetodoPago')->where('activo', true)],
            'condicion' => ['nullable', Rule::in([Venta::CONTADO, Venta::CREDITO])],
            'pagoInicial' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'recibido' => [$esEfectivo ? 'required' : 'nullable', 'numeric', 'min:0', 'max:9999999', 'decimal:0,2'],
            'totalEsperado' => ['required', 'numeric'],
        ], [
            'metodoPago.required' => 'Elige el método de pago.',
            'metodoPago.exists' => 'Elige un método de pago de la lista.',
            'recibido.required' => 'Escribe con cuánto paga el cliente (o usa «Exacto»).',
            'recibido.numeric' => 'Lo recibido debe ser una cantidad.',
            'recibido.min' => 'Lo recibido no puede ser negativo.',
            'recibido.decimal' => 'Lo recibido puede tener hasta 2 decimales.',
            'pagoInicial.min' => 'Lo que paga ahora no puede ser negativo.',
            'pagoInicial.decimal' => 'Lo que paga ahora puede tener hasta 2 decimales.',
        ])->validate();

        // Descuento o dejar saldo pendiente: no es un pago directo, lo autoriza un gerente o administrador
        $dejaSaldo = ($entrada['condicion'] ?? null) === Venta::CREDITO
            && round((float) ($entrada['pagoInicial'] ?? 0), 2) < round((float) $entrada['totalEsperado'], 2);
        $conDescuento = (float) $pedido->descuentoTotal > 0;
        $autorizo = Caja::autorizarSiHaceFalta($request, $dejaSaldo || $conDescuento, 'autorizacion',
            $dejaSaldo ? 'Dejar saldo pendiente' : 'Cobrar con descuento');

        $pedido->load('detalles');
        $datos = $this->datosParaCobrar($pedido) + [
            'metodoPago' => (int) $entrada['metodoPago'],
            'totalEsperado' => (float) $entrada['totalEsperado'],
            'condicion' => ($entrada['condicion'] ?? null) === Venta::CREDITO ? Venta::CREDITO : Venta::CONTADO,
            'pagoInicial' => round((float) ($entrada['pagoInicial'] ?? 0), 2),
            'recibido' => $esEfectivo ? round((float) $entrada['recibido'], 2) : null,
            'pedido' => $pedido->idPedido,
            'turno' => $turno->referencia(),
            'autorizo' => $autorizo,
        ];

        $venta = RegistroVenta::registrarVenta($datos, $request->user());

        $pago = $venta->pagos()->latest('idPagoVenta')->first();
        $cambio = $datos['recibido'] !== null && $pago ? $datos['recibido'] - (float) $pago->monto : null;
        $mensaje = "Pedido {$pedido->folio} cobrado: venta {$venta->folio} por " . Producto::formatoPrecio($venta->total) . '.';
        if ($cambio !== null) {
            $mensaje .= ' Cambio: ' . Producto::formatoPrecio($cambio) . '.';
        }
        if ($venta->tieneSaldo()) {
            $mensaje .= ' Queda debiendo ' . Producto::formatoPrecio($venta->saldo()) . '.';
        }

        return redirect()->route('cajero.pedidos.index')
            ->with('ventaCobrada', $venta->idVenta)
            ->with('alerta', ['success', $mensaje]);
    }

    /* Ventana "Cancelar pedido" (la autoriza un gerente o administrador) */
    public function cancelar(Request $request, Pedido $pedido): RedirectResponse
    {
        $volver = redirect()->to(url()->previous(route('cajero.pedidos.index')));

        try {
            $datos = validator($request->all(), [
                'motivo' => ['required', 'integer', Rule::exists('motivos', 'idMotivo')->where('categoriaMotivo', Motivo::CANCELACION)->where('activo', true)],
                'comentario' => ['nullable', 'string', 'max:300'],
            ], [
                'motivo.required' => 'Elige el motivo de la cancelación.',
                'motivo.exists' => 'Elige un motivo de la lista.',
                'comentario.max' => 'El comentario puede tener hasta 300 caracteres.',
            ])->validate();

            Caja::autorizarSiHaceFalta($request, true, 'cancelar', 'Cancelar un pedido');

            $motivo = Motivo::find($datos['motivo'])->nombre;
            $pedido = RegistroVenta::cancelarPedido($pedido, $motivo, $datos['comentario'] ?? null, $request->user());
        } catch (ValidationException $e) {
            return $volver->withErrors(['cancelar' => collect($e->errors())->flatten()->unique()->values()->all()])
                ->withInput($request->except('contrasena_autoriza'))->with('modalCancelarPedido', $pedido->idPedido);
        }

        return redirect()->route('cajero.pedidos.index')
            ->with('pedidoCancelado', $pedido->idPedido)
            ->with('alerta', ['success', "Pedido {$pedido->folio} cancelado."]);
    }

    /* Ticket de la venta (se imprime sin abrir otra pestaña) */
    public function ticket(Request $request, Venta $venta): View
    {
        \App\Http\Controllers\Cajero\VentaController::revisarQueSeaSuya($venta, $request);
        $venta->load(['detalles.producto', 'pagos.metodo', 'cliente', 'trabajador', 'motivoDeCancelacion']);

        return view('compartido.ticketVenta', ['venta' => $venta, 'tienda' => Tienda::datos()]);
    }

    /* Comprobante de un pedido cancelado (Figma: "Cajero - Re Impresión Cancelación") */
    public function comprobanteCancelacion(Request $request, Pedido $pedido): View
    {
        abort_unless($pedido->tipoPedido === Pedido::PEDIDO && $pedido->estadoPedido === Pedido::CANCELADO, 404);
        // Sólo los que canceló él
        abort_unless(Bitacora::where('tabla', 'pedidos')->where('accion', 'CANCELAR')->where('idRegistro', $pedido->idPedido)
            ->where('fk_idTrabajador', $request->user()->idTrabajador)->exists(), 404);
        $pedido->load(['detalles.producto', 'cliente', 'trabajador']);

        $registro = Bitacora::where('tabla', 'pedidos')->where('idRegistro', $pedido->idPedido)
            ->where('accion', 'CANCELAR')->latest('idBitacora')->first();

        return view('cajero.pedidos.cancelacion', [
            'pedido' => $pedido,
            'fechaCancelacion' => $registro?->fecha,
            'cancelo' => \App\Models\Trabajador::find($registro?->detalle['cancelo'] ?? $registro?->fk_idTrabajador)?->nombreCompleto(),
            'tienda' => Tienda::datos(),
        ]);
    }

    // ---------- Buscadores y cliente rápido (los mismos del administrador) ----------

    public function buscarClientes(Request $request): JsonResponse
    {
        return app(VentaController::class)->buscarClientes($request);
    }

    public function buscarProductos(Request $request): JsonResponse
    {
        return app(VentaController::class)->buscarProductos($request);
    }

    // ---------- Ayudantes ----------

    private function formulario(Request $request, ?Pedido $pedido): View
    {
        $pedido?->load(['detalles', 'cliente']);
        $carrito = old('productos') !== null
            ? RegistroVenta::carritoDesde(old('productos'))
            : ($pedido ? RegistroVenta::carritoDePedido($pedido) : []);

        $idCliente = old('cliente', $pedido?->fk_idCliente);
        $cliente = $idCliente ? \App\Models\Cliente::activos()->find((int) $idCliente) : null;

        return view('cajero.pedidos.registrar', [
            'pedido' => $pedido,
            'carritoAnterior' => $carrito,
            'clienteAnterior' => $cliente ? ['id' => $cliente->idCliente, 'nombre' => $cliente->nombre(), 'codigo' => $cliente->codigo(), 'telefono' => $cliente->telefono] : null,
        ]);
    }

    /* Lo que manda la pantalla de Registrar pedido (el cajero no aplica descuentos) */
    private function datosPedido(Request $request): array
    {
        $datos = RegistroVenta::validar($request, false);
        $datos['descuento'] = 0.0;

        return $datos;
    }

    /* Los productos, cliente y descuento guardados en el pedido, para cobrarlo */
    private function datosParaCobrar(Pedido $pedido): array
    {
        return [
            'cliente' => $pedido->fk_idCliente,
            'pedidos' => $pedido->detalles->mapWithKeys(fn ($d) => [$d->fk_idProducto => $d->cantidad])->all(),
            'descuento' => $pedido->porcentajeDescuento(),
        ];
    }

    /* Un pedido ya cobrado o cancelado no se cobra, ni se edita, ni se cancela otra vez */
    private function sinCobrar(Pedido $pedido): ?RedirectResponse
    {
        abort_unless($pedido->tipoPedido === Pedido::PEDIDO, 404);
        if ($pedido->estadoPedido === Pedido::PENDIENTE) {
            return null;
        }

        return redirect()->route('cajero.pedidos.index')->with('alerta', ['warning', "El pedido {$pedido->folio} ya "
            . ($pedido->estadoPedido === Pedido::CONVERTIDA ? 'se cobró.' : 'se canceló.')]);
    }

    public static function idEfectivoDe($metodos): ?int
    {
        return $metodos->first(fn ($m) => mb_strtolower(trim($m->nombre)) === 'efectivo')?->idMetodoPago;
    }

    private function idEfectivo($metodos): ?int
    {
        return $metodos->first(fn ($m) => mb_strtolower(trim($m->nombre)) === 'efectivo')?->idMetodoPago;
    }
}
