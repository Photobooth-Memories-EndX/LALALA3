<div class="row g-4 mt-4">

    {{-- IN PROGRESS --}}
    <div class="col-lg-4 col-md-4">

        <div class="dashboard-stat stat-blue">

            <div class="stat-icon">

                <i class="ti ti-player-play"></i>

            </div>

            <div class="stat-number">

                {{ str_pad($ongoingCourse,2,'0',STR_PAD_LEFT) }}

            </div>

            <div class="stat-label">

                In Progress

            </div>

        </div>

    </div>

    {{-- COMPLETED --}}
    <div class="col-lg-4 col-md-4">

        <div class="dashboard-stat stat-green">

            <div class="stat-icon">

                <i class="ti ti-circle-check"></i>

            </div>

            <div class="stat-number">

                {{ str_pad($completedCourse,2,'0',STR_PAD_LEFT) }}

            </div>

            <div class="stat-label">

                Completed

            </div>

        </div>

    </div>

    {{-- DEADLINE --}}
    <div class="col-lg-4 col-md-4">

        <div class="dashboard-stat stat-red">

            <div class="stat-icon">

                <i class="ti ti-calendar-event"></i>

            </div>

            <div class="stat-number">

                {{ str_pad($upcomingDeadlines->count(),2,'0',STR_PAD_LEFT) }}

            </div>

            <div class="stat-label">

                Deadlines

            </div>

        </div>

    </div>

</div>
