{{--
    Usuarios del administrador (Figma Page 2: "Administrador · Usuarios").
    Todo lo manda Administrador\UsuarioController@index.
    Ventanas: modalUsuario.blade.php (registrar / editar) y partials/modalAutorizacion (eliminar).
--}}
@extends('layouts.panel')

@section('titulo', 'Usuarios')
@section('menu', 'usuarios')

@section('pagina')
    @php
        $yo = auth()->user();
        $claseRol = [
            'ADMINISTRADOR' => 'badge-primary',
            'GERENTE' => 'badge-warning',
            'CAJERO' => 'badge-ok',
            'VENDEDOR' => 'badge-muted',
            'ALMACENISTA' => 'badge-muted',
        ];
    @endphp

    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <h1 class="page-heading m-0">Usuarios</h1>
            <p class="page-description m-0 mt-1">Administra a las personas que pueden entrar al sistema y su rol.</p>
        </div>
        @if (!$verInactivos)
            <button type="button" class="btn-app btn-app-primary" data-bs-toggle="modal" data-bs-target="#modalUsuario" data-modo="registrar">
                Registrar usuario
            </button>
        @endif
    </div>

    <section class="panel">
        <form method="get" action="{{ \App\Support\Ruta::a('usuarios.index') }}" class="panel-body d-flex flex-column gap-3">
            @if ($verInactivos)
                <input type="hidden" name="ver" value="inactivos">
            @endif
            <div>
                <h2 class="panel-title m-0">{{ $verInactivos ? 'Usuarios inactivos' : 'Consultar' }}</h2>
                <p class="panel-text m-0 mt-1">
                    {{ $verInactivos ? 'No pueden iniciar sesión. Puedes reactivarlos con autorización.' : 'Encuentra a un usuario por nombre, correo o RFC.' }}
                </p>
            </div>
            <div>
                <label for="buscar" class="field-label-app">Buscar usuario</label>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <input type="search" id="buscar" name="buscar" value="{{ $buscar }}" maxlength="100"
                           class="form-control input-app filtro-buscar" placeholder="Nombre o correo">
                    <button type="submit" class="btn-app btn-app-primary">Buscar</button>
                    @if ($buscar !== '')
                        <a href="{{ \App\Support\Ruta::a('usuarios.index', $verInactivos ? ['ver' => 'inactivos'] : []) }}" class="btn-text">Limpiar búsqueda</a>
                    @endif
                    <span class="panel-text ms-auto">
                        {{ number_format($totalActivos) }} {{ $totalActivos === 1 ? 'usuario activo' : 'usuarios activos' }} ·
                        @if ($verInactivos)
                            <a href="{{ \App\Support\Ruta::a('usuarios.index') }}" class="btn-text">Ver activos</a>
                        @else
                            <a href="{{ \App\Support\Ruta::a('usuarios.index', ['ver' => 'inactivos']) }}" class="btn-text">Ver inactivos ({{ $totalInactivos }})</a>
                        @endif
                    </span>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Último acceso</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $usuario)
                        @php
                            $esYo = (int) $usuario->idTrabajador === (int) $yo->idTrabajador;
                            $nombreRol = $usuario->nombreRol();
                        @endphp
                        <tr>
                            <td>
                                <div class="cell-main">
                                    {{ $usuario->nombreCompleto() }}
                                    @if ($esYo)
                                        <span class="field-help">(tú)</span>
                                    @endif
                                </div>
                                <div class="cell-sub">{{ $usuario->codigo() }}</div>
                            </td>
                            <td class="text-muted">{{ $usuario->correoElectronico }}</td>
                            <td>
                                <span class="badge-estado {{ $claseRol[$nombreRol] ?? 'badge-muted' }}">{{ $usuario->rolTexto() ?: 'Sin rol' }}</span>
                            </td>
                            <td>
                                @if ($usuario->activo)
                                    <span class="badge-estado badge-ok">Activo</span>
                                @else
                                    <span class="badge-estado badge-danger">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-muted text-nowrap">
                                @if ($usuario->fechaUltimoAcceso)
                                    <div>{{ $usuario->fechaUltimoAcceso->translatedFormat('d M Y') }}</div>
                                    <div class="cell-sub">{{ $usuario->fechaUltimoAcceso->format('h:i a') }}</div>
                                @else
                                    Nunca
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-2">
                                    @if ($verInactivos)
                                        <button type="button" class="btn-app btn-app-outline btn-app-sm"
                                                data-bs-toggle="modal" data-bs-target="#modalAutorizacion"
                                                data-url="{{ \App\Support\Ruta::a('usuarios.reactivar', $usuario) }}"
                                                data-titulo="Reactivar usuario"
                                                data-texto="El usuario podrá volver a iniciar sesión con su correo y su contraseña de siempre."
                                                data-nombre="{{ $usuario->nombreCompleto() }}"
                                                data-detalle="{{ $usuario->codigo() }} · {{ $usuario->correoElectronico }} · {{ $usuario->rolTexto() }}"
                                                data-boton="Confirmar reactivación" data-clase="btn-app-primary">
                                            Reactivar
                                        </button>
                                    @else
                                        <button type="button" class="btn-app btn-app-outline btn-app-sm"
                                                data-bs-toggle="modal" data-bs-target="#modalUsuario" data-modo="editar"
                                                data-url="{{ \App\Support\Ruta::a('usuarios.update', $usuario) }}"
                                                data-usuario="{{ json_encode($usuario->datosParaEditar()) }}"
                                                data-yo="{{ $esYo ? '1' : '' }}">
                                            Editar
                                        </button>
                                        @if ($usuario->activo && !$esYo)
                                            <button type="button" class="btn-app btn-app-danger btn-app-sm"
                                                    data-bs-toggle="modal" data-bs-target="#modalAutorizacion"
                                                    data-url="{{ \App\Support\Ruta::a('usuarios.desactivar', $usuario) }}"
                                                    data-titulo="Eliminar usuario"
                                                    data-texto="El usuario quedará inactivo: ya no podrá iniciar sesión, pero sus ventas y movimientos se conservan. Lo puedes reactivar en «Ver inactivos»."
                                                    data-nombre="{{ $usuario->nombreCompleto() }}"
                                                    data-detalle="{{ $usuario->codigo() }} · {{ $usuario->correoElectronico }} · {{ $usuario->rolTexto() }}"
                                                    data-boton="Confirmar eliminación" data-clase="btn-app-danger">
                                                Eliminar
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                @if ($buscar !== '')
                                    Ningún usuario coincide con «{{ $buscar }}».
                                @elseif ($verInactivos)
                                    No hay usuarios inactivos.
                                @else
                                    Todavía no hay usuarios activos.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.paginacion', ['paginas' => $usuarios, 'nombre' => 'usuarios'])
    </section>
@endsection

@push('modales')
    @include('administrador.usuarios.modalUsuario')
    @include('partials.modalAutorizacion')
@endpush

@push('scripts')
    <script src="{{ asset('javascript/administrador/usuarios.js') }}"></script>
@endpush
