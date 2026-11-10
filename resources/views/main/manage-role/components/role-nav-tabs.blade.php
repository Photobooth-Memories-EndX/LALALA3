{{-- Reusable Navigation Tabs between Active Users and Resigned Employees --}}
<div class="card border border-light-subtle shadow-sm rounded-3 bg-white mb-3 role-nav-tabs-card">
    <div class="card-body p-2">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
            <ul class="nav nav-pills gap-2" role="tablist">
                <li class="nav-item">
                    <a href="{{ route('manage.role.index') }}"
                       class="nav-link rounded-3 px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 {{ request()->routeIs('manage.role.index') ? 'active' : 'text-muted' }}"
                       @if(request()->routeIs('manage.role.index')) style="background-color: #003b6d; color: #ffffff;" @endif>
                        <i class="ti ti-users fs-2"></i>
                        <span>Active Employees</span>
                        <span class="badge {{ request()->routeIs('manage.role.index') ? 'bg-white text-primary' : 'bg-primary-lt' }} ms-1">
                            {{ number_format($totalUsers ?? \App\Models\User::count()) }}
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('manage.role.resign') }}"
                       class="nav-link rounded-3 px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 {{ request()->routeIs('manage.role.resign') ? 'active' : 'text-muted' }}"
                       @if(request()->routeIs('manage.role.resign')) style="background-color: #d63939; color: #ffffff;" @endif>
                        <i class="ti ti-user-off fs-2"></i>
                        <span>Resigned Employees</span>
                        <span class="badge {{ request()->routeIs('manage.role.resign') ? 'bg-white text-danger' : 'bg-danger-lt' }} ms-1">
                            {{ number_format($totalResigned ?? \App\Models\User::onlyTrashed()->count()) }}
                        </span>
                    </a>
                </li>
            </ul>

            <div class="text-muted small px-2 d-none d-md-flex align-items-center gap-1">
                @if(request()->routeIs('manage.role.resign'))
                    <span class="badge bg-danger-lt text-danger d-inline-flex align-items-center gap-1">
                        <i class="ti ti-database-search"></i> Source: users.deleted_at
                    </span>
                @else
                    <span class="badge bg-blue-lt text-primary d-inline-flex align-items-center gap-1">
                        <i class="ti ti-user-check"></i> Active LMS Accounts
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>
