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
                      <a href="#" class="text-muted">
                        <!-- Download SVG icon from http://tabler-icons.io/i/eye -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"></path></svg>
                        {{ $news->views }}
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
  </div>

@endsection
