{{-- Quick Filter Card --}}
<div class="card border-0 shadow-sm rounded-4 mb-4 user-filter-card">
    <div class="card-body p-3">
        <div class="fw-semibold mb-2 filter-label-head" style="font-size: 0.85rem;">
            Quick Filter
        </div>

        <form id="user-filter-form" action="{{ url()->current() }}" method="GET" class="m-0 p-0">
            @csrf
            <!-- Hidden inputs for More Filters Modal -->
            <input type="hidden" name="department" value="{{ request('department', 'all') }}">
            <input type="hidden" name="division" value="{{ request('division', 'all') }}">

            <div class="d-flex align-items-center gap-2 flex-nowrap w-100">
                
                <!-- 1. Entries Select -->
                <div style="width: 110px; flex-shrink: 0;">
                    <select class="form-select rounded-3" name="entries" onchange="this.form.submit()">
                        @foreach ([10, 25, 50, 100] as $opt)
                            <option value="{{ $opt }}" {{ (int)request('entries', 25) === $opt ? 'selected' : '' }}>{{ $opt }} Entries</option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Status Select -->
                <div style="width: 115px; flex-shrink: 0;">
                    <select class="form-select rounded-3" name="status" onchange="this.form.submit()">
                        <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>All Status</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <!-- 3. Company Type Select (Expanded to 165px to prevent text collision) -->
                <div style="width: 165px; flex-shrink: 0;">
                    <select class="form-select rounded-3" name="company_type" onchange="this.form.submit()">
                        <option value="all" {{ request('company_type', 'all') === 'all' ? 'selected' : '' }}>All Company Type</option>
                        <option value="ptkp" {{ request('company_type') === 'ptkp' ? 'selected' : '' }}>PTKP</option>
                        <option value="non_ptkp" {{ request('company_type') === 'non_ptkp' ? 'selected' : '' }}>Non-PTKP</option>
                    </select>
                </div>

                <!-- 4. Team Select -->
                <div style="width: 130px; flex-shrink: 0;">
                    <select class="form-select rounded-3" name="team" onchange="this.form.submit()">
                        <option value="all">All Team</option>
                        @foreach($teams ?? [] as $tm)
                            <option value="{{ $tm }}" {{ request('team') == $tm ? 'selected' : '' }}>{{ $tm }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 5. Search Box -->
                <div class="position-relative flex-grow-1 min-w-0">
                    <input type="text" name="q" value="{{ request('q') }}" 
                           class="form-control rounded-3" 
                           placeholder="Search by name, ID, email..." 
                           autocomplete="off">
                </div>

                <!-- 6. Compact Action Buttons (Reset button updated to Dark Blue) -->
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <button type="submit" class="btn btn-primary px-3" style="background-color: #003b6d; border-color: #003b6d;" title="Apply Filter">
                        <i class="ti ti-search fs-2"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary px-3" style="color: #003b6d; border-color: #003b6d;" data-bs-toggle="modal" data-bs-target="#moreUserFiltersModal" title="More Filters">
                        <i class="ti ti-adjustments-horizontal fs-2"></i>
                    </button>
                    <a href="{{ url()->current() }}" class="btn btn-outline-primary px-3" style="color: #003b6d; border-color: #003b6d;" title="Reset Filters">
                        <i class="ti ti-refresh fs-2"></i>
                    </a>
                </div>

            </div>
        </form>
    </div>
</div>

<div id="status-alert"></div>

{{-- Table Card --}}
<div class="card owl-main-card border-0 shadow-sm rounded-4 mb-4 user-card">
    <div class="card-header bg-white border-0 py-3">
        <div>
            <h3 class="card-title mb-1 fw-bold text-dark">List Active User</h3>
            <div class="text-muted small">
                Showing {{ $user->firstItem() ?? 0 }}–{{ $user->lastItem() ?? 0 }} of {{ $user->total() }} user(s)
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter table-hover table-modern owl-table mb-0">
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
                    <th class="text-end" style="width: 145px;">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($user as $list)
                    <tr>
                        <td class="text-muted">{{ ++$no }}</td>

                        <td>
                            <span
                                class="badge badge-light border copy-id"
                                data-id="{{ $list->id_emp }}"
                                style="cursor: pointer;"
                                title="Click to copy">
                                {{ $list->id_emp }}
                                <i class="ti ti-copy ms-1"></i>
                            </span>
                        </td>

                        <td>
                            <div>
                                <a href="{{ route('user.manage.profile', $list->ssohash) }}" class="fw-bold text-reset text-decoration-none monitoring-link">
                                    {{ $list->name }}
                                </a>
                                <div class="text-muted small">
                                    @if(substr($list->id_emp, 0, 2) === 'ex')
                                        <span class="badge bg-secondary-lt">Non-PTKP</span>
                                    @else
                                        <span class="badge bg-blue-lt">PTKP</span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td>{{ $list->team ?: '-' }}</td>
                        <td>{{ $list->department ?: '-' }}</td>
                        <td>{{ $list->division ?: '-' }}</td>
                        <td>{{ $list->email ?: '-' }}</td>

                        <td>
                            <input
                                data-id="{{ $list->id }}"
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

                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button"
                                    class="btn btn-white btn-icon btn-edit-user"
                                    title="Edit User"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditPtkp"
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
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalAction"
                                    data-toggle="modal"
                                    data-target="#modalAction"
                                    data-url="{{ route('user.resetpassword', $list->id) }}"
                                    data-method="PUT"
                                    data-title="Reset Password"
                                    data-message="Are you sure you want to reset this user's password {{ $list->name }}?"
                                    data-button="Reset"
                                    data-color="warning">
                                    <i class="ti ti-lock-open text-warning"></i>
                                </button>

                                <button type="button"
                                    class="btn btn-white btn-icon"
                                    title="Delete User"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalAction"
                                    data-toggle="modal"
                                    data-target="#modalAction"
                                    data-url="{{ route('user.destroy', $list->id) }}"
                                    data-method="DELETE"
                                    data-title="Delete User"
                                    data-message="Are you sure you want to delete this user {{ $list->name }}?"
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
                            <div class="empty">
                                <div class="empty-icon mb-2">
                                    <i class="ti ti-users text-muted" style="font-size: 3rem; opacity: .4;"></i>
                                </div>
                                <p class="empty-title fw-bold text-dark">No user data found</p>
                                <p class="empty-subtitle text-secondary small">Try adjusting your filters or search keywords.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer bg-white border-0 py-3">
        @if ($user->total() <= $entries)
            <x-page.pagination-first count="{{ $user->count() }}" />
        @else
            <div class="d-flex align-items-center justify-content-center">
                {{ $user->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

<!-- More User Filters Modal -->
<div class="modal fade" id="moreUserFiltersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="ti ti-adjustments-horizontal text-primary"></i> More User Filters
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('user.index') }}" method="GET">
                <div class="modal-body py-3">
                    <!-- Preserved parameters -->
                    <input type="hidden" name="q" value="{{ request('q') ?? ($search ?? '') }}">
                    <input type="hidden" name="status" value="{{ request('status') ?? request('active') }}">
                    <input type="hidden" name="company_type" value="{{ request('company_type') }}">
                    <input type="hidden" name="entries" value="{{ $entries }}">
                    <input type="hidden" name="team" value="{{ request('team') }}">

                    <!-- Department Dropdown -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">Department</label>
                        <select name="department" class="form-select rounded-3">
                            <option value="">All Department</option>
                            @foreach($departments ?? [] as $dept)
                                <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Division Dropdown -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">Division</label>
                        <select name="division" class="form-select rounded-3">
                            <option value="">All Division</option>
                            @foreach($divisions ?? [] as $div)
                                <option value="{{ $div }}" {{ request('division') == $div ? 'selected' : '' }}>{{ $div }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <a href="{{ route('user.index', array_filter(['q' => request('q'), 'status' => request('status'), 'company_type' => request('company_type'), 'entries' => $entries, 'team' => request('team')])) }}" class="btn btn-outline-secondary px-3">
                        Reset
                    </a>
                    <button type="submit" class="btn btn-primary px-4" style="background-color: #003b6d; border-color: #003b6d;">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>