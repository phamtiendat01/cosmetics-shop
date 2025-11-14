@if ($paginator->hasPages())
<nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-center mt-6">
    <ul class="inline-flex items-center gap-1 text-sm">
        {{-- Nút Previous --}}
        @if ($paginator->onFirstPage())
        <li class="px-3 py-1.5 rounded-md text-ink/40 border border-rose-100 cursor-not-allowed">
            <i class="fa-solid fa-angle-left"></i>
        </li>
        @else
        <li>
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                class="px-3 py-1.5 rounded-md border border-rose-200 hover:bg-rose-50">
                <i class="fa-solid fa-angle-left"></i>
            </a>
        </li>
        @endif

        {{-- Số trang --}}
        @foreach ($elements as $element)
        @if (is_string($element))
        <li class="px-3 py-1.5 text-ink/40">{{ $element }}</li>
        @endif

        @if (is_array($element))
        @foreach ($element as $page => $url)
        @if ($page == $paginator->currentPage())
        <li class="px-3 py-1.5 rounded-md bg-brand-600 text-white font-semibold">{{ $page }}</li>
        @else
        <li>
            <a href="{{ $url }}"
                class="px-3 py-1.5 rounded-md border border-rose-200 hover:bg-rose-50">{{ $page }}</a>
        </li>
        @endif
        @endforeach
        @endif
        @endforeach

        {{-- Nút Next --}}
        @if ($paginator->hasMorePages())
        <li>
            <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                class="px-3 py-1.5 rounded-md border border-rose-200 hover:bg-rose-50">
                <i class="fa-solid fa-angle-right"></i>
            </a>
        </li>
        @else
        <li class="px-3 py-1.5 rounded-md text-ink/40 border border-rose-100 cursor-not-allowed">
            <i class="fa-solid fa-angle-right"></i>
        </li>
        @endif
    </ul>
</nav>
@endif