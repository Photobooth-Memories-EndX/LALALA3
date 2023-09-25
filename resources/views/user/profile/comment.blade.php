@extends('layouts.master')
@section('content')
    <div class="page-body">
        <div class="container-xl">
            <x-alert type='success' />
            <x-alert type='warning' />
            <div class="card">
                <div class="row g-0">
                    <div class="col-3 d-none d-md-block border-end">
                        <div class="card-body">
                            <div class="list-group list-group-transparent">
                                <a href="{{ route('user.profile') }}" class="list-group-item list-group-item-action d-flex align-items-center">My Account</a>
                                <a href="{{ route('user.comment') }}" class="list-group-item list-group-item-action d-flex align-items-center active">My Comment</a>
                            </div>
                        </div>
                    </div>
                    <div class="col d-flex flex-column" id="tabmain">
                        <div class="card-body">
                            <h2 class="mb-4">My Comments</h2>

                            @unless (count($comment) == 0)
                                <div class="divide-y mt-5" id="comment_list">
                                    @foreach ($comment as $list)
                                        <div>
                                            <div class="row">
                                                <div class="col-auto">
                                                    {{-- <span class="avatar" style="background-image: url({{ asset('storage/profile') }}/{{ $list->user->avatar ?? 'default.jpg' }})"></span> --}}
                                                </div>
                                                <div class="col">
                                                    <div class="text-truncate">
                                                        <strong>{{ $list->user->name }}</strong> on <strong>{{ $commenttype[$list->type] }}</strong>
                                                        <br>
                                                        <a href="
                                                            @if ($list->type == 'content') {{ route('content.user.view', $list->content->slug) }} 
                                                            @elseif($list->type == 'news') {{ route('news.user.view', $list->news->slug) }}
                                                            @elseif($list->type == 'eventphoto') {{ route('gallery.user.view', $list->eventphoto->slug) }} @endif
                                                            ">{{ $list->content->title ?? ($list->eventphoto->title ?? $list->news->title) }}</a>
                                                    </div>
                                                    <div class="text-muted">{!! nl2br($list->description) !!}</div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="text-muted">{{ \Carbon\Carbon::parse($list->created_at)->diffForHumans() }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endunless

                        </div>
                        <div class="card-footer bg-transparent mt-auto">

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
    <link href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}" rel="stylesheet" />
    <style>
        @media (max-width: 767px) {
            .carousel-inner .carousel-item>div {
                display: none;
            }

            .carousel-inner .carousel-item>div:first-child {
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

            .c-home .carousel-control-prev,
            .c-home .carousel-control-next {
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
        document.addEventListener("DOMContentLoaded", function() {
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
                series: [45, 55],
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
        let items = document.querySelectorAll('#carousel-topvideo .carousel-item')
        let items_vid = document.querySelectorAll('#carousel-videos .carousel-item')
        let item_doc = document.querySelectorAll('#carousel-documents .carousel-item')

        items.forEach((el) => {
            const minPerSlide = 4
            let next = el.nextElementSibling
            for (var i = 1; i < minPerSlide; i++) {
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
            for (var i = 1; i < minPerSlide; i++) {
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
            for (var i = 1; i < minDocPerSlide; i++) {
                if (!next_doc) {
                    next_doc = item_doc[0]
                }
                let cloneChild4 = next_doc.cloneNode(true)
                el.appendChild(cloneChild4.children[0])
                next_doc = next_doc.nextElementSibling
            }
        })
    </script>
@endsection
