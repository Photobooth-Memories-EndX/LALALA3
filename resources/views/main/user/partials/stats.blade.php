<div class="row row-cards mb-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm shadow-sm border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-primary text-white avatar">
                            <i class="ti ti-users"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-bold">{{ $user->total() }}</div>
                        <div class="text-muted">Total User</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm shadow-sm border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-success text-white avatar">
                            <i class="ti ti-user-check"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-bold">{{ $user->count() }}</div>
                        <div class="text-muted">Showing Data</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm shadow-sm border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-info text-white avatar">
                            <i class="ti ti-user-check"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-bold">
                            {{ $user->where('active', 1)->count() }}
                        </div>
                        <div class="text-muted">Active User</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm shadow-sm border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-danger text-white avatar">
                            <i class="ti ti-user-x"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-bold">
                            {{ $user->where('active', 0)->count() }}
                        </div>
                        <div class="text-muted">Inactive User</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>