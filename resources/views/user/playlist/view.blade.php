@php
    $url_ytplayer_js = asset('Tabler/dist/libs/plyr/dist/plyr.min.js?1684106062');
    $url_ytplayer_css = asset('Tabler/dist/libs/plyr/dist/plyr.css?1684106062');
@endphp

@extends('layouts.master')
@section('content')
    <div class="page-body">
        <div class="container-xl">
            <div class="card card-lg">
                <div class="card-body p-5">
                    <div class="row g-4">
                        <div class="col-12 markdown">
                            <h2 class="mb-2">{{ $playlist->title }}</h2>
                            <h1 class="mb-2">{{ $content->title }}</h1>

                            <div class="d-flex align-items-center justify-content-between fs-5">
                                <div class="text-muted">{{ date('d F Y', strtotime($content->created_at)) }} by {{ $content->author }}. <span class="badge bg-blue text-blue-fg">{{ $content->classification->classification_name }}</span></div>

                                @if ($assesment)
                                    <a href="{{ route('assesment.user.view', $assesment->slug) }}" class="btn btn-primary {{ $assessmentAccess == false ? 'disabled' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
                                            <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"></path>
                                            <path d="M9 9l1 0"></path>
                                            <path d="M9 13l6 0"></path>
                                            <path d="M9 17l6 0"></path>
                                        </svg> Evaluation
                                    </a>
                                @endif
                            </div>

                            <div class="row">
                                <div class="col-9">
                                    <div class="mt-3">
                                        @php
                                            $contentfile = json_decode($content->file);
                                        @endphp
                                        <div style="text-align: center" id="contentContainer">
                                            @php
                                                if (isset($contentfile[0])) {
                                                    $getcontentfile = $contentfile[0];
                                                    $displaycontent = 'display:block;';
                                                } elseif ($content->type == 'YouTube') {
                                                    $getcontentfile = '';
                                                    $displaycontent = 'display:none;';
                                                } else {
                                                    $getcontentfile = '';
                                                    $displaycontent = 'display:none;';
                                                    echo 'No file exist';
                                                }
                                            @endphp
                                            @if ($content->type == 'Video')
                                                <video id="player" width="854" height="480" playsinline controls style="{{ $displaycontent }}" style="display: block; margin-left: auto; margin-right: auto;">
                                                    <source src="{{ asset('storage/files/content/' . $content->id . '/' . $getcontentfile . '') }}" type="video/mp4">
                                                    Your browser does not support the video tag.
                                                    <input type="hidden" name="replacecontent" id="replacecontent" value="{{ $getcontentfile }}">
                                                </video>
                                            @elseif ($content->type == 'Document' || $content->type == 'SOP/Regulation')
                                                <div class="row pdfviewer p-0 row h-100">
                                                    <div class="pdftoolbar text-center row m-0 p-0">
                                                    <div class="col-12 col-lg-6 my-1">
                                                        <button class="btn btn-secondary btn-sm btn-first" onclick="pdfViewer.first()"><i class="material-icons-outlined">skip_previous</i></button>
                                                        <button class="btn btn-secondary btn-sm btn-prev" onclick="pdfViewer.prev(); return false;"><i class="material-icons-outlined">navigate_before</i></button>
                                                        <span class="pageno"></span>
                                                        <button class="btn btn-secondary btn-sm btn-next" onclick="pdfViewer.next(); return false;"><i class="material-icons-outlined">navigate_next</i></button>
                                                        <button class="btn btn-secondary btn-sm btn-last" onclick="pdfViewer.last()"><i class="material-icons-outlined">skip_next</i></button>
                                                    </div>
                                                    <div class="col-12 col-lg-6 my-1">
                                                        <button class="btn btn-secondary btn-sm" onclick="pdfViewer.setZoom('out')"><i class="material-icons-outlined">zoom_out</i></button>
                                                        <span class="zoomval">100%</span>
                                                        <button class="btn btn-secondary btn-sm" onclick="pdfViewer.setZoom('in')"><i class="material-icons-outlined">zoom_in</i></button>
                                                        <button class="btn btn-secondary btn-sm ms-3" onclick="pdfViewer.setZoom('width')"><i class="material-icons-outlined">swap_horiz</i></button>
                                                        <button class="btn btn-secondary btn-sm" onclick="pdfViewer.setZoom('height')"><i class="material-icons-outlined">swap_vert</i></button>
                                                        <button class="btn btn-secondary btn-sm" onclick="pdfViewer.setZoom('fit')"><i class="material-icons-outlined">fit_screen</i></button>
                                                    </div>
                                                    </div>
                                                    <div class="pdfjs-viewer h-100"></div>
                                                </div>

                                                @push('styles')
                                                <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined">
                                                <link rel="stylesheet" href="{{ asset('Tabler/dist/pdfjs-viewer.min.css') }}">
                                                <style>
                                                .pdftoolbar,
                                                .pdftoolbar i {
                                                    font-size: 14px;
                                                }
                                                .pdftoolbar span {
                                                    margin-right: 0.5em;
                                                    margin-left: 0.5em;
                                                    width: 4em !important;
                                                    font-size: 12px;
                                                }
                                                .pdftoolbar .btn-sm {
                                                    padding: 0.12rem 0.25rem;
                                                }
                                                .pdfjs-viewer {
                                                    height: 720px !important;
                                                }
                                                .pdftoolbar {
                                                    width: 100%;
                                                    height: auto;
                                                    background: #ddd;
                                                    z-index: 100;
                                                }
                                                </style>
                                                @endpush

                                                @push('scripts')

                                                    <script src="{{ asset('js/pdf.min.js') }}"></script>
                                                    <script src="{{ asset('js/pdfjs-viewer.min.js') }}"></script>
                                                    <script>
                                                        let fileName        = "{{ "$getcontentfile" }}";
                                                        let fileNameFixed   = fileName.replaceAll(' ', '%20').replaceAll('&amp;', '&');
                                                        let fileLocation    = "{{ asset('storage/files/content/' . $content->id . '/' . ':fileContent' . '') }}";
                                                        let pdfFile         = fileLocation.replace(':fileContent', fileNameFixed);

                                                        var pdfjsLib = window["pdfjs-dist/build/pdf"];
                                                        pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('js/pdf.worker.min.js') }}";

                                                        let pdfViewer = new PDFjsViewer($(".pdfjs-viewer"), {
                                                            onDocumentReady: () => {
                                                                var progression = {{ (int)$content_user->progress }};
                                                                var current_page = (progression * pdfViewer.getPageCount()) / 100;

                                                                if(parseInt(current_page) < pdfViewer.getPageCount()){
                                                                    pdfViewer.scrollToPage(parseInt(current_page));
                                                                }
                                                            },
                                                            onZoomChange: function (zoom) {
                                                                zoom = parseInt(zoom * 10000) / 100;
                                                                $(".zoomval").text(zoom + "%");
                                                            },
                                                            onActivePageChanged: function (page, pageno) {
                                                                $(".pageno").text(pageno + "/" + this.getPageCount());
                                                                $.ajax({
                                                                    type: "PUT",
                                                                    url: $('#player_paused').data('url'),
                                                                    data: {'current_time': pageno, 'duration': this.getPageCount(), '_token': '{{ csrf_token() }}' },
                                                                    success: function(data){
                                                                        console.log(data.success)
                                                                    }
                                                                });
                                                            }
                                                        });

                                                        pdfViewer
                                                        .loadDocument(pdfFile)
                                                        .then(function () {
                                                            pdfViewer.setZoom("width");
                                                        });
                                                    </script>

                                                @endpush

                                            @elseif ($content->type == 'YouTube')
                                                <div id="player" data-plyr-provider="youtube" data-plyr-embed-id="{{ $content->link }}"></div>
                                            @endif

                                            @if (($content->type == 'Video') || ($content->type == 'YouTube'))
                                            @php
                                                $time_duration = explode(':', $content->duration);
                                                $minutes = (int)$time_duration[0];
                                                $seconds = (int)$time_duration[1];
                                            @endphp
                                            <input type="hidden" id="player_duration" value="{{ ($minutes*60)+$seconds }}">
                                            @endif
                                            <input type="hidden" id="player_progress" value="{{ $content_user->progress }}">
                                            <input type="hidden" data-url="{{ route('content.user.progress', $content->slug) }}" id="player_paused">

                                        </div>
                                    </div>

                                    <div class="my-2">
                                        <div class="row">
                                            <div class="col-6">
                                                <a href="#" class="btn text-muted border-0 shadow-none">
                                                    <!-- Download SVG icon from http://tabler-icons.io/i/eye -->
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path>
                                                        <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"></path>
                                                    </svg>
                                                    {{ $content->views }}
                                                </a>

                                                <a href="#comment" class="btn text-muted border-0 shadow-none">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-message-circle" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                        <path d="M3 20l1.3 -3.9c-2.324 -3.437 -1.426 -7.872 2.1 -10.374c3.526 -2.501 8.59 -2.296 11.845 .48c3.255 2.777 3.695 7.266 1.029 10.501c-2.666 3.235 -7.615 4.215 -11.574 2.293l-4.7 1"></path>
                                                    </svg>
                                                    {{ $comment->count() }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-1">

                                    <p>{!! nl2br($content->description) !!}</p>
                                </div>
                                <div class="col-3">
                                    <div class="card-header">
                                        <h3 class="card-title">On This Playlist</h3>
                                    </div>
                                    <div class="list-group list-group-flush list-group-hoverable overflow-auto" style="max-height: 40rem">
                                        @foreach ($content_all as $list)
                                            @if ($list->content->slug != null)
                                                <div class="list-group-item" @if ($content->slug == $list->content->slug) style="background-color: #ededed" @endif data-bs-trigger="hover" data-bs-toggle="popover" title="{{ $list->content->title }}" data-bs-html="true" data-bs-content="{{ $list->content->description }}">
                                                    <div class="row align-items-center">
                                                        <div class="col-auto">
                                                            @if ($list->content->type == 'YouTube')
                                                                <span class="avatar" style="background-image: url(http://img.youtube.com/vi/{{ $list->content->link }}/mqdefault.jpg)"></span>
                                                            @else
                                                                @php
                                                                    $src = 'storage/files/content/' . $list->content->id . '/thumb.jpg';
                                                                @endphp
                                                                @if (\File::exists(public_path($src)) && !is_dir($src))
                                                                    <span class="avatar" style="background-image: url({{ asset($src) }})"></span>
                                                                @else
                                                                    <span class="avatar" style="background-image: url({{ asset('default-video.jpg') }})"></span>
                                                                @endif
                                                            @endif
                                                        </div>
                                                        <div class="col text-truncate">
                                                            <a href="{{ route('playlist.user.view', [$playlist->slug, $list->content->slug]) }}" class="text-reset d-block" style="font-size: 1.75vh;">{{ $list->content->title }}</a>
                                                            <div class="d-block text-muted text-truncate mt-n1" style="font-size: 1.75vh;">{{ $list->content->description }}</div>
                                                        </div>
                                                        {{-- <div class="progress progress-xs" data-bs-toggle="tooltip" title="{{ $list->progress }}%" data-bs-placement="top">
                                                            <div class="progress-bar bg-primary" style="width: {{ $list->progress }}%"></div>
                                                        </div> --}}
                                                        <div class="col-auto">
                                                            @if ($content->slug == $list->content->slug)
                                                                <div class="now playing" id="music">
                                                                    <span class="bar n1">A</span>
                                                                    <span class="bar n2">B</span>
                                                                    <span class="bar n3">c</span>
                                                                    <span class="bar n4">D</span>
                                                                    {{-- <span class="bar n5">E</span>
                                                                    <span class="bar n6">F</span>
                                                                    <span class="bar n7">G</span>
                                                                    <span class="bar n8">H</span> --}}
                                                                </div>
                                                                {{-- Now Playing --}}
                                                            @endif
                                                            {{-- <a href="#" class="list-group-item-actions"><!-- Download SVG icon from http://tabler-icons.io/i/star -->
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon text-muted" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                <path d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" />
                                                            </svg>
                                                        </a> --}}
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <x-comment :content="$content" :comment="$comment" playlistslug="{{ $playlist->slug }}" type="contentplaylist" />

    </div>
@endsection

@section('style')
    <link rel="stylesheet" href="{{ $url_ytplayer_css }}">
    <style>
        .playing {
            position: absolute;

            width: auto;
            transform: translateX(-50%) translateY(-50%);
        }


        .now.playing .bar {
            display: inline-block;
            position: relative;
            margin-right: 0px;
            width: 2px;
            height: 1px;
            overflow: hidden;
            background: linear-gradient(to bottom, #ff9500, #ff5e3a);
            color: transparent;
            animation-name: pulse;
            animation-duration: 1s;
            animation-iteration-count: infinite;
        }

        .paused.playing .bar {
            animation-iteration-count: 1;
        }

        .paused.playing:after {
            content: "Paused (Lame)"
        }

        .n1 {
            animation-delay: 0.5s;
        }

        .n2 {
            animation-delay: 0.2s;
        }

        .n3 {
            animation-delay: 1.2s;
        }

        .n4 {
            animation-delay: 0.9s;
        }

        .n5 {
            animation-delay: 2.3s;
        }

        .n6 {
            animation-delay: 1.3s;
        }

        .n7 {
            animation-delay: 3.1s;
        }

        .n8 {
            animation-delay: 1.9s;
        }

        @keyframes pulse {
            0% {
                height: 1px;
                margin-top: 0;
            }

            10% {
                height: 20px;
                margin-top: -20px;
            }

            50% {
                height: 10px;
                margin-top: -10px;
            }

            60% {
                height: 15px;
                margin-top: -15px;
            }

            80% {
                height: 30px;
                margin-top: -30px;
            }

            100% {
                height: 1px;
                margin-top: 0;
            }
        }
    </style>
@endsection

@section('script')
    <script src={{ $url_ytplayer_js }}></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // window.Plyr && (new Plyr('#player'));
            @if ($content_user->progress >= 100)
            const player = new Plyr('#player');
            @else
            const player = new Plyr('#player', {
                                listeners: { //disable fast forward
                                    seek (e) {
                                        e.preventDefault();
                                        console.log('prevented');
                                        return false;
                                    }
                                }
                            });
            @endif

            // player.on('ready', function() {
            //     var progression = $('#player_progress').val();
            //     var durasi = $('#player_duration').val();
            //     var current_player_time = (progression * durasi) / 100;

            //     if (current_player_time < durasi) {
            //         player.currentTime = current_player_time;
            //     }
            // })

            player.on('loadeddata', onDataLoaded)
            function onDataLoaded () {

                var progression = $('#player_progress').val();
                var durasi = $('#player_duration').val();
                var current_player_time = (progression * durasi) / 100;

                if(current_player_time < durasi){
                    player.currentTime = current_player_time;
                }

                if (current_player_time <= player.duration) {
                player.off('loadeddata', onDataLoaded)
                player.currentTime = current_player_time
                }
            }

            player.on('pause', function() {
                var durasi = $('#player_duration').val();
                $.ajax({
                    type: "PUT",
                    url: $('#player_paused').data('url'),
                    data: {
                        'current_time': player.currentTime,
                        'duration': durasi,
                        '_token': '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        console.log(data.success)
                    }
                });
            })

        });
    </script>
@endsection
