<?php

namespace App\Support;

use App\Models\Bitacora;
use App\Models\Configuracion;
use App\Models\Producto;
use App\Models\Trabajador;
use App\Models\TurnoCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/*
    La caja del cajero: abrir el turno con su fondo (y, más adelante, el corte).

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
}
