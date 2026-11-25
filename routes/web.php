<?php

use App\Http\Controllers\Administrador\InicioController as InicioAdministrador;
use App\Http\Controllers\Almacenista\InicioController as InicioAlmacenista;
use App\Http\Controllers\Cajero\InicioController as InicioCajero;
use App\Http\Controllers\Gerente\InicioController as InicioGerente;
use App\Http\Controllers\Login\LoginController;
use App\Http\Controllers\Vendedor\InicioController as InicioVendedor;
use App\Models\Trabajador;
use Illuminate\Support\Facades\Route;

/*
    Rutas: cada dirección de la aplicación y qué código la atiende.
    Para ver la lista completa: php artisan route:list

    Cada rol tiene su propia carpeta (/administrador, /gerente...),
    sus propios controladores (app/Http/Controllers/<Rol>) y sus propias vistas
    (resources/views/<rol>). Un rol no entra a la carpeta de otro.
*/

// Inicio de sesión: el mismo para todos los roles
Route::middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login.entrar');
});

// Cerrar sesión: es POST (formulario con @csrf) para que otra página no pueda cerrarla a escondidas
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ---------- Administrador ----------
Route::middleware('rol:' . Trabajador::ROL_ADMINISTRADOR)
    ->prefix('administrador')->name('administrador.')
    ->group(function () {
        Route::get('/', [InicioAdministrador::class, 'index'])->name('inicio');
    });

// ---------- Gerente ----------
Route::middleware('rol:' . Trabajador::ROL_GERENTE)
    ->prefix('gerente')->name('gerente.')
    ->group(function () {
        Route::get('/', [InicioGerente::class, 'index'])->name('inicio');
    });

// ---------- Cajero ----------
Route::middleware('rol:' . Trabajador::ROL_CAJERO)
    ->prefix('cajero')->name('cajero.')
    ->group(function () {
        Route::get('/', [InicioCajero::class, 'index'])->name('inicio');
    });

// ---------- Vendedor ----------
Route::middleware('rol:' . Trabajador::ROL_VENDEDOR)
    ->prefix('vendedor')->name('vendedor.')
    ->group(function () {
        Route::get('/', [InicioVendedor::class, 'index'])->name('inicio');
    });

// ---------- Almacenista ----------
Route::middleware('rol:' . Trabajador::ROL_ALMACENISTA)
    ->prefix('almacenista')->name('almacenista.')
    ->group(function () {
        Route::get('/', [InicioAlmacenista::class, 'index'])->name('inicio');
    });
