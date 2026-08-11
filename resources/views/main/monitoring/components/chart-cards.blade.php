{{--
    Props:
    $charts      (array|null) — keys: category, type_distribution, status_distribution,
                                      monthly_activity, completion_trend, assessment_trend,
                                      top_categories, hours_trend
    $chartPrefix (string)     — unique prefix: 'monitoring-main' / 'monitoring-group' / 'monitoring-profile'
--}}
@php $p = $chartPrefix ?? 'monitoring'; @endphp

@if (empty($charts))
    <div class="alert alert-info mb-3">
        <i class="fas fa-info-circle mr-1"></i> Belum ada data analytics untuk ditampilkan.
    </div>
@else

@once
    @push('scripts')
        <script src="{{ asset('Tabler/dist/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
    @endpush
@endonce

{{-- Row 1: 3 donut charts --}}
<div class="row row-cards mb-3">
    <div class="col-12 col-lg-4 mb-3">
        <div class="card owl-main-card h-100">
            <div class="card-header bg-white border-0">
                <h3 class="card-title mb-0">Progress by Category</h3>
            </div>
            <div class="card-body">
                <div id="{{ $p }}-category-chart" style="min-height:260px;"></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4 mb-3">
        <div class="card owl-main-card h-100">
            <div class="card-header bg-white border-0">
                <h3 class="card-title mb-0">Learning Type Distribution</h3>
            </div>
            <div class="card-body">
                <div id="{{ $p }}-type-chart" style="min-height:260px;"></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4 mb-3">
        <div class="card owl-main-card h-100">
            <div class="card-header bg-white border-0">
                <h3 class="card-title mb-0">Progress Status</h3>
            </div>
            <div class="card-body">
                <div id="{{ $p }}-status-chart" style="min-height:260px;"></div>
            </div>
        </div>
    </div>
</div>

{{-- Row 2: Monthly Activity + Completion Trend --}}
<div class="row row-cards mb-3">
    <div class="col-12 col-lg-6 mb-3">
        <div class="card owl-main-card h-100">
            <div class="card-header bg-white border-0">
                <h3 class="card-title mb-0">Monthly Learning Activity</h3>
            </div>
            <div class="card-body">
                <div id="{{ $p }}-monthly-chart" style="min-height:260px;"></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6 mb-3">
        <div class="card owl-main-card h-100">
            <div class="card-header bg-white border-0">
                <h3 class="card-title mb-0">Learning Completion Trend</h3>
            </div>
            <div class="card-body">
                <div id="{{ $p }}-completion-chart" style="min-height:260px;"></div>
            </div>
        </div>
    </div>
</div>

{{-- Row 3: Assessment Score Trend + Top 5 Categories --}}
<div class="row row-cards mb-3">
    <div class="col-12 col-lg-6 mb-3">
        <div class="card owl-main-card h-100">
            <div class="card-header bg-white border-0">
                <h3 class="card-title mb-0">Assessment Score Trend</h3>
            </div>
            <div class="card-body">
                <div id="{{ $p }}-assessment-chart" style="min-height:260px;"></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6 mb-3">
        <div class="card owl-main-card h-100">
            <div class="card-header bg-white border-0">
                <h3 class="card-title mb-0">Top 5 Categories</h3>
            </div>
            <div class="card-body">
                <div id="{{ $p }}-top-chart" style="min-height:260px;"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var C = @json($charts);
    var P = @json($p);

    function whenReady(cb) {
        if (window.ApexCharts) { cb(); return; }
        var attempts = 0;
        var t = setInterval(function () {
            attempts++;
            if (window.ApexCharts) { clearInterval(t); cb(); }
            if (attempts > 160) clearInterval(t); // 8s timeout
        }, 50);
    }

    function isDark() {
        var h = document.documentElement, b = document.body;
        return h.getAttribute('data-bs-theme') === 'dark'
            || h.getAttribute('data-theme') === 'dark'
            || b.classList.contains('theme-dark');
    }

    function draw(id, opts) {
        var el = document.getElementById(id);
        if (!el) return;
        try { new ApexCharts(el, opts).render(); } catch (e) { console.error(id, e); }
    }

    whenReady(function () {
        var mode    = isDark() ? 'dark' : 'light';
        var palette = ['#206bc4', '#4299e1', '#2fb344', '#ae3ec9', '#f59f00', '#d63939', '#17a2b8', '#74b816'];

        function base(type) {
            return {
                chart: { type: type, height: 260, background: 'transparent', toolbar: { show: false } },
                theme: { mode: mode },
                colors: palette,
                dataLabels: { enabled: false },
                grid: { borderColor: 'rgba(128,128,128,.15)' }
            };
        }

        function circular(type, d) {
            return Object.assign(base(type), {
                series: (d && d.series) ? d.series : [],
                labels: (d && d.labels) ? d.labels : [],
                legend: { position: 'bottom' },
                noData: { text: 'Tidak ada data' }
            });
        }

        function xyArea(d, name) {
            return Object.assign(base('area'), {
                series: [{ name: name, data: (d && d.series) ? d.series : [] }],
                xaxis: { categories: (d && d.labels) ? d.labels : [] },
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
                noData: { text: 'Tidak ada data' }
            });
        }

        function xyLine(d, name) {
            return Object.assign(base('line'), {
                series: [{ name: name, data: (d && d.series) ? d.series : [] }],
                xaxis: { categories: (d && d.labels) ? d.labels : [] },
                stroke: { curve: 'smooth', width: 3 },
                noData: { text: 'Tidak ada data' }
            });
        }

        draw(P + '-category-chart',   circular('donut', C.category));
        draw(P + '-type-chart',       circular('donut', C.type_distribution));
        draw(P + '-status-chart',     circular('pie',   C.status_distribution));
        draw(P + '-monthly-chart',    xyArea(C.monthly_activity,  'Aktivitas'));
        draw(P + '-completion-chart', xyLine(C.completion_trend,  'Selesai'));
        draw(P + '-assessment-chart', xyLine(C.assessment_trend,  'Rata-rata Skor'));

        var top = C.top_categories || {};
        draw(P + '-top-chart', Object.assign(base('bar'), {
            series: [{ name: 'Learning', data: top.series || [] }],
            xaxis:  { categories: top.labels || [] },
            plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
            noData: { text: 'Tidak ada data' }
        }));
    });
})();
</script>
@endpush

@endif
