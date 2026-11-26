<?php

namespace App\Http\Controllers\Cajero;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\MetodoPago;
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
    Lo que se guarda lo hace App\Support\RegistroVenta (el mismo código que el administrador).
*/
class InicioController extends Controller
{
    public function index(Request $request): View
    {
        $metodos = MetodoPago::activos()->get();
        $idCliente = old('cliente');
        $cliente = $idCliente ? Cliente::activos()->find((int) $idCliente) : null;

        return view('cajero.inicio', [
            'turno' => Caja::turnoAbierto($request->user()),
            'fondoAutorizado' => Caja::fondoAutorizado(),
            'billetes' => Caja::BILLETES,
            'metodosPago' => $metodos,
            'idEfectivo' => PedidoController::idEfectivoDe($metodos),
            'carritoAnterior' => old('productos') !== null ? RegistroVenta::carritoDesde(old('productos')) : [],
            'clienteAnterior' => $cliente ? ['id' => $cliente->idCliente, 'nombre' => $cliente->nombre(), 'codigo' => $cliente->codigo(), 'telefono' => $cliente->telefono] : null,
            'ventaCobrada' => session('ventaCobrada') ? Venta::with('pagos')->find(session('ventaCobrada')) : null,
        ]);
    }

    /* Botón "Cobrar venta" */
    public function store(Request $request): RedirectResponse
    {
        $datos = RegistroVenta::validar($request, true);
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
