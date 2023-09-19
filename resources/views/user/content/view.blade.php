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
                        {{-- {{ $content->views }} --}}101
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
                            <video width="854" height="480" controls style="{{ $displaycontent }}" style="display: block; margin-left: auto; margin-right: auto;">
                                <source src="{{ asset('storage/files/content/' . $content->id . '/' . $getcontentfile . '') }}" type="video/mp4">
                                Your browser does not support the video tag.
                                <input type="hidden" name="replacecontent" id="replacecontent" value="{{ $getcontentfile }}">
                            </video>
                        @elseif ($content->type == 'Document' || $content->type == 'SOP/Regulation')
                            <object data="{{ asset('storage/files/content/' . $content->id . '/' . $getcontentfile . '') }}" width="800px" height="800px" style="text-align: center"></object>
                        @elseif ($content->type == 'YouTube')
                            <div id="player-youtube" data-plyr-provider="youtube" data-plyr-embed-id="{{ $content->link }}"></div>
                        @endif

                     </div>

                    </div>

                    <p>{!! nl2br($content->description) !!}</p>


                </div>
              </div>

              {{-- <div class="mt-5">
                <div class="row">
                  <div class="col-auto">
                    <span class="avatar avatar-md" style="background-image: url({{ asset('Tabler/static/avatars/002m.jpg') }})"></span>
                  </div>
                  <div class="col">
                    <div class="mb-3">
                        <form action="{{ route('content.comment.store', $content->slug) }}" method="post">
                            @csrf
                            @method('post')
                            <textarea class="form-control" name="description" data-bs-toggle="autosize" placeholder="Enter your thoughts.…" onkeyup="countWord(this)" maxlength="500" cols="500"></textarea>
                            <div class="row mt-3">
                                <div class="col">
                                    <span id="note_remaining" class="text-muted small">500 characters remaining</span>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-success" type="submit">Submit comment</button>
                                </div>
                            </div>
                        </form>
                    </div>
                  </div>
                </div>
              </div> --}}

            </div>
          </div>
    </div>

    <x-comment :content="$content" :comment="$comment" type="content" />

  </div>

@endsection

@section('style')
<link rel="stylesheet" href="{{ $url_ytplayer_css }}">
@endsection

@section('script')
<script src={{ $url_ytplayer_js }}></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        window.Plyr && (new Plyr('#player-youtube'));
    });
</script>
@endsection
