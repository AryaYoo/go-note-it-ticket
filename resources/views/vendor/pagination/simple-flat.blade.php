@if ($paginator->hasPages())
<nav>
    {{-- Previous --}}
    @if ($paginator->onFirstPage())
        <span>‹</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}">‹</a>
    @endif

    {{-- Page Numbers --}}
    @foreach ($elements as $element)
        @if (is_string($element))
            <span>{{ $element }}</span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="active">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Next --}}
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}">›</a>
    @else
        <span>›</span>
    @endif
</nav>
@endif
