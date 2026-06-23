{{-- Team analytics (role 3/4). Data: $teamAnalytics (lihat LearningAnalyticsService::team). --}}
<div class="mb-2">
    <h3 class="mb-1">Team Analytics</h3>
    <div class="text-muted">Ringkasan performa belajar seluruh anggota dalam cakupan Anda.</div>
</div>

{{-- Team summary --}}
<div class="row row-cards mb-3">
    @php
        $ts = $teamAnalytics['summary'];
        $teamCards = [
            ['label' => 'Team Avg Progress',   'value' => $ts['average_progress'] . '%', 'icon' => 'ti-chart-line',      'color' => 'azure'],
            ['label' => 'Team Assessment Avg',  'value' => $ts['assessment_avg'],         'icon' => 'ti-clipboard-check', 'color' => 'purple'],
            ['label' => 'Team Learning Hours',  'value' => $ts['learning_hours'],         'icon' => 'ti-clock',           'color' => 'green'],
            ['label' => 'Team Completion Rate', 'value' => $ts['completion_rate'] . '%',  'icon' => 'ti-checkbox',        'color' => 'primary'],
        ];
    @endphp
    @foreach ($teamCards as $card)
        <div class="col-6 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <span class="avatar bg-{{ $card['color'] }}-lt text-{{ $card['color'] }} me-3">
                            <i class="ti {{ $card['icon'] }}"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="text-muted small text-truncate">{{ $card['label'] }}</div>
                            <div class="h2 mb-0">{{ $card['value'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row row-cards mb-3">
    <div class="col-12 col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Team Progress Distribution</h3></div>
            <div class="card-body"><div id="ta-distribution" style="min-height:260px;"></div></div>
        </div>
    </div>
    <div class="col-12 col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Team Monthly Activity</h3></div>
            <div class="card-body"><div id="ta-monthly" style="min-height:260px;"></div></div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h3 class="card-title">Top Learner</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th class="w-1">#</th>
                    <th>Nama</th>
                    <th>NIP</th>
                    <th class="text-center">Content Selesai</th>
                    <th class="text-center">Avg Progress</th>
                    <th class="text-center">Avg Score</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($teamAnalytics['top_learners'] as $i => $learner)
                    <tr>
                        <td>
                            @if ($i === 0)
                                <span class="badge bg-yellow text-white">1</span>
                            @else
                                <span class="text-muted">{{ $i + 1 }}</span>
                            @endif
                        </td>
                        <td class="fw-bold">{{ $learner['name'] }}</td>
                        <td class="text-muted">{{ $learner['id_emp'] }}</td>
                        <td class="text-center">{{ number_format($learner['finished']) }}</td>
                        <td class="text-center">{{ $learner['avg_progress'] }}%</td>
                        <td class="text-center"><span class="badge bg-blue-lt text-blue">{{ $learner['avg_score'] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data anggota.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    var T = @json($teamAnalytics);

    function whenReady(cb) {
        if (window.ApexCharts) return cb();
        var t = setInterval(function () { if (window.ApexCharts) { clearInterval(t); cb(); } }, 50);
        setTimeout(function () { clearInterval(t); }, 6000);
    }
    function isDark() {
        var h = document.documentElement, b = document.body;
        return h.getAttribute('data-bs-theme') === 'dark'
            || h.getAttribute('data-theme') === 'dark'
            || b.classList.contains('theme-dark');
    }

    whenReady(function () {
        var mode = isDark() ? 'dark' : 'light';
        var palette = ['#206bc4', '#4299e1', '#2fb344', '#ae3ec9', '#f59f00'];

        function draw(id, opts) {
            var el = document.getElementById(id);
            if (!el) return;
            try { new ApexCharts(el, opts).render(); } catch (e) { console.error(id, e); }
        }

        var dist = T.progress_distribution || { labels: [], series: [] };
        draw('ta-distribution', {
            chart: { type: 'bar', height: 260, background: 'transparent', toolbar: { show: false } },
            theme: { mode: mode }, colors: palette, dataLabels: { enabled: false },
            grid: { borderColor: 'rgba(128,128,128,.15)' },
            series: [{ name: 'User', data: dist.series }],
            xaxis: { categories: dist.labels, title: { text: 'Rentang Progress (%)' } },
            plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
            noData: { text: 'Tidak ada data' }
        });

        var ma = T.monthly_activity || { labels: [], series: [] };
        draw('ta-monthly', {
            chart: { type: 'area', height: 260, background: 'transparent', toolbar: { show: false } },
            theme: { mode: mode }, colors: palette, dataLabels: { enabled: false },
            grid: { borderColor: 'rgba(128,128,128,.15)' },
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { opacityFrom: .4, opacityTo: .05 } },
            series: [{ name: 'Aktivitas', data: ma.series }],
            xaxis: { categories: ma.labels },
            noData: { text: 'Tidak ada data' }
        });
    });
})();
</script>
