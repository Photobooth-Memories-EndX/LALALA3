@extends('layouts.master')
@section('content')

    {{-- <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Learning On Going
                    </h2>
                </div>
                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Learning</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">On Going</a>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

    <!-- Page body -->
    <div class="page-body">
        <!-- Container Page -->
        <div class="container">
            @php
                $totalpercentage = 0;
            @endphp
            @foreach ($content as $list)
                @php
                    $totalpercentage += $list->progress;
                @endphp
            @endforeach
            @php
            if(count($content) > 0) {
                $percentage = round(($totalpercentage / (count($content) * 100)) * 100, 2);
            }
            @endphp

            <!-- Card -->
            <div class="card">
                <div class="row">

                    <div class="col-lg-6">
                        {{-- <div class="card"> --}}
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-3">
                                    <img src="{{ asset('storage/profile') }}/{{ $profile->avatar ?? 'default.jpg' }}" alt="Food Deliver UI dashboards" class="rounded">
                                </div>
                                <div class="col">
                                    <h3 class="card-title mb-1">
                                        <a href="#" class="text-reset">{{ $profile->name }}</a>
                                    </h3>
                                    <div class="text-muted">
                                        {{ $profile->team }}
                                    </div>
                                    @if (count($content) > 0)
                                    <div class="mt-3">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-auto">
                                                {{ $percentage }}%
                                            </div>
                                            <div class="col">
                                                <div class="progress progress-sm">
                                                    <div class="progress-bar" style="width: {{ $percentage }}%" role="progressbar" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100" aria-label="25% Complete">
                                                        <span class="visually-hidden">{{ $percentage }}% Complete</span>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    @endif
                                    <div class="text-muted">
                                        @unless (count($content) == 0)
                                            @php
                                                $totallearning = count($content);
                                                $totalongoing = 0;
                                                $totalfinish = 0;
                                                $chartongoing = 0;
                                                $chartfinish = 0;
                                            @endphp
                                            @foreach ($content as $list)
                                                @php
                                                    if ($list->finish == 0) {
                                                        $totalongoing = $totalongoing + 1;
                                                        $chartongoing = $chartongoing + 1;
                                                    } else {
                                                        $totalfinish = $totalfinish + 1;
                                                        $chartfinish = $chartfinish + 1;
                                                    }
                                                @endphp
                                            @endforeach
                                            @php
                                                $totallearning = '<b>' . $totallearning . '</b> Total Learning';
                                                $totalongoing = '<b>' . $totalongoing . '</b> Ongoing Learning';
                                                $totalfinish = '<b>' . $totalfinish . '</b> Finished Learning';
                                            @endphp
                                        @else
                                            @php
                                                $totallearning = '<b>0</b> Total Learning';
                                                $totalongoing = '<b>0</b> Ongoing Learning';
                                                $totalfinish = '<b>0</b> Finish Learning';
                                            @endphp
                                        @endunless
                                        <div>
                                            <!-- Download SVG icon from http://tabler-icons.io/i/check -->
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-notebook" width="24" height="24" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M6 4h11a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-11a1 1 0 0 1 -1 -1v-14a1 1 0 0 1 1 -1m3 0v18"></path>
                                                <path d="M13 8l2 0"></path>
                                                <path d="M13 12l2 0"></path>
                                            </svg>
                                            <?php echo $totallearning; ?>
                                        </div>
                                        <div>
                                            <!-- Download SVG icon from http://tabler-icons.io/i/check -->
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-refresh" width="24" height="24" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"></path>
                                                <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"></path>
                                            </svg>
                                            <?php echo $totalongoing; ?>
                                        </div>
                                        <div>
                                            <!-- Download SVG icon from http://tabler-icons.io/i/check -->
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon text-green" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M5 12l5 5l10 -10" />
                                            </svg>
                                            <?php echo $totalfinish; ?>
                                        </div>

                                    </div>
                                </div>

                            </div>
                        </div>
                        {{-- </div> --}}
                    </div>
                    <div class="col-lg-6">
                        <div class="row">
                            {{-- <div class="col-lg-2">asdasd</div> --}}
                            <div class="col-lg-4">
                                <div id="chart-demo-pie"></div>
                            </div>
                        </div>

                    </div>
                </div>
                {{-- <div class="card-body p-4 text-center">
                    <span class="avatar avatar-xl mb-3 rounded" style="background-image: url({{ asset('storage/profile') }}/{{ $user->avatar ?? 'default.jpg' }})"></span>
                    <h3 class="m-0 mb-1"><a href="#">{{ $profile->name }}</a></h3>
                    <div class="text-muted">{{ $profile->team }}</div>

                </div>

                <div class="card-header">
                    <h3 class="card-title"></h3>
                    <div class="card-actions row">
                        <!-- Page title actions -->

                    </div>
                </div> --}}
                <div class="card-body">
                    <div id="table-default" class="table-responsive">
                        {{-- {{ $content }} --}}
                        @unless (count($content) == 0)
                            <table class="table table-bordered table-striped table-vcenter" id="MyTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Status</th>
                                        <th>Name</th>
                                        <th>Title</th>
                                        <th>Classification</th>
                                        <th>Duration</th>
                                        <th>Date</th>
                                        <th width="10%">Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($content as $list)
                                        @php
                                            if ($list->active == 'Active') {
                                                $activecolor = 'green';
                                            } else {
                                                $activecolor = 'orange';
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td>{{ $list->status }}</td>
                                            <td>{{ $list->user->name }}</td>
                                            <td>
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        @if (in_array($list->content->type, ['Video', 'YouTube']))
                                                            <span class="bg-red-lt avatar">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-video" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                                    <path d="M15 10l4.553 -2.276a1 1 0 0 1 1.447 .894v6.764a1 1 0 0 1 -1.447 .894l-4.553 -2.276v-4z"></path>
                                                                    <path d="M3 6m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z"></path>
                                                                </svg>
                                                            </span>
                                                        @else
                                                            <span class="bg-yellow-lt avatar">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-type-pdf" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                                    <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
                                                                    <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4"></path>
                                                                    <path d="M5 18h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6"></path>
                                                                    <path d="M17 18h2"></path>
                                                                    <path d="M20 15h-3v6"></path>
                                                                    <path d="M11 15v6h1a2 2 0 0 0 2 -2v-2a2 2 0 0 0 -2 -2h-1z"></path>
                                                                </svg>
                                                            </span>
                                                        @endif

                                                    </div>
                                                    <div class="col">
                                                        <a href="{{ route('learning.manage.content', $list->content->slug) }}" class="text-reset">{{ $list->content->title }}</a>
                                                    </div>
                                                </div>

                                            </td>
                                            <td><a href="{{ route('learning.manage.category', $list->content->classification->slug) }}" class="text-reset">{{ $list->content->classification->classification_name ?? '' }}</a></td>
                                            <td>{{ $list->content->duration }}</td>
                                            <td>{{ $list->content->date }}</td>

                                            <td>
                                                <div class="progress progress-xs" data-bs-toggle="tooltip" title="{{ $list->progress }}%" data-bs-placement="top">
                                                    <div class="progress-bar bg-primary" style="width: {{ $list->progress }}%"></div>
                                                </div>
                                            </td>

                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p>No content data found</p>
                        @endunless
                    </div>
                </div>
            </div>
            <!-- Card -->

        </div>
        <!-- Container Page -->
    </div>
    <!-- Page body -->

@endsection

@section('style')
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endsection

@section('script')
    <script src="{{ asset('Tabler/dist/libs/apexcharts/dist/apexcharts.min.js?1684106062"') }} defer"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
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
                series: [{{ $chartongoing }}, {{ $chartfinish }}],
                labels: ["On Going", "Finished"],
                tooltip: {
                    theme: 'dark'
                },
                grid: {
                    strokeDashArray: 4,
                },
                colors: [tabler.getColor("yellow"), tabler.getColor("primary", 0.8)],
                legend: {
                    show: true,
                    position: 'top',
                    offsetY: 12,
                    markers: {
                        width: 10,
                        height: 10,
                        radius: 100,
                    },
                    itemMargin: {
                        horizontal: 4,
                        vertical: 4
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
        // let table = new DataTable('#MyTable');
        $(function() {
            $("#MyTable").DataTable({
                "lengthMenu": [
                    [10, 25, 50, -1],
                    [10, 25, 50, 'All'],
                ],
                "pageLength": 25,
                "autoWidth": false,
            });
        });
    </script>
@endsection
