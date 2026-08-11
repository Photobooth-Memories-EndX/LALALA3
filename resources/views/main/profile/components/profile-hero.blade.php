<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="card-body p-4 p-lg-5">

        <div class="row align-items-center">

            <div class="col-lg-12">

                <div class="d-flex align-items-center gap-4">

                    {{-- AVATAR --}}
                    @if(!empty($profile->avatar) && file_exists(public_path('storage/profile/' . $profile->avatar)))

                        <span class="avatar avatar-xl rounded-circle"
                              style="background-image:url('{{ asset('storage/profile/' . $profile->avatar) }}')">
                        </span>

                    @else

                        <span class="avatar avatar-xl bg-primary-lt text-primary fw-bold">
                            {{ strtoupper(substr($profile->name ?? 'U',0,1)) }}
                        </span>

                    @endif

                    {{-- PROFILE INFO --}}
                    <div class="flex-fill">

                        <div class="text-muted text-uppercase small mb-1">
                            Employee Learning Profile
                        </div>

                        <h1 class="mb-1">
                            {{ $profile->name ?? '-' }}
                        </h1>

                        <div class="text-muted mb-2">

                            <i class="ti ti-building me-1"></i>
                            {{ $profile->department ?? '-' }}

                            @if(!empty($profile->division))
                                • {{ $profile->division }}
                            @endif

                        </div>

                        <div class="text-muted small">

                            <i class="ti ti-users me-1"></i>
                            {{ $profile->team ?? '-' }}

                            @if(!empty($profile->id_emp))
                                • EMP {{ $profile->id_emp }}
                            @endif

                        </div>

                        {{-- PROGRESS --}}
                        <div class="mt-4">

                            <div class="d-flex justify-content-between mb-1">

                                <span class="text-muted small">
                                    Overall Learning Progress
                                </span>

                                <span class="fw-semibold">
                                    {{ $percentage }}%
                                </span>

                            </div>

                            <div class="progress progress-sm">

                                <div class="progress-bar bg-primary"
                                     style="width: {{ $percentage }}%">
                                </div>

                            </div>

                        </div>

                        {{-- SUMMARY CARD --}}
                        <div class="row mt-4 g-3">

                            <div class="col-md-4">

                                <div class="card bg-light border-0">

                                    <div class="card-body py-2">

                                        <div class="text-muted small">
                                            Last Learning
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $lastLearning ?? '-' }}
                                        </div>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="card bg-light border-0">

                                    <div class="card-body py-2">

                                        <div class="text-muted small">
                                            Favorite Category
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $favoriteCategory ?? '-' }}
                                        </div>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="card bg-light border-0">

                                    <div class="card-body py-2">

                                        <div class="text-muted small">
                                            Learning Hours
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $learningHours ?? 0 }} Hours
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>
</div>