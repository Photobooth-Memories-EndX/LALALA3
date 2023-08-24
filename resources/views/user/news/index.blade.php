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
      <div class="row g-4">
        <div class="col-12">
          <div class="row row-cards">

            @unless(count($news) == 0)
                @foreach ($news as $list)
                <div class="col-sm-6 col-lg-4">
                <div class="card card-sm">
                    {{-- <a href="#" class="d-block"><img src="{{ asset('storage/news').'/'.$list->image }}" class="card-img-top"></a> --}}
                    <div class="img-responsive img-responsive-2x1" style="background-image: url({{ asset('storage/news').'/'.$list->image }})"></div>
                    <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <div><a href="{{ route('news.user.view', $list->slug) }}">{{ $list->title }}</a></div>
                            <div class="text-muted">{{ $list->created_at }}</div>
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
                        <p>Currently there is no news data yet</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            @endunless

            <div class="card-footer clearfix mt-3">
                {{ $news->links() }}
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

@endsection
