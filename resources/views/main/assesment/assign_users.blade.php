@extends('layouts.sidebar-master')

@section('content')
    @include('main.assesment.partials.subpage-header', [
        'breadcrumb' => ['Assign Users'],
        'title'      => 'Assign Users',
    ])

    <div class="page-body">
        <div class="container-xl">

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary-lt">
                    <div class="row align-items-center w-100">
                        <div class="col">
                            <h3 class="card-title fw-bold mb-1">
                                <i class="fas fa-users-cog me-2 text-primary"></i>
                                List of Assign Users
                            </h3>

                            <div class="text-muted small">
                                Assign assessment access to employee, team, or department.
                            </div>
                        </div>

                        <div class="col-auto">
                            <span class="badge bg-primary-lt text-primary">
                                Assign Users
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card-body">

                    <x-alert type='success' />
                    <x-alert type='warning' />
                    <x-alert type='danger' />

                    <div class="card bg-dark-lt border-0 mb-4">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user-check me-2"></i>
                                Assign To
                            </h3>
                        </div>

                        <div class="card-body">
                            <form action="{{ route('assesment.assign.users.process', $assesment->slug) }}" method="post">
                                @csrf

                                <div class="row align-items-end">

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                Employee
                                            </label>

                                            <select name="assign_employee" id="assign-employee" class="form-select select2">
                                                <option value="">- Choose Employee -</option>
                                                @foreach ($employees as $employee)
                                                    <option value="{{ $employee->id_emp }}">
                                                        {{ $employee->id_emp . ' - ' . $employee->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                Team
                                            </label>

                                            <select name="assign_team" id="assign-team" class="form-select select2">
                                                <option value="">- Choose Team -</option>
                                                @foreach ($teams as $team)
                                                    <option value="{{ $team->id_team }}">
                                                        {{ $team->team }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                Department
                                            </label>

                                            <select name="assign_department" id="assign-department" class="form-select select2">
                                                <option value="">- Choose Department -</option>
                                                @foreach ($departments as $department)
                                                    <option value="{{ $department->id_department }}">
                                                        {{ $department->department }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-paper-plane me-2"></i>
                                                Submit
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header">
                            <div class="row align-items-center w-100">
                                <div class="col">
                                    <h3 class="card-title">
                                        <i class="fas fa-list-ul me-2"></i>
                                        List of Assigned Users
                                    </h3>
                                </div>

                                <div class="col-auto">
                                    <span class="badge bg-primary-lt text-primary">
                                        Total: {{ $assigned->count() ?? 0 }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-hover">
                                <thead>
                                    <tr class="text-center">
                                        <th style="width: 70px;">No</th>
                                        <th>ID</th>
                                        <th>Assign Type</th>
                                        <th class="text-start">Description</th>
                                        <th style="width: 120px;">Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($assigned as $assign)
                                        <tr>
                                            <td class="text-center text-muted">
                                                {{ $loop->iteration }}
                                            </td>

                                            <td class="text-center">
                                                <span class="badge bg-secondary-lt text-secondary">
                                                    {{ $assign->assigned_id }}
                                                </span>
                                            </td>

                                            <td class="text-center">
                                                <span class="badge bg-blue-lt text-blue">
                                                    {{ ucwords($assign->assigned_type) }}
                                                </span>
                                            </td>

                                            <td>
                                                <div class="fw-semibold">
                                                    {{ $assign->assigned_description }}
                                                </div>
                                            </td>

                                            <td class="text-center">
                                                <form action="{{ route('assesment.assign.users.delete', ['id' => $assign->id]) }}" method="post">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-5">
                                                <div class="empty">
                                                    <div class="empty-icon">
                                                        <i class="fas fa-users fa-2x text-muted"></i>
                                                    </div>

                                                    <p class="empty-title">
                                                        No Assigned Data Available
                                                    </p>

                                                    <p class="empty-subtitle text-muted">
                                                        No employee, team, or department has been assigned yet.
                                                    </p>
                                                </div>
                                            </td>
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
@endsection

@push('scripts')
    <script>
        $('.select2').select2({
            theme: 'bootstrap-5',
        });
    </script>
@endpush