@extends('layouts.sidebar-master')
@section('content')
    <!-- Page header -->
    <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">

                    <div class="col-3">
                        <h2 class="page-title">
                            Search results
                        </h2>
                        <div class="text-muted mt-1">{{ $totalcontent + $totalnews + $totaleventphoto }} result</div>
                    </div>
                    <div class="col">
                        <form action="{{ route('search', 'all') }}" method="get" autocomplete="off" novalidate>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <!-- Download SVG icon from http://tabler-icons.io/i/search -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                        <path d="M21 21l-6 -6" />
                                    </svg>
                                </span>
                                <input type="text" name="s" class="form-control" placeholder="Search…" aria-label="Search in website" value="{{ app('request')->input('s') }}">
                            </div>
                        </form>
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
                                <a class="list-group-item list-group-item-action d-flex align-items-center {{ request()->is('search/playlist*') ? 'active' : '' }}" href="{{ route('search', 'playlist') }}?s={{ request('s') }}">
                                    Playlist
                                    <small class="text-muted ms-auto">{{ $totalplaylist }}</small>
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

                                        @if ($list->searchtype == 'eventphoto')
                                            <div class="ribbon bg-orange">{{ $list->searchlabel }}</div>
                                            <a href="{{ route('gallery.user.view', $list->slug) }}" class="d-block"><img src="{{ asset('/storage/gallery/photo') }}/{{ $list->id }}/{{ $list->thumbnail }}" class="card-img-top cropped-ofp"></a>
                                        @elseif($list->searchtype == 'news')
                                            <div class="ribbon bg-lime">{{ $list->searchlabel }}</div>
                                            <a href="{{ route('news.user.view', $list->slug) }}" class="d-block"><img src="{{ asset('storage/news') . '/' . $list->image }}" class="card-img-top cropped-ofp"></a>
                                        @elseif($list->searchtype == 'content')
                                            <div class="ribbon bg-cyan">{{ $list->searchlabel }}</div>
                                            @if ($list->type == 'YouTube')
                                                <a href="{{ route('content.user.view', $list->slug) }}" class="d-block"><img src="http://img.youtube.com/vi/{{ $list->link }}/mqdefault.jpg" class="card-img-top cropped-ofp"></a>
                                            @else
                                                @php
                                                    $src = 'storage/files/content/' . $list->id . '/thumb.jpg';
                                                @endphp
                                                @if (\File::exists(public_path($src)) && !is_dir($src))
                                                    <a href="{{ route('content.user.view', $list->slug) }}" class="d-block"><img src="{{ asset($src) }}" class="card-img-top cropped-ofp"></a>
                                                @else
                                                    <a href="{{ route('content.user.view', $list->slug) }}" class="d-block"><img src="{{ asset('default-video.jpg') }}" class="card-img-top cropped-ofp"></a>
                                                @endif
                                            @endif
                                        @elseif($list->searchtype == 'playlist')
                                            <div class="ribbon bg-purple">{{ $list->searchlabel }}</div>
                                            <a href="{{ route('playlist.user.view', [$list->slug, $list->trainingplandetail[0]->content->slug]) }}" class="d-block">
                                                @if ($list->trainingplandetail[0]->content->type == 'YouTube')
                                                    <img src="http://img.youtube.com/vi/{{ $list->trainingplandetail[0]->content->link }}/mqdefault.jpg" class="card-img-top cropped-ofp">
                                                @else
                                                    @php
                                                        $src = 'storage/files/content/' . $list->trainingplandetail[0]->content->id . '/thumb.jpg';
                                                    @endphp
                                                    @if (\File::exists(public_path($src)) && !is_dir($src))
                                                        <img src="{{ asset($src) }}" class="card-img-top cropped-ofp">
                                                    @else
                                                        <img src="{{ asset('default-video.jpg') }}" class="card-img-top cropped-ofp">
                                                    @endif
                                                @endif
                                                {{-- <img src="{{ asset('storage/news') . '/' . $list->trainingplandetail[0]->content }}" class="card-img-top cropped-ofp"> --}}

                                            </a>
                                        @endif
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
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
@endsection
