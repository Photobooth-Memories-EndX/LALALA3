@extends('layouts.sidebar-master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}">
    @include('main.trainingplan.components.tomselect-content-style')
@endpush

@section('content')
    @include('main.monitoring.components.style')

    {{-- Page Header --}}
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Manage / Playlist</div>
                    <h2 class="owl-page-title mb-1">{{ $title }}</h2>
                    <div class="owl-page-subtitle">Buat playlist baru dengan menggabungkan beberapa konten.</div>
                </div>
                <div class="col-auto ms-auto">
                    <ol class="breadcrumb breadcrumb-arrows mb-0" aria-label="breadcrumbs">
                        <li class="breadcrumb-item"><a href="javascript:;">Manage</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('trainingplan.index') }}">Playlist</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <form action="{{ route('trainingplan.store') }}" method="post" autocomplete="off" id="trainingPlanForm" enctype="multipart/form-data">
                @csrf
                @method('POST')

                <div class="row g-3">

                    {{-- Kolom Kiri: Konten Playlist --}}
                    <div class="col-lg-8">

                        {{-- Informasi Playlist --}}
                        <div class="card owl-main-card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Informasi Playlist</h3>
                            </div>
                            <div class="card-body">

                                <x-alert type='warning'/>

                                <div class="mb-3">
                                    <label for="title" class="form-label required">Title</label>
                                    <input type="text" class="form-control" placeholder="Enter playlist title"
                                        name="title" id="title" required>
                                </div>
                                <div>
                                    <label for="url-link" class="form-label">URL Link</label>
                                    <input type="text" name="url_link" id="url-link" class="form-control"
                                        placeholder="https://...">
                                    <small class="form-hint">Opsional. Link eksternal yang terkait dengan playlist ini.</small>
                                </div>
                            </div>
                        </div>

                        {{-- Konten / Playlist --}}
                        <div class="card owl-main-card">
                            <div class="card-header">
                                <h3 class="card-title">Konten Playlist</h3>
                                <div class="card-subtitle text-muted mt-1">
                                    Pilih konten, lalu tarik item untuk mengatur urutan section.
                                    <span class="badge bg-blue-lt ms-2" style="font-size:10px;">Drag &amp; Drop</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <label for="select-content" class="form-label">Content</label>
                                <div class="playlist-content-select-wrapper">
                                    <select class="form-select js-content-select" id="select-content" name="content[]" multiple>
                                        @unless (count($content) == 0)
                                            @foreach ($content as $list)
                                                <option value="{{ $list->id }}">{{ $list->title }}</option>
                                            @endforeach
                                        @else
                                            <option value="" disabled>No content available</option>
                                        @endunless
                                    </select>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <small class="form-hint">Hanya konten yang sudah dipublish. Ketik untuk mencari.</small>
                                    <small class="content-select-count" id="content-count-label"></small>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" form="trainingPlanForm" class="btn btn-primary w-100" name="action" value="save" formnovalidate>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2z"></path>
                                            <path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                            <path d="M14 4l0 4l-6 0l0 -4"></path>
                                        </svg>
                                        Simpan Playlist
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Kolom Kanan: Thumbnail + Deadline + Aksi --}}
                    <div class="col-lg-4">

                        {{-- Thumbnail Playlist --}}
                        <div class="card owl-main-card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Thumbnail Playlist</h3>
                            </div>
                            <div class="card-body">
                                <div>
                                    <label class="form-label">Upload Thumbnail</label>
                                    <input type="file" name="thumbnail" id="thumbnail" class="form-control"
                                        accept="image/jpg,image/jpeg,image/png,image/webp">
                                    <small class="form-hint">Opsional. Format: JPG, PNG, WEBP — maks. 2 MB.</small>
                                </div>
                            </div>
                        </div>

                        {{-- Deadline Playlist --}}
                        <div class="card owl-main-card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Deadline Playlist</h3>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-check cursor-pointer">
                                        <input type="hidden" name="has_deadline" value="0">
                                        <input class="form-check-input" type="checkbox" id="has_deadline"
                                            name="has_deadline" value="1"
                                            @if(old('has_deadline')) checked @endif>
                                        <span class="form-check-label fw-medium">Perlu Deadline?</span>
                                    </label>
                                    <small class="form-hint d-block mt-1">Jika dicentang, isi rentang tanggal di bawah.</small>
                                </div>
                                <div id="deadline-fields" style="{{ old('has_deadline') ? '' : 'display:none;' }}">
                                    <div class="mb-2">
                                        <label class="form-label">From Date</label>
                                        <input type="date" class="form-control" name="from_date" id="from_date"
                                            value="{{ old('from_date') }}">
                                    </div>
                                    <div>
                                        <label class="form-label">To Date</label>
                                        <input type="date" class="form-control" name="to_date" id="to_date"
                                            value="{{ old('to_date') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card owl-main-card">
                            <div class="card-header">
                                <h3 class="card-title">Aksi</h3>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success" name="action" value="save" formnovalidate>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2z"></path>
                                            <path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                            <path d="M14 4l0 4l-6 0l0 -4"></path>
                                        </svg>
                                        Save (Draft)
                                    </button>
                                    <button type="submit" class="btn btn-primary" name="action" value="publish">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M7 11v8a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1v-7a1 1 0 0 1 1 -1h3a4 4 0 0 0 4 -4v-1a2 2 0 0 1 4 0v5h3a2 2 0 0 1 2 2l-1 5a2 3 0 0 1 -2 2h-7a3 3 0 0 1 -3 -3"></path>
                                        </svg>
                                        Save & Publish
                                    </button>
                                    <a href="{{ route('trainingplan.index') }}" class="btn btn-light">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('Tabler/dist/libs/tom-select/dist/js/tom-select.complete.min.js?1684106062') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-content-select').forEach(function (selectEl) {
                if (selectEl.tomselect) return;

                var tom = new TomSelect(selectEl, {
                    plugins: ['remove_button'],
                    maxItems: null,
                    create: false,
                    persist: false,
                    hideSelected: true,
                    closeAfterSelect: false,
                    sortField: [],
                    dropdownParent: 'body',
                    placeholder: 'Ketik untuk mencari konten...',
                    searchField: ['text'],
                    maxOptions: 80,
                    render: {
                        item: function (data, escape) {
                            return '<div class="playlist-section-item-inner">' +
                                '<span class="playlist-drag-handle" title="Tarik untuk mengubah urutan">&#9776;</span>' +
                                '<span class="playlist-section-badge"></span>' +
                                '<span class="playlist-section-title">' + escape(data.text) + '</span>' +
                                '</div>';
                        },
                        option: function (data, escape) {
                            return '<div class="playlist-option-item"><span>' + escape(data.text) + '</span></div>';
                        },
                        no_results: function () {
                            return '<div class="p-2 text-muted" style="font-size:13px;">Konten tidak ditemukan</div>';
                        }
                    },
                    onInitialize: function () {
                        this.wrapper.classList.add('playlist-ts-wrapper');
                        enablePlaylistSortable(this);
                        updatePlaylistSectionNumbers(this);
                        updateCountLabel(this);
                    },
                    onItemAdd: function () {
                        updatePlaylistSectionNumbers(this);
                        updateCountLabel(this);
                    },
                    onItemRemove: function () {
                        updatePlaylistSectionNumbers(this);
                        updateCountLabel(this);
                    }
                });

                function enablePlaylistSortable(tomInstance) {
                    if (typeof Sortable === 'undefined') {
                        console.error('SortableJS belum dimuat.');
                        return;
                    }
                    new Sortable(tomInstance.control, {
                        animation: 180,
                        handle: '.playlist-drag-handle',
                        draggable: '.item',
                        forceFallback: true,
                        fallbackOnBody: true,
                        swapThreshold: 0.65,
                        ghostClass: 'playlist-sortable-ghost',
                        chosenClass: 'playlist-sortable-chosen',
                        dragClass: 'playlist-sortable-drag',
                        onEnd: function () {
                            var orderedValues = Array.from(tomInstance.control.querySelectorAll('.item[data-value]'))
                                .map(function (item) { return item.getAttribute('data-value'); })
                                .filter(Boolean);
                            tomInstance.items = orderedValues;
                            tomInstance.setCaret(orderedValues.length);
                            tomInstance.refreshItems();
                            tomInstance.refreshOptions(false);
                            updatePlaylistSectionNumbers(tomInstance);
                            updateCountLabel(tomInstance);
                        }
                    });
                }

                function updatePlaylistSectionNumbers(tomInstance) {
                    Array.from(tomInstance.control.querySelectorAll('.item[data-value]')).forEach(function (item, index) {
                        var badge = item.querySelector('.playlist-section-badge');
                        if (badge) badge.textContent = 'SECTION ' + (index + 1);
                    });
                }

                function updateCountLabel(tomInstance) {
                    var count = tomInstance.items.length;
                    var total = Object.keys(tomInstance.options).length;
                    var label = document.getElementById('content-count-label');
                    if (!label) return;
                    label.textContent = count > 0 ? count + ' dari ' + total + ' konten dipilih' : '';
                }

                var formEl = selectEl.closest('form');
                if (formEl) {
                    formEl.addEventListener('submit', function () {
                        if (!selectEl.tomselect) return;
                        formEl.querySelectorAll('input.js-content-ordered').forEach(function (el) { el.remove(); });
                        selectEl.removeAttribute('name');
                        Array.from(tom.control.querySelectorAll('.item[data-value]')).forEach(function (item) {
                            var inp = document.createElement('input');
                            inp.type = 'hidden';
                            inp.name = 'content[]';
                            inp.value = item.getAttribute('data-value');
                            inp.className = 'js-content-ordered';
                            formEl.appendChild(inp);
                        });
                    });
                }
            });
        });
    </script>
    <script>
        (function () {
            var cb = document.getElementById('has_deadline');
            var fields = document.getElementById('deadline-fields');
            if (!cb || !fields) return;
            cb.addEventListener('change', function () {
                if (this.checked) {
                    fields.style.display = '';
                } else {
                    fields.style.display = 'none';
                    var fd = document.getElementById('from_date');
                    var td = document.getElementById('to_date');
                    if (fd) fd.value = '';
                    if (td) td.value = '';
                }
            });
        })();
    </script>
@endpush
