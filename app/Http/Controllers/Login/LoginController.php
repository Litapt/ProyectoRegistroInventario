<?php

namespace App\Http\Controllers\Login;

use App\Http\Controllers\Controller;
use App\Models\Trabajador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/*
    Inicio y cierre de sesión. Es el MISMO para los 5 roles:
    al entrar, cada quien va a su pantalla (Trabajador::rutaInicio()).
    El formulario está en resources/views/index.blade.php
*/
class LoginController extends Controller
{
    // Intentos fallidos permitidos y cuántos segundos queda bloqueado el correo
    private const MAX_INTENTOS = 5;
    private const SEGUNDOS_BLOQUEO = 300;

    /* Muestra el formulario de inicio de sesión */
    public function index(): View
    {
        return view('index');
    }

    /* Revisa el correo y la contraseña y abre la sesión */
    public function login(Request $request): RedirectResponse
    {
        $correo = trim((string) $request->input('correoElectronico', ''));
        $contrasena = (string) $request->input('contrasena', '');

        if ($correo === '' || $contrasena === '') {
            return $this->regresarError($request, 'Escribe tu correo electrónico y tu contraseña.');
        }

        // Frena a quien intenta adivinar contraseñas (por correo y dirección IP)
        $llaveIntentos = mb_strtolower($correo) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($llaveIntentos, self::MAX_INTENTOS)) {
            $minutos = (int) ceil(RateLimiter::availableIn($llaveIntentos) / 60);
            return $this->regresarError($request, "Demasiados intentos. Espera {$minutos} minuto(s) e inténtalo de nuevo.", 'warning');
        }

        $trabajador = Trabajador::with('rol')->where('correoElectronico', $correo)->first();

        // Mismo mensaje si el correo no existe o la contraseña está mal: así no se sabe qué correos existen
        if (!$trabajador || !Hash::check($contrasena, $trabajador->hashContrasena)) {
            RateLimiter::hit($llaveIntentos, self::SEGUNDOS_BLOQUEO);
            return $this->regresarError($request, 'Usuario o contraseña incorrectos.');
        }

        if (!$trabajador->activo) {
            return $this->regresarError($request, 'El usuario no está activo. Habla con el administrador.', 'warning');
        }

        $rutaInicio = $trabajador->rutaInicio();

        if ($rutaInicio === null) {
            return $this->regresarError($request, 'Tu usuario no tiene un rol válido. Habla con el administrador.', 'warning');
        }

        RateLimiter::clear($llaveIntentos);

        // Si la contraseña se cifró con un método viejo, se vuelve a cifrar con el actual
        if (Hash::needsRehash($trabajador->hashContrasena)) {
            $trabajador->hashContrasena = $contrasena;
        }

        $trabajador->fechaUltimoAcceso = now();
        $trabajador->save();

        Auth::login($trabajador, $request->boolean('recordarme'));

        // Igual que session_regenerate_id(true): evita que alguien reutilice una sesión anterior
        $request->session()->regenerate();

        return redirect()->route($rutaInicio);
    }

    /* Cierra la sesión y regresa al inicio de sesión */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /*
        Regresa al formulario con una alerta de Bootstrap.
        $tipo: danger (rojo), warning (amarillo) o success (verde)
    */
    private function regresarError(Request $request, string $mensaje, string $tipo = 'danger'): RedirectResponse
    {
        return redirect()
            ->route('login')
            ->withInput($request->only('correoElectronico'))
            ->with('alerta', [$tipo, $mensaje]);
    }
}
