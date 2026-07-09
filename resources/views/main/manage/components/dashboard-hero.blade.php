<div class="card border-0 shadow-sm mb-4 overflow-hidden"
     style="border-radius:20px;
            background:linear-gradient(135deg,#206bc4 0%,#4f46e5 100%);">

    <div class="card-body p-4 p-lg-5 text-white">

        <div class="row align-items-center">

            <div class="col-lg-8">

                <span class="badge bg-white text-primary mb-3">
                    Admin Control Center
                </span>

                <h2 class="text-white mb-2">
                    Welcome back,
                    {{ auth()->user()->name ?? 'Admin' }}
                </h2>

                <p class="mb-4" style="opacity:.9">
                    Manage all OWL LMS modules from a centralized dashboard.
                </p>

                <div class="d-flex flex-wrap gap-2">

                    <a href="{{ url('manage/content/create') }}"
                       class="btn btn-light">
                        <i class="ti ti-plus me-1"></i>
                        Add Content
                    </a>

                    <a href="{{ url('manage/user') }}"
                       class="btn btn-outline-light">
                        <i class="ti ti-users me-1"></i>
                        Manage Users
                    </a>

                </div>

            </div>

            <div class="col-lg-4 d-none d-lg-block text-end">

                <i class="ti ti-layout-dashboard"
                   style="font-size:140px;opacity:.15">
                </i>

            </div>

        </div>

    </div>

</div>
