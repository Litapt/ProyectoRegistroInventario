<?php

namespace App\Support;

use App\Models\Configuracion;
use Throwable;

/*
    Datos de la tienda (Configuración → "Datos de la tienda") en un solo lugar.
    Los usan la barra de arriba, el inicio de sesión, los tickets y las cotizaciones.

        Tienda::nombre()   "Refaccionaria El Güero"  (si no hay, APP_NAME del .env)
        Tienda::logo()     dirección de la imagen del logo, o la luna del diseño
        Tienda::datos()    todo junto (nombre, rfc, dirección, teléfono, pie del ticket, logo)

    Se lee una sola vez por página.
*/
class Tienda
{
    private static ?array $datos = null;

    public static function datos(): array
    {
        if (self::$datos !== null) {
            return self::$datos;
        }

        try {
            $valores = Configuracion::whereIn('clave', [
                'tienda.nombreComercial', 'tienda.rfc', 'tienda.direccion',
                'tienda.telefono', 'tienda.textoComprobante', 'tienda.logo',
            ])->pluck('valor', 'clave');
        } catch (Throwable) {
            $valores = collect(); // todavía no existe la tabla (antes de php artisan migrate)
        }

        $texto = fn (string $clave) => ($v = trim((string) ($valores[$clave] ?? ''))) !== '' ? $v : null;
        $logo = $texto('tienda.logo');

        return self::$datos = [
            'nombre' => $texto('tienda.nombreComercial') ?? config('app.name'),
            'rfc' => $texto('tienda.rfc'),
            'direccion' => $texto('tienda.direccion'),
            'telefono' => $texto('tienda.telefono'),
            'pie' => $texto('tienda.textoComprobante') ?? '¡Gracias por su compra!',
            'logo' => $logo && is_file(public_path($logo)) ? asset($logo) : null,
        ];
    }

    public static function nombre(): string
    {
        return self::datos()['nombre'];
    }

    /* El logo que subió el administrador; si no hay, el del diseño */
    public static function logo(): string
    {
        return self::datos()['logo'] ?? asset('imagenes/logo.svg');
    }

    public static function tieneLogo(): bool
    {
        return self::datos()['logo'] !== null;
    }

    /* Después de guardar cambios, para que la misma página ya los vea */
    public static function olvidar(): void
    {
        self::$datos = null;
    }
}
