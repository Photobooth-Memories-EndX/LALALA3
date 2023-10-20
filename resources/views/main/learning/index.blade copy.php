@extends('layouts.master')
@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Manage Learning
                    </h2>
                </div>
                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">Learning</a></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page body -->
    <div class="page-body">
        <!-- Container Page -->
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-md-6 col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><a href="{{ route('learning.manage.all') }}">Total Learning</a></h3>
                        </div>
                        <div class="card-body">
                            {{ $ongoing }} Ongoing<br>
                            {{ $finish }} Finished
                        </div>
                    </div>
                </div>
                {{-- <div class="col-md-6 col-lg-3">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Finished</h3>
                        </div>
                        <div class="card-body">{{ $finish }}</div>
                    </div>
                </div> --}}
            </div>


        </div>
        <br>
        <br>

        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Classification
                    </h2>
                </div>
            </div>
            <br>
            <br>
            <div class="row row-cards">
                @unless (count($classification) == 0)
                    @foreach ($classification as $list)
                        <div class="col-md-6 col-lg-3">
                            {{--  --}}
                            <div class="card card-sm">
                                <div class="card-status-top bg-green"></div>
                                <div class="card-body">
                                    <h3 class="card-header"><a href="{{ route('learning.manage.category', $list->slug) }}">{{ $list->classification_name }}</a></h3>
                                    <div class="mt-4">
                                        <div class="row">
                                            <div class="col">
                                                <h4>{{ $list->content_count }} Total Content
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path>
                                                        <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"></path>
                                                    </svg>
                                                </h4>
                                            </div>
                                            {{-- <div class="col-auto text-muted">
                                                <button class="switch-icon switch-icon-scale" data-bs-toggle="switch-icon">
                                                    <span class="switch-icon-a text-muted">
                                                        <!-- Download SVG icon from http://tabler-icons.io/i/heart -->
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572" />
                                                        </svg>
                                                    </span>
                                                    <span class="switch-icon-b text-red">
                                                        <!-- Download SVG icon from http://tabler-icons.io/i/heart -->
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-filled" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572" />
                                                        </svg>
                                                    </span>
                                                </button>
                                            </div>
                                            <div class="col-auto">
                                                <a href="#" class="link-muted">
                                                    <!-- Download SVG icon from http://tabler-icons.io/i/activity -->
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M3 12h4l3 8l4 -16l3 8h4" />
                                                    </svg>
                                                    1/3
                                                </a>
                                            </div>
                                            <div class="col-auto">
                                                <a href="#" class="link-muted"><!-- Download SVG icon from http://tabler-icons.io/i/message -->
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M8 9h8" />
                                                        <path d="M8 13h6" />
                                                        <path d="M18 4a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-5l-5 3v-3h-2a3 3 0 0 1 -3 -3v-8a3 3 0 0 1 3 -3h12z" />
                                                    </svg>
                                                    11</a>
                                            </div>
                                            <div class="col-auto">
                                                <a href="#" class="link-muted"><!-- Download SVG icon from http://tabler-icons.io/i/share -->
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M6 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                                        <path d="M18 6m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                                        <path d="M18 18m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                                        <path d="M8.7 10.7l6.6 -3.4" />
                                                        <path d="M8.7 13.3l6.6 3.4" />
                                                    </svg>
                                                </a>
                                            </div> --}}
                                        </div>
                                    </div>
                                    <div class="divide-y-2 mt-4">
                                        @unless (count($list->content) == 0)
                                            @php
                                                $totallearning = 0;
                                                $totalongoing = 0;
                                                $totalfinish = 0;
                                            @endphp
                                            @foreach ($list->content as $listcontent)
                                                @php
                                                    $totallearning += count($listcontent->contentuser);

                                                @endphp
                                                @unless (count($listcontent->contentuser) == 0)
                                                    @foreach ($listcontent->contentuser as $listcontentuser)
                                                        @php
                                                            if ($listcontentuser->finish == 0) {
                                                                $totalongoing = $totalongoing + 1;
                                                            } else {
                                                                $totalfinish = $totalfinish + 1;
                                                            }
                                                        @endphp
                                                    @endforeach
                                                @else
                                                @endunless
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
                            {{--  --}}

                        </div>
                    @endforeach
                </div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">List News</h3>
                        <div class="card-actions">
                            <a href="{{ route('news.create') }}" class="btn btn-primary">
                                <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M12 5l0 14"></path>
                                    <path d="M5 12l14 0"></path>
                                </svg>
                                Add news
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="table-default" class="table-responsive">
                            @unless (count($classification) == 0)
                                @unless (count($list->content) == 0)
                                    @php
                                        $totallearning = 0;
                                        $totalongoing = 0;
                                        $totalfinish = 0;
                                    @endphp
                                    @foreach ($list->content as $listcontent)
                                        @php
                                            $totallearning += count($listcontent->contentuser);

                                        @endphp
                                        @unless (count($listcontent->contentuser) == 0)
                                            @foreach ($listcontent->contentuser as $listcontentuser)
                                                @php
                                                    if ($listcontentuser->finish == 0) {
                                                        $totalongoing = $totalongoing + 1;
                                                    } else {
                                                        $totalfinish = $totalfinish + 1;
                                                    }
                                                @endphp
                                            @endforeach
                                        @else
                                        @endunless
                                    @endforeach
                                    {{-- @php
                                        $totallearning = '<b>' . $totallearning . '</b> Total Learning';
                                        $totalongoing = '<b>' . $totalongoing . '</b> Ongoing Learning';
                                        $totalfinish = '<b>' . $totalfinish . '</b> Finished Learning';
                                    @endphp --}}
                                @else
                                    @php
                                        $totallearning = '0';
                                        $totalongoing = '0';
                                        $totalfinish = '0';
                                    @endphp
                                @endunless
                                <table class="table table-bordered table-striped" id="MyTable">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Title</th>
                                            <th>Total Learning</th>
                                            <th>Ongoing Learning</th>
                                            <th>Finished Learning</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($classification as $list)
                                            <tr>
                                                <td>{{ $loop->index + 1 }}</td>
                                                <td>{{ $list->classification_name }}</td>
                                                <td>{{ $totallearning }}</td>
                                                <td>{{ $totalongoing }}</td>
                                                <td>{{ $totalfinish }}</td>

                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p>No news data found</p>
                            @endunless
                        </div>
                    </div>

                </div>
            @else
                No Classification
            @endunless
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
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
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
