{{-- COMPACT EMPLOYEE LEARNING PROFILE HEADER (Standardized to Offline Participant Design) --}}
<div class="card border-0 shadow-sm p-3 p-md-3 mb-3 rounded-3 bg-white profile-compact-header">
    {{-- Top Section: Left (Avatar + Employee Identity) & Right (Compact Progress Box) --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        {{-- Left: Avatar & Identity --}}
        <div class="d-flex align-items-center gap-3 min-w-0 flex-grow-1">
            {{-- Avatar / Photo / Initials --}}
            @if(!empty($profile->avatar) && file_exists(public_path('storage/profile/' . $profile->avatar)))
                <span class="avatar rounded-circle flex-shrink-0"
                      style="width: 52px; height: 52px; background-image:url('{{ asset('storage/profile/' . $profile->avatar) }}')">
                </span>
            @else
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 profile-avatar-box"
                     style="width: 52px; height: 52px; background-color: #eff6ff; color: #1d4ed8; font-size: 20px; font-weight: 700;">
                    {{ strtoupper(substr($profile->name ?? 'U', 0, 1)) }}
                </div>
            @endif

            <div class="min-w-0">
                <span class="visually-hidden">Employee Learning Profile</span>
                {{-- Line 1: Employee Name + Badges (NIP & Grade) --}}
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h2 class="h3 fw-bold mb-0 text-dark" id="profile-employee-name">{{ $profile->name ?? '-' }}</h2>
                    @if(!empty($profile->id_emp))
                        <span class="badge bg-primary-lt fw-bold px-2 py-0.5" id="profile-employee-nip">
                            NIP: {{ $profile->id_emp }}
                        </span>
                    @endif
                    @if(!empty($profile->jobgrade))
                        <span class="badge bg-light text-secondary border px-2 py-0.5">
                            Grade: {{ $profile->jobgrade }}
                        </span>
                    @endif
                </div>

                {{-- Line 2: Position, Team, Department, Company --}}
                <div class="text-muted small d-flex align-items-center flex-wrap gap-2 pt-0.5">
                    @if(!empty($profile->jobposition))
                        <span class="d-inline-flex align-items-center gap-1">
                            <i class="ti ti-briefcase text-secondary"></i>
                            <span>{{ $profile->jobposition }}</span>
                        </span>
                    @endif

                    @if(!empty($profile->jobposition) && (!empty($profile->department) || !empty($profile->team)))
                        <span class="text-muted">•</span>
                    @endif

                    @if(!empty($profile->department) || !empty($profile->team))
                        <span class="d-inline-flex align-items-center gap-1">
                            <i class="ti ti-building text-secondary"></i>
                            <span>{{ $profile->team ? $profile->team . ' — ' : '' }}{{ $profile->department ?: '-' }}</span>
                        </span>
                    @endif

                    @php
                        $company = $profile->company_name ?? ($profile->company ?? 'PT Krakatau Posco');
                    @endphp
                    @if(!empty($company))
                        <span class="text-muted">•</span>
                        <span class="d-inline-flex align-items-center gap-1">
                            <i class="ti ti-building-community text-secondary"></i>
                            <span>{{ $company }}</span>
                        </span>
                    @endif

                    @if(!empty($profile->division))
                        <span class="text-muted">•</span>
                        <span class="d-inline-flex align-items-center gap-1 text-truncate" style="max-width: 220px;" title="{{ $profile->division }}">
                            <i class="ti ti-hierarchy-2 text-secondary"></i>
                            <span>{{ $profile->division }}</span>
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right: Compact Overall Learning Progress --}}
        <div class="profile-compact-progress flex-shrink-0 ms-auto ms-md-0" style="min-width: 200px; max-width: 250px;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small fw-medium" style="font-size: 0.75rem;">Overall Learning Progress</span>
                <span class="fw-bold text-primary small">{{ $percentage }}%</span>
            </div>
            <div class="progress" style="height: 5px; border-radius: 4px; background-color: #e2e8f0;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    </div>

    {{-- Bottom Section: Compact Metadata Strip (Last Learning, Favorite Category, Learning Hours) --}}
    <div class="border-top pt-2 mt-2.5 d-flex align-items-center flex-wrap gap-x-3 gap-y-1 text-muted small" style="font-size: 0.8rem; border-color: #f1f5f9 !important;">
        <span class="d-inline-flex align-items-center gap-1">
            <i class="ti ti-history text-secondary"></i>
            <span class="text-muted">Last Learning:</span>
            <span class="fw-semibold text-dark text-truncate" style="max-width: 320px;" title="{{ $lastLearning ?? '-' }}">{{ $lastLearning ?? '-' }}</span>
        </span>

        <span class="text-muted d-none d-md-inline">•</span>

        <span class="d-inline-flex align-items-center gap-1">
            <i class="ti ti-category text-secondary"></i>
            <span class="text-muted">Favorite Category:</span>
            <span class="fw-semibold text-dark">{{ $favoriteCategory ?? '-' }}</span>
        </span>

        <span class="text-muted d-none d-md-inline">•</span>

        <span class="d-inline-flex align-items-center gap-1">
            <i class="ti ti-clock text-secondary"></i>
            <span class="text-muted">Learning Hours:</span>
            <span class="fw-semibold text-dark">{{ $learningHours ?? 0 }} Hours</span>
        </span>
    </div>
</div>
