<?php

namespace App\Http\Controllers\Perfil;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Trabajador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/*
    "Mi perfil" (menú del usuario, arriba a la derecha). Igual para TODOS los roles,
    como el inicio de sesión: cada quien ve y edita sólo sus propios datos.
    El rol y el RFC no se cambian aquí: eso lo hace el administrador en Usuarios.
*/
class PerfilController extends Controller
{
    private const CARPETA_FOTOS = 'imagenes/perfiles';

    public function index(Request $request): View
    {
        $yo = $request->user();

        return view('perfil.index', [
            'yo' => $yo,
            // Administrador y gerente tienen menú lateral; los demás, sólo barra de arriba
            'layout' => in_array($yo->nombreRol(), [Trabajador::ROL_ADMINISTRADOR, Trabajador::ROL_GERENTE], true)
                ? 'layouts.panel' : 'layouts.barraSuperior',
        ]);
    }

    /* Mis datos: nombre, apellidos, teléfono y correo (cambiar el correo pide la contraseña) */
    public function datos(Request $request): RedirectResponse
    {
        $yo = $request->user();
        $limpiar = fn ($campo) => preg_replace('/\s+/u', ' ', trim((string) $request->input($campo)));
        $request->merge([
            'nombres' => $limpiar('nombres'),
            'apellidoPaterno' => $limpiar('apellidoPaterno'),
            'apellidoMaterno' => $limpiar('apellidoMaterno'),
            'correo' => mb_strtolower(trim((string) $request->input('correo'))),
            'telefono' => trim((string) $request->input('telefono')),
        ]);
        $cambiaCorreo = $request->input('correo') !== mb_strtolower((string) $yo->correoElectronico);

        $datos = $request->validateWithBag('datos', [
            'nombres' => ['required', 'string', 'max:80'],
            'apellidoPaterno' => ['required', 'string', 'max:60'],
            'apellidoMaterno' => ['nullable', 'string', 'max:60'],
            'correo' => ['required', 'email', 'max:120',
                Rule::unique('trabajadores', 'correoElectronico')->ignore($yo->idTrabajador, 'idTrabajador')],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9 ()-]{10,20}$/'],
            'contrasenaConfirmar' => $cambiaCorreo ? ['required', 'current_password'] : ['nullable'],
        ], [
            'nombres.required' => 'Escribe tu nombre.',
            'apellidoPaterno.required' => 'Escribe tu apellido paterno.',
            'correo.required' => 'Escribe tu correo.',
            'correo.email' => 'Escribe un correo válido.',
            'correo.unique' => 'Ese correo ya lo usa otra persona.',
            'telefono.regex' => 'El teléfono debe tener al menos 10 dígitos (números, espacios o guiones).',
            'contrasenaConfirmar.required' => 'Para cambiar tu correo escribe tu contraseña actual.',
            'contrasenaConfirmar.current_password' => 'Tu contraseña actual no es correcta.',
        ]);

        $yo->nombres = $datos['nombres'];
        $yo->apellidoPaterno = $datos['apellidoPaterno'];
        $yo->apellidoMaterno = ($datos['apellidoMaterno'] ?? '') !== '' ? $datos['apellidoMaterno'] : null;
        $yo->correoElectronico = $datos['correo'];
        $yo->telefono = ($datos['telefono'] ?? '') !== '' ? $datos['telefono'] : null;

        $cambios = [];
        foreach ($yo->getDirty() as $campo => $nuevo) {
            $cambios[$campo] = ['antes' => $yo->getOriginal($campo), 'despues' => $nuevo];
        }
        $yo->save();

        if ($cambios) {
            Bitacora::registrar('EDITAR', 'trabajadores', $yo->idTrabajador, ['perfil' => true] + $cambios);
        }

        return redirect()->route('perfil')->with('alerta', ['success', $cambios
            ? ($cambiaCorreo ? 'Se guardaron tus datos. Desde ahora inicias sesión con ' . $yo->correoElectronico . '.' : 'Se guardaron tus datos.')
            : 'No cambiaste nada.']);
    }

    /* Foto de perfil: PNG o JPG de hasta 2 MB (se revisa que de verdad sea imagen) */
    public function foto(Request $request): RedirectResponse
    {
        $request->validateWithBag('foto', [
            'foto' => ['required', 'file', 'mimes:png,jpg,jpeg', 'mimetypes:image/png,image/jpeg', 'max:2048', 'dimensions:min_width=64,min_height=64'],
        ], [
            'foto.required' => 'Elige una imagen.',
            'foto.mimes' => 'La foto debe ser PNG o JPG.',
            'foto.mimetypes' => 'La foto debe ser PNG o JPG.',
            'foto.max' => 'La foto puede pesar hasta 2 MB.',
            'foto.dimensions' => 'La foto es muy pequeña (mínimo 64 × 64 píxeles).',
        ]);

        $yo = $request->user();
        $this->borrarFoto($yo);

        $archivo = $request->file('foto');
        $nombre = 'foto-' . Str::uuid() . '.' . ($archivo->guessExtension() === 'png' ? 'png' : 'jpg');
        File::ensureDirectoryExists(public_path(self::CARPETA_FOTOS));
        $archivo->move(public_path(self::CARPETA_FOTOS), $nombre);

        $yo->foto = self::CARPETA_FOTOS . '/' . $nombre;
        $yo->save();

        return redirect()->route('perfil')->with('alerta', ['success', 'Se actualizó tu foto de perfil.']);
    }

    public function quitarFoto(Request $request): RedirectResponse
    {
        $yo = $request->user();
        $this->borrarFoto($yo);
        $yo->foto = null;
        $yo->save();

        return redirect()->route('perfil')->with('alerta', ['success', 'Se quitó tu foto: se muestran tus iniciales.']);
    }

    /* Sólo se borra si está dentro de la carpeta de fotos (nunca otro archivo) */
    private function borrarFoto($yo): void
    {
        $actual = $yo->foto;
        if ($actual && str_starts_with($actual, self::CARPETA_FOTOS . '/') && !str_contains($actual, '..')) {
            File::delete(public_path($actual));
        }
    }

    /* Cambiar mi contraseña: se cierran mis otras sesiones abiertas */
    public function contrasena(Request $request): RedirectResponse
    {
        $request->validateWithBag('contrasena', [
            'contrasenaActual' => ['required', 'current_password'],
            'contrasenaNueva' => ['required', 'confirmed', 'different:contrasenaActual', Password::min(8)->letters()->numbers()],
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

        $yo = $request->user();
        $yo->hashContrasena = $request->input('contrasenaNueva');   // se cifra sola
        $yo->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $yo->idTrabajador)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        Bitacora::registrar('EDITAR', 'trabajadores', $yo->idTrabajador, ['perfil' => true, 'hashContrasena' => 'cambió']);

        return redirect()->route('perfil')->with('alerta', ['success', 'Tu contraseña cambió. Si tenías la sesión abierta en otra computadora, ahí se cerró.']);
    }
}
