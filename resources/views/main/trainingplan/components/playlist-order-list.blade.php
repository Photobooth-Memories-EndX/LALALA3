@php
    $trainingplan       = $trainingplan ?? null;
    $trainingplandetail = $trainingplandetail ?? collect();
    $isLocked           = false;
    $isEmpty            = $trainingplandetail->isEmpty();
@endphp

<div class="card owl-main-card">
    <div class="card-header d-flex align-items-center justify-content-between py-2 px-3">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary text-white rounded-circle" style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;">1</span>
            <div>
                <h3 class="card-title mb-0 fs-4 fw-bold">Playlist Content</h3>
                <small class="text-secondary d-block">Drag and drop materials to arrange their order.</small>
            </div>
        </div>
        <span class="badge bg-blue-lt text-blue">
            <i class="ti ti-books me-1"></i><span id="materialsCountBadge">{{ $trainingplandetail->count() }}</span> materials
        </span>
    </div>

    <div class="card-body p-3">
        @if ($isEmpty)
            <div class="text-center py-5">
                <div class="empty-icon mb-3">
                    <i class="ti ti-playlist text-muted" style="font-size: 3.5rem; opacity: 0.4;"></i>
                </div>
                <h4 class="text-dark fw-bold mb-1">No content has been added to this playlist yet.</h4>
                <p class="text-secondary small mb-3">Please add materials to this playlist from the edit page before arranging their order.</p>
                <a href="{{ route('playlist.edit', $trainingplan->slug) }}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                    <i class="ti ti-plus"></i>
                    <span>Add Content</span>
                </a>
            </div>
        @else
            <ul id="sortlist" class="trainingplan-sortlist p-0 m-0">
                @foreach ($trainingplandetail as $detail)
                    @php
                        $content = $detail->content;
                        $isAvailable = $content && $content->id && !(method_exists($content, 'trashed') && $content->trashed());
                    @endphp
                    <li class="trainingplan-sortlist-item list-group-item d-flex align-items-center gap-3 p-3 mb-2 rounded-3 border bg-white shadow-sm"
                        data-detail-id="{{ $detail->id }}"
                        data-order="{{ $detail->content_order }}">
                        <div class="trainingplan-drag-handle text-muted fs-3 flex-shrink-0"
                            style="cursor: grab;"
                            title="Drag to reorder"
                            aria-label="Drag handle">
                            <i class="ti ti-grip-vertical" style="pointer-events: none;"></i>
                        </div>

                        <span class="trainingplan-item-badge badge bg-primary text-white rounded-circle flex-shrink-0"
                            style="width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700;">
                            {{ $loop->iteration }}
                        </span>

                        <div class="flex-grow-1 min-w-0">
                            @if ($isAvailable)
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-blue-lt text-blue text-uppercase fw-semibold" style="font-size: 0.7rem;">
                                        {{ $content->type ?? 'Material' }}
                                    </span>
                                    @if ($content->active === 'Active')
                                        <span class="badge bg-success-lt text-success" style="font-size: 0.7rem;">Active</span>
                                    @else
                                        <span class="badge bg-warning-lt text-warning" style="font-size: 0.7rem;">Inactive</span>
                                    @endif
                                </div>
                                <h4 class="mb-0 text-truncate text-dark fw-semibold" style="font-size: 0.925rem;" title="{{ $content->title }}">
                                    {{ $content->title }}
                                </h4>
                            @else
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-danger-lt text-danger text-uppercase fw-semibold" style="font-size: 0.7rem;">
                                        Unavailable
                                    </span>
                                </div>
                                <h4 class="mb-0 text-truncate text-danger fst-italic" style="font-size: 0.925rem;">
                                    This material is no longer available.
                                </h4>
                            @endif
                        </div>

                        <div class="text-muted small text-end flex-shrink-0 ms-2">
                            @if ($isAvailable)
                                <span class="d-inline-flex align-items-center gap-1">
                                    <i class="ti ti-clock"></i>
                                    <span>{{ $content->duration ?? '00:00:00' }}</span>
                                </span>
                            @else
                                <span>—</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @unless ($isEmpty)
        <div class="card-footer d-flex justify-content-between align-items-center bg-light px-3 py-2 border-top">
            <a href="{{ route('playlist.edit', $trainingplan->slug) }}" class="btn btn-light d-inline-flex align-items-center gap-1">
                <i class="ti ti-arrow-left"></i>
                <span>Back to Edit</span>
            </a>

            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1" id="btnSaveOrder">
                <span class="spinner-border spinner-border-sm d-none" id="saveSpinner" role="status" aria-hidden="true"></span>
                <i class="ti ti-device-floppy" id="saveIcon"></i>
                <span id="saveBtnText">Save Order</span>
            </button>
        </div>
    @endunless
</div>