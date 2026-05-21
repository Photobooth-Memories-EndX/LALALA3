@extends('layouts.sidebar-master')
@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Event Gallery
                    </h2>
                    {{-- <div class="text-muted mt-1">1-12 of 241 photos</div> --}}
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row g-4">
                <div class="col-12">
                    <div class="row row-cards">
                        <style>
                            .cropped-ofp {
                                width: 420px;
                                height: 280px;
                                object-fit: cover;
                                object-position: 50% 50%;
                            }
                        </style>
                        @unless (count($eventphoto) == 0)
                            @foreach ($eventphoto as $list)
                                <div class="col-sm-6 col-lg-4">
                                    <div class="card card-sm">
                                        @php
                                            $ribbon = '';
                                            $now = time(); // or your date as well
                                            $datediff = $now - strtotime($list->created_at);
                                            $showdate = round($datediff / (60 * 60 * 24));
                                            if ($showdate == 0) {
                                                $posted = 'Today';
                                                $ribbon = "<div class=\"ribbon bg-red\">NEW</div>";
                                            } elseif ($showdate == 1) {
                                                $posted = 'Yesterday';
                                            } else {
                                                $posted = $showdate . ' days ago';
                                            }
                                        @endphp
                                        <?= $ribbon ?>
                                        <a href="{{ route('gallery.user.view', $list->slug) }}" class="d-block"><img src="{{ asset('/storage/gallery/photo') }}/{{ $list->id }}/{{ $list->thumbnail }}" class="card-img-top cropped-ofp"></a>
                                        <div class="card-body">
                                            <h2>{{ $list->title }}</h2>
                                            <div class="d-flex align-items-center">
                                                {{-- <span class="avatar me-3 rounded" style="background-image: url({{ asset('/storage/profile') }}/23.png)"></span> --}}
                                                <div>
                                                    <div>by {{ $list->uploader }}</div>
                                                    <div class="text-muted">
                                                        Posted
                                                        {{ $posted }}
                                                    </div>
                                                </div>
                                                <div class="ms-auto">
                                                    <a href="#" class="text-muted">
                                                        <!-- Download SVG icon from http://tabler-icons.io/i/eye -->
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                                            <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                                        </svg>
                                                        {{ $list->views }}
                                                    </a>
                                                    <a href="#" class="ms-3 text-muted">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-message-circle" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M3 20l1.3 -3.9c-2.324 -3.437 -1.426 -7.872 2.1 -10.374c3.526 -2.501 8.59 -2.296 11.845 .48c3.255 2.777 3.695 7.266 1.029 10.501c-2.666 3.235 -7.615 4.215 -11.574 2.293l-4.7 1"></path></svg>
                                                        {{ $list->comments_count }}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="col-sm-12 col-lg-12">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <p>Currently there is no gallery data yet</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endunless

                        <div class="card-footer clearfix mt-3">

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
