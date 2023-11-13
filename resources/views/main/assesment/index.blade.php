@extends('layouts.master')
@section('content')

    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Manage Assesment
                    </h2>
                </div>
                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">Assesment</a></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page body -->
    <div class="page-body">
        <!-- Container Page -->
        <div class="container">

            {{-- alert component   --}}
            <x-alert type='success' />
            <x-alert type='warning' />

            <!-- Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">List Assesment</h3>
                    <div class="card-actions">
                        <a href="{{ route('assesment.create') }}" class="btn btn-primary">
                            <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M12 5l0 14"></path>
                                <path d="M5 12l14 0"></path>
                            </svg>
                            Add assesment
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div id="table-default" class="table-responsive">
                        @unless (count($assesment) == 0)
                            <table class="table table-bordered table-striped table-vcenter" id="MyTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Type</th>
                                        <th>Title</th>
                                        <th>PG</th>
                                        <th>Datetime</th>
                                        <th>Status</th>
                                        <th width="12%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($assesment as $list)
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td>{{ $list->type }}</td>
                                            <td>
                                                <a href="{{ route('assesment.user.view', $list->slug) }}" class="text-reset link">
                                                    {{ $list->title }}
                                                </a>
                                                <button class="btn text-end btn-icon btn-copy" data-clipboard-text="{{ route('assesment.user.view', $list->slug) }}" data-bs-trigger="click" data-bs-toggle="tooltip" title="Copied!" data-bs-placement="bottom">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                        <path d="M8 8m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z"></path>
                                                        <path d="M16 8v-2a2 2 0 0 0 -2 -2h-8a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h2"></path>
                                                    </svg>
                                                </button>
                                            </td>
                                            <td>{{ $list->passing_grade }}</td>

                                            @if ($list->timer == 'y')
                                                <td class="text-end">From : {{ date('d/m/y H:i', strtotime($list->fromdate)) }} <br>To : {{ date('d/m/y H:i', strtotime($list->todate)) }}</td>
                                            @else
                                                <td class="text-center">-</td>
                                            @endif

                                            <td>
                                                <input data-id="{{ $list->id }}" data-toggle="toggle" class="toggle-class" type="checkbox" data-onstyle="success" data-offstyle="secondary" data-on="OK" data-off="NOT" data-size="small" data-width="40" {{ $list->status == 'OK' ? 'checked' : '' }}>
                                            </td>
                                            <td>
                                                <div class="d-inline">
                                                    <a href="{{ route('assesment.edit', $list->slug) }}" class="btn btn-info btn-icon mb-1" title="Edit">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                            <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"></path>
                                                            <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"></path>
                                                            <path d="M16 5l3 3"></path>
                                                        </svg>
                                                    </a>
                                                </div>

                                                <div class="d-inline">
                                                    <a class="btn btn-secondary btn-icon btn-newlink mb-1" href="{{ route('assesment.newlink', $list->slug) }}" title="Generate new link">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-refresh" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                            <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"></path>
                                                            <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"></path>
                                                        </svg>
                                                    </a>
                                                </div>

                                                <form action="{{ route('assesment.destroy', $list->slug) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-icon mb-1" onclick="return confirm('Are you sure want to delete {{ $list->title }}?')" title="Delete?"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                            <path d="M4 7l16 0"></path>
                                                            <path d="M10 11l0 6"></path>
                                                            <path d="M14 11l0 6"></path>
                                                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                                        </svg></button>
                                                </form>

                                                <div class="d-inline">
                                                    <a href="{{ route('assesment.question', $list->slug) }}" class="btn btn-success btn-icon mb-1" title="Question List">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-checkup-list" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                            <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2"></path>
                                                            <path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z"></path>
                                                            <path d="M9 14h.01"></path>
                                                            <path d="M9 17h.01"></path>
                                                            <path d="M12 16l1 1l3 -3"></path>
                                                        </svg>
                                                    </a>
                                                </div>

                                                <div class="d-inline">
                                                    <a href="{{ route('assesment.clone', $list->slug) }}" class="btn btn-warning btn-icon mb-1" title="Clone" onclick="return confirm('Are you sure want to clone {{ $list->title }}?')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-stack" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                            <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
                                                            <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4"></path>
                                                            <path d="M5 21h14"></path>
                                                            <path d="M5 18h14"></path>
                                                            <path d="M5 15h14"></path>
                                                         </svg>
                                                    </a>
                                                </div>

                                                <div class="d-inline">
                                                    <a href="{{ route('assesment.result', $list->slug) }}" class="btn btn-primary btn-icon mb-1" title="User Score">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"></path>
                                                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4"></path>
                                                            <path d="M15 19l2 2l4 -4"></path>
                                                        </svg>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p>No assesment data found</p>
                        @endunless
                    </div>
                </div>

            </div>
            <!-- Card -->

        </div>
        <!-- Container Page -->
    </div>
    <!-- Page body -->

    <!-- Modal Question -->
    <div class="modal modal-blur fade" id="modal-newlink" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="d-none modal-blur" id="overlay">
                    <div class="text-center">
                        <div class="text-muted mb-3">Getting assesment data</div>
                        <div class="progress">
                            <div class="progress-bar progress-bar-indeterminate"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-header">
                    <h5 class="modal-title">Generate New Assement Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <x-form action="" method="put">

                        <div class="mb-3 row">
                            <x-label class="col-3 col-form-label required">Title</x-label>
                            <div class="col">
                                <x-input type="text" name="title" placeholder="Enter title" id="title" />
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <x-label class="col-3 col-form-label required">Passing Grade</x-label>
                            <div class="col">
                                <x-input type="number" name="passing_grade" placeholder="Enter passing grade" id="passing_grade" />
                            </div>
                            <x-label class="col-2 col-form-label required">Time Limit?</x-label>
                            <div class="col">
                                <x-select name="timer" id="timer" onchange="setRequiredDate(this);">
                                    <option value="">-choose-</option>
                                    <option value="y">Yes</option>
                                    <option value="n">No</option>
                                </x-select>
                            </div>
                        </div>
                        <div class="mb-3 row" id="date_wrapper">
                            <x-label class="col-3 col-form-label required">From Date</x-label>
                            <div class="col">
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" />
                                            <path d="M16 3v4" />
                                            <path d="M8 3v4" />
                                            <path d="M4 11h16" />
                                            <path d="M11 15h1" />
                                            <path d="M12 15v3" />
                                        </svg>
                                    </span>
                                    <x-input placeholder="Select a date" id="datepicker-fromdate" name="fromdate" required class="input-date" />
                                </div>
                            </div>

                            <x-label class="col-2 col-form-label required">To Date</x-label>
                            <div class="col">
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" />
                                            <path d="M16 3v4" />
                                            <path d="M8 3v4" />
                                            <path d="M4 11h16" />
                                            <path d="M11 15h1" />
                                            <path d="M12 15v3" />
                                        </svg>
                                    </span>
                                    <x-input placeholder="Select a date" id="datepicker-todate" name="todate" required class="input-date" />
                                </div>
                            </div>
                        </div>

                        <x-button>Submit</x-button>
                    </x-form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
    <link href="{{ asset('css/bootstrap-toggle.min.css') }}" rel="stylesheet">
    <style>
        #overlay {
            position: absolute;top: 0rem;right: 0rem;left: 0rem;bottom: 0rem;display: -webkit-box;display: -webkit-flex;display: -ms-flexbox;display: flex;-webkit-align-items: center;-webkit-box-align: center;-ms-flex-align: center;align-items: center;-webkit-box-pack: center;-ms-flex-pack: center;-webkit-justify-content: center;justify-content: center;overflow: hidden;border-radius: 0.25rem;z-index: 1000;
        }
    </style>
@endsection

@section('script')
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('js/clipboard.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap-toggle.min.js') }}"></script>
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

    <script>
        function hideTooltip() {
            setTimeout(function() {
                $('.btn-copy').tooltip('hide');
            }, 500);
        }

        var clipboard = new ClipboardJS('.btn-copy');
        clipboard.on('success', function(e) {
            hideTooltip();
        });

        $('.toggle-class').change(function() {
            var status = $(this).prop('checked') == true ? 'OK' : 'NOT';
            var assesment_id = $(this).data('id');

            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '{{ route('assesment.changestatus') }}',
                data: {
                    "status": status,
                    "id": assesment_id,
                    "_token": "{{ csrf_token() }}",
                },
                success: function(data) {
                    console.log(data.success);
                },
            });
        })
    </script>

@endsection
