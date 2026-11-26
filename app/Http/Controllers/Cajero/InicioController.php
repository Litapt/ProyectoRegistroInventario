<?php

namespace App\Http\Controllers\Cajero;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Venta;
use App\Support\Caja;
use App\Support\RegistroVenta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
    Pantalla principal del CAJERO: "Nueva venta" (vende directo y cobra).

    - Si todavía no abre su turno, encima aparece la ventana "Abrir turno".
    - El cajero sólo hace PAGOS DIRECTOS por su cuenta. Si pone un descuento o el cliente
      paga sólo una parte, la venta necesita el correo y la contraseña de un gerente o administrador.
    - En efectivo escribe con cuánto paga el cliente y ve el cambio.
    - Al cobrar se queda en esta pantalla (lista para la siguiente venta) y el ticket se imprime solo.
    - Cotizaciones: "Mostrar cotización" (sólo imprime) y "Convertir a cotización" (la guarda),
      sin permiso; con descuento piden autorización (Cajero\VentaController).
    - ?cotizacion=ID: "Cobrar" una cotización guardada.
    Lo que se guarda lo hace App\Support\RegistroVenta (el mismo código que el administrador).
*/
class InicioController extends Controller
{
    public function index(Request $request): View
    {
        $metodos = MetodoPago::activos()->get();
        $productos = old('productos');
        $idCliente = old('cliente');
        $descuento = old('descuento');

        // "Cobrar" una cotización guardada: se arma la venta con sus productos, cliente y descuento
        $cotizacion = null;
        $idCotizacion = old('cotizacion', $request->query('cotizacion'));
        if ($idCotizacion) {
            $cotizacion = Pedido::with('detalles')->where('tipoPedido', Pedido::COTIZACION)
                ->where('fk_idTrabajador', $request->user()->idTrabajador)   // sólo sus cotizaciones
                ->where('estadoPedido', Pedido::PENDIENTE)->find((int) $idCotizacion);
            if ($cotizacion && $productos === null) {
                $productos = $cotizacion->detalles->map(fn ($d) => ['id' => $d->fk_idProducto, 'cantidad' => $d->cantidad])->all();
                $idCliente = $cotizacion->fk_idCliente;
                $descuento = $cotizacion->porcentajeDescuento() ?: null;
            }
        }
        $cliente = $idCliente ? Cliente::activos()->find((int) $idCliente) : null;

        return view('cajero.inicio', [
            'turno' => Caja::turnoAbierto($request->user()),
            'fondoAutorizado' => Caja::fondoAutorizado(),
            'billetes' => Caja::BILLETES,
            'metodosPago' => $metodos,
            'idEfectivo' => PedidoController::idEfectivoDe($metodos),
            'carritoAnterior' => $productos !== null ? RegistroVenta::carritoDesde($productos) : [],
            'descuentoAnterior' => $descuento,
            'cotizacion' => $cotizacion,
            'cotizacionGuardada' => session('cotizacionGuardada') ? Pedido::with('cliente')->find(session('cotizacionGuardada')) : null,
            'clienteAnterior' => $cliente ? ['id' => $cliente->idCliente, 'nombre' => $cliente->nombre(), 'codigo' => $cliente->codigo(), 'telefono' => $cliente->telefono] : null,
            'ventaCobrada' => session('ventaCobrada') ? Venta::with('pagos')->find(session('ventaCobrada')) : null,
            'corteGuardado' => session('corteGuardado') ? \App\Models\TurnoCaja::find(session('corteGuardado')) : null,
        ]);
    }

    /* Botón "Cobrar venta" */
    public function store(Request $request): RedirectResponse
    {
        $datos = RegistroVenta::validar($request, true);
        // Sólo puede cobrar sus propias cotizaciones
        if ($datos['cotizacion'] && !Pedido::whereKey($datos['cotizacion'])->where('fk_idTrabajador', $request->user()->idTrabajador)->exists()) {
            $datos['cotizacion'] = null;
        }
        $metodos = MetodoPago::activos()->get();
        $esEfectivo = $datos['metodoPago'] === PedidoController::idEfectivoDe($metodos);

        // Lo que no es pago directo necesita autorización (el total exacto lo revisa RegistroVenta)
        $dejaSaldo = $datos['condicion'] === Venta::CREDITO && $datos['pagoInicial'] < round((float) ($datos['totalEsperado'] ?? 0), 2);
        $conDescuento = $datos['descuento'] > 0;
        $que = match (true) {
            $dejaSaldo && $conDescuento => 'Dar descuento y dejar saldo pendiente',
            $dejaSaldo => 'Dejar saldo pendiente',
            default => 'Dar descuento',
        };
        $datos['autorizo'] = Caja::autorizarSiHaceFalta($request, $dejaSaldo || $conDescuento, 'autorizacion', $que);

        if ($esEfectivo && $datos['recibido'] === null) {
            throw \Illuminate\Validation\ValidationException::withMessages(['recibido' => 'Escribe con cuánto paga el cliente (o usa «Exacto»).']);
        }
        if (!$esEfectivo) {
            $datos['recibido'] = null;
        }
        $datos['turno'] = $request->attributes->get('turno')->referencia();

        $venta = RegistroVenta::registrarVenta($datos, $request->user());

        $pago = $venta->pagos()->latest('idPagoVenta')->first();
        $mensaje = "Venta {$venta->folio} cobrada por " . Producto::formatoPrecio($venta->total) . '.';
        if ($datos['recibido'] !== null && $pago) {
            $mensaje .= ' Cambio: ' . Producto::formatoPrecio($datos['recibido'] - (float) $pago->monto) . '.';
        }
        if ($venta->tieneSaldo()) {
            $mensaje .= ' Queda debiendo ' . Producto::formatoPrecio($venta->saldo()) . '.';
        }

        return redirect()->route('cajero.inicio')
            ->with('ventaCobrada', $venta->idVenta)
            ->with('alerta', ['success', $mensaje]);
    }
}
