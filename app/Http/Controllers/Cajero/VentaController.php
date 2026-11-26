<?php

namespace App\Http\Controllers\Cajero;

use App\Http\Controllers\Administrador\VentaController as VentasDelAdministrador;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Venta;
use App\Support\Caja;
use App\Support\RegistroVenta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/*
    Ventas y cotizaciones del CAJERO. Usa la misma lógica que Ventas del administrador
    (hereda de Administrador\VentaController) pero con sus propias pantallas (resources/views/cajero/ventas)
    y con la regla del cajero: sólo los pagos directos los hace solo.

      - Consultar ventas: ver, ticket, abonar, devolución y cancelar.
      - Abonar: necesita autorización de un gerente o administrador (no es un pago directo).
      - Devolución y cancelar: piden autorización igual que para todos (lo revisa el administrador).
      - Cotizaciones: las hace sin permiso; sólo si llevan descuento piden autorización.

    El cajero sólo VE y TOCA sus propias ventas y cotizaciones (las que él hizo);
    el gerente y el administrador ven las de todos. Si escribe la dirección de una ajena: "no existe".
*/
class VentaController extends VentasDelAdministrador
{
    /* Sólo las suyas (lo usan Consultar y Cotizaciones del administrador) */
    protected function soloDe(Request $request): ?int
    {
        return $request->user()->idTrabajador;
    }

    /* Una venta o cotización de otro trabajador: para el cajero "no existe" */
    public static function revisarQueSeaSuya(Venta|Pedido $registro, Request $request): void
    {
        abort_unless((int) $registro->fk_idTrabajador === (int) $request->user()->idTrabajador, 404);
    }

    /* Pantalla "Ventas" del cajero: los mismos datos que Consultar ventas, en su pantalla */
    public function consultar(Request $request): View
    {
        return view('cajero.ventas.index', parent::consultar($request)->getData());
    }

    /* Ventana "Registrar abono" con autorización */
    public function abonar(Request $request, Venta $venta): RedirectResponse
    {
        self::revisarQueSeaSuya($venta, $request);

        try {
            Caja::autorizarSiHaceFalta($request, true, 'abono', 'Registrar un abono');
        } catch (ValidationException $e) {
            return redirect()->to(url()->previous(route('cajero.ventas.consultar')))
                ->withErrors(['abono' => collect($e->errors())->flatten()->all()])
                ->withInput($request->except('contrasena_autoriza'))
                ->with('modalAbono', $venta->idVenta);
        }

        return parent::abonar($request, $venta);
    }

    /* Cancelar: sólo sus ventas (motivo y autorización, como el administrador) */
    public function cancelar(Request $request, Venta $venta): RedirectResponse
    {
        self::revisarQueSeaSuya($venta, $request);

        return parent::cancelar($request, $venta);
    }

    /* Reimprimir una cotización: sólo las suyas */
    public function cotizacion(Pedido $pedido): View
    {
        self::revisarQueSeaSuya($pedido, request());

        return parent::cotizacion($pedido);
    }

    /* Pantalla "Cotizaciones" del cajero */
    public function cotizaciones(Request $request): View
    {
        return view('cajero.ventas.cotizaciones', parent::cotizaciones($request)->getData());
    }

    /* "Mostrar cotización" (sin guardar): con descuento, necesita autorización */
    public function vistaCotizacion(Request $request): View
    {
        $datos = RegistroVenta::validar($request, false);
        Caja::autorizarSiHaceFalta($request, $datos['descuento'] > 0, 'autorizacion', 'Cotizar con descuento');

        return parent::vistaCotizacion($request);
    }

    /* "Convertir a cotización": se guarda; con descuento, necesita autorización */
    public function guardarCotizacion(Request $request): RedirectResponse
    {
        $datos = RegistroVenta::validar($request, false);
        $autorizo = Caja::autorizarSiHaceFalta($request, $datos['descuento'] > 0, 'autorizacion', 'Cotizar con descuento');
        $cotizacion = RegistroVenta::guardarCotizacion($datos, $request->user());

        if ($autorizo) {
            \App\Models\Bitacora::registrar('AUTORIZAR', 'pedidos', $cotizacion->idPedido, [
                'folio' => $cotizacion->folio, 'descuento' => $datos['descuento'],
            ], $autorizo);
        }

        return redirect()->route('cajero.inicio')
            ->with('alerta', ['success', "Cotización {$cotizacion->folio} guardada por " . Producto::formatoPrecio($cotizacion->total)
                . '. Vale hasta el ' . $cotizacion->vigencia->format('d/m/Y') . '. La encuentras en «Cotizaciones».'])
            ->with('cotizacionGuardada', $cotizacion->idPedido);
    }
}
