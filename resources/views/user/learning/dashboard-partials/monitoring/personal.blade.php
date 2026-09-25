{{-- Personal monitoring (role 1/2). Data: $summary, $recentLearning, $recentAssessment --}}
<div class="mb-3">
    <h3 class="mb-1">My Learning Insight</h3>
    <div class="text-muted">Ringkasan progress belajar Anda.</div>
</div>

@include('user.learning.dashboard-partials.monitoring.stat-cards', [
    'cards' => [
        ['label' => 'Total Learning',        'value' => number_format($analytics['summary']['total_learning']),       'icon' => 'ti-book',            'color' => 'primary'],
        ['label' => 'Finished Learning',     'value' => number_format($analytics['summary']['finished_learning']),    'icon' => 'ti-circle-check',    'color' => 'green'],
        ['label' => 'Ongoing Learning',      'value' => number_format($analytics['summary']['ongoing_learning']),     'icon' => 'ti-player-play',     'color' => 'orange'],
        ['label' => 'Average Progress',      'value' => $analytics['summary']['average_progress'] . '%',              'icon' => 'ti-chart-line',      'color' => 'azure'],
        ['label' => 'Assessment Completed',  'value' => number_format($analytics['summary']['assessment_completed']), 'icon' => 'ti-clipboard-check', 'color' => 'purple'],
        ['label' => 'Average Score',         'value' => $analytics['summary']['average_score'],                       'icon' => 'ti-star',            'color' => 'teal'],
        ['label' => 'Certificate Generated', 'value' => number_format($analytics['summary']['certificate']),          'icon' => 'ti-certificate',     'color' => 'yellow'],
        ['label' => 'Learning Hours',        'value' => $analytics['summary']['learning_hours'],                      'icon' => 'ti-clock',           'color' => 'cyan'],
    ],
])

@include('user.learning.dashboard-partials.monitoring.analytics-personal', ['analytics' => $analytics])

<div class="row row-cards">
    <div class="col-lg-7 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">Recent Learning Progress</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Content</th>
                            <th>Category</th>
                            <th style="width:160px;">Progress</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentLearning as $row)
                            @php $progress = (int) ($row->progress ?? 0); @endphp
                            <tr>
                                <td class="fw-bold">{{ optional($row->content)->title ?? 'Content Deleted' }}</td>
                                <td class="text-muted">{{ optional(optional($row->content)->classification)->classification_name ?? '-' }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress progress-sm flex-fill">
                                            <div class="progress-bar" style="width: {{ $progress }}%"></div>
                                        </div>
                                        <span class="ms-2 small text-muted">{{ $progress }}%</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if ((int) ($row->finish ?? 0) === 1)
                                        <span class="badge bg-green-lt text-green">Finished</span>
                                    @else
                                        <span class="badge bg-yellow-lt text-yellow">Ongoing</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">There is no learning activity yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">Recent Assessment Result</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Assessment</th>
                            <th class="text-center">Score</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentAssessment as $row)
                            <tr>
                                <td class="fw-bold">{{ optional($row->assesment)->title ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-blue-lt text-blue">{{ (int) ($row->score ?? 0) }}</span>
                                </td>
                                <td class="text-center">
                                    @if ((int) ($row->pass ?? 0) === 1)
                                        <span class="badge bg-green-lt text-green">Pass</span>
                                    @else
                                        <span class="badge bg-red-lt text-red">Not Pass</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">Belum ada hasil assessment.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
