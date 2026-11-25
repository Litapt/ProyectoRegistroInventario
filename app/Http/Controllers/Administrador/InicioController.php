<?php

namespace App\Http\Controllers\Administrador;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/*
    Pantalla principal del rol ADMINISTRADOR.
    Por ahora sólo da la bienvenida; aquí se irán agregando sus módulos.
*/
class InicioController extends Controller
{
    public function index(): View
    {
        return view('administrador.inicio');
    }
}
