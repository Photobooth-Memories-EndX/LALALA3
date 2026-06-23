<div class="modern-card mt-4">

    <div class="card-header border-0">

        <h2 class="section-title mb-0">

            Learning Progress

        </h2>

    </div>

    <div class="card-body">

        @php

            $completed = $content_user
                ->where('finish',1)
                ->count();

            $total = $content_user
                ->count();

            $progress = $total > 0
                ? round(($completed / $total) * 100)
                : 0;

        @endphp

        <div class="progress-summary">

            <div class="progress-circle-small">

                {{ $progress }}%

            </div>

            <div>

                <h4 class="mb-1">

                    {{ $completed }}

                    Completed Courses

                </h4>

                <small class="text-muted">

                    Keep going to reach your learning goals.

                </small>

            </div>

        </div>

        <div class="progress progress-modern mt-4">

            <div
                class="progress-bar"
                style="width: {{ $progress }}%">
            </div>

        </div>

    </div>

</div>
