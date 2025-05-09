@extends('layouts.master')

@section('content')
    @php
        $content_video = $content->filter(function ($list){
            return in_array($list->type, ['YouTube', 'Video']);
        });

        $content_pdf = $content->filter(function ($list){
            return $list->type=='Document';
        });
    @endphp

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="row g-0">

                    <div class="col d-flex flex-column border-end">
                        <div class="card-body">
                            <div class="">
                                <a href="javascript:;" class="btn btn-warning px-5" id="btn-assessment-access"><i class="fas fa-clipboard-list me-2"></i>Assessment Access</a>
                                <hr class="my-2">
                            </div>

                            <h2 class="mb-4">TOP VIDEO LEARNING</h2>

                            <div class="card card-md">
                                <div class="card-body p-3">
                                    <div class="col">
                                        <div id="carousel-topvideo" class="carousel slide c-home" data-bs-ride="carousel">
                                            <div class="carousel-inner">
                                                @foreach ($top_video as $item)
                                                    <div class="carousel-item {{ ($loop->first) ? 'active' : '' }}">
                                                        <div class="col-md-3 p-2">
                                                            <div class="card border-0">
                                                                @if ($item->type == "YouTube")
                                                                    <div class="img-responsive img-responsive-4x3" style="background-image: url(http://img.youtube.com/vi/{{ $item->link }}/mqdefault.jpg); position:relative;">
                                                                        <div class="ribbon bg-red ribbon-bottom">{{ $item->duration }}</div>
                                                                    </div>
                                                                @else
                                                                    @php
                                                                        $src = 'storage/files/content/'.$item->id.'/thumb.jpg';
                                                                    @endphp
                                                                    @if (\File::exists(public_path($src)) && !is_dir($src))
                                                                        <div class="img-responsive img-responsive-4x3" style="background-image: url({{ asset($src) }});position:relative;">
                                                                            <div class="ribbon bg-red ribbon-bottom">{{ $item->duration }}</div>
                                                                        </div>
                                                                    @else
                                                                        <div class="img-responsive img-responsive-4x3" style="background-image: url({{ asset('default-video.jpg') }});position:relative;">
                                                                            <div class="ribbon bg-red ribbon-bottom">{{ $item->duration }}</div>
                                                                        </div>
                                                                    @endif
                                                                @endif
                                                                <div class="card-body py-2 px-0">
                                                                    <h3 class="card-title h4">
                                                                        <a href="{{ route('content.user.view', $item->slug) }}">{{ Str::limit($item->title, 70) }}</a>
                                                                    </h3>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <a class="carousel-control-prev" href="#carousel-topvideo" role="button" data-bs-slide="prev">
                                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Previous</span>
                                            </a>
                                            <a class="carousel-control-next" href="#carousel-topvideo" role="button" data-bs-slide="next">
                                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Next</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h2 class="mb-4 mt-5">Training Module</h2>

                            <div class="card">
                                <div class="card-header">
                                    <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                                        <li class="nav-item">
                                            <a href="#tabs-video" class="nav-link active" data-bs-toggle="tab">Video</a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#tabs-document" class="nav-link" data-bs-toggle="tab">Document</a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#tabs-playlist" class="nav-link" data-bs-toggle="tab">Playlist</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <div class="tab-content">
                                        <!-- TABS ALL VIDEO : Start -->
                                        <div class="tab-pane active show" id="tabs-video">
                                            <div class="card-body p-3 px-0">
                                                <div class="col">
                                                    <div id="carousel-videos" class="carousel slide c-home" data-bs-ride="carousel">
                                                        <div class="carousel-inner">
                                                            @foreach ($content_video as $item)
                                                                <div class="carousel-item {{ ($loop->first) ? 'active' : '' }}">
                                                                    <div class="col-md-3 p-2">
                                                                        <div class="card border-0">
                                                                            @if ($item->type == "YouTube")
                                                                                <div class="img-responsive img-responsive-4x3" style="background-image: url(http://img.youtube.com/vi/{{ $item->link }}/mqdefault.jpg); position:relative;">
                                                                                    <div class="ribbon bg-red ribbon-bottom">{{ $item->duration }}</div>
                                                                                </div>
                                                                            @else
                                                                                @php
                                                                                    $src = 'storage/files/content/'.$item->id.'/thumb.jpg';
                                                                                @endphp
                                                                                @if (\File::exists(public_path($src)) && !is_dir($src))
                                                                                    <div class="img-responsive img-responsive-4x3" style="background-image: url({{ asset($src) }});position:relative;">
                                                                                        <div class="ribbon bg-red ribbon-bottom">{{ $item->duration }}</div>
                                                                                    </div>
                                                                                @else
                                                                                    <div class="img-responsive img-responsive-4x3" style="background-image: url({{ asset('default-video.jpg') }});position:relative;">
                                                                                        <div class="ribbon bg-red ribbon-bottom">{{ $item->duration }}</div>
                                                                                    </div>
                                                                                @endif
                                                                            @endif
                                                                            <div class="card-body py-2 px-0">
                                                                                <h3 class="card-title h4">
                                                                                    <a href="{{ route('content.user.view', $item->slug) }}">{{ Str::limit($item->title, 70) }}</a>
                                                                                </h3>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        <a class="carousel-control-prev" href="#carousel-videos" role="button" data-bs-slide="prev">
                                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                            <span class="visually-hidden">Previous</span>
                                                        </a>
                                                        <a class="carousel-control-next" href="#carousel-videos" role="button" data-bs-slide="next">
                                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                            <span class="visually-hidden">Next</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- TABS ALL VIDEO : End -->

                                        <!-- TABS ALL Documents : Start -->
                                        <div class="tab-pane" id="tabs-document">
                                            <div class="card-body p-3 px-0">
                                                <div class="col">
                                                    <div id="carousel-documents" class="carousel slide c-home" data-bs-ride="carousel">
                                                        <div class="carousel-inner">
                                                            @foreach ($content_pdf as $pdf)
                                                                <div class="carousel-item {{ ($loop->first) ? 'active' : '' }}">
                                                                    <div class="col-md-3 p-2">
                                                                        <div class="card border-0">
                                                                            @php
                                                                                $src = 'storage/files/content/'.$pdf->id.'/thumb.jpg';
                                                                            @endphp
                                                                            @if (\File::exists(public_path($src)) && !is_dir($src))
                                                                                <div class="img-responsive img-responsive-4x3" style="background-image: url({{ asset($src) }});position:relative;height:150px"></div>
                                                                            @else
                                                                                <div class="img-responsive img-responsive-4x3" style="background-image: url({{ asset('pdf.png') }});position:relative;height:150px"></div>
                                                                            @endif
                                                                            <div class="card-body py-2 px-0">
                                                                                <h3 class="card-title h4">
                                                                                    <a href="{{ route('content.user.view', $pdf->slug) }}">{{ Str::limit($pdf->title, 70) }}</a>
                                                                                </h3>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        <a class="carousel-control-prev" href="#carousel-documents" role="button" data-bs-slide="prev">
                                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                            <span class="visually-hidden">Previous</span>
                                                        </a>
                                                        <a class="carousel-control-next" href="#carousel-documents" role="button" data-bs-slide="next">
                                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                            <span class="visually-hidden">Next</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- TABS ALL Documents : End -->

                                        <!-- TABS ALL PLAYLIST : Start -->
                                        <div class="tab-pane" id="tabs-playlist">
                                            <div class="card-body p-3 px-0">
                                                <div class="col">
                                                    <div id="carousel-playlist" class="carousel slide c-home" data-bs-ride="carousel">
                                                        <div class="carousel-inner">
                                                            @foreach ($playlist as $list)
                                                                <div class="carousel-item {{ ($loop->first) ? 'active' : '' }}">
                                                                    <div class="col-md-3 p-2">
                                                                        <div class="card border-0">
                                                                            Playlist {{ $loop->iteration }}
                                                                            <div class="card-body py-2 px-0">
                                                                                <h3 class="card-title h4">
                                                                                    <a href="{{ route('playlist.user.view', [$list->slug, $list->trainingplandetail[0]->content->slug]) }}">{{ Str::limit($list->title, 70) }}</a>
                                                                                </h3>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        <a class="carousel-control-prev" href="#carousel-playlist" role="button" data-bs-slide="prev">
                                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                            <span class="visually-hidden">Previous</span>
                                                        </a>
                                                        <a class="carousel-control-next" href="#carousel-playlist" role="button" data-bs-slide="next">
                                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                            <span class="visually-hidden">Next</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- TABS ALL PLAYLIST : End -->
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="col-3 d-none d-md-block">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-auto">
                                    <span class="avatar rounded">{{ substr(auth()->user()->initials, 0, 2) }}</span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium">{{ ucwords(strtolower(auth()->user()->name ?? '')) }}</div>
                                    <div class="text-muted small" style="font-size: 8pt">{{ auth()->user()->team ?? '' }}</div>
                                </div>
                            </div>

                            <div class="row">
                                <div id="chart-demo-pie"></div>
                            </div>

                            <div class="row row-cards mt-3">
                                <div class="col-12">
                                    <div class="card card-md">
                                        <div class="card-body p-3">
                                            <div class="row align-items-center">
                                                <div class="col">
                                                    <h2 class="h3">Latest Gallery</h2>
                                                    <div id="carousel-event-photo" class="carousel slide c-home" data-bs-ride="carousel">
                                                        <div class="carousel-inner">
                                                            @foreach ($eventphoto as $event)
                                                                <div class="carousel-item {{ ($loop->first) ? 'active' : '' }}">
                                                                    <a href="{{ route('gallery.user.view', $event->slug) }}" class="d-block" title="{{ $event->title }}">
                                                                        <img src="{{ asset('/storage/gallery/photo') }}/{{ $event->id }}/{{ $event->thumbnail }}" class="img-fluid">
                                                                        <h6 class="mb-0">{{ $event->title }}</h6>
                                                                    </a>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        <a class="carousel-control-prev" href="#carousel-event-photo" role="button" data-bs-slide="prev">
                                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                            <span class="visually-hidden">Previous</span>
                                                        </a>
                                                        <a class="carousel-control-next" href="#carousel-event-photo" role="button" data-bs-slide="next">
                                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                            <span class="visually-hidden">Next</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row row-cards mt-3">
                                <div class="col-12">
                                    <div class="card card-md">
                                        <div class="card-body p-3">
                                            <div class="row align-items-center">
                                                <div class="col">
                                                    <h2 class="h3">News</h2>
                                                    <div class="divide-y-2 mt-3">
                                                        @foreach ($news as $list)
                                                            <div>
                                                                <a href="{{ route('news.user.view', $list->slug) }}" class="text-reset">{{ $list->title }}</a>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Modal Assessment Access --}}
    <div class="modal fade" id="modal-assessment-access" tabindex="-1">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ASSESSMENT ACCESS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-info">
                        <i class="fas fa-info-circle me-1"></i> Please input the unique code to access the assessment.
                    </div>
                    <input type="text" name="unique_code" id="unique-code" class="form-control" placeholder="Input unique code" autocomplete="off">
                    <div class="invalid-feedback d-none" id="invalid-text-unique-code">dasf</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="fa fa-times me-2"></i>Cancel</button>
                    <button type="button" onclick="submitAssessmentAccess()" class="btn btn-primary"><i class="fas fa-clipboard-list me-2"></i>Submit</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
    <link href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}" rel="stylesheet"/>
    <style>
        @media (max-width: 767px) {
            .carousel-inner .carousel-item > div {
                display: none;
            }
            .carousel-inner .carousel-item > div:first-child {
                display: block;
            }
        }

        .carousel-inner .carousel-item.active,
        .carousel-inner .carousel-item-next,
        .carousel-inner .carousel-item-prev {
            display: flex;
        }

        /* medium and up screens */
        @media (min-width: 768px) {
            .carousel-inner .carousel-item-end.active,
            .carousel-inner .carousel-item-next {
                transform: translateX(25%);
            }

            .carousel-inner .carousel-item-start.active,
            .carousel-inner .carousel-item-prev {
                transform: translateX(-25%);
            }

            .c-home .carousel-control-prev, .c-home .carousel-control-next{
                width: 5% !important;
            }
        }

        .carousel-inner .carousel-item-end,
        .carousel-inner .carousel-item-start {
            transform: translateX(0);
        }
    </style>
@endsection

@section('script')
    <script src="{{ asset('Tabler/dist/libs/apexcharts/dist/apexcharts.min.js?1684106062') }}" defer></script>
    <script>
        // @formatter:off
        document.addEventListener("DOMContentLoaded", function () {
            window.ApexCharts && (new ApexCharts(document.getElementById('chart-demo-pie'), {
                chart: {
                    type: "donut",
                    fontFamily: 'inherit',
                    height: 240,
                    sparkline: {
                        enabled: true
                    },
                    animations: {
                        enabled: false
                    },
                },
                fill: {
                    opacity: 1,
                },
                series: [{{ $content_user->where('finish', '0')->count() }}, {{ $content_user->where('finish', '1')->count() }}],
                labels: ["On Going Course", "Completed Course"],
                tooltip: {
                    theme: 'dark'
                },
                grid: {
                    strokeDashArray: 4,
                },
                colors: [tabler.getColor("primary"), tabler.getColor("primary", 0.8), tabler.getColor("primary", 0.6), tabler.getColor("gray-300")],
                legend: {
                    show: true,
                    position: 'left',
                    offsetY: 12,
                    markers: {
                        width: 10,
                        height: 10,
                        radius: 100,
                    },
                    itemMargin: {
                        horizontal: 8,
                        vertical: 8
                    },
                },
                tooltip: {
                    fillSeriesColor: false
                },
            })).render();
        });
        // @formatter:on
    </script>

    <script>
        let items            = document.querySelectorAll('#carousel-topvideo .carousel-item');
        let items_vid        = document.querySelectorAll('#carousel-videos .carousel-item');
        let item_doc         = document.querySelectorAll('#carousel-documents .carousel-item');
        let item_playlist    = document.querySelectorAll('#carousel-playlist .carousel-item');
        let item_event_photo = document.querySelectorAll('#carousel-event-photo .carousel-item');

        items.forEach((el) => {
            const minPerSlide = 4
            let next = el.nextElementSibling
            for (var i=1; i<minPerSlide; i++) {
                if (!next) {
                    // wrap carousel by using first child
                    next = items[0]
                }
                let cloneChild = next.cloneNode(true)
                el.appendChild(cloneChild.children[0])
                next = next.nextElementSibling
            }
        })

        items_vid.forEach((el) => {
            const minPerSlide = 4
            let next = el.nextElementSibling
            for (var i=1; i<minPerSlide; i++) {
                if (!next) {
                    // wrap carousel by using first child
                    next = items_vid[0]
                }
                let cloneChild = next.cloneNode(true)
                el.appendChild(cloneChild.children[0])
                next = next.nextElementSibling
            }
        })

        item_doc.forEach((el) => {
            const minDocPerSlide = 4
            let next_doc = el.nextElementSibling
            for (var i=1; i<minDocPerSlide; i++) {
                if (!next_doc) {
                    next_doc = item_doc[0]
                }
                let cloneChild4 = next_doc.cloneNode(true)
                el.appendChild(cloneChild4.children[0])
                next_doc = next_doc.nextElementSibling
            }
        })

        item_playlist.forEach((el) => {
            const minPlaylistPerSlide = 4
            let next_playlist = el.nextElementSibling
            for (var i=1; i < minPlaylistPerSlide; i++) {
                if (!next_playlist) {
                    next_playlist = item_playlist[0]
                }
                let cloneChildPlaylist = next_playlist.cloneNode(true)
                el.appendChild(cloneChildPlaylist.children[0])
                next_playlist = next_playlist.nextElementSibling
            }
        })
    </script>

    <script>
        $('#btn-assessment-access').on('click', () => {
            $('#unique-code').val('');
            $('#invalid-text-unique-code').html('');
            $('#invalid-text-unique-code').removeClass('d-block');
            $('#invalid-text-unique-code').addClass('d-none');

            $('#modal-assessment-access').modal('show');
        });

        let submitAssessmentAccess = () => {
            let unique_code = $('#unique-code').val();

            $.ajax({
                url: "{{ route('home.user.access.assessment.by.unique.code') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    unique_code: unique_code,
                },
                success: function (response) {
                    let routeAssessment = "{{ route('assesment.user.view', ':slug') }}";
                    let routeAssessmentFixed = routeAssessment.replace(':slug', response.assessment_slug);
                    
                    window.location.replace(routeAssessmentFixed);
                },
                error: function (error) {                    
                    if (error.responseJSON.unique_code) {
                        $('#invalid-text-unique-code').html(error.responseJSON.unique_code[0]);
                        $('#invalid-text-unique-code').removeClass('d-none');
                        $('#invalid-text-unique-code').addClass('d-block');
                    }

                    if (error.responseJSON.error_message) {
                        $('#invalid-text-unique-code').html(error.responseJSON.error_message);
                        $('#invalid-text-unique-code').removeClass('d-none');
                        $('#invalid-text-unique-code').addClass('d-block');
                    }
                }
            });
        }
    </script>
@endsection
