<?php

namespace App\Http\Controllers\Administrador;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\MarcaVehiculo;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Trabajador;
use App\Models\Ubicacion;
use App\Support\Autorizacion;
use App\Support\MensajesValidacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/*
    Productos del administrador (Figma Page 2: "Administrador · Productos").
    Consultar con filtros, registrar, editar, desactivar y reactivar.
    Las ventanas (modales) están en resources/views/administrador/productos/
*/
class ProductoController extends Controller
{
    private const POR_PAGINA = 10;
    private const CARPETA_IMAGENES = 'imagenes/productos';

    // Nombres de los campos como se ven en pantalla (para los mensajes de error)
    private const CAMPOS = [
        'codigo' => 'código / SKU',
        'nombre' => 'nombre',
        'categoria' => 'categoría',
        'marca' => 'marca',
        'precioCompra' => 'precio de compra',
        'precioVenta' => 'precio de venta',
        'stockInicial' => 'stock inicial',
        'stockMinimo' => 'stock mínimo',
        'ubicacion' => 'ubicación en almacén',
        'vehiculos' => 'vehículos compatibles',
        'vehiculos.*.modelo' => 'modelo del vehículo',
        'vehiculos.*.desde' => 'año inicial del vehículo',
        'vehiculos.*.hasta' => 'año final del vehículo',
        'imagen' => 'imagen del producto',
    ];

    /* Lista con el "Menú consulta" (buscar + filtros) */
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $categoria = (int) $request->query('categoria', 0);
        $marca = (int) $request->query('marca', 0);
        $vehiculo = (int) $request->query('vehiculo', 0);
        $estado = (string) $request->query('estado', '');
        $verDesactivados = $request->query('ver') === 'desactivados';

        $consulta = Producto::with(['categoria', 'marca', 'ubicacion', 'compatibilidades'])
            ->where('activo', !$verDesactivados);

        if ($buscar !== '') {
            $consulta->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('codigoInterno', 'like', "%{$buscar}%");
            });
        }

        if ($categoria > 0) {
            $consulta->where('fk_idCategoria', $categoria);
        }

        if ($marca > 0) {
            $consulta->where('fk_idMarca', $marca);
        }

        // Filtro por modelo de vehículo (id de modelosVehiculo)
        if ($vehiculo > 0) {
            $consulta->whereHas('compatibilidades', fn ($q) => $q->where('fk_idModeloVehiculo', $vehiculo));
        }

        if (in_array($estado, [Producto::ESTADO_DISPONIBLE, Producto::ESTADO_BAJO, Producto::ESTADO_AGOTADO], true)) {
            $consulta->conEstado($estado);
        }

        $productos = $consulta->orderBy('nombre')->paginate(self::POR_PAGINA)->withQueryString();

        return view('administrador.productos.index', [
            'productos' => $productos,
            'categorias' => Categoria::activas()->get(),
            'marcas' => Marca::activas()->get(),
            // Marcas de vehículo con sus modelos: para el filtro y para la ventana
            'marcasVehiculo' => MarcaVehiculo::activas()
                ->with(['modelos' => fn ($q) => $q->where('activo', true)])->get(),
            'totalActivos' => Producto::activos()->count(),
            'totalDesactivados' => Producto::where('activo', false)->count(),
            'filtros' => compact('buscar', 'categoria', 'marca', 'vehiculo', 'estado'),
            'verDesactivados' => $verDesactivados,
            // Si "Editar" regresó con errores, el producto que se estaba editando
            'productoReabierto' => session('modalProducto') === 'editar'
                ? Producto::with(['ubicacion', 'compatibilidades'])->find(session('modalProductoId'))
                : null,
        ]);
    }

    /* Ventana "Registrar producto" → Guardar */
    public function store(Request $request): RedirectResponse
    {
        $validador = $this->validador($request, null);

        if ($validador->fails()) {
            return $this->regresarConErrores($validador, 'registrar');
        }

        $datos = $validador->validated();
        $imagen = $this->guardarImagen($request->file('imagen'));

        try {
            $producto = DB::transaction(function () use ($datos, $imagen) {
                $producto = new Producto($this->camposProducto($datos));
                $producto->imagen = $imagen;
                $producto->existencia = (int) $datos['stockInicial'];
                $producto->activo = true;
                $producto->save();

                $this->guardarVehiculos($producto, $datos['vehiculos'] ?? []);

                // El stock inicial queda en el historial como una entrada
                if ($producto->existencia > 0) {
                    MovimientoInventario::create([
                        'fk_idProducto' => $producto->idProducto,
                        'tipoMovimiento' => MovimientoInventario::ENTRADA,
                        'cantidad' => $producto->existencia,
                        'existenciaAnterior' => 0,
                        'existenciaNueva' => $producto->existencia,
                        'costoUnitario' => $producto->precioCompraActual,
                        'fk_idTrabajador' => auth()->id(),
                        'observaciones' => 'Stock inicial al registrar el producto',
                    ]);
                }

                Bitacora::registrar('CREAR', 'productos', $producto->idProducto, [
                    'codigo' => $producto->codigoInterno,
                    'nombre' => $producto->nombre,
                ]);

                return $producto;
            });
        } catch (Throwable $e) {
            $this->borrarImagen($imagen); // no se quedan fotos sueltas si algo falló
            throw $e;
        }

        return redirect()
            ->to(\App\Support\Ruta::a('productos.index'))
            ->with('alerta', ['success', "Producto «{$producto->nombre}» registrado correctamente."]);
    }

    /* Ventana "Editar producto" → Guardar cambios */
    public function update(Request $request, Producto $producto): RedirectResponse
    {
        if (!$producto->activo) {
            return $this->noDisponible();
        }

        $validador = $this->validador($request, $producto);

        if ($validador->fails()) {
            return $this->regresarConErrores($validador, 'editar', $producto);
        }

        $datos = $validador->validated();
        $imagenNueva = $this->guardarImagen($request->file('imagen'));
        $imagenVieja = $producto->imagen;

        try {
            DB::transaction(function () use ($producto, $datos, $imagenNueva) {
                $producto->fill($this->camposProducto($datos));

                if ($imagenNueva !== null) {
                    $producto->imagen = $imagenNueva;
                }

                // Qué cambió, para la bitácora (antes → después)
                $cambios = [];
                foreach ($producto->getDirty() as $campo => $nuevo) {
                    $cambios[$campo] = ['antes' => $producto->getOriginal($campo), 'despues' => $nuevo];
                }

                $producto->fechaActualizacion = now();
                $producto->save();

                $vehiculosAntes = $producto->compatibilidades()->count();
                $producto->compatibilidades()->delete();
                $this->guardarVehiculos($producto, $datos['vehiculos'] ?? []);
                $cambios['vehiculos'] = ['antes' => $vehiculosAntes, 'despues' => count($datos['vehiculos'] ?? [])];

                Bitacora::registrar('EDITAR', 'productos', $producto->idProducto, $cambios);
            });
        } catch (Throwable $e) {
            $this->borrarImagen($imagenNueva);
            throw $e;
        }

        // La foto anterior se borra hasta que lo demás se guardó bien
        if ($imagenNueva !== null) {
            $this->borrarImagen($imagenVieja);
        }

        return redirect()
            ->to(\App\Support\Ruta::a('productos.index'))
            ->with('alerta', ['success', "Se guardaron los cambios de «{$producto->nombre}»."]);
    }

    /*
        Ventana "Eliminar producto": NO se borra, se desactiva (decisión del equipo)
        para no perder las ventas e historial que lo usan. Se puede reactivar.
        Siempre pide correo y contraseña de un gerente o administrador (App\Support\Autorizacion).
    */
    public function desactivar(Request $request, Producto $producto): RedirectResponse
    {
        if (!$producto->activo) {
            return $this->noDisponible();
        }

        $autorizo = $this->autorizar($request);
        if (is_string($autorizo)) {
            return redirect()->to(\App\Support\Ruta::a('productos.index'))->with('alerta', ['danger', $autorizo]);
        }

        DB::transaction(function () use ($producto, $autorizo) {
            $producto->activo = false;
            $producto->fechaActualizacion = now();
            $producto->save();

            Bitacora::registrar('DESACTIVAR', 'productos', $producto->idProducto, [
                'codigo' => $producto->codigoInterno,
                'nombre' => $producto->nombre,
            ], $autorizo);
        });

        return redirect()
            ->to(\App\Support\Ruta::a('productos.index'))
            ->with('alerta', ['success', "«{$producto->nombre}» se desactivó. Puedes reactivarlo en «Ver desactivados»."]);
    }

    /* Vuelve a activar un producto desactivado (también con autorización) */
    public function reactivar(Request $request, Producto $producto): RedirectResponse
    {
        if ($producto->activo) {
            return redirect()->to(\App\Support\Ruta::a('productos.index'));
        }

        $autorizo = $this->autorizar($request);
        if (is_string($autorizo)) {
            return redirect()->to(\App\Support\Ruta::a('productos.index', ['ver' => 'desactivados']))->with('alerta', ['danger', $autorizo]);
        }

        DB::transaction(function () use ($producto, $autorizo) {
            $producto->activo = true;
            $producto->fechaActualizacion = now();
            $producto->save();

            Bitacora::registrar('REACTIVAR', 'productos', $producto->idProducto, [
                'codigo' => $producto->codigoInterno,
                'nombre' => $producto->nombre,
            ], $autorizo);
        });

        return redirect()
            ->to(\App\Support\Ruta::a('productos.index'))
            ->with('alerta', ['success', "«{$producto->nombre}» está activo otra vez."]);
    }

    /* Revisa correo y contraseña del que autoriza: regresa el Trabajador o el texto del error */
    private function autorizar(Request $request): Trabajador|string
    {
        $request->validate(Autorizacion::REGLAS, MensajesValidacion::MENSAJES, Autorizacion::CAMPOS);

        return Autorizacion::verificar($request);
    }

    // ---------------------------------------------------------------------

    /* Reglas de los formularios Registrar y Editar ($producto = null al registrar) */
    private function validador(Request $request, ?Producto $producto): \Illuminate\Validation\Validator
    {
        // Antes de revisar: código en mayúsculas y sin espacios en las orillas
        $request->merge([
            'codigo' => mb_strtoupper(trim((string) $request->input('codigo'))),
            'nombre' => trim((string) $request->input('nombre')),
        ]);

        $anioMaximo = (int) now()->year + 1;

        $reglas = [
            'codigo' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9][A-Z0-9-]*$/',
                Rule::unique('productos', 'codigoInterno')->ignore($producto?->idProducto, 'idProducto')],
            'nombre' => ['required', 'string', 'max:150'],
            'categoria' => ['required', 'integer', Rule::exists('categorias', 'idCategoria')->where('activo', true)],
            'marca' => ['required', 'integer', Rule::exists('marcas', 'idMarca')->where('activo', true)],
            'precioCompra' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999.99'],
            'precioVenta' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999.99'],
            'stockMinimo' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'ubicacion' => ['nullable', 'string', 'max:40'],
            'vehiculos' => ['nullable', 'array', 'max:30'],
            'vehiculos.*.modelo' => ['required', 'integer', Rule::exists('modelosVehiculo', 'idModeloVehiculo')->where('activo', true)],
            'vehiculos.*.desde' => ['required', 'integer', 'min:1950', "max:{$anioMaximo}"],
            'vehiculos.*.hasta' => ['nullable', 'integer', 'min:1950', "max:{$anioMaximo}", 'gte:vehiculos.*.desde'],
            'imagen' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:5120'],
        ];

        // El stock sólo se escribe al registrar; después cambia con "Ajuste stock"
        if ($producto === null) {
            $reglas['stockInicial'] = ['required', 'integer', 'min:0', 'max:999999'];
        }

        $mensajes = MensajesValidacion::MENSAJES + [
            'codigo.regex' => 'El código / SKU sólo puede llevar letras, números y guiones (ej. FR-BOS-001).',
            'vehiculos.*.hasta.gte' => 'El año final del vehículo no puede ser menor que el inicial.',
            'imagen.mimes' => 'La imagen debe ser PNG o JPG.',
            'imagen.max' => 'La imagen no puede pesar más de 5 MB.',
        ];

        return Validator::make($request->all(), $reglas, $mensajes, self::CAMPOS);
    }

    /* Convierte lo que llegó del formulario a las columnas de la tabla productos */
    private function camposProducto(array $datos): array
    {
        return [
            'codigoInterno' => $datos['codigo'],
            'nombre' => $datos['nombre'],
            'fk_idCategoria' => (int) $datos['categoria'],
            'fk_idMarca' => (int) $datos['marca'],
            'precioCompraActual' => $datos['precioCompra'],
            'precioVentaActual' => $datos['precioVenta'],
            'stockMinimo' => isset($datos['stockMinimo']) && $datos['stockMinimo'] !== '' ? (int) $datos['stockMinimo'] : null,
            'fk_idUbicacion' => Ubicacion::desdeTexto($datos['ubicacion'] ?? '')->idUbicacion,
        ];
    }

    /* Un renglón en productosCompatibilidades por cada vehículo (sin repetidos) */
    private function guardarVehiculos(Producto $producto, array $vehiculos): void
    {
        $vistos = [];

        foreach ($vehiculos as $vehiculo) {
            $modelo = (int) $vehiculo['modelo'];
            $desde = (int) $vehiculo['desde'];
            $hasta = isset($vehiculo['hasta']) && $vehiculo['hasta'] !== '' ? (int) $vehiculo['hasta'] : null;

            $llave = "{$modelo}|{$desde}|{$hasta}";
            if (isset($vistos[$llave])) {
                continue;
            }
            $vistos[$llave] = true;

            $producto->compatibilidades()->create([
                'fk_idModeloVehiculo' => $modelo,
                'anioDesde' => $desde,
                'anioHasta' => $hasta,
            ]);
        }
    }

    /* Guarda la foto en public/imagenes/productos con un nombre al azar; regresa el nombre */
    private function guardarImagen(?UploadedFile $archivo): ?string
    {
        if ($archivo === null) {
            return null;
        }

        // La extensión sale del CONTENIDO del archivo, no del nombre que trae
        $nombre = Str::uuid() . '.' . ($archivo->guessExtension() === 'png' ? 'png' : 'jpg');
        $archivo->move(public_path(self::CARPETA_IMAGENES), $nombre);

        return $nombre;
    }

    private function borrarImagen(?string $nombre): void
    {
        // basename() evita que un nombre raro apunte fuera de la carpeta
        if ($nombre !== null && $nombre !== '') {
            $ruta = public_path(self::CARPETA_IMAGENES . '/' . basename($nombre));
            if (is_file($ruta)) {
                @unlink($ruta);
            }
        }
    }

    /* Regresa a la lista con los errores y vuelve a abrir la ventana que se estaba llenando */
    private function regresarConErrores(\Illuminate\Validation\Validator $validador, string $modal, ?Producto $producto = null): RedirectResponse
    {
        return redirect()
            ->to(\App\Support\Ruta::a('productos.index'))
            ->withErrors($validador)
            ->withInput()
            ->with('modalProducto', $modal)
            ->with('modalProductoId', $producto?->idProducto);
    }

    private function noDisponible(): RedirectResponse
    {
        return redirect()
            ->to(\App\Support\Ruta::a('productos.index'))
            ->with('alerta', ['warning', 'Ese producto está desactivado. Reactívalo para poder modificarlo.']);
    }
}
