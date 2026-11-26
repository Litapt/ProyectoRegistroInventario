<?php

namespace App\Support;

use App\Models\Bitacora;
use App\Models\Configuracion;
use App\Models\MetodoPago;
use App\Models\PagoVenta;
use App\Models\Solicitud;
use App\Models\Venta;
use App\Models\Producto;
use App\Models\Trabajador;
use App\Models\TurnoCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/*
    La caja del cajero: abrir el turno con su fondo y, al terminar, el CORTE DE CAJA.

    El fondo se cuenta por billetes ($500, $200, $100, $50, $20) y monedas (un monto).
    El total lo calcula el servidor con el conteo; lo que se ve en pantalla es sólo ayuda.
    Si el fondo contado no es el autorizado (Configuración → Ventas), tiene que autorizarlo
    un gerente o administrador con su correo y contraseña, y escribir qué pasó.
*/
class Caja
{
    public const BILLETES = [500, 200, 100, 50, 20];
    public const MAX_PIEZAS = 9999;

    /* El fondo que debe tener la caja al abrir */
    public static function fondoAutorizado(): float
    {
        return round((float) Configuracion::valor('fondoInicialCaja', '1000'), 2);
    }

    /*
        El cajero sólo cobra pagos directos. Descuentos, dejar saldo pendiente, abonos,
        devoluciones y cancelaciones los autoriza un gerente o administrador con su correo y contraseña.
        Regresa quién autorizó (o null si no hacía falta); si falta o está mal, error en $campo.
    */
    public static function autorizarSiHaceFalta(Request $request, bool $haceFalta, string $campo, string $que): ?Trabajador
    {
        if (!$haceFalta) {
            return null;
        }

        $resultado = Autorizacion::verificar($request, 'correo_autoriza', 'contrasena_autoriza');
        if (is_string($resultado)) {
            throw ValidationException::withMessages([$campo => ucfirst($que) . ' necesita la autorización de un gerente o administrador. ' . $resultado]);
        }

        return $resultado;
    }

    /* El turno abierto del cajero (o null) */
    public static function turnoAbierto(Trabajador $cajero): ?TurnoCaja
    {
        return TurnoCaja::abiertos()->where('fk_idTrabajador', $cajero->idTrabajador)->latest('idTurno')->first();
    }

    /* Revisa el conteo de la pantalla: ['billetes' => [500 => 1, ...], 'monedas' => 10.0, 'total' => 1010.0] */
    public static function leerConteo(Request $request, string $bolsa): array
    {
        $reglas = ['monedas' => ['nullable', 'numeric', 'min:0', 'max:99999', 'decimal:0,2']];
        $mensajes = [
            'monedas.numeric' => 'Las monedas deben ser una cantidad en pesos.',
            'monedas.min' => 'Las monedas no pueden ser negativas.',
            'monedas.max' => 'Revisa el monto de las monedas.',
            'monedas.decimal' => 'Las monedas pueden tener hasta 2 decimales.',
        ];
        foreach (self::BILLETES as $billete) {
            $reglas["billetes.{$billete}"] = ['nullable', 'integer', 'min:0', 'max:' . self::MAX_PIEZAS];
            $mensajes["billetes.{$billete}.integer"] = "Escribe cuántos billetes de \${$billete} hay (número entero).";
            $mensajes["billetes.{$billete}.min"] = "Los billetes de \${$billete} no pueden ser negativos.";
            $mensajes["billetes.{$billete}.max"] = "Revisa cuántos billetes de \${$billete} hay.";
        }

        $datos = validator($request->all(), $reglas, $mensajes)->validate();

        $billetes = [];
        $centavos = 0;
        foreach (self::BILLETES as $billete) {
            $piezas = (int) ($datos['billetes'][$billete] ?? 0);
            $billetes[$billete] = $piezas;
            $centavos += $piezas * $billete * 100;
        }
        $monedas = round((float) ($datos['monedas'] ?? 0), 2);
        $centavos += (int) round($monedas * 100);

        return ['billetes' => $billetes, 'monedas' => $monedas, 'total' => $centavos / 100];
    }

    /*
        Abre el turno. Si el fondo no es el autorizado, $autorizo (gerente o administrador)
        y $observaciones son obligatorios.
    */
    public static function abrir(Trabajador $cajero, array $conteo, ?Trabajador $autorizo, ?string $observaciones): TurnoCaja
    {
        return DB::transaction(function () use ($cajero, $conteo, $autorizo, $observaciones) {
            // Bloquea al cajero: si da doble clic, no se abren dos turnos
            Trabajador::whereKey($cajero->idTrabajador)->lockForUpdate()->first();
            if ($turno = self::turnoAbierto($cajero)) {
                return $turno;
            }

            $autorizado = self::fondoAutorizado();
            $cuadra = abs($conteo['total'] - $autorizado) < 0.005;
            $observaciones = $observaciones !== null && trim($observaciones) !== '' ? trim($observaciones) : null;

            if (!$cuadra && !$autorizo) {
                throw ValidationException::withMessages(['turno' => 'Contaste ' . Producto::formatoPrecio($conteo['total'])
                    . ' y el fondo autorizado es ' . Producto::formatoPrecio($autorizado)
                    . '. Para abrir así, un gerente o administrador tiene que autorizarlo.']);
            }
            if (!$cuadra && $observaciones === null) {
                throw ValidationException::withMessages(['turno' => 'Escribe en «Observaciones» por qué el fondo es distinto.']);
            }

            $turno = TurnoCaja::create([
                'fk_idTrabajador' => $cajero->idTrabajador,
                'numeroCaja' => 1,
                'estado' => TurnoCaja::ABIERTO,
                'fondoInicial' => $conteo['total'],
                'conteoApertura' => ['billetes' => $conteo['billetes'], 'monedas' => $conteo['monedas']],
                'fk_idTrabajadorAutorizaApertura' => $cuadra ? null : $autorizo->idTrabajador,
                'observacionesApertura' => $observaciones,
            ]);

            Bitacora::registrar('ABRIR_TURNO', 'turnosCaja', $turno->idTurno, [
                'fondo' => $conteo['total'], 'autorizado' => $autorizado,
            ], $cuadra ? null : $autorizo);

            return $turno;
        });
    }

    /*
        Lo que DEBERÍA haber en la caja al hacer el corte (Figma: "Corte esperado"):
          - Efectivo: el fondo + lo que cobró en efectivo en su turno − lo que regresó en devoluciones.
          - Cada otro método (tarjeta, transferencia...): lo que cobró con él en su turno.
        "Cobró" = pagos que registró ESTE cajero desde que abrió el turno (ventas y abonos), sin ventas canceladas.
    */
    public static function esperado(TurnoCaja $turno): array
    {
        $hasta = $turno->fechaCierre ?? now();
        $pagos = PagoVenta::with('metodo')
            ->where('fk_idTrabajador', $turno->fk_idTrabajador)
            ->whereBetween('fechaPago', [$turno->fechaApertura, $hasta])
            ->whereHas('venta', fn ($v) => $v->where('estadoVenta', Venta::COMPLETADA))
            ->get();

        $reembolsos = round((float) Solicitud::where('tipoSolicitud', Solicitud::DEVOLUCION)->where('estado', Solicitud::APROBADA)
            ->where('fk_idTrabajadorSolicitante', $turno->fk_idTrabajador)
            ->whereBetween('fechaSolicitud', [$turno->fechaApertura, $hasta])
            ->get()->sum(fn ($d) => $d->reembolso()), 2);

        $metodos = MetodoPago::activos()->get();
        $esEfectivo = fn ($metodo) => mb_strtolower(trim($metodo->nombre)) === 'efectivo';
        $cobradoCon = fn ($id) => round((float) $pagos->where('fk_idMetodoPago', $id)->sum('monto'), 2);

        $efectivo = $metodos->first($esEfectivo);
        $cobradoEfectivo = $efectivo ? $cobradoCon($efectivo->idMetodoPago) : 0.0;

        // Otros métodos: los activos y, si se desactivó uno que sí se usó, también
        $otros = $metodos->reject($esEfectivo)->values();
        foreach ($pagos->pluck('metodo')->filter()->unique('idMetodoPago') as $usado) {
            if (!$esEfectivo($usado) && !$otros->contains('idMetodoPago', $usado->idMetodoPago)) {
                $otros->push($usado);
            }
        }

        return [
            'fondo' => (float) $turno->fondoInicial,
            'cobradoEfectivo' => $cobradoEfectivo,
            'reembolsos' => $reembolsos,
            'efectivo' => round((float) $turno->fondoInicial + $cobradoEfectivo - $reembolsos, 2),
            'otros' => $otros->map(fn ($m) => ['id' => $m->idMetodoPago, 'nombre' => $m->nombre, 'esperado' => $cobradoCon($m->idMetodoPago)])->values()->all(),
            'ventas' => $pagos->pluck('fk_idVenta')->unique()->count(),
        ];
    }

    /*
        Guarda el corte y CIERRA el turno. $otrosContados: [idMetodo => monto contado].
        Si el total contado no es el esperado (falta o sobra dinero), lo autoriza un gerente
        o administrador y se escribe qué pasó.
    */
    public static function cerrar(TurnoCaja $turno, array $conteo, array $otrosContados, ?Trabajador $autorizo, ?string $observaciones): TurnoCaja
    {
        return DB::transaction(function () use ($turno, $conteo, $otrosContados, $autorizo, $observaciones) {
            $turno = TurnoCaja::whereKey($turno->idTurno)->lockForUpdate()->firstOrFail();
            if ($turno->estado !== TurnoCaja::ABIERTO) {
                throw ValidationException::withMessages(['corte' => "El {$turno->nombre()} ya tiene su corte."]);
            }

            $esperado = self::esperado($turno);
            $otros = collect($esperado['otros'])->map(fn ($m) => $m + ['contado' => round((float) ($otrosContados[$m['id']] ?? 0), 2)]);

            $totalContado = round($conteo['total'] + $otros->sum('contado'), 2);
            $totalEsperado = round($esperado['efectivo'] + $otros->sum('esperado'), 2);
            $diferencia = round($totalContado - $totalEsperado, 2);
            $cuadra = abs($diferencia) < 0.005;
            $observaciones = $observaciones !== null && trim($observaciones) !== '' ? trim($observaciones) : null;

            if (!$cuadra && !$autorizo) {
                throw ValidationException::withMessages(['corte' => ($diferencia < 0 ? 'Faltan ' : 'Sobran ') . Producto::formatoPrecio(abs($diferencia))
                    . '. Para guardar el corte así, un gerente o administrador tiene que autorizarlo.']);
            }
            if (!$cuadra && $observaciones === null) {
                throw ValidationException::withMessages(['corte' => 'Escribe en «Observaciones» qué pasó con la diferencia.']);
            }

            // Tarjeta y transferencia también en sus columnas (para consultas rápidas)
            $porNombre = fn (string $texto, string $campo) => round((float) $otros
                ->filter(fn ($m) => str_contains(mb_strtolower($m['nombre']), $texto))->sum($campo), 2);

            $turno->fill([
                'estado' => TurnoCaja::CERRADO,
                'fechaCierre' => now(),
                'conteoCierre' => [
                    'billetes' => $conteo['billetes'],
                    'monedas' => $conteo['monedas'],
                    'otros' => $otros->values()->all(),
                    'cobradoEfectivo' => $esperado['cobradoEfectivo'],
                    'reembolsos' => $esperado['reembolsos'],
                ],
                'efectivoContado' => $conteo['total'],
                'tarjetaContado' => $porNombre('tarjeta', 'contado'),
                'transferenciaContado' => $porNombre('transfer', 'contado'),
                'efectivoEsperado' => $esperado['efectivo'],
                'tarjetaEsperado' => $porNombre('tarjeta', 'esperado'),
                'transferenciaEsperado' => $porNombre('transfer', 'esperado'),
                'diferencia' => $diferencia,
                'fk_idTrabajadorAutorizaCierre' => $cuadra ? null : $autorizo->idTrabajador,
                'observacionesCierre' => $observaciones,
            ])->save();

            Bitacora::registrar('CORTE_CAJA', 'turnosCaja', $turno->idTurno, [
                'contado' => $totalContado, 'esperado' => $totalEsperado, 'diferencia' => $diferencia,
            ], $cuadra ? null : $autorizo);

            return $turno;
        });
    }
}
