<?php

namespace App\Http\Controllers\Gerente;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/*
    Pantalla principal del rol GERENTE.
    Por ahora sólo da la bienvenida; aquí se irán agregando sus módulos.
*/
class InicioController extends Controller
{
    public function index(): View
    {
        return view('gerente.inicio');
    }
}
