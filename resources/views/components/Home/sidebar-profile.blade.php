@php
    $user = auth()->user();
    $userName = $user?->name ? ucwords(strtolower($user->name)) : 'Learner';
    $userDivision = $user?->division ?? $user?->team ?? 'Training Team';
    $userAvatar = !empty($user?->avatar) ? asset('storage/profile/' . $user->avatar) : null;
@endphp

<div class="home-modern-card home-modern-sidebar-profile-card d-none d-md-block">
    <div class="home-modern-trainingplan-card-body">
        <a href="{{ route('trainingplan.user.index') }}"
           class="home-modern-btn-training-plan home-modern-assessment-btn bg-[#ff5700] hover:bg-[#e04d00] text-white font-semibold rounded-lg shadow-md shadow-orange-500/40">
            <i class="ti ti-calendar-event"></i>
            <span>Training Schedule</span>
        </a>
    </div>
</div>

