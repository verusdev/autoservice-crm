@if (isset($paginator) && $paginator->hasPages())
    <div class="card-head" style="border-top:1px solid var(--border);border-bottom:0;justify-content:space-between">
        <div class="small muted">
            Показано {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} из {{ $paginator->total() }}
        </div>
        <div class="pill-list">
            @if ($paginator->onFirstPage())
                <span class="badge b-gray">←</span>
            @else
                <a class="btn btn-sm" href="{{ $paginator->previousPageUrl() }}">← Назад</a>
            @endif

            @if ($paginator->hasMorePages())
                <a class="btn btn-sm" href="{{ $paginator->nextPageUrl() }}">Вперёд →</a>
            @else
                <span class="badge b-gray">→</span>
            @endif
        </div>
    </div>
@endif