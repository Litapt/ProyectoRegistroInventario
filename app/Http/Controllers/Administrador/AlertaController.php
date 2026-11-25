<?php

namespace App\Http\Controllers\Administrador;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use App\Models\Motivo;
use App\Models\Producto;
use App\Models\Solicitud;
use App\Support\Incidencias;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/*
    Alertas del administrador (Figma Page 2: "Alertas · Stock" y "Alertas · Incidencias").
    La lógica de las incidencias está en App\Support\Incidencias.
*/
class AlertaController extends Controller
{
    /* Pestaña "Stock": productos en o bajo su mínimo, agotados primero */
    public function stock(): View
    {
        $productos = Producto::with(['marca', 'compatibilidades.modelo.marca'])
            ->requierenAtencion()
            ->orderByRaw('CASE WHEN existencia = 0 THEN 0 ELSE 1 END')
            ->orderBy('existencia')->orderBy('nombre')
            ->paginate(10);

        return view('administrador.alertas.stock', $this->contadores() + [
            'productos' => $productos,
            'agotados' => Producto::requierenAtencion()->where('existencia', 0)->count(),
            'alertasActivadas' => Configuracion::valor('alertasStockBajo', '1') === '1',
        ]);
    }

    /* Pestaña "Incidencias": abiertas (y en revisión) o, aparte, las resueltas */
    public function incidencias(Request $request): View
    {
        $verResueltas = $request->query('ver') === 'resueltas';
        $buscar = mb_substr(trim((string) $request->query('buscar', '')), 0, 60);
        $prioridad = array_key_exists((string) $request->query('prioridad'), Solicitud::PRIORIDADES) ? $request->query('prioridad') : '';

        $incidencias = Solicitud::with(['producto.categoria', 'producto.ubicacion', 'motivoDe', 'solicitante.rol', 'responsable'])
            ->where('tipoSolicitud', Solicitud::INCIDENCIA)
            ->when($verResueltas,
                fn ($q) => $q->where('estado', Solicitud::RESUELTA),
                fn ($q) => $q->whereIn('estado', [Solicitud::PENDIENTE, Solicitud::EN_REVISION]))
            ->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('folio', 'like', "%{$buscar}%")
                ->orWhere('motivo', 'like', "%{$buscar}%")
                ->orWhereHas('producto', fn ($p) => $p->where('nombre', 'like', "%{$buscar}%")->orWhere('codigoInterno', 'like', "%{$buscar}%"))))
            ->when($prioridad !== '', fn ($q) => $q->where('prioridad', $prioridad))
            ->when($verResueltas,
                fn ($q) => $q->orderByDesc('fechaResolucion'),
                fn ($q) => $q->orderByRaw("CASE prioridad WHEN 'ALTA' THEN 0 WHEN 'MEDIA' THEN 1 ELSE 2 END")->orderByDesc('fechaSolicitud'))
            ->orderByDesc('idSolicitud')
            ->paginate(10)->withQueryString();

        return view('administrador.alertas.incidencias', $this->contadores() + [
            'incidencias' => $incidencias,
            'verResueltas' => $verResueltas,
            'buscar' => $buscar,
            'prioridad' => $prioridad,
            'tipos' => Motivo::de(Motivo::INCIDENCIA)->get(),
            'resueltas' => Solicitud::where('tipoSolicitud', Solicitud::INCIDENCIA)->where('estado', Solicitud::RESUELTA)->count(),
            'enRevision' => Solicitud::where('tipoSolicitud', Solicitud::INCIDENCIA)->where('estado', Solicitud::EN_REVISION)->count(),
            'productoAnterior' => old('producto') ? Producto::with('marca')->find(old('producto')) : null,
            'incidenciaReabierta' => session('modalIncidencia') ? Solicitud::with(['producto', 'motivoDe', 'solicitante', 'responsable'])->find(session('modalIncidencia')) : null,
        ]);
    }

    /* "Registrar incidencia" */
    public function registrar(Request $request): RedirectResponse
    {
        $datos = $request->validateWithBag('registrar', [
            'producto' => ['required', 'integer', Rule::exists('productos', 'idProducto')],
            'tipo' => ['required', 'integer', Rule::exists('motivos', 'idMotivo')->where('categoriaMotivo', Motivo::INCIDENCIA)->where('activo', true)],
            'cantidad' => ['required', 'integer', 'min:1', 'max:9999'],
            'prioridad' => ['required', Rule::in(array_keys(Solicitud::PRIORIDADES))],
            'descripcion' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'producto.required' => 'Busca y elige el producto.',
            'producto.exists' => 'Ese producto no existe.',
            'tipo.required' => 'Elige el tipo de incidencia.',
            'tipo.exists' => 'Elige un tipo de la lista.',
            'cantidad.required' => 'Escribe cuántas piezas tienen el problema.',
            'cantidad.min' => 'Debe ser al menos 1 pieza.',
            'prioridad.in' => 'Elige la prioridad.',
            'descripcion.required' => 'Describe el problema.',
            'descripcion.min' => 'Describe un poco más el problema.',
        ]);

        $incidencia = Incidencias::registrar((int) $datos['producto'], (int) $datos['tipo'], (int) $datos['cantidad'],
            $datos['prioridad'], trim($datos['descripcion']), $request->user());

        return redirect()->route('administrador.alertas.incidencias')
            ->with('alerta', ['success', "Incidencia {$incidencia->folio} registrada."]);
    }

    public function enRevision(Request $request, Solicitud $incidencia): RedirectResponse
    {
        try {
            Incidencias::enRevision($incidencia, $request->user());
        } catch (ValidationException $e) {
            return back()->with('alerta', ['warning', collect($e->errors())->flatten()->first()]);
        }

        return back()->with('alerta', ['success', "La incidencia {$incidencia->folio} quedó «En revisión»."]);
    }

    public function resolver(Request $request, Solicitud $incidencia): RedirectResponse
    {
        try {
            $datos = validator($request->all(), [
                'comentario' => ['required', 'string', 'min:5', 'max:500'],
                'piezasDeBaja' => ['nullable', 'integer', 'min:0', 'max:9999'],
            ], [
                'comentario.required' => 'Escribe cómo se resolvió.',
                'comentario.min' => 'Escribe un poco más de cómo se resolvió.',
                'piezasDeBaja.integer' => 'Las piezas a dar de baja deben ser un número entero.',
                'piezasDeBaja.min' => 'Las piezas a dar de baja no pueden ser negativas.',
            ])->validate();

            Incidencias::resolver($incidencia, $datos['comentario'], (int) ($datos['piezasDeBaja'] ?? 0), $request->user());
        } catch (ValidationException $e) {
            return back()
                ->withErrors(['incidencia' => collect($e->errors())->flatten()->all()], 'resolver')
                ->withInput()
                ->with('modalIncidencia', $incidencia->idSolicitud);
        }

        return redirect()->route('administrador.alertas.incidencias')
            ->with('alerta', ['success', "La incidencia {$incidencia->folio} quedó resuelta."]);
    }

    private function contadores(): array
    {
        return [
            'totalStock' => Producto::requierenAtencion()->count(),
            'totalIncidencias' => Solicitud::where('tipoSolicitud', Solicitud::INCIDENCIA)
                ->whereIn('estado', [Solicitud::PENDIENTE, Solicitud::EN_REVISION])->count(),
        ];
    }
}
