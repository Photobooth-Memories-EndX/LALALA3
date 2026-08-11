@extends('layouts.sidebar-master')

@php
    $url_dropzone_css = asset('Tabler/dist/libs/dropzone/dist/dropzone.css?1684106062');
    $url_dropzone_js  = asset('Tabler/dist/libs/dropzone/dist/dropzone-min.js?1684106062');
    $url_lightbox_js  = asset('Tabler/dist/libs/fslightbox/index.js?1684106062');
    $url_litepicker   = asset('Tabler/dist/libs/litepicker/dist/litepicker.js?1684106062');
@endphp

@section('style')
<link rel="stylesheet" href="{{ $url_dropzone_css }}">
<style>
.admin-gallery-edit-cover {
    width: 100%;
    height: 320px;
    object-fit: cover;
    border-radius: 12px;
}
.admin-gallery-edit-cover-placeholder {
    width: 100%;
    height: 200px;
    border-radius: 12px;
    background: var(--tblr-bg-surface-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: .5rem;
    color: var(--tblr-secondary);
}
.admin-gallery-photo-card {
    border-radius: 12px;
    overflow: hidden;
    transition: transform .2s, box-shadow .2s;
}
.admin-gallery-photo-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0,0,0,.12);
}
.admin-gallery-photo-img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    display: block;
}
.admin-gallery-thumb-badge {
    position: absolute;
    top: 8px;
    right: 8px;
}
.admin-gallery-photo-wrapper {
    position: relative;
}
.eventphoto-upload-zone {
    min-height: 180px;
    border: 2px dashed var(--tblr-primary);
    border-radius: 16px;
    background: var(--tblr-bg-surface);
    transition: background .2s, border-color .2s;
    cursor: pointer;
    padding: 16px;
}
.eventphoto-upload-zone.dz-drag-hover {
    background: var(--tblr-bg-surface-secondary);
    border-color: var(--tblr-primary);
}
.eventphoto-upload-empty {
    min-height: 140px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: var(--tblr-secondary);
    text-align: center;
}
.eventphoto-upload-empty i {
    font-size: 42px;
    color: var(--tblr-primary);
}
.eventphoto-upload-preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
}
.eventphoto-upload-preview:not(:empty) {
    margin-top: 16px;
}
.eventphoto-upload-card {
    border: 1px solid rgba(98,105,118,.18);
    border-radius: 12px;
    overflow: hidden;
    background: var(--tblr-bg-surface);
    cursor: default;
}
.eventphoto-upload-card-img-wrap img {
    width: 100%;
    height: 110px;
    object-fit: cover;
    display: block;
}
.eventphoto-upload-card-body {
    padding: 8px;
}
.eventphoto-upload-card-title {
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.eventphoto-upload-card-meta {
    font-size: 11px;
    color: var(--tblr-secondary);
    margin-bottom: 6px;
}
.eventphoto-upload-card .dz-error-message {
    display: none;
}
.eventphoto-upload-remove {
    width: 100%;
}
[data-bs-theme="dark"] .admin-gallery-photo-card { background: #1f2937; }
[data-bs-theme="dark"] .eventphoto-upload-zone   { background: #111827; border-color: var(--tblr-primary); }
[data-bs-theme="dark"] .eventphoto-upload-card   { background: #1f2937; border-color: rgba(255,255,255,.12); }
</style>
@endsection

@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-lg bg-{{ $eventphoto->active == 'Active' ? 'success' : 'warning' }}-lt">
                        <i class="ti ti-photo-edit fs-2"></i>
                    </div>
                    <div>
                        <h2 class="page-title mb-1">Edit Event Gallery</h2>
                        <div class="text-secondary">Manage photos, thumbnail and publishing status</div>
                    </div>
                </div>
            </div>
            <div class="col-auto d-flex align-items-center gap-2">
                <span class="badge bg-{{ $eventphoto->active == 'Active' ? 'success' : 'warning' }}-lt text-{{ $eventphoto->active == 'Active' ? 'success' : 'warning' }} fs-5">
                    {{ $eventphoto->active }}
                </span>
                <a href="{{ route('eventphoto.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <x-alert type="success" />
        <x-alert type="warning" />

        @php
            $search   = ['[', ']', '"'];
            $arraypic = $eventphoto->photos ? explode(',', $eventphoto->photos) : [];
            $statPics = json_decode($eventphoto->photos, true) ?? [];
        @endphp

        {{-- Stats Row --}}
        <div class="row row-cards mb-4">
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar bg-primary-lt"><i class="ti ti-photo"></i></span>
                            <div>
                                <div class="text-secondary small text-uppercase fw-bold">Total Photos</div>
                                <div class="h2 mb-0">{{ count($statPics) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar bg-info-lt"><i class="ti ti-calendar-event"></i></span>
                            <div>
                                <div class="text-secondary small text-uppercase fw-bold">Event Date</div>
                                <div class="h4 mb-0">{{ date('d M Y', strtotime($eventphoto->eventdate)) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar bg-{{ $eventphoto->active == 'Active' ? 'success' : 'warning' }}-lt">
                                <i class="ti ti-{{ $eventphoto->active == 'Active' ? 'circle-check' : 'file-pencil' }}"></i>
                            </span>
                            <div>
                                <div class="text-secondary small text-uppercase fw-bold">Status</div>
                                <div class="h4 mb-0">{{ $eventphoto->active }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Thumbnail Preview --}}
        @if($eventphoto->thumbnail)
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title">Featured Thumbnail</h3>
            </div>
            <div class="card-body">
                <img src="{{ asset('storage/gallery/photo/'.$eventphoto->id.'/'.$eventphoto->thumbnail) }}"
                     class="admin-gallery-edit-cover"
                     alt="{{ $eventphoto->title }}"
                     onerror="this.outerHTML='<div class=\'admin-gallery-edit-cover-placeholder\'><i class=\'ti ti-photo-off\' style=\'font-size:3rem;\'></i><span>Thumbnail not found</span></div>'">
            </div>
        </div>
        @endif

        <form action="{{ route('eventphoto.update', $eventphoto->slug) }}" method="POST" autocomplete="off">
            @csrf
            @method('PUT')
            <input type="hidden" name="id" value="{{ $eventphoto->id }}">

            {{-- Event Info --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title">Event Information</h3>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <label class="form-label required">Event Title</label>
                            <input type="text"
                                   name="title"
                                   id="title"
                                   class="form-control @error('title') is-invalid @enderror"
                                   placeholder="Enter event title"
                                   value="{{ old('title', $eventphoto->title) }}"
                                   required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label required">Event Date</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                         viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                         stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-12a2 2 0 0 1-2-2v-12z"/>
                                        <path d="M16 3v4"/><path d="M8 3v4"/><path d="M4 11h16"/>
                                        <path d="M11 15h1"/><path d="M12 15v3"/>
                                    </svg>
                                </span>
                                <input class="form-control @error('eventdate') is-invalid @enderror"
                                       id="datepicker-icon-prepend"
                                       name="eventdate"
                                       placeholder="Select a date"
                                       value="{{ old('eventdate', $eventphoto->eventdate) }}"
                                       required>
                                @error('eventdate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Photo Gallery Grid --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title">Photo Gallery</h3>
                    <div class="card-subtitle">{{ count($arraypic) }} photo(s) — select a thumbnail below</div>
                </div>
                <div class="card-body">
                    @if(count($arraypic) > 0)
                    <div class="row g-3">
                        @foreach ($arraypic as $listphoto)
                        @php
                            $photo        = str_replace($search, '', $listphoto);
                            $photo        = trim($photo);
                            $isThumbnail  = ($photo == $eventphoto->thumbnail);
                        @endphp
                        <div class="col-sm-6 col-md-4 col-lg-3" id="{{ $eventphoto->id . $photo }}">
                            <div class="card admin-gallery-photo-card h-100">
                                <div class="admin-gallery-photo-wrapper">
                                    <a data-fslightbox="gallery"
                                       href="{{ asset('storage/gallery/photo/'.$eventphoto->id.'/'.$photo) }}">
                                        <img src="{{ asset('storage/gallery/photo/'.$eventphoto->id.'/'.$photo) }}"
                                             class="admin-gallery-photo-img"
                                             alt="Photo"
                                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect width=\'100\' height=\'100\' fill=\'%23f0f0f0\'/%3E%3C/svg%3E'">
                                    </a>
                                    @if($isThumbnail)
                                        <span class="badge bg-primary admin-gallery-thumb-badge">Thumbnail</span>
                                    @endif
                                </div>
                                <div class="card-body py-2">
                                    <label class="form-check mb-0">
                                        <input class="form-check-input" type="radio"
                                               name="thumbnail" value="{{ $photo }}"
                                               @checked($isThumbnail)>
                                        <span class="form-check-label small">Set as Thumbnail</span>
                                    </label>
                                </div>
                                @if($eventphoto->active == 'Inactive' && !$isThumbnail)
                                <div class="card-footer bg-transparent p-2">
                                    <button type="button"
                                            onclick="deleteimage(event, this)"
                                            class="btn btn-danger btn-sm w-100 btndelete"
                                            data-id="{{ $eventphoto->id }}"
                                            data-option="{{ $photo }}">
                                        <i class="ti ti-trash me-1"></i>Delete
                                    </button>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="empty py-4">
                        <div class="empty-icon"><i class="ti ti-photo-off" style="font-size:2.5rem;opacity:.4;"></i></div>
                        <p class="empty-title">No photos yet</p>
                        <p class="empty-subtitle text-secondary">Upload photos using the section below.</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Upload Additional Photos --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title">Upload Additional Photos</h3>
                    <div class="card-subtitle">
                        Current: <strong>{{ count($arraypic) }}</strong> photo(s)
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
                        <i class="ti ti-info-circle"></i>
                        <div>Upload JPG, JPEG or PNG. Maximum <strong>20 files</strong> per upload.</div>
                    </div>
                    <div class="eventphoto-upload-zone" id="document-dropzone">
                        <div class="dz-message eventphoto-upload-empty">
                            <i class="ti ti-cloud-upload"></i>
                            <strong>Drop photos here or click to upload</strong>
                            <span>JPG, JPEG, PNG. Maximum 20 files per upload.</span>
                        </div>
                        <div class="eventphoto-upload-preview" id="eventPhotoPreview"></div>
                        <div class="fallback"></div>
                    </div>
                    <template id="eventPhotoPreviewTemplate">
                        <div class="eventphoto-upload-card dz-preview dz-file-preview">
                            <div class="eventphoto-upload-card-img-wrap dz-image">
                                <img data-dz-thumbnail alt="preview">
                            </div>
                            <div class="eventphoto-upload-card-body">
                                <div class="eventphoto-upload-card-title" data-dz-name></div>
                                <div class="eventphoto-upload-card-meta" data-dz-size></div>
                                <div class="dz-error-message"><span data-dz-errormessage></span></div>
                                <button type="button" class="btn btn-sm btn-outline-danger eventphoto-upload-remove" data-dz-remove>
                                    <i class="ti ti-trash me-1"></i>Remove
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    @if($eventphoto->active == 'Active')
                        <button type="submit" class="btn btn-warning" name="action" value="unpublish">
                            <i class="ti ti-eye-off me-1"></i>Unpublish
                        </button>
                    @else
                        <button type="submit" class="btn btn-primary" name="action" value="publish">
                            <i class="ti ti-send me-1"></i>Save & Publish
                        </button>
                    @endif
                    <button type="submit" class="btn btn-success" name="action" value="save">
                        <i class="ti ti-device-floppy me-1"></i>Simpan Perubahan
                    </button>
                </div>
            </div>

        </form>

    </div>
</div>

@endsection

@section('script')
<script src="{{ $url_dropzone_js }}"></script>
<script src="{{ $url_litepicker }}"></script>
<script src="{{ $url_lightbox_js }}"></script>
<script>
function deleteimage(e, btn) {
    e.preventDefault();
    if (!confirm('Are you sure you want to delete this photo?')) return;
    var id     = $(btn).data('id');
    var option = $(btn).data('option');
    $.ajax({
        type: 'POST',
        url:  '{{ route('eventphoto.deleteImage') }}',
        data: { _token: '{{ csrf_token() }}', id: id, option: option },
        success: function(data) {
            if (data.status == 200) {
                $('#' + id + option).fadeOut(300, function() { $(this).remove(); });
            }
        }
    });
}

var uploadedDocumentMap = {};
Dropzone.autoDiscover = false;

var myDropzone = new Dropzone('div#document-dropzone', {
    url:               '{{ route('eventphoto.uploadfileajax') }}',
    clickable:         '.eventphoto-upload-empty',
    previewsContainer: '#eventPhotoPreview',
    previewTemplate:   document.getElementById('eventPhotoPreviewTemplate').innerHTML,
    autoProcessQueue:  true,
    uploadMultiple:    true,
    acceptedFiles:     '.jpg,.png,.jpeg',
    parallelUploads:   2,
    headers:           { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
    maxFiles:          20,
    maxFilesize:       1000,
    init: function() {
        this.on('error', function(file, message) {
            alert(message);
            this.removeFile(file);
        });
    },
    successmultiple: function(data, response) {
        $.each(response['name'], function(key, val) {
            $('form').append('<input type="hidden" name="documents[]" value="' + val + '">');
            uploadedDocumentMap[data[key].name] = val;
        });
    },
    removedfile: function(file) {
        if (file.previewElement) file.previewElement.remove();
        var name = (typeof file.file_name !== 'undefined')
            ? file.file_name
            : uploadedDocumentMap[file.name];
        if (name) {
            $('form').find('input[name="documents[]"][value="' + name + '"]').remove();
            $.ajax({
                url:  '{{ route('eventphoto.deletefileajax') }}',
                type: 'GET',
                data: { name: name },
                success: function(data) { console.log('File deleted:', data); }
            });
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    if (window.Litepicker) {
        new Litepicker({
            element: document.getElementById('datepicker-icon-prepend'),
            format:  'YYYY-MM-DD',
            buttonText: {
                previousMonth: '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6"/></svg>',
                nextMonth:     '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6"/></svg>'
            }
        });
    }
    if (window.refreshFsLightbox) refreshFsLightbox();
});
</script>
@endsection
