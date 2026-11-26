<?php

namespace App\Http\Controllers\Cajero;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/*
    "Consultar productos" del cajero (Figma: "Cajero - Menú consulta"). Sólo para VER:
    nombre o código, estado de stock, categoría, marca y modelo de vehículo.
    Lo pide la ventana (javascript/cajero/consultaProductos.js) sin salir de la pantalla en la que está.
*/
class ProductoController extends Controller
{
    private const POR_PAGINA = 10;

    public function consultar(Request $request): JsonResponse
    {
        $buscar = mb_substr(trim((string) $request->query('buscar', '')), 0, 60);
        $categoria = (int) $request->query('categoria', 0);
        $marca = (int) $request->query('marca', 0);
        $vehiculo = (int) $request->query('vehiculo', 0);
        $estado = (string) $request->query('estado', '');

        $productos = Producto::with(['categoria', 'marca', 'ubicacion', 'compatibilidades.modelo.marca'])
            ->activos()
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nombre', 'like', "%{$buscar}%")
                ->orWhere('codigoInterno', 'like', "%{$buscar}%")
                ->orWhere('codigoBarras', $buscar)
                ->orWhere('numeroParteFabricante', 'like', "%{$buscar}%")))
            ->when($categoria > 0, fn ($q) => $q->where('fk_idCategoria', $categoria))
            ->when($marca > 0, fn ($q) => $q->where('fk_idMarca', $marca))
            ->when($vehiculo > 0, fn ($q) => $q->whereHas('compatibilidades', fn ($c) => $c->where('fk_idModeloVehiculo', $vehiculo)))
            ->when(in_array($estado, [Producto::ESTADO_DISPONIBLE, Producto::ESTADO_BAJO, Producto::ESTADO_AGOTADO], true),
                fn ($q) => $q->conEstado($estado))
            ->orderBy('nombre')
            ->paginate(self::POR_PAGINA);

        $iva = (float) Configuracion::valor('iva', '16');

        return response()->json([
            'total' => $productos->total(),
            'pagina' => $productos->currentPage(),
            'paginas' => $productos->lastPage(),
            'desde' => $productos->firstItem() ?? 0,
            'hasta' => $productos->lastItem() ?? 0,
            'productos' => $productos->getCollection()->map(function (Producto $p) use ($iva) {
                $primero = $p->compatibilidades->first();
                $mas = $p->compatibilidades->count() - 1;
                $tasa = $p->tasaIVA !== null ? (float) $p->tasaIVA : $iva;

                return [
                    'codigo' => $p->codigoInterno,
                    'nombre' => $p->nombre,
                    'categoria' => $p->categoria?->nombre,
                    'marca' => $p->marca?->nombre,
                    'vehiculo' => $primero ? $primero->vehiculo() : 'Universal',
                    'anios' => $primero ? $primero->anios() . ($mas > 0 ? " · y {$mas} más" : '') : 'Multimarca',
                    'existencia' => $p->existencia,
                    'estado' => $p->estadoStock(),
                    'ubicacion' => $p->ubicacion?->texto(),
                    'precio' => Producto::formatoPrecio($p->precioVentaActual),
                    'precioConIva' => Producto::formatoPrecio(round((float) $p->precioVentaActual * (1 + $tasa / 100), 2)),
                ];
            })->values(),
        ]);
    }
}
