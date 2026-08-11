<div class="row g-3 mt-2">

    {{-- IN PROGRESS --}}
    <div class="col-lg-4 col-md-4 col-6">
        <div class="home-modern-stat home-modern-stat-blue">
            <div class="home-modern-stat-icon">
                <i class="ti ti-player-play-filled"></i>
            </div>
            <div class="home-modern-stat-body">
                <div class="home-modern-stat-number">{{ str_pad($ongoingCourse,2,'0',STR_PAD_LEFT) }}</div>
                <div class="home-modern-stat-label">In Progress</div>
            </div>
        </div>
    </div>

    {{-- COMPLETED --}}
    <div class="col-lg-4 col-md-4 col-6">
        <div class="home-modern-stat home-modern-stat-green">
            <div class="home-modern-stat-icon">
                <i class="ti ti-circle-check-filled"></i>
            </div>
            <div class="home-modern-stat-body">
                <div class="home-modern-stat-number">{{ str_pad($completedCourse,2,'0',STR_PAD_LEFT) }}</div>
                <div class="home-modern-stat-label">Completed</div>
            </div>
        </div>
    </div>

    {{-- DEADLINES --}}
    <div class="col-lg-4 col-md-4 col-12">
        <div class="home-modern-stat home-modern-stat-red">
            <div class="home-modern-stat-icon">
                <i class="ti ti-calendar-event"></i>
            </div>
            <div class="home-modern-stat-body">
                <div class="home-modern-stat-number">{{ str_pad($upcomingDeadlines->count(),2,'0',STR_PAD_LEFT) }}</div>
                <div class="home-modern-stat-label">Deadlines</div>
            </div>
        </div>
    </div>

</div>
