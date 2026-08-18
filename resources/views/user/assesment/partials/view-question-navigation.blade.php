<div id="smartwizard" class="assessment-wizard">
    <ul class="nav">
        @foreach ($assesment_question as $step)
            <li class="nav-item">
                <a class="nav-link" href="#step-{{ $step->id }}">
                    <div class="num">{{ $loop->index + 1 }}</div>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="progress mb-3">
        <div class="progress-bar bg-primary"
             role="progressbar"
             style="width: 0%"
             aria-valuenow="0"
             aria-valuemin="0"
             aria-valuemax="100">
        </div>
    </div>

    <div class="tab-content">
        @php
            $i = 0;
        @endphp

        @foreach ($assesment_question as $step)
            @php
                $i = $i + 1;
                $options = collect($step->option);
                $jawaban_temp = optional($assesment_result_draft)->user_answer[$step->id] ?? null;
            @endphp

            @include('user.assesment.partials.view-question-item', [
                'step' => $step,
                'loop' => $loop,
                'i' => $i,
                'options' => $options,
                'jawaban_temp' => $jawaban_temp,
            ])
        @endforeach
    </div>
</div>
