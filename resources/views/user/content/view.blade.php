@php
    $url_ytplayer_js = asset('Tabler/dist/libs/plyr/dist/plyr.min.js?1684106062');
    $url_ytplayer_css = asset('Tabler/dist/libs/plyr/dist/plyr.css?1684106062');
@endphp

@extends('layouts.master')
@section('content')
{{-- <div class="page-header d-print-none">
    <div class="container-xl">
      <div class="row g-2 align-items-center">
        <div class="col">
          <h2 class="page-title">
            Learning Content
          </h2>
        </div>
      </div>
    </div>
</div> --}}

<div class="page-body">
    <div class="container-xl">
        <div class="card card-lg">
            <div class="card-body ">
              <div class="row g-4">
                <div class="col-12 markdown">
                  <h1 class="mb-2">{{ $content->title }}</h1>

                  <div class="d-flex align-items-center fs-5">
                    <div>
                      <div class="text-muted">{{ date('d F Y', strtotime($content->created_at)) }} by {{ $content->author }}. <span class="badge bg-blue text-blue-fg">{{ $content->classification->classification_name }}</span></div>
                    </div>
                    <div class="ms-auto">
                      <a href="#" class="btn text-muted border-0">
                        <!-- Download SVG icon from http://tabler-icons.io/i/eye -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"></path></svg>
                        {{ $content->views }}
                      </a>

                      <a href="#comment" class="btn text-muted border-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-message-circle" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M3 20l1.3 -3.9c-2.324 -3.437 -1.426 -7.872 2.1 -10.374c3.526 -2.501 8.59 -2.296 11.845 .48c3.255 2.777 3.695 7.266 1.029 10.501c-2.666 3.235 -7.615 4.215 -11.574 2.293l-4.7 1"></path></svg>
                        {{ $comment->count() }}
                      </a>

                      @if ($assesment)
                      <a href="{{ route('assesment.user.view', $assesment->slug) }}" class="btn btn-primary">
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
                  </div>

                  <div class="my-3">
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

                                    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.12.313/pdf.min.js"></script>
                                    <script src="https://cdn.jsdelivr.net/gh/dealfonso/pdfjs-viewer@1.0.0/pdfjs-viewer.min.js"></script>
                                    <script>
                                    var pdfjsLib = window["pdfjs-dist/build/pdf"];
                                    pdfjsLib.GlobalWorkerOptions.workerSrc =
                                    "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.12.313/pdf.worker.min.js";

                                    let pdfViewer = new PDFjsViewer($(".pdfjs-viewer"), {
                                                          onDocumentReady: () => {
                                                              var progression = $('#player_progress').val();
                                                              var current_page = (progression * pdfViewer.getPageCount()) / 100;

                                                              if(current_page < pdfViewer.getPageCount()){
                                                                pdfViewer.scrollToPage(current_page);
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
                                    .loadDocument("{{ asset('storage/files/content/' . $content->id . '/' . $getcontentfile . '') }}")
                                    .then(function () {
                                        pdfViewer.setZoom("width");
                                    });

                                    </script>

                                  @endpush

                        @elseif ($content->type == 'YouTube')
                            <div id="player" data-plyr-provider="youtube" data-plyr-embed-id="{{ $content->link }}"></div>
                        @endif

                        <input type="hidden" id="player_progress" value="{{ $content_user->progress }}">
                        <input type="hidden" data-url="{{ route('content.user.progress', $content->slug) }}" id="player_paused">

                     </div>

                    </div>

                    <p>{!! nl2br($content->description) !!}</p>


                </div>
              </div>

            </div>
          </div>
    </div>

    <x-comment :content="$content" :comment="$comment" type="content" />

  </div>

@endsection

@section('style')
<link rel="stylesheet" href="{{ $url_ytplayer_css }}">
</style>
@endsection

@section('script')
<script src={{ $url_ytplayer_js }}></script>
<script>
    $(document).ready(function() {
        // window.Plyr && (new Plyr('#player'));
        const player = new Plyr('#player', {
                            listeners: { //disable fast forward
                                seek (e) {
                                    e.preventDefault();
                                    console.log('prevented');
                                    return false;
                                }
                            }
                        });

        player.on('ready', function() { //alert(player.duration);
            var progression = $('#player_progress').val();
            var current_player_time = (progression * player.duration) / 100;

            if(current_player_time < player.duration){
                player.currentTime = current_player_time;
            }
        })

        player.on('pause', function() {
            $.ajax({
                type: "PUT",
                url: $('#player_paused').data('url'),
                data: {'current_time': player.currentTime, 'duration': player.duration, '_token': '{{ csrf_token() }}' },
                success: function(data){
                    console.log(data.success)
                }
            });
        })

    });
</script>

@endsection
