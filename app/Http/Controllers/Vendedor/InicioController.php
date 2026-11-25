<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/*
    Pantalla principal del rol VENDEDOR.
    Por ahora sólo da la bienvenida; aquí se irán agregando sus módulos.
*/
class InicioController extends Controller
{
    public function index(): View
    {
        return view('vendedor.inicio');
    }
}
