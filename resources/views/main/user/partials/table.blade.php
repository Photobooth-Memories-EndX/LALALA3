<table class="table table-bordered table-striped table-user">
    <thead>
        <tr>
            <th>No</th>
            <th>ID</th>
            <th>Name</th>
            <th>Team</th>
            <th>Department</th>
            <th>Division</th>
            <th>Email</th>
            <th width="120px">Action</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($user as $list)
            <tr>
                <td>{{ ++$no }}</td>
                <td>{{ $list->id_emp }}</td>

                <td>
                    <a href="{{ route('user.manage.profile', $list->ssohash) }}">
                        {{ $list->name }}
                    </a>
                </td>

                <td>{{ $list->team }}</td>
                <td>{{ $list->department }}</td>
                <td>{{ $list->division }}</td>
                <td>{{ $list->email }}</td>

                <td class="text-left">
                    <button type="button"
                        class="btn btn-info btn-icon mb-1 btn-edit-user"
                        title="Edit"
                        data-toggle="modal"
                        data-target="#modalEditPtkp"
                        data-id="{{ $list->id }}"
                        data-id-emp="{{ $list->id_emp }}"
                        data-name="{{ $list->name }}"
                        data-email="{{ $list->email }}"
                        data-team="{{ $list->team }}"
                        data-department="{{ $list->department }}"
                        data-division="{{ $list->division }}">
                        <i class="ti ti-edit"></i>
                    </button>

                    <button type="button"
                        class="btn btn-warning btn-icon mb-1"
                        title="Reset Password"
                        data-toggle="modal"
                        data-target="#modalAction"
                        data-url="{{ route('user.resetpassword', $list->id) }}"
                        data-method="PUT"
                        data-title="Reset Password"
                        data-message="Are you sure want to reset {{ $list->name }} password?"
                        data-button="Reset Password"
                        data-color="warning">
                        <i class="ti ti-lock-open"></i>
                    </button>

                    <button type="button"
                        class="btn btn-danger btn-icon mb-1"
                        title="Delete"
                        data-toggle="modal"
                        data-target="#modalAction"
                        data-url="{{ route('user.destroy', $list->id) }}"
                        data-method="DELETE"
                        data-title="Delete User"
                        data-message="Are you sure want to delete this data?"
                        data-button="Delete"
                        data-color="danger">
                        <i class="ti ti-trash"></i>
                    </button>

                    <input data-id="{{ $list->id }}"
                        data-toggle="toggle"
                        class="toggle-class"
                        type="checkbox"
                        data-onstyle="success"
                        data-offstyle="danger"
                        data-on="ACTIVE"
                        data-off="INACTIVE"
                        data-size="small"
                        data-width="80"
                        {{ $list->active == 0 ? 'checked' : '' }}>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

@if ($user->total() <= $entries)
    <x-page.pagination-first count="{{ $user->count() }}" />
@else
    <div class="mt-3">
        {{ $user->links('pagination::bootstrap-5') }}
    </div>
@endif