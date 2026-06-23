<div class="home-modern-hero">
    <div class="row align-items-center">

        <div class="col-lg-8">

            <span class="home-modern-hero-label">
                <i class="ti ti-school me-1"></i>
                Learning Management System
            </span>

            <h1 class="home-modern-hero-greeting">Welcome back,</h1>

            <h1 class="home-modern-hero-name">{{ auth()->user()->name }}</h1>

            <p class="home-modern-hero-subtitle">
                Continuing journey as <strong>{{ auth()->user()->team }}</strong>
            </p>

            <div class="home-modern-hero-badges">
                <span class="home-modern-badge-streak">
                    <i class="ti ti-flame me-1"></i>Learning Streak
                </span>
                <span class="home-modern-badge-progress">
                    <i class="ti ti-check me-1"></i>{{ $progressPercent }}% Completed
                </span>
            </div>

        </div>

        <div class="col-lg-4 text-center mt-4 mt-lg-0">

            <div class="home-modern-donut-wrap">

                <div class="home-modern-donut-outer"
                     style="background: conic-gradient(#38bdf8 {{ $progressPercent }}%, rgba(255,255,255,.15) 0)">
                    <div class="home-modern-donut-inner">
                        <strong>{{ $progressPercent }}%</strong>
                        <small>Progress</small>
                    </div>
                </div>

                <p class="home-modern-donut-label">Learning Progress</p>

            </div>

        </div>

    </div>
</div>
