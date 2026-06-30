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
                    <div class="owl-page-subtitle">
                        {{ $trainingplan->title }}
                        <span class="badge bg-{{ $trainingplan->active == 'Active' ? 'success' : 'warning' }}-lt ms-2">
                            {{ $trainingplan->active }}
                        </span>
                    </div>
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
            <form action="{{ route('trainingplan.update', $trainingplan->slug) }}" method="post" autocomplete="off" id="trainingPlanForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="savetype" value="update">

                <div class="row g-3">

                    {{-- Kolom Kiri: Konten Playlist --}}
                    <div class="col-lg-8">

                        {{-- Informasi Playlist --}}
                        <div class="card owl-main-card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Informasi Playlist</h3>
                            </div>
                            <div class="card-body">

                                <x-alert type="success" />
                                <x-alert type="warning" />

                                <div class="mb-3">
                                    <label class="form-label required">Title</label>
                                    <input type="text" class="form-control" placeholder="Enter playlist title"
                                        name="title" id="title"
                                        value="{{ $trainingplan->title }}" required>
                                </div>
                                <div>
                                    <label for="url-link" class="form-label">URL Link</label>
                                    <input type="url" name="url_link" id="url-link" class="form-control"
                                        value="{{ $trainingplan->url_link }}"
                                        placeholder="https://...">
                                    <small class="form-hint">Opsional. Link eksternal terkait playlist ini.</small>
                                </div>
                            </div>
                        </div>

                        {{-- Konten Playlist --}}
                        <div class="card owl-main-card">
                            <div class="card-header">
                                <h3 class="card-title">Konten Playlist</h3>
                                <div class="card-subtitle text-muted mt-1">
                                    Pilih konten, lalu tarik item untuk mengatur urutan section.
                                    <span class="badge bg-blue-lt ms-2" style="font-size:10px;">Drag &amp; Drop</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <label class="form-label">Content</label>
                                <div class="playlist-content-select-wrapper">
                                    <select class="form-select js-content-select" id="select-content" name="content[]" multiple>
                                        @unless (count($content) == 0)
                                            @foreach ($content as $list)
                                                <option value="{{ $list->id }}"
                                                    @if (in_array($list->id, $activecontent)) selected @endif>
                                                    {{ $list->title }}
                                                </option>
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
                                        Simpan Perubahan Playlist
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Kolom Kanan: Aksi --}}
                    <div class="col-lg-4">
                        <div class="card owl-main-card">
                            <div class="card-header">
                                <h3 class="card-title">Aksi</h3>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    @if ($trainingplan->active == 'Active')
                                        <button type="submit" class="btn btn-warning" name="action" value="unpublish">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                                                <path d="M9 12h6"></path>
                                            </svg>
                                            Unpublish This Playlist
                                        </button>
                                    @else
                                        <a type="button" class="btn btn-secondary" href="{{ route('trainingplan.order', $trainingplan->slug) }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M3 9l4 -4l4 4m-4 -4v14"></path>
                                                <path d="M21 15l-4 4l-4 -4m4 4v-14"></path>
                                            </svg>
                                            Re-order Content
                                        </a>
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
                                    @endif
                                    <a href="{{ route('trainingplan.index') }}" class="btn btn-light">Back to List</a>
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
        var activeContentOrdered = @json(
            $trainingplan->trainingplandetail->sortBy('content_order')->pluck('content_id')->values()->toArray()
        );

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
                        var self = this;
                        self.wrapper.classList.add('playlist-ts-wrapper');

                        if (activeContentOrdered.length > 0) {
                            self.clear(true);
                            activeContentOrdered.forEach(function (id) {
                                self.addItem(String(id), true);
                            });
                        }

                        enablePlaylistSortable(self);
                        updatePlaylistSectionNumbers(self);
                        updateCountLabel(self);
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
@endpush
