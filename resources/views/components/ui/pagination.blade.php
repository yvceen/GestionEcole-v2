@props([
    'paginator' => null,
])

@if($paginator && method_exists($paginator, 'links') && $paginator->hasPages())
    <div class="app-card px-4 py-3">
        {{ $paginator->onEachSide(1)->links() }}
    </div>
@endif
