@extends('layouts.master')
@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
      <div class="row g-2 align-items-center">
        <div class="col">
          <h2 class="page-title">
            News
          </h2>
        </div>
      </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card card-lg">
            <div class="card-body ">
              <div class="row g-4">
                <div class="col-12 markdown">
                  <h1 class="mb-2">{{ $news->title }}</h1>

                  <div class="d-flex align-items-center">
                    <div>
                      <div class="text-muted">{{ date('d F Y', strtotime($news->created_at)) }} by {{ $news->user->name }}</div>
                    </div>
                    <div class="ms-auto">
                      <a href="#" class="btn text-muted border-0">
                        <!-- Download SVG icon from http://tabler-icons.io/i/eye -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"></path></svg>
                        {{ $news->views }}
                      </a>
                      <a href="#comment" class="btn text-muted border-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-message-circle" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M3 20l1.3 -3.9c-2.324 -3.437 -1.426 -7.872 2.1 -10.374c3.526 -2.501 8.59 -2.296 11.845 .48c3.255 2.777 3.695 7.266 1.029 10.501c-2.666 3.235 -7.615 4.215 -11.574 2.293l-4.7 1"></path></svg>
                        {{ $comment->count() }}
                      </a>
                    </div>
                  </div>
                  {{-- <div class="img-responsive" style="background-image: url({{ asset('storage/news').'/'.$news->image }})"></div> --}}
                  <div class="mb-3 mt-3">
                    <img src="{{ asset('storage/news').'/'.$news->image }}" alt="{{ $news->slug }}" class="img-fluid">
                  </div>
                  <p>{!! nl2br($news->description) !!}</p>
                </div>
              </div>
            </div>
          </div>
    </div>

    <x-comment :content="$news" :comment="$comment" type="news" />

  </div>

@endsection
