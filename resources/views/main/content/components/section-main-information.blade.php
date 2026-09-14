@php
    $content = $content ?? null;
    $classification = $classification ?? [];
    $stepNumber = $stepNumber ?? 1;
    $isLocked = $isLocked ?? ($content && $content->isPublished());
@endphp

<div class="card owl-main-card mb-3">
    <div class="card-header d-flex align-items-center">
        <span class="badge bg-primary-lt text-primary rounded-circle me-2" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;">{{ $stepNumber }}</span>
        <h3 class="card-title mb-0">Main Information</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- 1. Title --}}
            <div class="col-12">
                <label class="form-label required" for="title">Title</label>
                <input type="text" class="form-control" placeholder="Enter title"
                    name="title" id="title" value="{{ old('title', $content->title ?? '') }}"
                    {{ $isLocked ? 'disabled' : 'required' }}>
            </div>

            {{-- 2. Author --}}
            <div class="col-12">
                <label class="form-label required" for="author">Author</label>
                <input type="text" class="form-control" placeholder="Enter author"
                    name="author" id="author" value="{{ old('author', $content->author ?? '') }}"
                    {{ $isLocked ? 'disabled' : 'required' }}>
            </div>

            {{-- 3. Classification & Type --}}
            <div class="col-md-6">
                <label class="form-label required" for="classification">Classification</label>
                <select name="classification" id="classification" class="form-select" {{ $isLocked ? 'disabled' : 'required' }}>
                    <option value="">-- Select Classification --</option>
                    @foreach ($classification as $list)
                        <option value="{{ $list->id }}" @selected(old('classification', $content->classification_id ?? '') == $list->id)>
                            {{ $list->classification_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label required" for="type">Type</label>
                <select name="type" id="type" class="form-select" onchange="typevalue(this)" {{ $isLocked ? 'disabled' : 'required' }}>
                    <option value="">-- Select Type --</option>
                    <option value="Video" @selected(old('type', $content->type ?? '') === 'Video')>Video</option>
                    <option value="Document" @selected(old('type', $content->type ?? '') === 'Document')>Document</option>
                    <option value="YouTube" @selected(in_array(old('type', $content->type ?? ''), ['YouTube', 'MOOC']))>MOOC</option>
                    <option value="SOP/Regulation" @selected(old('type', $content->type ?? '') === 'SOP/Regulation')>SOP/Regulation</option>
                </select>
            </div>

            {{-- External Course Link (Appears when Type = YouTube/MOOC) --}}
            <div class="col-12" style="{{ in_array(old('type', $content->type ?? ''), ['YouTube', 'MOOC']) ? 'display: block;' : 'display: none;' }}" id="linkrow">
                <label class="form-label" for="link">External Course Link</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="ti ti-world text-primary" style="font-size: 1.1rem;"></i>
                    </span>
                    @php
                        $legacyLink = $content->link ?? '';
                        $formattedLink = '';
                        if (filled($legacyLink)) {
                            if (str_starts_with($legacyLink, 'http://') || str_starts_with($legacyLink, 'https://')) {
                                $formattedLink = $legacyLink;
                            } else {
                                $formattedLink = 'https://www.youtube.com/watch?v=' . $legacyLink;
                            }
                        }
                    @endphp
                    <input type="text" class="form-control" placeholder="https://..."
                        name="link" id="link"
                        value="{{ old('link', $formattedLink) }}"
                        {{ $isLocked ? 'disabled' : '' }}>
                </div>
                <small class="form-hint">Enter the full URL to the external course platform (e.g. Coursera, edX, or other online learning platform).</small>
            </div>

            {{-- 4. Content Description --}}
            <div class="col-12">
                <label class="form-label required" for="description">Content Description</label>
                <textarea name="description" id="description" rows="4" class="form-control" {{ $isLocked ? 'disabled' : 'required' }}>{{ old('description', $content->description ?? '') }}</textarea>
            </div>

            {{-- 5. Only Show on Playlist --}}
            <div class="col-12">
                <label class="form-check {{ $isLocked ? 'cursor-not-allowed' : 'cursor-pointer' }}" for="playlistonly">
                    <input type="hidden" name="playlistonly" value="N" {{ $isLocked ? 'disabled' : '' }}>
                    <input class="form-check-input" type="checkbox" name="playlistonly" id="playlistonly" value="Y"
                        @checked(in_array(old('playlistonly', $content->playlistonly ?? 'N'), ['Y', '1', 1, true, 'yes', 'Yes'], true))
                        {{ $isLocked ? 'disabled' : '' }}>
                    <span class="form-check-label">Only show on Playlist</span>
                </label>
                <small class="form-hint d-block mt-1">This content is only available within playlists and will not appear in the general content list.</small>
            </div>
        </div>
    </div>
</div>
