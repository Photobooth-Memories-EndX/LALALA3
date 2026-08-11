{{-- Organization/team monitoring (role 3/4). Data: $isSuperAdmin, $scopeLabel, $isScopeEmpty, $summary --}}
<div class="mb-3">
    <h3 class="mb-1">{{ $isSuperAdmin ? 'Organization Learning Monitoring' : 'Team Learning Monitoring' }}</h3>
    <div class="text-muted">Monitor user learning progress within your assigned scope.</div>
</div>

@if ($isScopeEmpty)
    @include('user.learning.dashboard-partials.monitoring.empty-state', [
        'message' => 'Scope organisasi Anda belum diset. Hubungi Super Admin.',
    ])
@else
    <div class="alert alert-info" role="alert">
        <i class="ti ti-info-circle me-1"></i>
        @if ($isSuperAdmin)
            You can view all users.
        @else
            Anda melihat scope: <strong>{{ $scopeLabel }}</strong>.
        @endif
    </div>

    @include('user.learning.dashboard-partials.monitoring.stat-cards', [
        'cards' => [
            ['label' => 'Total User',            'value' => number_format($summary['total_users']),           'icon' => 'ti-users',           'color' => 'primary'],
            ['label' => 'Average Progress',      'value' => $summary['average_progress'] . '%',               'icon' => 'ti-chart-line',      'color' => 'azure'],
            ['label' => 'Finished Content',      'value' => number_format($summary['finished_content']),      'icon' => 'ti-circle-check',    'color' => 'green'],
            ['label' => 'Assessment Completed',  'value' => number_format($summary['assessment_completed']),  'icon' => 'ti-clipboard-check', 'color' => 'purple'],
            ['label' => 'Certificate Generated', 'value' => number_format($summary['certificate_generated']), 'icon' => 'ti-certificate',     'color' => 'yellow'],
        ],
    ])

    @include('user.learning.dashboard-partials.monitoring.analytics-team', ['teamAnalytics' => $teamAnalytics])

    @include('user.learning.dashboard-partials.monitoring.user-table')
@endif
