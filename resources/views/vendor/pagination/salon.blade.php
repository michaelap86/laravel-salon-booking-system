@if ($paginator->hasPages())
    <nav class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-4 text-sm" aria-label="Pagination">
        <p class="text-muted">Showing <b class="text-ink">{{ $paginator->firstItem() }}</b> to <b class="text-ink">{{ $paginator->lastItem() }}</b> of <b class="text-ink">{{ number_format($paginator->total()) }}</b></p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="a-btn a-btn-ghost a-btn-sm pointer-events-none opacity-40">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="a-btn a-btn-ghost a-btn-sm">Previous</a>
            @endif
            <span class="px-3 text-muted">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="a-btn a-btn-ghost a-btn-sm">Next</a>
            @else
                <span class="a-btn a-btn-ghost a-btn-sm pointer-events-none opacity-40">Next</span>
            @endif
        </div>
    </nav>
@endif
