{{--
    Paginación con el estilo del diseño: Anterior · 1 2 3 … 10 · Siguiente
    Uso: @include('partials.paginacion', ['paginas' => $clientes, 'nombre' => 'clientes'])
--}}
@php
    $actual = $paginas->currentPage();
    $ultima = $paginas->lastPage();
    // Se muestran: la primera, la última y dos alrededor de la actual
    $numeros = collect([1, $ultima, $actual - 1, $actual, $actual + 1])
        ->filter(fn ($n) => $n >= 1 && $n <= $ultima)
        ->unique()
        ->sort()
        ->values();
@endphp

<div class="table-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
    <span>
        @if ($paginas->total() > 0)
            Mostrando {{ $paginas->firstItem() }}–{{ $paginas->lastItem() }} de {{ $paginas->total() }} {{ $nombre }}
        @else
            Sin resultados
        @endif
    </span>

    @if ($ultima > 1)
        <nav class="d-flex align-items-center gap-2" aria-label="Páginas">
            <a href="{{ $paginas->previousPageUrl() ?? '#' }}" class="page-btn {{ $paginas->onFirstPage() ? 'is-disabled' : '' }}">Anterior</a>
            @foreach ($numeros as $i => $numero)
                @if ($i > 0 && $numero - $numeros[$i - 1] > 1)
                    <span class="px-1">…</span>
                @endif
                <a href="{{ $paginas->url($numero) }}" class="page-btn {{ $numero === $actual ? 'is-active' : '' }}" @if ($numero === $actual) aria-current="page" @endif>{{ $numero }}</a>
            @endforeach
            <a href="{{ $paginas->nextPageUrl() ?? '#' }}" class="page-btn {{ $paginas->hasMorePages() ? '' : 'is-disabled' }}">Siguiente</a>
        </nav>
    @endif
</div>
