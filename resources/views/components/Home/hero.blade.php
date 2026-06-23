<div class="dashboard-hero">

    <div class="row align-items-center">

        <div class="col-lg-8">

            <span class="hero-label">
                Learning Management System
            </span>

            <h1 class="hero-title">
                Welcome Back,
            </h1>

            <h1 class="hero-title hero-name">
                {{ auth()->user()->name }}
            </h1>

            <p class="hero-subtitle">
                Continue your learning journey as
                <strong>{{ auth()->user()->team }}</strong>
            </p>

            <div class="hero-achievement">

                <span class="streak-badge">
                    Learning Streak
                </span>

                <span class="learner-badge">
                    {{ $progressPercent }}% Completed
                </span>

            </div>

        </div>

        <div class="col-lg-4 text-center">

            <div class="progress-circle-text">

                <h1>
                    {{ $progressPercent }}%
                </h1>

                <span>
                    Learning Progress
                </span>

            </div>

        </div>

    </div>

</div>
