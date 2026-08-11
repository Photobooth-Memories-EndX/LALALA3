@extends('layouts.sidebar-master')
@section('content')

    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Manage Admin
                    </h2>
                </div>
                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">Admin</a></li>
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

            <div class="row row-cards">
                <div class="col-md-6">
                    <!-- Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">List Admin</h3>
                        </div>
                        <div class="card-body">
                            <div id="table-default" class="table-responsive">
                                @unless (count($admin) == 0)
                                    <table class="table table-bordered table-striped table-vcenter" id="MyTable">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($admin as $list)
                                                <tr>
                                                    <td>{{ $loop->index + 1 }}</td>
                                                    <td>{{ $list->id_emp }}</td>
                                                    <td>{{ $list->name }}</td>
                                                    <td class="text-left">
                                                        <x-form action="{{ route('admin.update', $list->id) }}" method="put" class="d-inline">
                                                            <button type="submit" title="Delete" class="btn btn-danger btn-icon mb-1" onclick="return confirm('Are you sure want to remove {{ $list->name }} from admin list?')">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M4 7l16 0"></path><path d="M10 11l0 6"></path><path d="M14 11l0 6"></path><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                                                </svg>
                                                            </button>
                                                        </x-form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <p>No admin data found</p>
                                @endunless
                            </div>
                        </div>
                    </div>
                    <!-- Card -->
                </div>

                <div class="col-md-6">
                    <x-form class="card" action="{{ route('admin.store') }}" method="post">
                        <div class="card-header">
                          <h3 class="card-title">Add Admin</h3>
                        </div>
                        <div class="card-body">

                          <div class="row">
                            <label class="col-3 col-form-label">Training Team</label>
                            <div class="col">
                              <x-select class="form-select" name="id_user" id="id_user">
                                <option>-Select User-</option>
                                @foreach ($trainingteam as $list)
                                <option value="{{ $list->id }}">{{ $list->name }}</option>
                                @endforeach
                              </x-select>
                              <small class="form-hint">Add user if not exist yet at above list.</small>
                            </div>
                          </div>

                        </div>
                        <div class="card-footer text-end">
                          <button type="submit" class="btn btn-primary">Submit</button>
                        </div>

                    </x-form>
                </div>
            </div>


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
    </script>
@endsection
