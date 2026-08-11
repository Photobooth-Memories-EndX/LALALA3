{{-- Tab Monitoring. Role 1/2 -> personal insight, role 3/4 -> organization/team monitoring. --}}
@if (($mode ?? 'personal') === 'personal')
    @include('user.learning.dashboard-partials.monitoring.personal')
@else
    @include('user.learning.dashboard-partials.monitoring.organization')
@endif
