@extends('layouts.master')
@section('content')
    @php
        $url_lightbox_js = asset('Tabler/dist/libs/fslightbox/index.js?1684106062');
        $search = ['[', ']', '"'];
        $arraypic = explode(',', $eventphoto->photos);
        
    @endphp
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        {{ $eventphoto->title }}
                    </h2>
                    <div class="text-muted mt-1">
                        {{ count($arraypic) }} photos (ifadmin)<a href="{{ route('eventphoto.edit', $eventphoto->slug) }}">Manage</a>(/ifadmin)
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cols-6 g-3">
                @foreach ($arraypic as $listphoto)
                    <div class="col">
                        <a data-fslightbox="gallery" href="{{ asset('storage/gallery/photo/' . $eventphoto->id . '/' . str_replace($search, '', $listphoto)) }}">
                            <!-- Photo -->
                            <div class="img-responsive img-responsive-1x1 rounded border" style="background-image: url({{ asset('storage/gallery/photo/' . $eventphoto->id . '/' . str_replace($search, '', $listphoto)) }})"></div>
                        </a>
                    </div>
                @endforeach
                {{-- </div> --}}
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src={{ $url_lightbox_js }}></script>
@endsection
