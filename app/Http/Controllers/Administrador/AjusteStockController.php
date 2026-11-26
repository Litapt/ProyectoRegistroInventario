<?php

namespace App\Http\Controllers\Administrador;

use App\Http\Controllers\Controller;
use App\Models\AjusteInventario;
use App\Models\Motivo;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Support\Folios;
use App\Support\MensajesValidacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/*
    Ajuste de stock del administrador (Figma: "Registro de ajuste de stock" y
    "Ajuste de stock · Confirmación"). Entrada = suma piezas, egreso = las resta.

    El administrador es quien autoriza, así que su ajuste se aplica de inmediato.
    (Cuando lo pida un almacenista quedará PENDIENTE hasta que un gerente o
    administrador lo autorice; eso se hace en la parte del almacenista.)
*/
class AjusteStockController extends Controller
{
    private const CAMPOS = [
        'tipo' => 'tipo de movimiento',
        'cantidad' => 'cantidad a ajustar',
        'motivo' => 'motivo',
        'especificacion' => 'especificación',
        'referencia' => 'factura o referencia',
    ];

    /* Pantalla "Registrar ajuste de stock" de un producto */
    public function create(Producto $producto): View|RedirectResponse
    {
        if (!$producto->activo) {
            return $this->noDisponible();
        }

        $producto->load(['categoria', 'marca', 'ubicacion', 'compatibilidades']);

        return view('administrador.productos.ajuste', [
            'producto' => $producto,
            'motivos' => Motivo::de(Motivo::AJUSTE)->get(),
            'movimientos' => $producto->movimientos()->with(['trabajador', 'motivo'])
                ->orderByDesc('idMovimientoInventario')->limit(5)->get(),
        ]);
    }

    /* Botón "Confirmar entrada / egreso" (después de la ventana de confirmación) */
    public function store(Request $request, Producto $producto): RedirectResponse
    {
        $datos = $request->validate([
            'tipo' => ['required', Rule::in([AjusteInventario::ENTRADA, AjusteInventario::SALIDA])],
            'cantidad' => ['required', 'integer', 'min:1', 'max:99999'],
            'motivo' => ['required', 'integer', Rule::exists('motivos', 'idMotivo')
                ->where('categoriaMotivo', Motivo::AJUSTE)->where('activo', true)],
            'especificacion' => ['nullable', 'required_if:tipo,' . AjusteInventario::SALIDA, 'string', 'max:500'],
            'referencia' => ['nullable', 'string', 'max:60'],
        ], MensajesValidacion::MENSAJES + [
            'especificacion.required_if' => 'En un egreso escribe a dónde van las piezas o por qué salen.',
        ], self::CAMPOS);

        $esEntrada = $datos['tipo'] === AjusteInventario::ENTRADA;
        $cantidad = (int) $datos['cantidad'];

        $ajuste = DB::transaction(function () use ($producto, $datos, $esEntrada, $cantidad) {
            // Se vuelve a leer el producto BLOQUEADO: si alguien vende o ajusta al mismo tiempo,
            // espera a que termine este ajuste (así el stock nunca se calcula con un número viejo)
            $producto = Producto::whereKey($producto->getKey())->lockForUpdate()->firstOrFail();

            if (!$producto->activo) {
                throw ValidationException::withMessages(['cantidad' => 'Ese producto está desactivado.']);
            }

            $antes = $producto->existencia;

            if (!$esEntrada && $cantidad > $antes) {
                throw ValidationException::withMessages([
                    'cantidad' => "Sólo hay {$antes} " . ($antes === 1 ? 'pieza' : 'piezas') . '; no puedes retirar más.',
                ]);
            }

            $despues = $esEntrada ? $antes + $cantidad : $antes - $cantidad;

            $ajuste = AjusteInventario::create([
                'folio' => Folios::siguiente('AJUSTE'),
                'tipoAjuste' => $datos['tipo'],
                'fk_idProducto' => $producto->idProducto,
                'cantidad' => $cantidad,
                'existenciaSistema' => $antes,
                'costoUnitario' => $esEntrada ? $producto->precioCompraActual : null,
                'fk_idTrabajador' => auth()->id(),
                'estado' => AjusteInventario::APLICADO,
                'fk_idMotivo' => $datos['motivo'],
                'referencia' => $this->textoONulo($datos['referencia'] ?? null),
                'observaciones' => $this->textoONulo($datos['especificacion'] ?? null),
                'fk_idTrabajadorAutoriza' => auth()->id(),
                'fechaAutorizacion' => now(),
            ]);

            $producto->existencia = $despues;
            $producto->fechaActualizacion = now();
            $producto->save();

            MovimientoInventario::create([
                'fk_idProducto' => $producto->idProducto,
                'tipoMovimiento' => $esEntrada ? MovimientoInventario::ENTRADA : MovimientoInventario::SALIDA,
                'cantidad' => $cantidad,
                'existenciaAnterior' => $antes,
                'existenciaNueva' => $despues,
                'costoUnitario' => $ajuste->costoUnitario,
                'fk_idTrabajador' => auth()->id(),
                'referenciaTipo' => 'AJUSTE',
                'referenciaId' => $ajuste->idAjuste,
                'fk_idMotivo' => $datos['motivo'],
                'observaciones' => $ajuste->observaciones,
            ]);

            return $ajuste;
        });

        $piezas = $cantidad === 1 ? 'pieza' : 'piezas';
        $texto = $esEntrada ? "Entrada de {$cantidad} {$piezas} registrada" : "Egreso de {$cantidad} {$piezas} registrado";
        $despues = $esEntrada ? $ajuste->existenciaSistema + $cantidad : $ajuste->existenciaSistema - $cantidad;

        return redirect()
            ->to(\App\Support\Ruta::a('productos.index'))
            ->with('alerta', ['success', "{$texto} ({$ajuste->folio}): «{$producto->nombre}» pasó de {$ajuste->existenciaSistema} a {$despues}."]);
    }

    private function textoONulo(?string $texto): ?string
    {
        $texto = trim((string) $texto);

        return $texto === '' ? null : $texto;
    }

    private function noDisponible(): RedirectResponse
    {
        return redirect()
            ->to(\App\Support\Ruta::a('productos.index'))
            ->with('alerta', ['warning', 'Ese producto está desactivado. Reactívalo para poder ajustar su stock.']);
    }
}
