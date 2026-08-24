@if ($paginator->hasPages())
    <nav class="rm-pagination" role="navigation" aria-label="Paginación">
        <p class="rm-pagination-summary">
            Mostrando {{ $paginator->firstItem() }} a {{ $paginator->lastItem() }} de {{ $paginator->total() }} registros
        </p>

        <div class="rm-pagination-pages">
            @if ($paginator->onFirstPage())
                <span class="rm-pagination-button is-disabled" aria-disabled="true">
                    <span aria-hidden="true">&lsaquo;</span>
                </span>
            @else
                <button class="rm-pagination-button" type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" rel="prev" aria-label="Anterior">
                    <span aria-hidden="true">&lsaquo;</span>
                </button>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="rm-pagination-button is-ellipsis" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="rm-pagination-button is-active" aria-current="page">{{ $page }}</span>
                        @else
                            <button class="rm-pagination-button" type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" aria-label="Ir a página {{ $page }}">
                                {{ $page }}
                            </button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <button class="rm-pagination-button" type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" rel="next" aria-label="Siguiente">
                    <span aria-hidden="true">&rsaquo;</span>
                </button>
            @else
                <span class="rm-pagination-button is-disabled" aria-disabled="true">
                    <span aria-hidden="true">&rsaquo;</span>
                </span>
            @endif
        </div>
    </nav>
@endif
