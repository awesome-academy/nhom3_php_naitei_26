@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Phân trang" class="flex items-center justify-between sm:justify-end">
        {{-- Mobile compact navigation --}}
        <div class="flex items-center justify-between w-full gap-2 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center gap-1 px-3.5 py-2 text-xs font-medium text-gray-400 bg-gray-50 border border-border rounded-lg cursor-not-allowed select-none">
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    Trước
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center gap-1 px-3.5 py-2 text-xs font-medium text-gray-700 bg-white border border-border rounded-lg shadow-sm hover:bg-gray-50 hover:text-primary transition-colors focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    Trước
                </a>
            @endif

            <span class="text-xs font-medium text-gray-600">
                Trang {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center gap-1 px-3.5 py-2 text-xs font-medium text-gray-700 bg-white border border-border rounded-lg shadow-sm hover:bg-gray-50 hover:text-primary transition-colors focus:outline-none focus:ring-2 focus:ring-primary/20">
                    Sau
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                </a>
            @else
                <span class="inline-flex items-center gap-1 px-3.5 py-2 text-xs font-medium text-gray-400 bg-gray-50 border border-border rounded-lg cursor-not-allowed select-none">
                    Sau
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                </span>
            @endif
        </div>

        {{-- Desktop standard navigation --}}
        <div class="hidden sm:flex sm:items-center sm:gap-1.5">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Trang trước" class="inline-flex items-center justify-center size-9 text-gray-300 bg-gray-50 border border-border rounded-lg cursor-not-allowed select-none">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Trang trước" class="inline-flex items-center justify-center size-9 text-gray-600 bg-white border border-border rounded-lg shadow-sm hover:bg-gray-50 hover:text-primary hover:border-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span aria-disabled="true" class="inline-flex items-center justify-center size-9 text-sm font-medium text-gray-400 select-none">
                        {{ $element }}
                    </span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex items-center justify-center min-w-9 h-9 px-3 text-sm font-bold text-white bg-primary border border-primary rounded-lg shadow-sm select-none">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="inline-flex items-center justify-center min-w-9 h-9 px-3 text-sm font-medium text-gray-700 bg-white border border-border rounded-lg shadow-sm hover:bg-gray-50 hover:text-primary hover:border-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-primary/20" aria-label="Đến trang {{ $page }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Trang sau" class="inline-flex items-center justify-center size-9 text-gray-600 bg-white border border-border rounded-lg shadow-sm hover:bg-gray-50 hover:text-primary hover:border-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-primary/20">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="Trang sau" class="inline-flex items-center justify-center size-9 text-gray-300 bg-gray-50 border border-border rounded-lg cursor-not-allowed select-none">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
