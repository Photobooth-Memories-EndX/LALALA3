@extends('layouts.master')
@section('content')

@php
    $content_video = $content->filter(function ($list){
                            return in_array($list->type, ['YouTube', 'Video']);
                    });

    $content_pdf = $content->filter(function ($list){
                            return $list->type=='Document';
                            // return in_array($list->type, ['Document']);
                    });
@endphp

<div class="page-body">
    <div class="container-xl">
      <div class="card">
        <div class="row g-0">

          <div class="col d-flex flex-column border-end">
            <div class="card-body">
              <h2 class="mb-4">{{ $category->classification_name }} Video</h2>

            <div class="card card-md">
                <div class="card-body p-3">

                    <div class="row">

                        @unless(count($content_video) == 0)
                            @foreach ($content_video as $item)

                            <div class="col-md-3">
                            <div class="card border-0">
                                @if ($item->type == "YouTube")
                                <div class="img-responsive img-responsive-4x3" style="background-image: url(http://img.youtube.com/vi/{{ $item->link }}/mqdefault.jpg); position:relative;"><div class="ribbon bg-red ribbon-bottom">{{ $item->duration }}</div></div>
                                @else
                                @php
                                    $src = 'storage/files/content/'.$item->id.'/thumb.jpg';
                                @endphp
                                    @if (\File::exists(public_path($src)) && !is_dir($src))
                                    <div class="img-responsive img-responsive-4x3" style="background-image: url({{ asset($src) }});position:relative;"><div class="ribbon bg-red ribbon-bottom">{{ $item->duration }}</div></div>
                                    @else
                                    <div class="img-responsive img-responsive-4x3" style="background-image: url({{ asset('default-video.jpg') }});position:relative;"><div class="ribbon bg-red ribbon-bottom">{{ $item->duration }}</div></div>
                                    @endif

                                @endif

                                <div class="card-body py-2 px-0">
                                <h3 class="card-title h4"><a href="{{ route('content.user.view', $item->slug) }}">{{ Str::limit($item->title, 100) }}</a></h3>
                                </div>
                            </div>
                            </div>

                            @endforeach
                        @else
                        <div class="col">
                            There are no videos in this category yet
                        </div>
                        @endunless

                    </div>

                </div>
            </div>

            <h2 class="mb-4 mt-5">{{ $category->classification_name }} Documents</h2>
            <div class="row row-cards">
                <div class="card-body border m-2 p-3">
                <div class="row">
                @unless(count($content_pdf) == 0)
                  @foreach ($content_pdf as $item)
                  <div class="col-md-3">
                    <div class="card card-md border-0">

                        @php
                            $src = 'storage/files/content/'.$item->id.'/thumb.jpg';
                        @endphp
                            @if (\File::exists(public_path($src)) && !is_dir($src))
                            <div class="img-responsive img-responsive-4x3" style="background-image: url({{ asset($src) }});position:relative;"></div>
                            @else
                            <div class="img-responsive img-responsive-4x3" style="background-image: url({{ asset('pdf.png') }});position:relative;"></div>
                            @endif

                      <div class="card-body py-2 px-0">
                        <h3 class="card-title h4"><a href="{{ route('content.user.view', $item->slug) }}">{{ Str::limit($item->title, 80) }}</a></h3>
                      </div>
                    </div>
                  </div>
                  @endforeach
                @else
                <div class="col">
                    There are no documents in this category yet
                </div>
                @endunless
                </div>
                </div>

            </div>

          </div>

        </div>

      </div>
    </div>
  </div>

@endsection

@section('style')
<link href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}" rel="stylesheet"/>

@endsection

@section('script')

@endsection
