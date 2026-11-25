<?php

namespace App\Http\Controllers\Administrador;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Cliente;
use App\Support\Autorizacion;
use App\Support\MensajesValidacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/*
    Clientes del administrador (Figma Page 2: "Administrador · Clientes" y sus ventanas).
    Registrar es libre; editar, eliminar (desactivar) y reactivar piden la
    autorización de un gerente o administrador (App\Support\Autorizacion).
*/
class ClienteController extends Controller
{
    private const POR_PAGINA = 8;

    private const CAMPOS = [
        'nombre' => 'nombre completo',
        'telefono' => 'teléfono',
        'correo' => 'correo',
        'rfc' => 'RFC',
        'direccion' => 'dirección',
        'notas' => 'notas',
    ];

    /* Lista con el buscador */
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $verDesactivados = $request->query('ver') === 'desactivados';

        $clientes = Cliente::where('activo', !$verDesactivados)
            ->buscar($buscar)
            ->orderByDesc('fechaRegistro')->orderByDesc('idCliente')
            ->paginate(self::POR_PAGINA)->withQueryString();

        return view('administrador.clientes.index', [
            'clientes' => $clientes,
            'buscar' => $buscar,
            'verDesactivados' => $verDesactivados,
            'totalActivos' => Cliente::activos()->count(),
            'totalDesactivados' => Cliente::where('activo', false)->count(),
            'clienteReabierto' => session('modalCliente') === 'editar' ? Cliente::find(session('modalClienteId')) : null,
        ]);
    }

    /* Ventana "Registrar cliente" → Guardar */
    public function store(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validador = $this->validador($request, null);

        if ($validador->fails()) {
            // Desde la pantalla de Ventas ("Registrar nuevo cliente") se pide en JSON
            if ($request->expectsJson()) {
                return response()->json(['errores' => $validador->errors()->all()], 422);
            }

            return $this->regresarConErrores($validador->errors()->toArray(), 'registrar');
        }

        $cliente = DB::transaction(function () use ($validador) {
            $cliente = new Cliente($this->camposCliente($validador->validated()));
            $cliente->activo = true;
            $cliente->save();

            Bitacora::registrar('CREAR', 'clientes', $cliente->idCliente, ['nombre' => $cliente->nombre()]);

            return $cliente;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $cliente->idCliente,
                'nombre' => $cliente->nombre(),
                'codigo' => $cliente->codigo(),
                'telefono' => $cliente->telefono,
            ], 201);
        }

        return redirect()
            ->route('administrador.clientes.index')
            ->with('alerta', ['success', "Cliente «{$cliente->nombre()}» registrado con el código {$cliente->codigo()}."]);
    }

    /* Ventana "Editar cliente" → "Confirmar edición" (con autorización) */
    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        if (!$cliente->activo) {
            return $this->noDisponible();
        }

        $validador = $this->validador($request, $cliente);

        if ($validador->fails()) {
            return $this->regresarConErrores($validador->errors()->toArray(), 'editar', $cliente);
        }

        $autorizo = $this->autorizar($request);
        if (is_string($autorizo)) {
            // Se regresa a la ventana con lo que se había escrito, para no perder los cambios
            return $this->regresarConErrores(['autorizacion' => [$autorizo]], 'editar', $cliente);
        }

        DB::transaction(function () use ($cliente, $validador, $autorizo) {
            $cliente->fill($this->camposCliente($validador->validated()));

            $cambios = [];
            foreach ($cliente->getDirty() as $campo => $nuevo) {
                $cambios[$campo] = ['antes' => $cliente->getOriginal($campo), 'despues' => $nuevo];
            }

            $cliente->save();
            Bitacora::registrar('EDITAR', 'clientes', $cliente->idCliente, $cambios, $autorizo);
        });

        return redirect()
            ->route('administrador.clientes.index')
            ->with('alerta', ['success', "Se guardaron los cambios de «{$cliente->nombre()}»."]);
    }

    /* Ventana "Confirmar eliminación": se desactiva (no se borra, por sus ventas) */
    public function desactivar(Request $request, Cliente $cliente): RedirectResponse
    {
        if (!$cliente->activo) {
            return $this->noDisponible();
        }

        $autorizo = $this->autorizar($request);
        if (is_string($autorizo)) {
            return redirect()->route('administrador.clientes.index')->with('alerta', ['danger', $autorizo]);
        }

        DB::transaction(function () use ($cliente, $autorizo) {
            $cliente->activo = false;
            $cliente->save();
            Bitacora::registrar('DESACTIVAR', 'clientes', $cliente->idCliente, ['nombre' => $cliente->nombre()], $autorizo);
        });

        return redirect()
            ->route('administrador.clientes.index')
            ->with('alerta', ['success', "«{$cliente->nombre()}» se desactivó. Puedes reactivarlo en «Ver desactivados»."]);
    }

    public function reactivar(Request $request, Cliente $cliente): RedirectResponse
    {
        if ($cliente->activo) {
            return redirect()->route('administrador.clientes.index');
        }

        $autorizo = $this->autorizar($request);
        if (is_string($autorizo)) {
            return redirect()->route('administrador.clientes.index', ['ver' => 'desactivados'])->with('alerta', ['danger', $autorizo]);
        }

        DB::transaction(function () use ($cliente, $autorizo) {
            $cliente->activo = true;
            $cliente->save();
            Bitacora::registrar('REACTIVAR', 'clientes', $cliente->idCliente, ['nombre' => $cliente->nombre()], $autorizo);
        });

        return redirect()
            ->route('administrador.clientes.index')
            ->with('alerta', ['success', "«{$cliente->nombre()}» está activo otra vez."]);
    }

    // ---------------------------------------------------------------------

    private function validador(Request $request, ?Cliente $cliente): \Illuminate\Validation\Validator
    {
        // Antes de revisar: sin espacios de más y el RFC en mayúsculas
        $request->merge([
            'nombre' => preg_replace('/\s+/', ' ', trim((string) $request->input('nombre'))),
            'telefono' => trim((string) $request->input('telefono')),
            'correo' => mb_strtolower(trim((string) $request->input('correo'))),
            'rfc' => mb_strtoupper(str_replace([' ', '-'], '', trim((string) $request->input('rfc')))),
        ]);

        return Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'min:3', 'max:80'],
            // Números, espacios, guiones, paréntesis y +; con 10 a 15 dígitos
            'telefono' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9 ()-]+$/', function ($campo, $valor, $falla) {
                $digitos = strlen(preg_replace('/\D/', '', (string) $valor));
                if ($digitos < 10 || $digitos > 15) {
                    $falla('El teléfono debe tener de 10 a 15 dígitos.');
                }
            }],
            'correo' => ['nullable', 'email', 'max:120'],
            // RFC de persona (13) o de empresa (12); no se puede repetir entre clientes
            'rfc' => ['nullable', 'regex:/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/u',
                Rule::unique('clientes', 'RFC')->ignore($cliente?->idCliente, 'idCliente')],
            'direccion' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string', 'max:500'],
        ], MensajesValidacion::MENSAJES + [
            'telefono.regex' => 'El teléfono sólo puede llevar números, espacios, guiones y paréntesis.',
            'rfc.regex' => 'El RFC no tiene un formato válido (ej. LOPM900412H21).',
            'rfc.unique' => 'Ya hay otro cliente con ese RFC.',
        ], self::CAMPOS);
    }

    private function camposCliente(array $datos): array
    {
        $nulo = fn ($valor) => ($valor === null || trim((string) $valor) === '') ? null : trim((string) $valor);

        return [
            'nombres' => $datos['nombre'],
            'telefono' => $datos['telefono'],
            'correo' => $nulo($datos['correo'] ?? null),
            'RFC' => $nulo($datos['rfc'] ?? null),
            'direccion' => $nulo($datos['direccion'] ?? null),
            'notas' => $nulo($datos['notas'] ?? null),
        ];
    }

    /* Los campos se llaman "correo_autoriza" para no confundirse con el correo del cliente */
    private function autorizar(Request $request): \App\Models\Trabajador|string
    {
        return Autorizacion::verificar($request, 'correo_autoriza', 'contrasena_autoriza');
    }

    /* Regresa a la lista con los errores y vuelve a abrir la ventana que se estaba llenando */
    private function regresarConErrores(array $errores, string $modal, ?Cliente $cliente = null): RedirectResponse
    {
        return redirect()
            ->route('administrador.clientes.index')
            ->withErrors($errores)
            ->withInput(request()->except(['contrasena_autoriza']))
            ->with('modalCliente', $modal)
            ->with('modalClienteId', $cliente?->idCliente);
    }

    private function noDisponible(): RedirectResponse
    {
        return redirect()
            ->route('administrador.clientes.index')
            ->with('alerta', ['warning', 'Ese cliente está desactivado. Reactívalo para poder modificarlo.']);
    }
}
