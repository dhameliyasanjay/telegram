@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; width: 100%;">
        <div style="font-size: 0.82rem; color: var(--text-dim);">
            Showing <span style="color:#fff; font-weight:600;">{{ $paginator->firstItem() }}</span> to <span style="color:#fff; font-weight:600;">{{ $paginator->lastItem() }}</span> of <span style="color:#fff; font-weight:600;">{{ $paginator->total() }}</span> results
        </div>

        <div style="display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap;">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span style="opacity: 0.4; cursor: not-allowed; padding: 6px 12px; font-size: 0.8rem; background: #18181b; border: 1px solid var(--border); border-radius: 6px; color: var(--text-muted); display: inline-flex; align-items: center; gap: 4px;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg> Previous
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" style="text-decoration: none; padding: 6px 12px; font-size: 0.8rem; background: #18181b; border: 1px solid var(--border); border-radius: 6px; color: var(--text-muted); display: inline-flex; align-items: center; gap: 4px; transition: all 0.15s ease;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg> Previous
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span style="padding: 6px 10px; color: var(--text-dim); font-size: 0.8rem;">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span style="padding: 6px 12px; font-size: 0.8rem; font-weight: 700; background: var(--accent); border: 1px solid var(--accent); border-radius: 6px; color: #fff; box-shadow: 0 2px 8px var(--accent-glow);">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" style="text-decoration: none; padding: 6px 12px; font-size: 0.8rem; background: #18181b; border: 1px solid var(--border); border-radius: 6px; color: var(--text-muted); transition: all 0.15s ease;">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" style="text-decoration: none; padding: 6px 12px; font-size: 0.8rem; background: #18181b; border: 1px solid var(--border); border-radius: 6px; color: var(--text-muted); display: inline-flex; align-items: center; gap: 4px; transition: all 0.15s ease;">
                    Next <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                <span style="opacity: 0.4; cursor: not-allowed; padding: 6px 12px; font-size: 0.8rem; background: #18181b; border: 1px solid var(--border); border-radius: 6px; color: var(--text-muted); display: inline-flex; align-items: center; gap: 4px;">
                    Next <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif

