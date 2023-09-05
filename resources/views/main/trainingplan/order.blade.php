@extends('layouts.master')
@section('content')

    {{--  <link href="./dist/css/tabler-flags.min.css?1684106062" rel="stylesheet"/>
        <link href="./dist/css/tabler-payments.min.css?1684106062" rel="stylesheet"/>
        <link href="./dist/css/tabler-vendors.min.css?1684106062" rel="stylesheet"/> --}}
    {{-- @push('css')
        <link rel="stylesheet" href="{{ $url_dropzone_css }}">
    @endpush --}}
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        {{ $title }}
                    </h2>
                </div>
                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('trainingplan.index') }}">Training Plan</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('trainingplan.edit', $slug) }}">Edit Training Plan</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">{{ $title }}</a>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page body -->
    <div class="page-body">
        <!-- Container Page -->
        <div class="page page-center">
            {{-- <div class="container container-tight py-4"> --}}
            <div class="container container-sm py-4">
                @if (session()->has('success'))
                    <div class="alert alert-important alert-success alert-dismissible" role="alert">
                        <div class="d-flex">
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M5 12l5 5l10 -10"></path>
                                </svg>
                            </div>
                            <div>
                                {{ session('success') }}
                            </div>
                        </div>
                        <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                @endif

                <form class="card card-md" action="{{ route('trainingplan.update', $slug) }}" method="post" autocomplete="off">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <input type="hidden" name="savetype" value="reorder">
                        <h2 class="card-title text-center mb-4">Edit Content order</h2>

                        <div class="row">
                            <div class="col-xl-8">
                                <div class="mb-3">
                                    <label class="form-label">Content order</label>
                                    <ul id="sortlist">


                                        {{-- {{ $trainingplan->trainingplandetail }} --}}
                                        @unless (count($trainingplandetail) == 0)
                                            @foreach ($trainingplandetail as $list)
                                                {{-- {{ $list->id }}<br> --}}
                                                <input type="hidden" name="order[{{ $list->id }}]" id="order{{ $list->id }}" value="{{ $list->content_order }}">
                                                <li data-id="order{{ $list->id }}">{{ $list->content->id }} - [{{ $list->content->type }}] {{ $list->content->title }}</li>
                                            @endforeach
                                        @else
                                            <option value="">No content available</option>
                                        @endunless


                                    </ul>
                                </div>
                            </div>
                        </div>



                        <div class="form-footer" style="float: right">
                            <button type="submit" class="btn btn-success" name="action" value="save" formnovalidate>Save</button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
        <!-- Container Page -->
    </div>
    <!-- Page body -->
@endsection

@section('style')
    <style>
        /* (A) LIST STYLES */
        .slist {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .slist li {
            margin: 10px;
            padding: 15px;
            border: 1px solid #dfdfdf;
            background: #f5f5f5;
        }

        /* (B) DRAG-AND-DROP HINT */
        .slist li.hint {
            border: 1px solid #ffc49a;
            background: #feffb4;
        }

        .slist li.active {
            border: 1px solid #ffa5a5;
            background: #ffe7e7;
        }
    </style>
@endsection

@section('script')
    <script></script>
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
                            if (current == items[it]) {
                                currentpos = it;
                            }
                            if (i == items[it]) {
                                droppedpos = it;
                            }
                        }
                        if (currentpos < droppedpos) {
                            i.parentNode.insertBefore(current, i.nextSibling);
                        } else {
                            i.parentNode.insertBefore(current, i);
                        }
                        // string aaa = ;
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
        slist(document.getElementById("sortlist"));
    </script>
@endsection
