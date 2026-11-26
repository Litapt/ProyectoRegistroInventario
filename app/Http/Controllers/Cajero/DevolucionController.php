<?php

namespace App\Http\Controllers\Cajero;

use App\Http\Controllers\Administrador\DevolucionVentaController as DevolucionesDelAdministrador;
use App\Models\Solicitud;
use App\Models\Venta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
    "Registrar devolución" desde Ventas del cajero. Las mismas reglas que el administrador
    (App\Support\DevolucionVenta): motivo y autorización de un gerente o administrador.
    El cajero sólo puede hacerlo en SUS ventas.
*/
class DevolucionController extends DevolucionesDelAdministrador
{
    public function store(Request $request, Venta $venta): RedirectResponse
    {
        VentaController::revisarQueSeaSuya($venta, $request);

        return parent::store($request, $venta);
    }

    public function comprobante(Solicitud $solicitud): View
    {
        abort_unless($solicitud->venta, 404);
        VentaController::revisarQueSeaSuya($solicitud->venta, request());

        return parent::comprobante($solicitud);
    }
}
