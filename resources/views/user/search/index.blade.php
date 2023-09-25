@extends('layouts.master')
@section('content')
    <div class="page-wrapper">
        <!-- Page header -->
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <h2 class="page-title">
                            Search results
                        </h2>
                        <div class="text-muted mt-1">{{ $totalcontent + $totalnews + $totaleventphoto }} result</div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Page body -->
        <div class="page-body">
            <div class="container-xl">
                <div class="row g-4">
                    <div class="col-3">
                        {{-- {{ Request::path() }} --}}
                        @php
                            $array_url = explode('/', Request::path());
                        @endphp
                        <form action="./" method="get" autocomplete="off" novalidate>
                            <div class="subheader mb-2">Type</div>
                            <div class="list-group list-group-transparent mb-3">
                                <a class="list-group-item list-group-item-action d-flex align-items-center {{ request()->is('search/all*') ? 'active' : '' }}" href="{{ route('search', 'all') }}?s={{ request('s') }}">
                                    Total Search
                                    <small class="text-muted ms-auto">{{ $totalcontent + $totalnews + $totaleventphoto }}</small>
                                </a>
                                <a class="list-group-item list-group-item-action d-flex align-items-center {{ request()->is('search/content*') ? 'active' : '' }}" href="{{ route('search', 'content') }}?s={{ request('s') }}">
                                    Content
                                    <small class="text-muted ms-auto">{{ $totalcontent }}</small>
                                </a>
                                <a class="list-group-item list-group-item-action d-flex align-items-center {{ request()->is('search/news*') ? 'active' : '' }}" href="{{ route('search', 'news') }}?s={{ request('s') }}">
                                    NEWS
                                    <small class="text-muted ms-auto">{{ $totalnews }}</small>
                                </a>
                                <a class="list-group-item list-group-item-action d-flex align-items-center {{ request()->is('search/eventphoto*') ? 'active' : '' }}" href="{{ route('search', 'eventphoto') }}?s={{ request('s') }}">
                                    Gallery
                                    <small class="text-muted ms-auto">{{ $totaleventphoto }}</small>
                                </a>
                            </div>



                        </form>
                    </div>
                    <div class="col-9">
                        <div class="row row-cards">
                            @foreach ($result as $list)
                                <div class="col-sm-6 col-lg-4">
                                    <div class="card card-sm">
                                        <style>
                                            .cropped-ofp {
                                                width: 420px;
                                                height: 180px;
                                                object-fit: cover;
                                                object-position: 50% 50%;
                                            }
                                        </style>
                                        @php
                                            
                                            $now = time(); // or your date as well
                                            $datediff = $now - strtotime($list->created_at);
                                            $showdate = round($datediff / (60 * 60 * 24));
                                            if ($showdate == 0) {
                                                $posted = 'Today';
                                            } elseif ($showdate == 1) {
                                                $posted = 'Yesterday';
                                            } else {
                                                $posted = $showdate . ' days ago';
                                            }
                                        @endphp
                                        <div class="ribbon bg-red">{{ $list->searchlabel }}</div>
                                        @if ($list->searchtype == 'eventphoto')
                                            <a href="#" class="d-block"><img src="{{ asset('/storage/gallery/photo') }}/{{ $list->id }}/{{ $list->thumbnail }}" class="card-img-top cropped-ofp"></a>
                                        @elseif($list->searchtype == 'news')
                                            <a href="#" class="d-block"><img src="{{ asset('storage/news') . '/' . $list->image }}" class="card-img-top cropped-ofp"></a>
                                        @elseif($list->searchtype == 'content')
                                            @if ($list->type == 'YouTube')
                                                <a href="#" class="d-block"><img src="http://img.youtube.com/vi/{{ $list->link }}/mqdefault.jpg" class="card-img-top cropped-ofp"></a>
                                            @else
                                                @php
                                                    $src = 'storage/files/content/' . $list->id . '/thumb.jpg';
                                                @endphp
                                                @if (\File::exists(public_path($src)) && !is_dir($src))
                                                    <a href="#" class="d-block"><img src="{{ asset($src) }}" class="card-img-top cropped-ofp"></a>
                                                @else
                                                    <a href="#" class="d-block"><img src="{{ asset('default-video.jpg') }}" class="card-img-top cropped-ofp"></a>
                                                @endif
                                            @endif
                                        @endif
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <span class="avatar me-3 rounded" style="background-image: url(./static/avatars/000m.jpg)"></span>
                                                <div>
                                                    <div>{{ $list->title }}</div>
                                                    <div class="text-muted"> Posted
                                                        {{ $posted }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
