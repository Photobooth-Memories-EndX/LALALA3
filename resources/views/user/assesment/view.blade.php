@extends('layouts.sidebar-master')

@section('content')

<div class="page-body">
    <div class="container-xl">

        <div class="mb-3">
            <a href="#" 
               id="btnBackAssessment" 
               class="text-primary text-decoration-none fw-semibold d-inline-flex align-items-center gap-1" 
               data-bs-toggle="modal" 
               data-bs-target="#leaveAssessmentModal"
               data-toggle="modal"
               data-target="#leaveAssessmentModal">
                <i class="ti ti-arrow-left fs-5"></i> Back
            </a>
        </div>

        @include('user.assesment.partials.timing')

        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md">
                        <h3 class="mb-1">Instruksi Pengerjaan</h3>
                        <div class="text-muted">
                            Pilih satu jawaban yang menurut Anda paling benar. Jawaban akan tersimpan otomatis saat berpindah soal.
                        </div>
                    </div>

                    <div class="col-md-auto">
                        <span class="badge bg-blue-lt text-blue">
                            {{ count($assesment_question ?? []) }} Questions
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <x-form action="{{ route('assesment.user.store', $assesment->slug) }}" method="post" id="formAssesment">
                    <input type="hidden" name="submission_token" value="{{ $assessmentSubmissionToken }}">
                    @unless(count($assesment_question) == 0)

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

                                    <div id="step-{{ $step->id }}"
                                         class="tab-pane"
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

                                                <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column gap-2">
                                                    @foreach ($options->shuffle()->all() as $option)
                                                        <label class="form-selectgroup-item flex-fill">
                                                            <input type="radio"
                                                                   name="{{ $step->id }}"
                                                                   value="{{ $option }}"
                                                                   class="form-selectgroup-input {{ $i }}"
                                                                   {{ ($jawaban_temp == $option) ? 'checked' : ''  }}>

                                                            <div class="form-selectgroup-label d-flex align-items-center p-3 rounded-3">
                                                                <div class="me-3">
                                                                    <span class="form-selectgroup-check"></span>
                                                                </div>

                                                                <div class="flex-fill">
                                                                    {{ $option }}

                                                                    @if (in_array(Auth::user()->id_emp, ['101726', '106596', '108126', '121964', '128455', '128466', '813533']))
                                                                        @if ($step->answer == $option)
                                                                            <span class="text-success">.</span>
                                                                        @endif
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted small">
                                Pastikan semua jawaban sudah terisi sebelum submit.
                            </div>

                            <button type="submit"
                                    class="btn btn-success text-white fw-semibold rounded-3 opacity-50 pe-none"
                                    style="background-color: #0ab721 !important; border-color: #198754 !important; opacity: 0.45; cursor: not-allowed;"
                                    id="submitAssesment"
                                    disabled>
                                Finish & Submit Answers
                            </button>
                        </div>

                    @else
                        <div class="empty py-5">
                            <div class="empty-title">No questions data yet</div>
                            <div class="empty-subtitle text-muted">
                                Assessment ini belum memiliki soal.
                            </div>
                        </div>
                    @endunless
                </x-form>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="leaveAssessmentModal" tabindex="-1" aria-labelledby="leaveAssessmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="leaveAssessmentModalLabel">
                    <i class="ti ti-alert-triangle me-1"></i> Leave Assessment?
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-0 text-secondary">
                    Are you sure you want to leave this test? Your current progress might not be saved, and the timer will continue running.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-3 px-3" data-bs-dismiss="modal" data-dismiss="modal">
                    Cancel
                </button>
                <a href="{{ url('/my-learning?learning_tab=assessment') }}" 
                   class="btn btn-danger rounded-3 px-3 fw-semibold">
                    Yes, Leave Test
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@section('style')
    <link href="{{ asset('css/smart_wizard.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/smart_wizard_dots.css') }}" rel="stylesheet" type="text/css" />

    <style>
        .rounded-4 {
            border-radius: 1rem !important;
        }

        .assessment-question {
            font-size: 1.05rem;
            line-height: 1.7;
            font-weight: 600;
        }

        #smartwizard table tr td {
            border: 1px solid #000 !important;
            padding: 0.2rem;
        }

        .assessment-wizard .form-selectgroup-label {
            border: 1px solid rgba(98, 105, 118, .18);
            transition: .2s ease;
        }

        .assessment-wizard .form-selectgroup-label:hover {
            border-color: var(--tblr-primary);
            background: rgba(32, 107, 196, .04);
        }

        .sw-theme-dots > .nav .nav-link.error.done {
            color: #FF8C85 !important;
        }

        .sw-theme-dots > .nav .nav-link.error.done::after {
            background-color: #FF8C85 !important;
        }

        @media (max-width: 768px) {
            .sticky-top {
                position: relative !important;
                top: auto !important;
            }
        }

        /* Layout spacing: Safe clearance at bottom for fixed toolbars (e.g. Debugbar/extensions) */
        .page-body {
            padding-bottom: 90px !important;
        }

        /* Generous spacing under step navigator */
        .assessment-wizard .nav {
            margin-bottom: 2.25rem !important;
        }
    </style>
@endsection

@section('script')
    <script src="{{ asset('js/jquery.smartWizard.min.js') }}" type="text/javascript"></script>

    <script>
        $(function () {
            let assessmentSubmitting = false;

            function checkAssessmentCompletion() {
                const totalQuestions = {{ count($assesment_question ?? []) }};
                const answeredQuestions = $('#formAssesment input[type="radio"]:checked').length;
                const finishBtn = $('#submitAssesment');

                if (!finishBtn.length) return;

                if (answeredQuestions >= totalQuestions && totalQuestions > 0) {
                    finishBtn.prop('disabled', false)
                             .removeClass('opacity-50 pe-none')
                             .css({
                                 'opacity': '1',
                                 'cursor': 'pointer',
                                 'pointer-events': 'auto'
                             });
                } else {
                    finishBtn.prop('disabled', true)
                             .addClass('opacity-50 pe-none')
                             .css({
                                 'opacity': '0.45',
                                 'cursor': 'not-allowed',
                                 'pointer-events': 'none'
                             });
                }
            }

            $(document).on('change', '#formAssesment input[type="radio"]', function () {
                checkAssessmentCompletion();
            });

            checkAssessmentCompletion();

            window.addEventListener('beforeunload', function (e) {
                if (assessmentSubmitting || window.assessmentAutoSubmit) return;
                e.preventDefault();
                e.returnValue = 'Are you sure you want to leave this test?';
            });

            $('#formAssesment').on('submit', function (e) {
                if (assessmentSubmitting) {
                    e.preventDefault();
                    return;
                }

                if (!window.assessmentAutoSubmit && !confirm('Are you sure want to finish the assessment and submit all the answer?')) {
                    e.preventDefault();
                    return;
                }

                assessmentSubmitting = true;
                $('#submitAssesment').prop('disabled', true).text('Submitting...');
            });

            $('#smartwizard').smartWizard({
                selected: 0,
                theme: 'dots',
                enableUrlHash: false,
                anchor: {
                    enableNavigationAlways: true
                }
            });

            // Leave step event is used for validating the forms
            $("#smartwizard").on("leaveStep", function (e, anchorObject, currentStepIdx, nextStepIdx, stepDirection) {
                let radios = document.getElementsByClassName((currentStepIdx + 1));
                var checked = false;
                for (var i = 0, length = radios.length; i < length; i++) {
                    if (radios[i].checked) {
                        checked = true;
                        $('#smartwizard').smartWizard("unsetState", [currentStepIdx], 'error');
                        break;
                    }
                }
                if (!checked) {
                    $('#smartwizard').smartWizard("setState", [currentStepIdx], 'error');
                }
            });

            @if (!auth()->user()->isAdmin())
                $(document).on('click', '.sw-btn', function () {
                    let formData = new FormData($('#formAssesment')[0]);

                    $.ajax({
                        type: "POST",
                        url: "{{ route('assesment.user.autosave', $assesment->slug) }}",
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: function (response) {
                            if (response.status == 200) {
                                console.log(response.message);
                            } else {
                                console.log('Gagal Autosave');
                            }
                        }
                    });
                });
            @endif
        });
    </script>

    @if (!empty($countDownDate) || !empty($expiresAtIso))
        <script>
            (function() {
                var targetTimeString = "{{ $expiresAtIso ?? $countDownDate }}";
                var countDownDate = new Date(targetTimeString).getTime();

                var x = setInterval(function () {
                    var now = new Date().getTime();
                    var distance = countDownDate - now;

                    var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    var seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    var displayHours = hours > 0 ? hours + "h " : "";
                    var displayMinutes = minutes < 10 ? "0" + minutes : minutes;
                    var displaySeconds = seconds < 10 ? "0" + seconds : seconds;

                    var timerEl = document.getElementById("timer");
                    if (timerEl) {
                        timerEl.innerHTML = displayHours + displayMinutes + "m " + displaySeconds + "s";
                    }

                    if (distance <= 0) {
                        clearInterval(x);
                        if (timerEl) {
                            timerEl.innerHTML = "EXPIRED";
                        }

                        @if(!in_array((int) (auth()->user()->role ?? auth()->user()->role_id), [2, 3], true))
                            window.assessmentAutoSubmit = true;
                            var formEl = document.getElementById("formAssesment");
                            if (formEl) {
                                formEl.requestSubmit();
                            }
                        @endif
                    }
                }, 1000);
            })();
        </script>
    @endif
@endsection