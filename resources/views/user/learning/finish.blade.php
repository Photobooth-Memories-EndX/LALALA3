@extends('layouts.master')
@section('content')

    {{-- <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Learning Record
                    </h2>
                </div>
                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Learning</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">Record</a>
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

            <!-- Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Learning Record</h3>
                </div>
                <div class="card-body">
                    <div id="table-default" class="table-responsive">
                        @unless (count($content_user) == 0)
                            <table class="table table-bordered table-striped" id="MyTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Title</th>
                                        <th>Classification</th>
                                        <th>Duration</th>
                                        <th>Date</th>
                                        <th>Score Test</th>
                                        <th>Certificate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($content_user as $list)
                                        @php
                                            if ($list->active == 'Active') {
                                                $activecolor = 'green';
                                            } else {
                                                $activecolor = 'orange';
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td>
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                      @if (in_array($list->content->type, ['Video','YouTube']))
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
                                                        <a href="{{ route('content.user.view', $list->content->slug) }}" class="text-reset">{{ $list->content->title }}</a>
                                                    </div>
                                                  </div>

                                            </td>
                                            <td>{{ $list->content->classification->classification_name ?? '' }}</td>
                                            <td>{{ $list->content->duration }}</td>
                                            <td>{{ $list->content->date }}</td>

                                            <td>
                                                @if (!blank($list->assesment))
                                                    @if (!blank($datas = $list->assesment->scores))
                                                        @php
                                                            $filter = $datas->filter(function ($assesment_result) use($list) {
                                                                                return $assesment_result->user_id==$list->user_id;
                                                                            })->sortByDesc('batch')->first();
                                                        @endphp
                                                        @if (!blank($filter))
                                                            {{ $filter->score }}
                                                        @endif
                                                    @endif
                                                @endif
                                            </td>

                                            <td>
                                                <div class="d-inline">
                                                    <a href="" class="btn btn-info btn-icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                            <path d="M15 15m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                                                            <path d="M13 17.5v4.5l2 -1.5l2 1.5v-4.5"></path>
                                                            <path d="M10 19h-5a2 2 0 0 1 -2 -2v-10c0 -1.1 .9 -2 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -1 1.73"></path>
                                                            <path d="M6 9l12 0"></path>
                                                            <path d="M6 12l3 0"></path>
                                                            <path d="M6 15l2 0"></path>
                                                         </svg>
                                                    </a>
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
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
@endsection

@section('script')
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
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
