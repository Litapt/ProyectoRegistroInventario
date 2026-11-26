<?php

namespace App\Support;

use App\Models\Bitacora;
use App\Models\Configuracion;
use App\Models\DetallePedido;
use App\Models\DetalleVenta;
use App\Models\MovimientoInventario;
use App\Models\PagoVenta;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Trabajador;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/*
    Guardar una venta o una cotización. Está aquí (y no en un controlador) para que
    el administrador, el gerente y el cajero usen EXACTAMENTE el mismo código.

    Lo que manda la pantalla sólo son ids y cantidades: los precios, el IVA y los totales
    se vuelven a calcular aquí con lo que dice la base de datos (CalculoVenta).
*/
class RegistroVenta
{
    public const MAX_RENGLONES = 60;

    private const CAMPOS = [
        'cliente' => 'cliente',
        'productos' => 'productos',
        'productos.*.id' => 'producto',
        'productos.*.cantidad' => 'cantidad',
        'descuento' => 'descuento',
        'metodoPago' => 'método de pago',
        'totalEsperado' => 'total',
        'condicion' => 'forma de pago',
        'pagoInicial' => 'lo que paga ahora',
    ];

    /* Revisa el formulario y regresa [cliente, renglones pedidos, % descuento, datos] */
    public static function validar(Request $request, bool $pideMetodoPago): array
    {
        $datos = Validator::make($request->all(), [
            'cliente' => ['nullable', 'integer', Rule::exists('clientes', 'idCliente')->where('activo', true)],
            'productos' => ['required', 'array', 'min:1', 'max:' . self::MAX_RENGLONES],
            'productos.*.id' => ['required', 'integer', 'distinct'],
            'productos.*.cantidad' => ['required', 'integer', 'min:1', 'max:9999'],
            'descuento' => ['nullable', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'metodoPago' => $pideMetodoPago
                ? ['required', 'integer', Rule::exists('metodosPago', 'idMetodoPago')->where('activo', true)]
                : ['nullable'],
            'totalEsperado' => ['nullable', 'numeric'],
            // CONTADO = paga todo; CREDITO = paga una parte (o nada) y queda a cuenta de un cliente registrado
            'condicion' => ['nullable', Rule::in([Venta::CONTADO, Venta::CREDITO])],
            'pagoInicial' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'cotizacion' => ['nullable', 'integer'],
        ], MensajesValidacion::MENSAJES + [
            'pagoInicial.min' => 'Lo que paga ahora no puede ser negativo.',
            'pagoInicial.decimal' => 'Lo que paga ahora puede tener hasta 2 decimales.',
            'productos.required' => 'Agrega al menos un producto.',
            'productos.min' => 'Agrega al menos un producto.',
            'productos.max' => 'Una venta admite como máximo ' . self::MAX_RENGLONES . ' productos distintos.',
            'productos.*.id.distinct' => 'Un producto está repetido; usa la cantidad.',
            'cliente.exists' => 'Ese cliente no existe o está desactivado.',
            'metodoPago.exists' => 'Elige un método de pago válido.',
            'descuento.max' => 'El descuento no puede pasar de 100 %.',
        ], self::CAMPOS)->validate();

        $pedidos = collect($datos['productos'])
            ->mapWithKeys(fn ($r) => [(int) $r['id'] => (int) $r['cantidad']])
            ->all();

        return [
            'cliente' => isset($datos['cliente']) ? (int) $datos['cliente'] : null,
            'pedidos' => $pedidos,
            'descuento' => (float) ($datos['descuento'] ?? 0),
            'metodoPago' => isset($datos['metodoPago']) ? (int) $datos['metodoPago'] : null,
            'totalEsperado' => isset($datos['totalEsperado']) ? (float) $datos['totalEsperado'] : null,
            'condicion' => ($datos['condicion'] ?? null) === Venta::CREDITO ? Venta::CREDITO : Venta::CONTADO,
            'pagoInicial' => isset($datos['pagoInicial']) ? round((float) $datos['pagoInicial'], 2) : 0.0,
            'cotizacion' => isset($datos['cotizacion']) ? (int) $datos['cotizacion'] : null,
        ];
    }

    /*
        Registra la venta: descuenta el inventario, guarda renglones, pago y movimientos.
        Todo o nada (si algo falla, no se guarda nada).
    */
    public static function registrarVenta(array $datos, Trabajador $quien): Venta
    {
        return DB::transaction(function () use ($datos, $quien) {
            // Productos BLOQUEADOS (en orden de id para que dos cajas no se atoren entre sí)
            $productos = Producto::whereKey(array_keys($datos['pedidos']))
                ->orderBy('idProducto')->lockForUpdate()->get()->keyBy('idProducto');

            $renglones = self::renglones($datos['pedidos'], $productos, true);
            $cuentas = CalculoVenta::calcular($renglones, $datos['descuento']);
            self::revisarTotal($datos, $cuentas);

            // A crédito: sólo a un cliente registrado y pagando menos del total (si paga todo, es de contado)
            $credito = ($datos['condicion'] ?? Venta::CONTADO) === Venta::CREDITO;
            $pagoAhora = $credito ? (float) ($datos['pagoInicial'] ?? 0) : $cuentas['total'];
            if ($credito && !$datos['cliente']) {
                throw ValidationException::withMessages(['condicion' => 'Para dejar saldo pendiente, elige un cliente registrado.']);
            }
            if ($credito && $pagoAhora >= $cuentas['total']) {
                $credito = false;
                $pagoAhora = $cuentas['total'];
            }

            $venta = Venta::create([
                'folio' => Folios::siguiente('VENTA'),
                'fk_idCliente' => $datos['cliente'],
                'fk_idTrabajador' => $quien->idTrabajador,
                'estadoVenta' => Venta::COMPLETADA,
                'condicionPago' => $credito ? Venta::CREDITO : Venta::CONTADO,
                'subtotal' => $cuentas['subtotal'],
                'descuentoTotal' => $cuentas['descuento'],
                'impuestosTotal' => $cuentas['iva'],
                'total' => $cuentas['total'],
            ]);

            foreach ($cuentas['renglones'] as $r) {
                $producto = $r['producto'];

                DetalleVenta::create([
                    'fk_idVenta' => $venta->idVenta,
                    'fk_idProducto' => $producto->idProducto,
                    'cantidad' => $r['cantidad'],
                    'precioUnitarioVenta' => $r['precio'],
                    'descuento' => $r['descuento'],
                    'tasaIVA' => $r['tasa'],
                    'subtotal' => $r['subtotal'],
                    'totalLinea' => $r['total'],
                ]);

                $antes = $producto->existencia;
                $producto->existencia = $antes - $r['cantidad'];
                $producto->fechaActualizacion = now();
                $producto->save();

                MovimientoInventario::create([
                    'fk_idProducto' => $producto->idProducto,
                    'tipoMovimiento' => MovimientoInventario::VENTA,
                    'cantidad' => $r['cantidad'],
                    'existenciaAnterior' => $antes,
                    'existenciaNueva' => $producto->existencia,
                    'costoUnitario' => $producto->precioCompraActual,
                    'fk_idTrabajador' => $quien->idTrabajador,
                    'referenciaTipo' => 'VENTA',
                    'referenciaId' => $venta->idVenta,
                    'observaciones' => "Venta {$venta->folio}",
                ]);
            }

            if ($pagoAhora > 0) {
                PagoVenta::create([
                    'fk_idVenta' => $venta->idVenta,
                    'fk_idMetodoPago' => $datos['metodoPago'],
                    'monto' => $pagoAhora,
                    'fk_idTrabajador' => $quien->idTrabajador,
                    'observaciones' => $credito ? 'Pago inicial (anticipo)' : null,
                ]);
            }

            // Si venía de una cotización, queda marcada como cobrada
            if (!empty($datos['cotizacion'])) {
                Pedido::where('idPedido', $datos['cotizacion'])
                    ->where('tipoPedido', Pedido::COTIZACION)->where('estadoPedido', Pedido::PENDIENTE)
                    ->update(['estadoPedido' => Pedido::CONVERTIDA, 'fk_idVenta' => $venta->idVenta]);
            }

            Bitacora::registrar('CREAR', 'ventas', $venta->idVenta, [
                'folio' => $venta->folio,
                'total' => $cuentas['total'],
                'descuentoPorcentaje' => $datos['descuento'],
                'condicion' => $credito ? Venta::CREDITO : Venta::CONTADO,
                'pagoAhora' => $pagoAhora,
                'productos' => count($cuentas['renglones']),
            ]);

            return $venta;
        });
    }

    /*
        Cancela una venta: regresa las piezas al inventario (con su movimiento),
        la marca CANCELADA y guarda quién, cuándo y por qué. La venta NO se borra.
        $autorizo es el gerente o administrador que dio su contraseña.
    */
    public static function cancelarVenta(Venta $venta, int $idMotivo, ?string $comentario, Trabajador $quien, Trabajador $autorizo): Venta
    {
        return DB::transaction(function () use ($venta, $idMotivo, $comentario, $quien, $autorizo) {
            // Se vuelve a leer BLOQUEADA: si dos personas cancelan al mismo tiempo, sólo una lo logra
            $venta = Venta::whereKey($venta->idVenta)->lockForUpdate()->firstOrFail();
            if ($venta->estaCancelada()) {
                throw ValidationException::withMessages(['venta' => "La venta {$venta->folio} ya estaba cancelada."]);
            }

            $detalles = $venta->detalles()->get();
            // Las piezas que el cliente ya devolvió no las tiene: no se vuelven a sumar
            $devueltas = $venta->devoluciones()->get()->groupBy('fk_idProducto')->map(fn ($d) => (int) $d->sum('cantidad'));
            $productos = Producto::whereKey($detalles->pluck('fk_idProducto'))
                ->orderBy('idProducto')->lockForUpdate()->get()->keyBy('idProducto');

            foreach ($detalles as $detalle) {
                $regresan = $detalle->cantidad - ($devueltas[$detalle->fk_idProducto] ?? 0);
                if ($regresan <= 0) {
                    continue;
                }

                $producto = $productos[$detalle->fk_idProducto];
                $antes = $producto->existencia;
                $producto->existencia = $antes + $regresan;
                $producto->fechaActualizacion = now();
                $producto->save();

                MovimientoInventario::create([
                    'fk_idProducto' => $producto->idProducto,
                    'tipoMovimiento' => MovimientoInventario::CANCELACION_VENTA,
                    'cantidad' => $regresan,
                    'existenciaAnterior' => $antes,
                    'existenciaNueva' => $producto->existencia,
                    'costoUnitario' => $producto->precioCompraActual,
                    'fk_idTrabajador' => $quien->idTrabajador,
                    'referenciaTipo' => 'VENTA',
                    'referenciaId' => $venta->idVenta,
                    'fk_idMotivo' => $idMotivo,
                    'observaciones' => "Cancelación de la venta {$venta->folio}",
                ]);
            }

            $venta->estadoVenta = Venta::CANCELADA;
            $venta->fechaCancelacion = now();
            $venta->fk_idTrabajadorCancelacion = $quien->idTrabajador;
            $venta->fk_idMotivoCancelacion = $idMotivo;
            $venta->motivoCancelacion = $comentario !== null && trim($comentario) !== '' ? trim($comentario) : null;
            $venta->save();

            Bitacora::registrar('CANCELAR', 'ventas', $venta->idVenta, [
                'folio' => $venta->folio,
                'total' => (float) $venta->total,
                'motivo' => $idMotivo,
                'comentario' => $venta->motivoCancelacion,
            ], $autorizo);

            return $venta;
        });
    }

    /* Abono a una venta a crédito: no puede pasar de lo que debe */
    public static function abonar(Venta $venta, float $monto, int $idMetodoPago, Trabajador $quien): PagoVenta
    {
        return DB::transaction(function () use ($venta, $monto, $idMetodoPago, $quien) {
            $venta = Venta::whereKey($venta->idVenta)->lockForUpdate()->firstOrFail();
            if ($venta->estaCancelada()) {
                throw ValidationException::withMessages(['abono' => "La venta {$venta->folio} está cancelada."]);
            }

            $saldo = $venta->saldo();
            if ($saldo < 0.01) {
                throw ValidationException::withMessages(['abono' => "La venta {$venta->folio} ya está liquidada."]);
            }
            if ($monto < 0.01 || $monto - $saldo >= 0.005) {
                throw ValidationException::withMessages(['abono' => 'El abono debe ser de $0.01 a ' . Producto::formatoPrecio($saldo) . ' (lo que debe).']);
            }

            $pago = PagoVenta::create([
                'fk_idVenta' => $venta->idVenta,
                'fk_idMetodoPago' => $idMetodoPago,
                'monto' => round($monto, 2),
                'fk_idTrabajador' => $quien->idTrabajador,
                'observaciones' => 'Abono',
            ]);

            Bitacora::registrar('ABONO', 'ventas', $venta->idVenta, [
                'folio' => $venta->folio, 'monto' => round($monto, 2), 'saldoAntes' => $saldo,
            ]);

            return $pago;
        });
    }

    /* Guarda la cotización (NO toca el inventario) */
    public static function guardarCotizacion(array $datos, Trabajador $quien): Pedido
    {
        return DB::transaction(function () use ($datos, $quien) {
            $productos = Producto::whereKey(array_keys($datos['pedidos']))->get()->keyBy('idProducto');
            $cuentas = CalculoVenta::calcular(self::renglones($datos['pedidos'], $productos, false), $datos['descuento']);
            self::revisarTotal($datos, $cuentas);

            $cotizacion = Pedido::create([
                'folio' => Folios::siguiente('COTIZACION'),
                'tipoPedido' => Pedido::COTIZACION,
                'fk_idCliente' => $datos['cliente'],
                'fk_idTrabajador' => $quien->idTrabajador,
                'vigencia' => now()->addDays(Pedido::DIAS_VIGENCIA)->toDateString(),
                'estadoPedido' => Pedido::PENDIENTE,
                'subtotal' => $cuentas['subtotal'],
                'descuentoTotal' => $cuentas['descuento'],
                'impuestosTotal' => $cuentas['iva'],
                'total' => $cuentas['total'],
            ]);

            foreach ($cuentas['renglones'] as $r) {
                DetallePedido::create([
                    'fk_idPedido' => $cotizacion->idPedido,
                    'fk_idProducto' => $r['producto']->idProducto,
                    'cantidad' => $r['cantidad'],
                    'precioUnitario' => $r['precio'],
                    'descuento' => $r['descuento'],
                    'tasaIVA' => $r['tasa'],
                    'subtotal' => $r['subtotal'],
                    'totalLinea' => $r['total'],
                ]);
            }

            Bitacora::registrar('CREAR', 'pedidos', $cotizacion->idPedido, [
                'folio' => $cotizacion->folio, 'tipo' => Pedido::COTIZACION, 'total' => $cuentas['total'],
            ]);

            return $cotizacion;
        });
    }

    /* Las cuentas sin guardar nada (para "Mostrar cotización") */
    public static function previa(array $datos): array
    {
        $productos = Producto::whereKey(array_keys($datos['pedidos']))->get()->keyBy('idProducto');
        $cuentas = CalculoVenta::calcular(self::renglones($datos['pedidos'], $productos, false), $datos['descuento']);
        self::revisarTotal($datos, $cuentas);   // que no se imprima un precio distinto al de la pantalla

        return $cuentas;
    }

    /* Datos para volver a armar la tabla de productos si el servidor regresa un error */
    public static function carritoDesde(?array $productos): array
    {
        $cantidades = collect($productos ?? [])
            ->filter(fn ($r) => is_array($r) && isset($r['id']))
            ->mapWithKeys(fn ($r) => [(int) $r['id'] => max(1, (int) ($r['cantidad'] ?? 1))]);

        if ($cantidades->isEmpty()) {
            return [];
        }

        $encontrados = Producto::with(['marca', 'compatibilidades.modelo.marca'])
            ->whereKey($cantidades->keys())->where('activo', true)->get()->keyBy('idProducto');

        return $cantidades
            ->filter(fn ($cantidad, $id) => $encontrados->has($id))
            ->map(fn ($cantidad, $id) => self::datosProducto($encontrados[$id]) + ['cantidad' => $cantidad])
            ->values()->all();
    }

    /* Lo que la pantalla necesita de un producto */
    public static function datosProducto(Producto $producto): array
    {
        $ivaGeneral = (float) Configuracion::valor('iva', '16');
        $vehiculo = $producto->compatibilidades->first();

        return [
            'id' => $producto->idProducto,
            'codigo' => $producto->codigoInterno,
            'nombre' => $producto->nombre,
            'detalle' => trim(($producto->marca?->nombre ?? '') . ($vehiculo ? ' · ' . $vehiculo->vehiculo() : '')
                . ($producto->compatibilidades->count() > 1 ? ' y más' : ''), ' ·'),
            'precio' => (float) $producto->precioVentaActual,
            'tasa' => $producto->tasaIVA !== null ? (float) $producto->tasaIVA : $ivaGeneral,
            'existencia' => $producto->existencia,
        ];
    }

    /* Une ids pedidos con los productos de la base; con $revisarStock, no deja vender de más */
    private static function renglones(array $pedidos, $productos, bool $revisarStock): array
    {
        $errores = [];
        $renglones = [];

        foreach ($pedidos as $id => $cantidad) {
            $producto = $productos->get($id);

            if (!$producto || !$producto->activo) {
                $errores[] = 'Un producto de la lista ya no está disponible (se desactivó). Quítalo para continuar.';
                continue;
            }

            if ($revisarStock && $cantidad > $producto->existencia) {
                $errores[] = $producto->existencia === 0
                    ? "«{$producto->nombre}» ya no tiene existencias."
                    : "De «{$producto->nombre}» sólo hay {$producto->existencia} " . ($producto->existencia === 1 ? 'pieza' : 'piezas') . '.';
                continue;
            }

            $renglones[] = ['producto' => $producto, 'cantidad' => $cantidad];
        }

        if ($errores) {
            throw ValidationException::withMessages(['productos' => array_values(array_unique($errores))]);
        }

        return $renglones;
    }

    /* Si el total que vio la persona no es el que calcula el servidor, algo cambió: que lo revise */
    private static function revisarTotal(array $datos, array $cuentas): void
    {
        if ($datos['totalEsperado'] !== null && abs($datos['totalEsperado'] - $cuentas['total']) >= 0.005) {
            throw ValidationException::withMessages([
                'totalEsperado' => 'Algún precio cambió mientras capturabas. Revisa la lista: el total correcto es '
                    . Producto::formatoPrecio($cuentas['total']) . '.',
            ]);
        }
    }
}
