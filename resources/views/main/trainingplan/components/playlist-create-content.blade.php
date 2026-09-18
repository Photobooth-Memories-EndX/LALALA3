@php
    $content = $content ?? [];
@endphp

<div class="card owl-main-card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <span class="badge bg-primary-lt text-primary rounded-circle me-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">4</span>
            <div>
                <h3 class="card-title mb-0">Playlist Content</h3>
                <p class="text-muted mb-0 small">Select materials, then drag and drop items to arrange their order.</p>
            </div>
        </div>
        <span class="badge bg-blue-lt text-blue">Drag &amp; Drop</span>
    </div>
    <div class="card-body">
        <label class="form-label fw-semibold">Playlist Materials</label>
        <div class="playlist-content-select-wrapper">
            <select class="form-select js-content-select" id="select-content" name="content[]" multiple>
                @unless (count($content) == 0)
                    @foreach ($content as $list)
                        <option value="{{ $list->id }}"
                            data-status="{{ $list->active }}"
                            @if (is_array(old('content')) && in_array($list->id, old('content'))) selected @endif>
                            {{ $list->title }}
                        </option>
                    @endforeach
                @else
                    <option value="" disabled>No materials available</option>
                @endunless
            </select>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <small class="form-hint">Type to search materials. Drag items to rearrange the order.</small>
            <small class="content-select-count text-muted fw-semibold" id="content-count-label"></small>
        </div>
    </div>
</div>