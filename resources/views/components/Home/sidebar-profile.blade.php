@php
    $user = auth()->user();
    $userName = $user?->name ? ucwords(strtolower($user->name)) : 'Learner';
    $userDivision = $user?->division ?? $user?->team ?? 'Training Team';
    $userAvatar = !empty($user?->avatar) ? asset('storage/profile/' . $user->avatar) : null;
@endphp

<div class="home-modern-card home-modern-sidebar-profile-card">
    <div class="home-modern-card-body d-flex align-items-center gap-3 pb-3">
        <div class="home-modern-profile-avatar-wrap">
            @if ($userAvatar)
                <span class="avatar avatar-md rounded-circle" style="background-image: url('{{ $userAvatar }}')"></span>
            @else
                <span class="avatar avatar-md rounded-circle bg-primary-subtle text-primary fw-bold">
                    <i class="ti ti-user fs-2"></i>
                </span>
            @endif
        </div>
        <div class="home-modern-profile-info flex-grow-1 min-w-0">
            <h4 class="home-modern-profile-name text-truncate mb-0" style="font-size: 1rem; font-weight: 700;" title="{{ $userName }}">
                {{ $userName }}
            </h4>
            <span class="home-modern-profile-division text-muted small text-truncate d-block" style="font-size: 0.8125rem;">
                {{ $userDivision }}
            </span>
        </div>
    </div>

    <div class="home-modern-trainingplan-card-body pt-0">
        <a href="{{ route('trainingplan.user.index') }}"
           class="home-modern-btn-training-plan home-modern-assessment-btn">
            <i class="ti ti-clipboard-list"></i>
            <span>Training Plan</span>
        </a>
    </div>
</div>
