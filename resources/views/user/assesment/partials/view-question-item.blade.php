<div id="step-{{ $step->id }}"
     class="tab-pane question-item"
     data-question-id="{{ $step->id }}"
     role="tabpanel"
     aria-labelledby="step-{{ $step->id }}">

    <div class="card border rounded-4 mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="badge bg-primary-lt text-primary">
                    Question {{ $loop->iteration }}
                </span>

                <span class="text-muted small">
                    Choose one answer
                </span>
            </div>

            <div class="assessment-question mb-3">
                {!! $step->question !!}
            </div>

            @if ($step->image != '')
                @php
                    $file = asset('storage/assesment').'/'.$step->image;

                    if (file_exists($file) == false) {
                        $file = asset('storage/question_bank/image').'/'.$step->image;
                    }
                @endphp

                <div class="mb-3">
                    <img src="{{ $file }}" class="img-fluid rounded-3 border">
                </div>
            @endif

            @include('user.assesment.partials.view-question-options', [
                'step' => $step,
                'options' => $options,
                'jawaban_temp' => $jawaban_temp,
                'i' => $i,
            ])
        </div>
    </div>
</div>
