<?php

namespace App\Http\Controllers\Administrador;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Support\Tablero;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/*
    Inicio del ADMINISTRADOR (Figma Page 2: "Administrador · Inicio").
    Los números salen de App\Support\Tablero. Se puede ver otro día con el calendario.
*/
class InicioController extends Controller
{
    public function index(Request $request): View
    {
        $dia = now();
        $texto = (string) $request->query('dia', '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $texto)) {
            try {
                $elegido = Carbon::createFromFormat('!Y-m-d', $texto);
                $dia = $elegido->isFuture() ? now() : $elegido;   // no hay ventas en el futuro
            } catch (\Throwable) {
                // fecha inválida: se queda hoy
            }
        }

        $buscar = mb_substr(trim((string) $request->query('buscar', '')), 0, 60);

        $productos = Producto::with(['categoria', 'compatibilidades.modelo.marca'])
            ->requierenAtencion()
            ->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('nombre', 'like', "%{$buscar}%")->orWhere('codigoInterno', 'like', "%{$buscar}%")))
            ->orderByRaw('CASE WHEN existencia = 0 THEN 0 ELSE 1 END')->orderBy('existencia')->orderBy('nombre')
            ->paginate(5)->withQueryString();

        return view('administrador.inicio', Tablero::datos($dia) + [
            'productos' => $productos,
            'buscar' => $buscar,
        ]);
    }
}
