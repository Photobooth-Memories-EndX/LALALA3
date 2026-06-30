@extends('layouts.sidebar-master')

@section('content')
    @include('main.monitoring.components.style')

    {{-- Page Header --}}
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Manage / Playlist</div>
                    <h2 class="owl-page-title mb-1">{{ $title }}</h2>
                    <div class="owl-page-subtitle">Seret dan lepas item untuk mengubah urutan konten dalam playlist.</div>
                </div>
                <div class="col-auto ms-auto">
                    <ol class="breadcrumb breadcrumb-arrows mb-0" aria-label="breadcrumbs">
                        <li class="breadcrumb-item"><a href="#">Manage</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('trainingplan.index') }}">Playlist</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('trainingplan.edit', $slug) }}">Edit Playlist</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Re-order</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            @if (session()->has('success'))
                <div class="alert alert-important alert-success alert-dismissible mb-3" role="alert">
                    <div class="d-flex">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M5 12l5 5l10 -10"></path>
                            </svg>
                        </div>
                        <div>{{ session('success') }}</div>
                    </div>
                    <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
                </div>
            @endif

            <div class="row g-3">
                <div class="col-lg-8">
                    <form action="{{ route('trainingplan.update', $slug) }}" method="post" autocomplete="off">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="savetype" value="reorder">

                        <div class="card owl-main-card">
                            <div class="card-header">
                                <h3 class="card-title">Urutan Konten</h3>
                            </div>
                            <div class="card-body">
                                @unless (count($trainingplandetail) == 0)
                                    @foreach ($trainingplandetail as $list)
                                        <input type="hidden" name="order[{{ $list->id }}]"
                                            id="order{{ $list->id }}"
                                            value="{{ $list->content_order }}">
                                    @endforeach

                                    <ul id="sortlist" class="trainingplan-sortlist">
                                        @foreach ($trainingplandetail as $list)
                                            <li data-id="order{{ $list->id }}" class="trainingplan-sortlist-item">
                                                <span class="trainingplan-drag-handle">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                        <path d="M9 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"></path>
                                                        <path d="M9 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"></path>
                                                        <path d="M9 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"></path>
                                                        <path d="M15 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"></path>
                                                        <path d="M15 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"></path>
                                                        <path d="M15 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"></path>
                                                    </svg>
                                                </span>
                                                <span class="trainingplan-item-badge">{{ $list->content_order + 1 }}</span>
                                                <span class="trainingplan-item-type badge bg-blue-lt me-2">{{ $list->content->type }}</span>
                                                <span class="trainingplan-item-title">{{ $list->content->title }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="text-center py-4">
                                        <p class="text-muted">Tidak ada konten dalam playlist ini.</p>
                                        <a href="{{ route('trainingplan.edit', $slug) }}" class="btn btn-sm btn-primary">
                                            Tambah Konten
                                        </a>
                                    </div>
                                @endunless
                            </div>
                            @unless (count($trainingplandetail) == 0)
                                <div class="card-footer d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Seret item untuk mengubah urutan, lalu klik Save.</small>
                                    <button type="submit" class="btn btn-success" name="action" value="save" formnovalidate>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2z"></path>
                                            <path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                            <path d="M14 4l0 4l-6 0l0 -4"></path>
                                        </svg>
                                        Save Order
                                    </button>
                                </div>
                            @endunless
                        </div>
                    </form>
                </div>

                {{-- Panel Info --}}
                <div class="col-lg-4">
                    <div class="card owl-main-card">
                        <div class="card-header">
                            <h3 class="card-title">Cara Penggunaan</h3>
                        </div>
                        <div class="card-body">
                            <ol class="text-muted" style="font-size: 13px; padding-left: 1.2rem; line-height: 1.8;">
                                <li>Klik dan tahan item pada daftar di sebelah kiri.</li>
                                <li>Seret item ke posisi yang diinginkan.</li>
                                <li>Lepaskan untuk menempatkan item di posisi baru.</li>
                                <li>Klik <strong>Save Order</strong> untuk menyimpan perubahan urutan.</li>
                            </ol>
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('trainingplan.edit', $slug) }}" class="btn btn-light w-100">
                                Kembali ke Edit Playlist
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection

@section('style')
    <style>
        /* Sort list container */
        .trainingplan-sortlist {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        /* Sort list item */
        .trainingplan-sortlist-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            margin-bottom: 8px;
            border-radius: 10px;
            border: 1px solid rgba(98, 105, 118, .16);
            background: var(--tblr-card-bg, #ffffff);
            cursor: grab;
            transition: background .15s ease, box-shadow .15s ease;
            font-size: 13px;
        }

        .trainingplan-sortlist-item:last-child { margin-bottom: 0; }

        .trainingplan-sortlist-item:hover {
            background: #f8fafc;
            box-shadow: 0 4px 12px rgba(0,0,0,.06);
        }

        /* Drag handle icon */
        .trainingplan-drag-handle {
            color: var(--tblr-muted, #94a3b8);
            flex-shrink: 0;
        }

        /* Number badge */
        .trainingplan-item-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--tblr-primary);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .trainingplan-item-title {
            flex: 1;
            font-weight: 500;
            color: var(--tblr-body-color);
        }

        /* Drag hint state */
        .trainingplan-sortlist-item.hint {
            border-color: #ffc49a;
            background: #fffbf2;
        }

        /* Drag active state */
        .trainingplan-sortlist-item.active {
            border-color: #ffa5a5;
            background: #fff5f5;
            box-shadow: 0 6px 20px rgba(255, 100, 100, .15);
        }

        /* Dark mode */
        [data-theme="dark"] .trainingplan-sortlist-item,
        body.theme-dark .trainingplan-sortlist-item {
            background: #182433 !important;
            border-color: #25384d !important;
            color: #dce6f1 !important;
        }

        [data-theme="dark"] .trainingplan-sortlist-item:hover,
        body.theme-dark .trainingplan-sortlist-item:hover {
            background: #223247 !important;
        }

        [data-theme="dark"] .trainingplan-item-title,
        body.theme-dark .trainingplan-item-title {
            color: #dce6f1 !important;
        }

        [data-theme="dark"] .trainingplan-sortlist-item.hint,
        body.theme-dark .trainingplan-sortlist-item.hint {
            border-color: #ffc49a !important;
            background: #2a2010 !important;
        }

        [data-theme="dark"] .trainingplan-sortlist-item.active,
        body.theme-dark .trainingplan-sortlist-item.active {
            border-color: #ffa5a5 !important;
            background: #2a1010 !important;
        }
    </style>
@endsection

@section('script')
    <script>
        function slist(target) {
            // (A) SET CSS + GET ALL LIST ITEMS
            target.classList.add("slist");
            let items = target.getElementsByTagName("li"),
                current = null;

            // (B) MAKE ITEMS DRAGGABLE + SORTABLE
            for (let i of items) {
                // (B1) ATTACH DRAGGABLE
                i.draggable = true;

                // (B2) DRAG START - YELLOW HIGHLIGHT DROPZONES
                i.ondragstart = e => {
                    current = i;
                    for (let it of items) {
                        if (it != current) {
                            it.classList.add("hint");
                        }
                    }
                };

                // (B3) DRAG ENTER - RED HIGHLIGHT DROPZONE
                i.ondragenter = e => {
                    if (i != current) {
                        i.classList.add("active");
                    }
                };

                // (B4) DRAG LEAVE - REMOVE RED HIGHLIGHT
                i.ondragleave = () => i.classList.remove("active");

                // (B5) DRAG END - REMOVE ALL HIGHLIGHTS
                i.ondragend = () => {
                    for (let it of items) {
                        it.classList.remove("hint");
                        it.classList.remove("active");
                    }
                };

                // (B6) DRAG OVER - PREVENT THE DEFAULT "DROP", SO WE CAN DO OUR OWN
                i.ondragover = e => e.preventDefault();

                // (B7) ON DROP - DO SOMETHING
                i.ondrop = e => {
                    e.preventDefault();
                    if (i != current) {
                        let currentpos = 0,
                            droppedpos = 0;
                        for (let it = 0; it < items.length; it++) {
                            if (current == items[it]) { currentpos = it; }
                            if (i == items[it]) { droppedpos = it; }
                        }
                        if (currentpos < droppedpos) {
                            i.parentNode.insertBefore(current, i.nextSibling);
                        } else {
                            i.parentNode.insertBefore(current, i);
                        }
                        let holdid = $(current).attr("data-id");
                        let replaceid = $(i).attr("data-id");
                        console.log(holdid + "|" + droppedpos);
                        console.log(replaceid + "|" + currentpos);
                        document.getElementById(holdid).value = droppedpos;
                        document.getElementById(replaceid).value = currentpos;
                    }
                };
            }
        }

        var sortEl = document.getElementById("sortlist");
        if (sortEl) { slist(sortEl); }
    </script>
@endsection
