<div class="card shadow-sm border-0 user-card">
    <div class="card-header bg-white border-0">
        <div>
            <h3 class="card-title mb-1">List Active User</h3>
            <div class="text-muted small">
                Data karyawan aktif, PTKP, dan outsourcing.
            </div>
        </div>
    </div>

    <div class="card-body border-bottom py-3">
        <form action="{{ route('user.search') }}" method="GET" id="form-filter-data-user">
            <div class="row align-items-center">
                <div class="col-md-3 mb-2 mb-md-0">
                    <div class="d-flex align-items-center">
                        <span class="text-muted mr-2">Show</span>

                        <select name="show_entries" id="show-entries" class="form-control form-control-sm" style="width: 80px;">
                            <option value="10" {{ $entries == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ $entries == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ $entries == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $entries == 100 ? 'selected' : '' }}>100</option>
                        </select>

                        <span class="text-muted ml-2">Entries</span>
                    </div>
                </div>

                <div class="col-md-6 ml-auto">
                    <div class="input-group input-group-sm">
                        <input type="text"
                            name="search_user"
                            class="form-control"
                            placeholder="Search user by name, email, team..."
                            value="{{ $search }}">

                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">
                                <i class="ti ti-search"></i>
                                Search
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter table-hover table-modern mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th style="width: 110px;">ID</th>
                    <th>User</th>
                    <th>Team</th>
                    <th>Department</th>
                    <th>Division</th>
                    <th>Email</th>
                    <th style="width: 90px;">Status</th>
                    <th class="text-right" style="width: 145px;">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($user as $list)
                    <tr>
                        <td class="text-muted">{{ ++$no }}</td>

                        <td>
                            <span class="badge badge-light border">
                                {{ $list->id_emp }}
                            </span>
                        </td>

                        <td>
                            <div class="d-flex align-items-center">
                                <div class="user-avatar mr-2">
                                    {{ strtoupper(substr($list->name, 0, 1)) }}
                                </div>

                                <div>
                                    <a href="{{ route('user.manage.profile', $list->ssohash) }}" class="font-weight-bold text-reset">
                                        {{ $list->name }}
                                    </a>
                                    <div class="text-muted small">
                                        {{ $list->role_name ?? 'User' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td>{{ $list->team ?: '-' }}</td>
                        <td>{{ $list->department ?: '-' }}</td>
                        <td>{{ $list->division ?: '-' }}</td>
                        <td>{{ $list->email ?: '-' }}</td>

                        <td>
                            <input data-id="{{ $list->id }}"
                                data-toggle="toggle"
                                class="toggle-class"
                                type="checkbox"
                                data-onstyle="success"
                                data-offstyle="secondary"
                                data-on="ON"
                                data-off="OFF"
                                data-size="small"
                                data-width="48"
                                {{ $list->active == 1 ? 'checked' : '' }}>
                        </td>

                        <td class="text-right">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button"
                                    class="btn btn-white btn-icon btn-edit-user"
                                    title="Edit User"
                                    data-toggle="modal"
                                    data-target="#modalEditPtkp"
                                    data-id="{{ $list->id }}"
                                    data-id-emp="{{ $list->id_emp }}"
                                    data-name="{{ $list->name }}"
                                    data-email="{{ $list->email }}"
                                    data-team="{{ $list->team }}"
                                    data-department="{{ $list->department }}"
                                    data-division="{{ $list->division }}">
                                    <i class="ti ti-edit text-primary"></i>
                                </button>

                                <button type="button"
                                    class="btn btn-white btn-icon"
                                    title="Reset Password"
                                    data-toggle="modal"
                                    data-target="#modalAction"
                                    data-url="{{ route('user.resetpassword', $list->id) }}"
                                    data-method="PUT"
                                    data-title="Reset Password"
                                    data-message="Yakin ingin reset password user {{ $list->name }}?"
                                    data-button="Reset"
                                    data-color="warning">
                                    <i class="ti ti-lock-open text-warning"></i>
                                </button>

                                <button type="button"
                                    class="btn btn-white btn-icon"
                                    title="Delete User"
                                    data-toggle="modal"
                                    data-target="#modalAction"
                                    data-url="{{ route('user.destroy', $list->id) }}"
                                    data-method="DELETE"
                                    data-title="Delete User"
                                    data-message="Yakin ingin menghapus user {{ $list->name }}?"
                                    data-button="Delete"
                                    data-color="danger">
                                    <i class="ti ti-trash text-danger"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            No user data found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer bg-white">
        @if ($user->total() <= $entries)
            <x-page.pagination-first count="{{ $user->count() }}" />
        @else
            {{ $user->links('pagination::bootstrap-5') }}
        @endif
    </div>
</div>