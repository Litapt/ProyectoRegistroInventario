<?php

namespace App\Http\Controllers\Administrador;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Rol;
use App\Models\Trabajador;
use App\Support\Autorizacion;
use App\Support\MensajesValidacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/*
    Usuarios del administrador (Figma Page 2: "Administrador · Usuarios" y sus ventanas).
    Un usuario es un trabajador de la tabla trabajadores con su rol.

    Activar / desactivar NO se hace al editar: sólo con "Eliminar" y "Reactivar",
    que piden la autorización de un gerente o administrador.

    Reglas para no quedarse sin acceso:
      - nadie puede eliminarse ni cambiarse el rol a sí mismo;
      - siempre debe quedar al menos un ADMINISTRADOR activo.
*/
class UsuarioController extends Controller
{
    private const POR_PAGINA = 8;

    private const CAMPOS = [
        'nombres' => 'nombre(s)',
        'apellidoPaterno' => 'apellido paterno',
        'apellidoMaterno' => 'apellido materno',
        'rfc' => 'RFC',
        'correo' => 'correo',
        'telefono' => 'teléfono',
        'rol' => 'rol',
        'contrasena' => 'contraseña',
        'contrasena_confirmation' => 'confirmar contraseña',
    ];

    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $verInactivos = $request->query('ver') === 'inactivos';

        $usuarios = Trabajador::with('rol')
            ->where('activo', !$verInactivos)
            ->when($buscar !== '', fn ($q) => $q->where(function ($q) use ($buscar) {
                $q->where('nombres', 'like', "%{$buscar}%")
                    ->orWhere('apellidoPaterno', 'like', "%{$buscar}%")
                    ->orWhere('apellidoMaterno', 'like', "%{$buscar}%")
                    ->orWhere('correoElectronico', 'like', "%{$buscar}%")
                    ->orWhere('RFC', 'like', "%{$buscar}%");
            }))
            ->orderBy('nombres')->orderBy('apellidoPaterno')
            ->paginate(self::POR_PAGINA)->withQueryString();

        return view('administrador.usuarios.index', [
            'usuarios' => $usuarios,
            'buscar' => $buscar,
            'roles' => Rol::where('activo', true)->orderBy('idRol')->get(),
            'verInactivos' => $verInactivos,
            'totalActivos' => Trabajador::where('activo', true)->count(),
            'totalInactivos' => Trabajador::where('activo', false)->count(),
            'usuarioReabierto' => session('modalUsuario') === 'editar' ? Trabajador::find(session('modalUsuarioId')) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validador = $this->validador($request, null);

        if ($validador->fails()) {
            return $this->regresarConErrores($validador->errors()->toArray(), 'registrar');
        }

        $datos = $validador->validated();

        $usuario = DB::transaction(function () use ($datos) {
            $usuario = new Trabajador($this->camposUsuario($datos));
            $usuario->hashContrasena = $datos['contrasena']; // se cifra sola (cast "hashed")
            $usuario->fk_idRol = (int) $datos['rol'];
            $usuario->activo = true;
            $usuario->save();

            Bitacora::registrar('CREAR', 'trabajadores', $usuario->idTrabajador, [
                'nombre' => $usuario->nombreCompleto(),
                'rol' => Rol::find($usuario->fk_idRol)?->nombre,
            ]);

            return $usuario;
        });

        return redirect()
            ->to(\App\Support\Ruta::a('usuarios.index'))
            ->with('alerta', ['success', "Usuario «{$usuario->nombreCompleto()}» registrado. Ya puede iniciar sesión con su correo."]);
    }

    public function update(Request $request, Trabajador $usuario): RedirectResponse
    {
        if (!$usuario->activo) {
            return redirect()->to(\App\Support\Ruta::a('usuarios.index', ['ver' => 'inactivos']))
                ->with('alerta', ['warning', 'Ese usuario está inactivo. Reactívalo para poder editarlo.']);
        }

        $validador = $this->validador($request, $usuario);

        if ($validador->fails()) {
            return $this->regresarConErrores($validador->errors()->toArray(), 'editar', $usuario);
        }

        $datos = $validador->validated();
        $nuevoRol = (int) $datos['rol'];
        $esUnoMismo = (int) $usuario->idTrabajador === (int) auth()->id();

        if ($esUnoMismo && $nuevoRol !== (int) $usuario->fk_idRol) {
            return $this->regresarConErrores(['rol' => ['No puedes cambiar tu propio rol. Pídeselo a otro administrador.']], 'editar', $usuario);
        }

        if ($this->dejariaSinAdministradores($usuario, $nuevoRol, true)) {
            return $this->regresarConErrores(['rol' => ['Debe quedar al menos un administrador activo.']], 'editar', $usuario);
        }

        $cambioContrasena = ($datos['contrasena'] ?? '') !== '';

        DB::transaction(function () use ($usuario, $datos, $nuevoRol, $cambioContrasena) {
            $usuario->fill($this->camposUsuario($datos));
            $usuario->fk_idRol = $nuevoRol;
            if ($cambioContrasena) {
                $usuario->hashContrasena = $datos['contrasena'];
            }

            $cambios = [];
            foreach ($usuario->getDirty() as $campo => $nuevo) {
                // La contraseña nunca se guarda en la bitácora, sólo que cambió
                $cambios[$campo] = $campo === 'hashContrasena'
                    ? 'cambió'
                    : ['antes' => $usuario->getOriginal($campo), 'despues' => $nuevo];
            }

            $usuario->save();
            Bitacora::registrar('EDITAR', 'trabajadores', $usuario->idTrabajador, $cambios);
        });

        // Si cambió la contraseña, se cierran sus sesiones abiertas
        if ($cambioContrasena) {
            $this->cerrarSesiones($usuario);
        }

        return redirect()
            ->to(\App\Support\Ruta::a('usuarios.index'))
            ->with('alerta', ['success', "Se guardaron los cambios de «{$usuario->nombreCompleto()}»."]);
    }

    /* Ventana "Eliminar usuario con autorización": se desactiva (no se borra, por su historial) */
    public function desactivar(Request $request, Trabajador $usuario): RedirectResponse
    {
        if (!$usuario->activo) {
            return redirect()->to(\App\Support\Ruta::a('usuarios.index'))
                ->with('alerta', ['warning', 'Ese usuario ya está inactivo.']);
        }

        if ((int) $usuario->idTrabajador === (int) auth()->id()) {
            return redirect()->to(\App\Support\Ruta::a('usuarios.index'))
                ->with('alerta', ['warning', 'No puedes eliminar tu propio usuario.']);
        }

        if ($this->dejariaSinAdministradores($usuario, (int) $usuario->fk_idRol, false)) {
            return redirect()->to(\App\Support\Ruta::a('usuarios.index'))
                ->with('alerta', ['warning', 'Debe quedar al menos un administrador activo.']);
        }

        $autorizo = Autorizacion::verificar($request, 'correo_autoriza', 'contrasena_autoriza');
        if (is_string($autorizo)) {
            return redirect()->to(\App\Support\Ruta::a('usuarios.index'))->with('alerta', ['danger', $autorizo]);
        }

        DB::transaction(function () use ($usuario, $autorizo) {
            $usuario->activo = false;
            $usuario->save();
            Bitacora::registrar('DESACTIVAR', 'trabajadores', $usuario->idTrabajador, ['nombre' => $usuario->nombreCompleto()], $autorizo);
        });

        $this->cerrarSesiones($usuario);

        return redirect()
            ->to(\App\Support\Ruta::a('usuarios.index'))
            ->with('alerta', ['success', "«{$usuario->nombreCompleto()}» quedó inactivo: ya no puede iniciar sesión. Lo puedes reactivar en «Ver inactivos»."]);
    }

    /* Botón "Reactivar" de la lista de inactivos (con autorización) */
    public function reactivar(Request $request, Trabajador $usuario): RedirectResponse
    {
        if ($usuario->activo) {
            return redirect()->to(\App\Support\Ruta::a('usuarios.index'));
        }

        $autorizo = Autorizacion::verificar($request, 'correo_autoriza', 'contrasena_autoriza');
        if (is_string($autorizo)) {
            return redirect()->to(\App\Support\Ruta::a('usuarios.index', ['ver' => 'inactivos']))->with('alerta', ['danger', $autorizo]);
        }

        DB::transaction(function () use ($usuario, $autorizo) {
            $usuario->activo = true;
            $usuario->save();
            Bitacora::registrar('REACTIVAR', 'trabajadores', $usuario->idTrabajador, ['nombre' => $usuario->nombreCompleto()], $autorizo);
        });

        return redirect()
            ->to(\App\Support\Ruta::a('usuarios.index'))
            ->with('alerta', ['success', "«{$usuario->nombreCompleto()}» está activo otra vez y ya puede iniciar sesión."]);
    }

    // ---------------------------------------------------------------------

    private function validador(Request $request, ?Trabajador $usuario): \Illuminate\Validation\Validator
    {
        $limpiar = fn ($campo) => preg_replace('/\s+/', ' ', trim((string) $request->input($campo)));

        $request->merge([
            'nombres' => $limpiar('nombres'),
            'apellidoPaterno' => $limpiar('apellidoPaterno'),
            'apellidoMaterno' => $limpiar('apellidoMaterno'),
            'rfc' => mb_strtoupper(str_replace([' ', '-'], '', trim((string) $request->input('rfc')))),
            'correo' => mb_strtolower(trim((string) $request->input('correo'))),
            'telefono' => trim((string) $request->input('telefono')),
        ]);

        $contrasena = Password::min(8)->letters()->numbers();

        return Validator::make($request->all(), [
            'nombres' => ['required', 'string', 'max:80'],
            'apellidoPaterno' => ['required', 'string', 'max:60'],
            'apellidoMaterno' => ['nullable', 'string', 'max:60'],
            'rfc' => ['required', 'regex:/^[A-ZÑ&]{4}[0-9]{6}[A-Z0-9]{3}$/u',
                Rule::unique('trabajadores', 'RFC')->ignore($usuario?->idTrabajador, 'idTrabajador')],
            'correo' => ['required', 'email', 'max:120',
                Rule::unique('trabajadores', 'correoElectronico')->ignore($usuario?->idTrabajador, 'idTrabajador')],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9 ()-]{10,20}$/'],
            'rol' => ['required', 'integer', Rule::exists('roles', 'idRol')->where('activo', true)],
            // Al registrar es obligatoria; al editar sólo si se quiere cambiar
            'contrasena' => $usuario === null
                ? ['required', 'confirmed', $contrasena]
                : ['nullable', 'confirmed', $contrasena],
        ], MensajesValidacion::MENSAJES + [
            'rfc.regex' => 'El RFC de una persona tiene 13 caracteres (ej. RAML900101AB1).',
            'rfc.unique' => 'Ya hay otro usuario con ese RFC.',
            'correo.unique' => 'Ya hay otro usuario con ese correo.',
            'telefono.regex' => 'El teléfono debe tener al menos 10 dígitos (números, espacios o guiones).',
            'contrasena.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'contrasena.letters' => 'La contraseña debe llevar al menos una letra.',
            'contrasena.numbers' => 'La contraseña debe llevar al menos un número.',
        ], self::CAMPOS);
    }

    private function camposUsuario(array $datos): array
    {
        return [
            'nombres' => $datos['nombres'],
            'apellidoPaterno' => $datos['apellidoPaterno'],
            'apellidoMaterno' => ($datos['apellidoMaterno'] ?? '') !== '' ? $datos['apellidoMaterno'] : null,
            'RFC' => $datos['rfc'],
            'correoElectronico' => $datos['correo'],
            'telefono' => ($datos['telefono'] ?? '') !== '' ? $datos['telefono'] : null,
        ];
    }

    /* ¿El cambio dejaría al sistema sin ningún administrador activo? */
    private function dejariaSinAdministradores(Trabajador $usuario, int $nuevoRol, bool $nuevoActivo): bool
    {
        $idAdministrador = Rol::idDe(Trabajador::ROL_ADMINISTRADOR);
        $eraAdministradorActivo = (int) $usuario->fk_idRol === (int) $idAdministrador && $usuario->activo;
        $seguiraSiendolo = $nuevoRol === (int) $idAdministrador && $nuevoActivo;

        if (!$eraAdministradorActivo || $seguiraSiendolo) {
            return false;
        }

        return Trabajador::where('fk_idRol', $idAdministrador)->where('activo', true)
            ->whereKeyNot($usuario->idTrabajador)->doesntExist();
    }

    /* Saca al usuario de todas sus sesiones abiertas (sesiones guardadas en la base de datos) */
    private function cerrarSesiones(Trabajador $usuario): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $usuario->idTrabajador)->delete();
        }
    }

    private function regresarConErrores(array $errores, string $modal, ?Trabajador $usuario = null): RedirectResponse
    {
        return redirect()
            ->to(\App\Support\Ruta::a('usuarios.index'))
            ->withErrors($errores)
            ->withInput(request()->except(['contrasena', 'contrasena_confirmation']))
            ->with('modalUsuario', $modal)
            ->with('modalUsuarioId', $usuario?->idTrabajador);
    }
}
