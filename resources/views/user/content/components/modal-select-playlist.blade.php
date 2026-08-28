@php
    $modalPlaylists = $activePlaylists ?? ($item->relationLoaded('trainingPlans')
        ? $item->trainingPlans->filter(fn ($p) => $p->active === 'Active' && $p->isAccessibleByDeadline())->values()
        : collect());
@endphp

<div class="modal modal-blur fade" id="modal-select-playlist-{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold">
                        <i class="ti ti-playlist text-primary me-1"></i>
                        Pilih Playlist
                    </h5>
                    <div class="small text-muted text-truncate" style="max-width: 350px;" title="{{ $item->title }}">
                        {{ $item->title }}
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <p class="text-muted small mb-3">
                    Materi ini tersedia dalam beberapa playlist. Silakan pilih playlist yang ingin Anda ikuti:
                </p>
                <div class="list-group list-group-flush gap-2">
                    @foreach ($modalPlaylists as $plan)
                        @php
                            $planThumb = $plan->thumbnail_url ?? asset('default-video.jpg');
                        @endphp
                        <a href="{{ route('playlist.user.view', ['slug' => $plan->slug, 'vidslug' => $item->slug]) }}"
                           class="list-group-item list-group-item-action d-flex align-items-center p-2 rounded-3 border">
                            <div class="me-3 rounded-2 overflow-hidden bg-muted-lt d-flex align-items-center justify-content-center"
                                 style="width: 56px; height: 56px; min-width: 56px;">
                                @if($plan->thumbnail && file_exists(public_path('storage/' . ltrim($plan->thumbnail, '/'))))
                                    <img src="{{ asset('storage/' . ltrim($plan->thumbnail, '/')) }}"
                                         alt="{{ $plan->title }}"
                                         class="w-100 h-100 object-cover">
                                @else
                                    <i class="ti ti-playlist text-primary fs-2"></i>
                                @endif
                            </div>
                            <div class="flex-fill me-2">
                                <div class="fw-bold text-dark mb-1 text-truncate" style="max-width: 260px;" title="{{ $plan->title }}">
                                    {{ $plan->title }}
                                </div>
                                <div class="small text-muted d-flex align-items-center">
                                    <i class="ti ti-arrow-right text-primary me-1"></i>
                                    <span>Buka Materi</span>
                                </div>
                            </div>
                            <i class="ti ti-chevron-right text-muted"></i>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
