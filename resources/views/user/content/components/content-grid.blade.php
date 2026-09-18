<div class="d-flex align-items-center mb-3">
    <div>
        <h2 class="mb-0">Learning Materials</h2>
        <div class="text-muted">Explore learning materials available in OWL LMS.</div>
    </div>
</div>

<div class="row row-cards">
    @forelse ($contents as $item)
        <div class="col-12 col-md-6 col-lg-4 col-xl-3 content-grid-item">
            @if (in_array($item->type, ['YouTube', 'MOOC']))
                @include('user.content.components.card-mooc', ['item' => $item])
            @elseif ($item->type === 'Video')
                @include('user.content.components.card-video', ['item' => $item])
            @else
                @include('user.content.components.card-document', ['item' => $item])
            @endif
        </div>
    @empty
        @include('user.content.components.content-empty')
    @endforelse
</div>

<div class="mt-4 content-pagination-wrap">
    {{ $contents->links() }}
</div>
