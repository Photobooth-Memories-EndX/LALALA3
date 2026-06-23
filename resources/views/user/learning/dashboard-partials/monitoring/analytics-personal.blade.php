{{-- Personal analytics charts. Data: $analytics (lihat LearningAnalyticsService::personal). --}}
<div class="row row-cards mb-3">
    <div class="col-12 col-lg-4 mb-3">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Progress by Category</h3></div>
            <div class="card-body"><div id="pa-category" style="min-height:260px;"></div></div>
        </div>
    </div>
    <div class="col-12 col-lg-4 mb-3">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Learning Type Distribution</h3></div>
            <div class="card-body"><div id="pa-type" style="min-height:260px;"></div></div>
        </div>
    </div>
    <div class="col-12 col-lg-4 mb-3">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Progress Status</h3></div>
            <div class="card-body"><div id="pa-status" style="min-height:260px;"></div></div>
        </div>
    </div>
</div>

<div class="row row-cards mb-3">
    <div class="col-12 col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Monthly Learning Activity</h3></div>
            <div class="card-body"><div id="pa-monthly" style="min-height:260px;"></div></div>
        </div>
    </div>
    <div class="col-12 col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Learning Completion Trend</h3></div>
            <div class="card-body"><div id="pa-completion" style="min-height:260px;"></div></div>
        </div>
    </div>
</div>

<div class="row row-cards mb-3">
    <div class="col-12 col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Assessment Score Trend</h3></div>
            <div class="card-body"><div id="pa-assessment" style="min-height:260px;"></div></div>
        </div>
    </div>
    <div class="col-12 col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Learning Hours Trend</h3></div>
            <div class="card-body"><div id="pa-hours" style="min-height:260px;"></div></div>
        </div>
    </div>
</div>

<div class="row row-cards mb-3">
    <div class="col-12 col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Top 5 Favorite Categories</h3></div>
            <div class="card-body"><div id="pa-top" style="min-height:260px;"></div></div>
        </div>
    </div>
    <div class="col-12 col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Weekly Learning Activity</h3></div>
            <div class="card-body"><div id="pa-weekly" style="min-height:260px;"></div></div>
        </div>
    </div>
</div>

<script>
(function () {
    var A = @json($analytics);
    window.__owlApexHelpers ? renderPersonal() : loadHelpersThenRender();

    function loadHelpersThenRender() {
        // Helper umum (sekali saja per halaman).
        window.__owlApexHelpers = true;
        window.__owlWhenApexReady = function (cb) {
            if (window.ApexCharts) return cb();
            var t = setInterval(function () { if (window.ApexCharts) { clearInterval(t); cb(); } }, 50);
            setTimeout(function () { clearInterval(t); }, 6000);
        };
        window.__owlIsDark = function () {
            var h = document.documentElement, b = document.body;
            return h.getAttribute('data-bs-theme') === 'dark'
                || h.getAttribute('data-theme') === 'dark'
                || b.classList.contains('theme-dark');
        };
        renderPersonal();
    }

    function renderPersonal() {
        window.__owlWhenApexReady(function () {
            var mode = window.__owlIsDark() ? 'dark' : 'light';
            var palette = ['#206bc4', '#4299e1', '#2fb344', '#ae3ec9', '#f59f00', '#d63939', '#17a2b8', '#74b816'];

            function base(type, height) {
                return { chart: { type: type, height: height || 260, background: 'transparent', toolbar: { show: false } },
                    theme: { mode: mode }, colors: palette, dataLabels: { enabled: false },
                    grid: { borderColor: 'rgba(128,128,128,.15)' } };
            }
            function draw(id, opts) {
                var el = document.getElementById(id);
                if (!el) return;
                try { new ApexCharts(el, opts).render(); } catch (e) { console.error(id, e); }
            }
            function circular(type, d) {
                return Object.assign(base(type), { series: d.series || [], labels: d.labels || [],
                    legend: { position: 'bottom' }, noData: { text: 'Tidak ada data' } });
            }
            function xy(type, d, name) {
                return Object.assign(base(type), {
                    series: [{ name: name, data: d.series || [] }],
                    xaxis: { categories: d.labels || [] },
                    stroke: { curve: 'smooth', width: type === 'area' ? 2 : 3 },
                    fill: type === 'area' ? { type: 'gradient', gradient: { opacityFrom: .4, opacityTo: .05 } } : {},
                    noData: { text: 'Tidak ada data' }
                });
            }

            draw('pa-category', circular('donut', A.category));
            draw('pa-type', circular('donut', A.type_distribution));
            draw('pa-status', circular('pie', A.status_distribution));
            draw('pa-monthly', xy('area', A.monthly_activity, 'Aktivitas'));
            draw('pa-completion', xy('line', A.completion_trend, 'Selesai'));
            draw('pa-assessment', xy('line', A.assessment_trend, 'Rata-rata Skor'));
            draw('pa-hours', xy('area', A.hours_trend, 'Jam Belajar'));

            draw('pa-top', Object.assign(base('bar'), {
                series: [{ name: 'Learning', data: (A.top_categories && A.top_categories.series) || [] }],
                xaxis: { categories: (A.top_categories && A.top_categories.labels) || [] },
                plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
                noData: { text: 'Tidak ada data' }
            }));

            var wk = A.weekly_heatmap || { labels: [], series: [] };
            draw('pa-weekly', Object.assign(base('heatmap'), {
                series: [{ name: 'Aktivitas', data: (wk.labels || []).map(function (l, i) { return { x: l, y: (wk.series || [])[i] || 0 }; }) }],
                noData: { text: 'Tidak ada data' }
            }));
        });
    }
})();
</script>
