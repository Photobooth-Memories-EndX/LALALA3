@extends('layouts.master')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Manage Assesment</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('assesment.index') }}">Assesment</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">Assign Users</a></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">List of Assign Users</h3>
                </div>
                <div class="card-body">

                    {{-- alert component --}}
                    <x-alert type='success' />
                    <x-alert type='warning' />

                    <div class="card">
                        <div class="card-body p-3">
                            <form action="{{ route('assesment.assign.users.process', $assesment->slug) }}" method="post">
                                @csrf
                                <div class="row align-items-center">
                                    <div class="col-1 text-end">
                                        <label class="form-label mb-0">Assign To: </label>
                                    </div>
                                    <div class="col-3 pe-1">
                                        <select name="assign_employee" id="assign-employee" class="form-select select2">
                                            <option value="">- Choose Employee -</option>
                                            @foreach ($employees as $employee)
                                                <option value="{{ $employee->id_emp }}">{{ $employee->id_emp . ' - ' . $employee->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-3 px-1">
                                        <select name="assign_team" id="assign-team" class="form-select select2">
                                            <option value="">- Choose Team -</option>
                                            @foreach ($teams as $team)
                                                <option value="{{ $team->id_team }}">{{ $team->team }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-4 px-1">
                                        <select name="assign_department" id="assign-department" class="form-select select2">
                                            <option value="">- Choose Department -</option>
                                            @foreach ($departments as $department)
                                                <option value="{{ $department->id_department }}">{{ $department->department }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-1 ps-1">
                                        <div class="d-flex align-items-end h-100">
                                            <button type="submit" class="btn btn-primary h-100 w-100">
                                                Submit
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <hr class="my-2">

                    <div class="card">
                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th colspan="4" class="py-2">List of Assigned Users</th>
                                        </tr>
                                        <tr class="text-center">
                                            <th class="py-2">No</th>
                                            <th class="py-2">ID</th>
                                            <th class="py-2">Assign Type</th>
                                            <th class="py-2">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($assigned as $assign)
                                            <tr class="fs-5">
                                                <td class="py-2 text-center">{{ $loop->iteration }}</td>
                                                <td class="py-2">{{ $assign->assigned_id }}</td>
                                                <td class="py-2">{{ ucwords($assign->assigned_type) }}</td>
                                                <td class="py-2">{{ $assign->assigned_description }}</td>
                                            </tr>
                                        @empty
                                            <tr class="fs-5">
                                                <td class="text-center py-2" colspan="4">No Assigned Data Available.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $('.select2').select2({
            theme: 'bootstrap-5',
        });
    </script>
@endpush