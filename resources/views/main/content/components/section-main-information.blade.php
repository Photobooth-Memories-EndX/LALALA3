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
                    <option value="YouTube" @selected(old('type', $content->type ?? '') === 'YouTube')>MOOC</option>
                    <option value="SOP/Regulation" @selected(old('type', $content->type ?? '') === 'SOP/Regulation')>SOP/Regulation</option>
                </select>
            </div>

            {{-- YouTube Link (Appears when Type = YouTube) --}}
            <div class="col-12" style="display: none;" id="linkrow">
                <label class="form-label" for="link">YouTube Link</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon text-red" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M2 8a4 4 0 0 1 4 -4h12a4 4 0 0 1 4 4v8a4 4 0 0 1 -4 4h-12a4 4 0 0 1 -4 -4v-8z"></path>
                            <path d="M10 9l5 3l-5 3z"></path>
                        </svg>
                    </span>
                    <input type="text" class="form-control" placeholder="https://www.youtube.com/watch?v=..."
                        name="link" id="link"
                        value="{{ old('link', isset($content->link) ? 'https://www.youtube.com/watch?v=' . $content->link : '') }}"
                        {{ $isLocked ? 'disabled' : '' }}>
                </div>
            </div>

            {{-- 4. Content Description --}}
            <div class="col-12">
                <label class="form-label required" for="description">Content Description</label>
                <textarea name="description" id="description" rows="4" class="form-control" {{ $isLocked ? 'disabled' : 'required' }}>{{ old('description', $content->description ?? '') }}</textarea>
            </div>

            {{-- 5. Only Show on Playlist --}}
            <div class="col-12">
                <label class="form-check cursor-pointer">
                    <input type="hidden" name="playlistonly" value="N">
                    <input class="form-check-input" type="checkbox" name="playlistonly" value="Y"
                        @checked(old('playlistonly', $content->playlistonly ?? '') === 'Y')
                        {{ $isLocked ? 'disabled' : '' }}>
                    <span class="form-check-label">Only show on Playlist</span>
                </label>
                <small class="form-hint d-block mt-1">This content is only available within playlists and will not appear in the general content list.</small>
            </div>
        </div>
    </div>
</div>
