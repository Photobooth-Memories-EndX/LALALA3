<div class="row g-4 gallery-grid" id="gallery-list">
    @forelse($eventphoto as $list)
        @include('user.gallery.components.gallery-card', ['list' => $list])
    @empty
        @include('user.gallery.components.gallery-empty', ['hasFilters' => $hasFilters])
    @endforelse
</div>

@if($eventphoto->hasPages() || $eventphoto->total() > 0)
    <div class="d-flex flex-column flex-sm-row align-items-center justify-content-center gap-3 mt-5 pt-3 border-top text-muted small w-100 text-center">
        <div>
            Showing <b>{{ $eventphoto->firstItem() ?? 0 }}</b> to <b>{{ $eventphoto->lastItem() ?? 0 }}</b> of <b>{{ $eventphoto->total() }}</b> results
        </div>
        <ul class="pagination pagination-sm m-0 align-items-center gap-1">
            {{-- Previous Link --}}
            <li class="page-item {{ $eventphoto->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link rounded-3 border-0 bg-transparent text-secondary" href="{{ $eventphoto->previousPageUrl() }}">&lsaquo;</a>
            </li>
            {{-- Pagination Elements --}}
            @foreach ($eventphoto->getUrlRange(1, $eventphoto->lastPage()) as $page => $url)
                <li class="page-item">
                    <a class="page-link rounded-3 px-3 py-1.5 {{ $page == $eventphoto->currentPage() ? 'bg-primary text-white fw-bold' : 'border-0 bg-transparent text-secondary' }}" href="{{ $url }}">{{ $page }}</a>
                </li>
            @endforeach
            {{-- Next Link --}}
            <li class="page-item {{ !$eventphoto->hasMorePages() ? 'disabled' : '' }}">
                <a class="page-link rounded-3 border-0 bg-transparent text-secondary" href="{{ $eventphoto->nextPageUrl() }}">&rsaquo;</a>
            </li>
        </ul>
    </div>
@endif
