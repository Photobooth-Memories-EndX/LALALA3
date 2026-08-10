@php
    $photosArr = json_decode($list->photos, true);
    $photosArr = is_array($photosArr) ? $photosArr : [];
    $cover = $list->thumbnail ?: ($photosArr[0] ?? null);

    $imgSrc = $cover
        ? asset('storage/gallery/photo/' . $list->id . '/' . $cover)
        : null;
    $photoCount = count($photosArr);
    $eventDate = $list->eventdate
        ? date('d M Y', strtotime($list->eventdate))
        : 'Date unavailable';
    $uploaderName = $list->uploader ?: 'Training Team';
@endphp

<article class="col-xl-4 col-md-6">
    <div class="card gallery-card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
        <a href="{{ route('gallery.user.view', array_merge(['slug' => $list->slug], request()->query())) }}" class="d-block text-decoration-none text-reset">
            <div class="gallery-thumb position-relative">
                <div class="gallery-thumb-placeholder">
                    <i class="ti ti-photo"></i>
                </div>

                @if($imgSrc)
                    <img
                        src="{{ $imgSrc }}"
                        alt="{{ $list->title ?: 'Learning event gallery' }}"
                        class="gallery-thumb-img"
                        data-gallery-image
                        loading="lazy"
                    >
                @endif

                <span class="gallery-badge">{{ $list->category ?? 'Event' }}</span>

                @if($photoCount > 0)
                    <span class="gallery-photo-count">
                        <i class="ti ti-photo"></i> {{ $photoCount }}
                    </span>
                @endif
            </div>
        </a>

        <div class="card-body d-flex flex-column p-3">
            <h3 class="gallery-card-title mb-2" title="{{ $list->title }}">
                <a href="{{ route('gallery.user.view', array_merge(['slug' => $list->slug], request()->query())) }}" class="text-reset text-decoration-none fw-bold">
                    {{ $list->title ?: 'Untitled Gallery' }}
                </a>
            </h3>

            <div class="mt-auto pt-2">
                {{-- AUTHOR ICON & NAME --}}
                <div class="small text-muted mb-2 d-flex align-items-center">
                    <i class="ti ti-users me-1"></i>
                    <span>Author : {{ $uploaderName }}</span>
                </div>

                <div class="gallery-event-meta mb-3">
                    <span title="Event date">
                        <i class="ti ti-calendar"></i> {{ $eventDate }}
                    </span>
                    <span title="Views">
                        <i class="ti ti-eye"></i> {{ $list->views ?? 0 }}
                    </span>
                    <span title="Comments">
                        <i class="ti ti-message"></i> {{ $list->comments_count }}
                    </span>
                </div>

                <a href="{{ route('gallery.user.view', array_merge(['slug' => $list->slug], request()->query())) }}" class="btn btn-primary w-100 rounded-pill">
                    <i class="ti ti-eye me-1"></i> View Gallery
                </a>
            </div>
        </div>
    </div>
</article>
