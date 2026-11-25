<?php

namespace App\Http\Controllers\Administrador;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\Marca;
use App\Models\MetodoPago;
use App\Support\Folios;
use App\Support\Tienda;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/*
    Configuración (Figma Page 2: "Configuración"). Cinco secciones; cada una se guarda aparte:
      1 Datos de la tienda · 2 Inventario · 3 Ventas · 4 Notificaciones · 5 Seguridad
    Los valores viven en la tabla configuraciones (clave → valor), salvo las listas
    (categorías, marcas, métodos de pago), que son sus propias tablas, y el folio (tabla folios).
*/
class ConfiguracionController extends Controller
{
    public const MINUTOS_CIERRE = [5, 10, 15, 30, 60, 120, 0];   // 0 = nunca

    private const CARPETA_LOGO = 'imagenes/tienda';

    public function index(): View
    {
        $valores = Configuracion::pluck('valor', 'clave');
        $folioVenta = DB::table('folios')->where('tipoDocumento', 'VENTA')->first();

        return view('administrador.configuracion.index', [
            'v' => fn (string $clave, ?string $porDefecto = null) => ($valores[$clave] ?? null) ?? $porDefecto,
            'tienda' => Tienda::datos(),
            'categorias' => Categoria::activas()->pluck('nombre')->implode(', '),
            'marcas' => Marca::activas()->pluck('nombre')->implode(', '),
            'metodosPago' => MetodoPago::activos()->pluck('nombre')->implode(', '),
            'folioVenta' => $folioVenta,
            'siguienteNumero' => $folioVenta
                ? ((str_contains($folioVenta->formato, '{AAAA}') && (int) $folioVenta->anio !== (int) now()->year) ? 1 : $folioVenta->ultimoNumero + 1)
                : 1,
            'minutosCierre' => self::MINUTOS_CIERRE,
        ]);
    }

    /* 1 · Datos de la tienda (y logo) */
    public function tienda(Request $request): RedirectResponse
    {
        $request->merge(['rfc' => mb_strtoupper(str_replace([' ', '-'], '', trim((string) $request->input('rfc'))))]);

        $datos = $request->validateWithBag('tienda', [
            'nombre' => ['required', 'string', 'max:120'],
            'rfc' => ['nullable', 'regex:/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/u'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'pie' => ['nullable', 'string', 'max:200'],
            // Sólo imágenes de verdad (se revisa el contenido, no sólo la extensión)
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'mimetypes:image/png,image/jpeg', 'max:2048'],
        ], [
            'nombre.required' => 'Escribe el nombre de la tienda.',
            'rfc.regex' => 'El RFC no tiene un formato válido (12 o 13 caracteres, ej. RAM900101AB1).',
            'telefono.regex' => 'El teléfono sólo puede llevar números, espacios, guiones y paréntesis.',
            'logo.mimes' => 'El logo debe ser una imagen PNG o JPG.',
            'logo.mimetypes' => 'El logo debe ser una imagen PNG o JPG.',
            'logo.max' => 'El logo puede pesar hasta 2 MB.',
            'pie.max' => 'El texto del ticket puede tener hasta 200 caracteres.',
        ]);

        $quien = $request->user();
        $guardar = fn (string $clave, ?string $valor) => Configuracion::guardar($clave, $valor !== null && trim($valor) !== '' ? trim($valor) : null, $quien);

        $guardar('tienda.nombreComercial', $datos['nombre']);
        $guardar('tienda.rfc', $datos['rfc'] ?? null);
        $guardar('tienda.direccion', $datos['direccion'] ?? null);
        $guardar('tienda.telefono', $datos['telefono'] ?? null);
        $guardar('tienda.textoComprobante', $datos['pie'] ?? null);

        if ($request->hasFile('logo')) {
            $this->borrarLogo();
            $archivo = $request->file('logo');
            $nombre = 'logo-' . Str::uuid() . '.' . ($archivo->guessExtension() === 'png' ? 'png' : 'jpg');
            File::ensureDirectoryExists(public_path(self::CARPETA_LOGO));
            $archivo->move(public_path(self::CARPETA_LOGO), $nombre);
            $guardar('tienda.logo', self::CARPETA_LOGO . '/' . $nombre);
        } elseif ($request->boolean('quitarLogo')) {
            $this->borrarLogo();
            $guardar('tienda.logo', null);
        }

        Bitacora::registrar('EDITAR', 'configuraciones', null, ['seccion' => 'Datos de la tienda']);
        Tienda::olvidar();

        return $this->listo('tienda', 'Se guardaron los datos de la tienda.');
    }

    /* 2 · Inventario: stock mínimo, categorías y marcas (separadas con comas) */
    public function inventario(Request $request): RedirectResponse
    {
        $datos = $request->validateWithBag('inventario', [
            'stockMinimo' => ['required', 'integer', 'min:0', 'max:99999'],
            'categorias' => ['required', 'string', 'max:3000'],
            'marcas' => ['required', 'string', 'max:3000'],
        ], [
            'stockMinimo.required' => 'Escribe el stock mínimo por defecto.',
            'stockMinimo.integer' => 'El stock mínimo debe ser un número entero.',
            'stockMinimo.min' => 'El stock mínimo no puede ser negativo.',
            'categorias.required' => 'Debe haber al menos una categoría.',
            'marcas.required' => 'Debe haber al menos una marca.',
        ]);

        $categorias = $this->lista($datos['categorias'], 80, 'categorías', 'inventario');
        $marcas = $this->lista($datos['marcas'], 80, 'marcas', 'inventario');

        DB::transaction(function () use ($request, $datos, $categorias, $marcas) {
            Configuracion::guardar('stockMinimoDefecto', (string) $datos['stockMinimo'], $request->user());
            $cambios = [
                'categorias' => $this->sincronizar(Categoria::class, $categorias),
                'marcas' => $this->sincronizar(Marca::class, $marcas),
            ];
            Bitacora::registrar('EDITAR', 'configuraciones', null, ['seccion' => 'Inventario'] + $cambios);
        });

        return $this->listo('inventario', 'Se guardó la configuración de inventario.');
    }

    /* 3 · Ventas: IVA, formato de folio y métodos de pago */
    public function ventas(Request $request): RedirectResponse
    {
        $datos = $request->validateWithBag('ventas', [
            'iva' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'formatoFolio' => ['required', Rule::in(Folios::FORMATOS_VENTA)],
            'metodosPago' => ['required', 'string', 'max:1000'],
        ], [
            'iva.required' => 'Escribe el IVA.',
            'iva.numeric' => 'El IVA debe ser un número.',
            'iva.max' => 'El IVA no puede pasar de 100 %.',
            'iva.min' => 'El IVA no puede ser negativo.',
            'iva.decimal' => 'El IVA puede tener hasta 2 decimales.',
            'formatoFolio.in' => 'Elige uno de los formatos de folio.',
            'metodosPago.required' => 'Debe haber al menos un método de pago.',
        ]);

        $metodos = $this->lista($datos['metodosPago'], 40, 'métodos de pago', 'ventas');

        DB::transaction(function () use ($request, $datos, $metodos) {
            Configuracion::guardar('iva', rtrim(rtrim(number_format((float) $datos['iva'], 2, '.', ''), '0'), '.'), $request->user());
            DB::table('folios')->where('tipoDocumento', 'VENTA')->update(['formato' => $datos['formatoFolio']]);
            $cambios = ['metodosPago' => $this->sincronizar(MetodoPago::class, $metodos)];
            Bitacora::registrar('EDITAR', 'configuraciones', null, [
                'seccion' => 'Ventas', 'iva' => $datos['iva'], 'formatoFolio' => $datos['formatoFolio'],
            ] + $cambios);
        });

        return $this->listo('ventas', 'Se guardó la configuración de ventas. Las ventas que ya existen no cambian.');
    }

    /* 4 · Notificaciones */
    public function notificaciones(Request $request): RedirectResponse
    {
        $datos = $request->validateWithBag('notificaciones', [
            'alertasStockBajo' => ['required', Rule::in(['0', '1'])],
            'notificacionesCorreo' => ['required', Rule::in(['0', '1'])],
            'correoNotificaciones' => ['nullable', 'required_if:notificacionesCorreo,1', 'email', 'max:120'],
        ], [
            'correoNotificaciones.required_if' => 'Para mandar avisos por correo, escribe a qué correo.',
            'correoNotificaciones.email' => 'Escribe un correo válido.',
        ]);

        $quien = $request->user();
        Configuracion::guardar('alertasStockBajo', $datos['alertasStockBajo'], $quien);
        Configuracion::guardar('notificacionesCorreo', $datos['notificacionesCorreo'], $quien);
        Configuracion::guardar('correoNotificaciones', ($datos['correoNotificaciones'] ?? '') !== '' ? mb_strtolower(trim($datos['correoNotificaciones'])) : null, $quien);
        Bitacora::registrar('EDITAR', 'configuraciones', null, ['seccion' => 'Notificaciones']);

        return $this->listo('notificaciones', 'Se guardaron las notificaciones.');
    }

    /* 5 · Seguridad: cambiar MI contraseña y el cierre de sesión automático */
    public function seguridad(Request $request): RedirectResponse
    {
        $yo = $request->user();
        $cambiaContrasena = $request->filled('contrasenaNueva') || $request->filled('contrasenaActual');

        $request->validateWithBag('seguridad', [
            'minutosCierre' => ['required', 'integer', Rule::in(self::MINUTOS_CIERRE)],
            'contrasenaActual' => $cambiaContrasena ? ['required', 'current_password'] : ['nullable'],
            'contrasenaNueva' => $cambiaContrasena
                ? ['required', 'confirmed', 'different:contrasenaActual', Password::min(8)->letters()->numbers()]
                : ['nullable'],
        ], [
            'contrasenaActual.required' => 'Escribe tu contraseña actual.',
            'contrasenaActual.current_password' => 'Tu contraseña actual no es correcta.',
            'contrasenaNueva.required' => 'Escribe la nueva contraseña.',
            'contrasenaNueva.confirmed' => 'La confirmación no coincide con la nueva contraseña.',
            'contrasenaNueva.different' => 'La nueva contraseña debe ser distinta de la actual.',
            'contrasenaNueva.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'contrasenaNueva.letters' => 'La nueva contraseña debe llevar al menos una letra.',
            'contrasenaNueva.numbers' => 'La nueva contraseña debe llevar al menos un número.',
        ]);

        Configuracion::guardar('minutosCierreSesion', (string) (int) $request->input('minutosCierre'), $yo);

        if ($cambiaContrasena) {
            $yo->hashContrasena = $request->input('contrasenaNueva'); // se cifra sola
            $yo->save();

            // Se cierran sus otras sesiones abiertas (otra computadora, otro navegador)
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $yo->idTrabajador)
                    ->where('id', '!=', $request->session()->getId())
                    ->delete();
            }
        }

        Bitacora::registrar('EDITAR', 'configuraciones', null, [
            'seccion' => 'Seguridad', 'minutosCierre' => (int) $request->input('minutosCierre'),
            'cambioSuContrasena' => $cambiaContrasena,
        ]);

        return $this->listo('seguridad', $cambiaContrasena
            ? 'Se guardó la seguridad y tu contraseña cambió. Tus otras sesiones abiertas se cerraron.'
            : 'Se guardó la seguridad.');
    }

    // ---------------------------------------------------------------------

    /* "Frenos, filtros,  encendido" → ['Frenos', 'Filtros', 'Encendido'] (sin repetidos) */
    private function lista(string $texto, int $maximo, string $nombre, string $bolsa): array
    {
        $elementos = collect(explode(',', $texto))
            ->map(fn ($t) => preg_replace('/\s+/u', ' ', trim($t)))
            ->filter(fn ($t) => $t !== '')
            ->unique(fn ($t) => mb_strtolower($t))
            ->values();

        $largo = $elementos->first(fn ($t) => mb_strlen($t) > $maximo);
        if ($elementos->isEmpty() || $largo !== null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $nombre => $elementos->isEmpty()
                    ? "Debe haber al menos un elemento en {$nombre}."
                    : "«{$largo}» es muy largo: máximo {$maximo} letras.",
            ])->errorBag($bolsa);
        }

        return $elementos->all();
    }

    /*
        La lista escrita manda: los que están se activan (o se crean), los que se quitaron
        se DESACTIVAN (no se borran: los productos y ventas que los usan los conservan).
    */
    private function sincronizar(string $modelo, array $nombres): array
    {
        /** @var Model $modelo */
        $existentes = $modelo::all();
        $buscados = collect($nombres)->keyBy(fn ($n) => mb_strtolower($n));
        $resultado = ['nuevos' => [], 'desactivados' => [], 'reactivados' => []];

        foreach ($existentes as $fila) {
            $clave = mb_strtolower($fila->nombre);
            $debeEstar = $buscados->has($clave);

            if ($debeEstar && !$fila->activo) {
                $resultado['reactivados'][] = $fila->nombre;
            } elseif (!$debeEstar && $fila->activo) {
                $resultado['desactivados'][] = $fila->nombre;
            }
            if ((bool) $fila->activo !== $debeEstar) {
                $fila->activo = $debeEstar;
                $fila->save();
            }
            $buscados->forget($clave);
        }

        foreach ($buscados as $nombre) {
            $nuevo = new $modelo();
            $nuevo->nombre = $nombre;
            $nuevo->activo = true;
            $nuevo->save();
            $resultado['nuevos'][] = $nombre;
        }

        return array_filter($resultado);
    }

    private function borrarLogo(): void
    {
        $actual = Configuracion::valor('tienda.logo');
        // Sólo se borra si está dentro de la carpeta del logo (nunca otro archivo)
        if ($actual && str_starts_with($actual, self::CARPETA_LOGO . '/') && !str_contains($actual, '..')) {
            File::delete(public_path($actual));
        }
    }

    private function listo(string $seccion, string $mensaje): RedirectResponse
    {
        return redirect()->to(\App\Support\Ruta::a('configuracion.index') . '#' . $seccion)
            ->with('alerta', ['success', $mensaje])
            ->with('seccionGuardada', $seccion);
    }
}
