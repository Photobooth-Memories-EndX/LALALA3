@extends('layouts.master')
@section('content')

    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Manage User
                    </h2>
                </div>
                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">User</a></li>
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

            @if($errors->any())
                {{ implode('', $errors->all('<div>:message</div>')) }}
            @endif

            <!-- Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">List Active user</h3>
                    <div class="card-actions">
                        <a href="{{ route('user.create') }}" class="btn btn-primary">
                            <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M12 5l0 14"></path>
                                <path d="M5 12l14 0"></path>
                            </svg>
                            Add User PTKP
                        </a>
                        <a href="{{ route('manage.create.user.outsourcing') }}" class="btn btn-success">
                            <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M12 5l0 14"></path>
                                <path d="M5 12l14 0"></path>
                            </svg>
                            Add User non-PTKP
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div id="table-default" class="table-responsive">
                        @unless (count($user) == 0)
                            <form action="{{ route('user.search') }}" method="get" class="mt-1" id="form-filter-data-user">
                                <div class="row justify-content-between">
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center">
                                            <label class="me-2">Show</label>
                                            <select name="show_entries" id="show-entries" class="form-select" style="width: 75px">
                                                <option value="10" {{ $entries == 10 ? 'selected' : '' }}>10</option>
                                                <option value="25" {{ $entries == 25 ? 'selected' : '' }}>25</option>
                                                <option value="50" {{ $entries == 50 ? 'selected' : '' }}>50</option>
                                                <option value="100" {{ $entries == 100 ? 'selected' : '' }}>100</option>
                                            </select>
                                            <label class="ms-2">Entries</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group mb-3">
                                            <input type="text" name="search_user" class="form-control" placeholder="Search User" aria-label="Search User" aria-describedby="button-addon2" value="{{ $search }}">
                                            <button class="btn btn-outline-primary" type="submit" id="button-addon2">
                                                <svg  xmlns="http://www.w3.org/2000/svg" class="mx-0"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-user-search">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                                    <path d="M6 21v-2a4 4 0 0 1 4 -4h1.5" />
                                                    <path d="M18 18m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                                    <path d="M20.2 20.2l1.8 1.8" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Team</th>
                                        <th>Department</th>
                                        <th>Division</th>
                                        <th>Email</th>
                                        <th width="80px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($user as $list)
                                        <tr>
                                            <td>{{ ++$no }}</td>
                                            <td>{{ $list->id_emp }}</td>
                                            <td><a href="{{ route('user.manage.profile', $list->ssohash) }}">{{ $list->name }}</a></td>
                                            <td>{{ $list->team }}</td>
                                            <td>{{ $list->department }}</td>
                                            <td>{{ $list->division }}</td>
                                            <td>{{ $list->email }}</td>
                                            <td class="text-left">
                                                <div class="d-inline">
                                                    <a href="{{ route('user.edit', $list->id) }}" class="btn btn-info btn-icon mb-1" title="Edit">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"></path><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"></path><path d="M16 5l3 3"></path>
                                                        </svg>
                                                    </a>
                                                </div>

                                                <form action="{{ route('user.resetpassword', $list->id) }}" method="post" class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-warning btn-icon mb-1" title="Reset Password" onclick="return confirm('Are you sure want to reset {{ $list->name }} password?')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-lock-open" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M5 11m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z"></path><path d="M12 16m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"></path><path d="M8 11v-5a4 4 0 0 1 8 0"></path>
                                                         </svg>
                                                    </button>
                                                </form>

                                                <form action="{{ route('user.destroy', $list->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Delete" class="btn btn-danger btn-icon mb-1" onclick="return confirm('Are you sure want to delete this data?')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M4 7l16 0"></path><path d="M10 11l0 6"></path><path d="M14 11l0 6"></path><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                                        </svg>
                                                    </button>
                                                </form>

                                                <input data-id="{{ $list->id }}" data-toggle="toggle" class="toggle-class" type="checkbox" data-onstyle="success" data-offstyle="secondary" data-on="ON" data-off="OFF" data-size="small" data-width="40" {{ $list->active == 1 ? 'checked' : '' }}>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            @if ($user->total() <= $entries)
                                <x-page.pagination-first count='{{ $user->count() }}' />
                            @else
                                <div class="mt-3">
                                    {{ $user->links('pagination::bootstrap-5') }}
                                </div>
                            @endif

                        @else
                            <p>No user data found</p>
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
    <link href="{{ asset('css/bootstrap-toggle.min.css') }}" rel="stylesheet">
@endsection

@section('script')
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
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

        $('.toggle-class').change(function() {
            var active = $(this).prop('checked') == true ? '1' : '0';
            var user_id = $(this).data('id');

            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: '{{ route('user.changestatus') }}',
                data: {
                    "active": active,
                    "id": user_id,
                    "_token": "{{ csrf_token() }}",
                },
                success: function(data) {
                    console.log(data.success);
                },
            });
        })

        $('#show-entries').on('change', function() {
            $('#form-filter-data-user').submit();
        });
    </script>
@endsection
