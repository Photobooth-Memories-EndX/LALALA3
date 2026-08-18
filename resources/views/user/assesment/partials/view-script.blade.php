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

    document.addEventListener('DOMContentLoaded', function() {
        var leaveModalEl = document.getElementById('leaveAssessmentModal');
        var leaveModal = leaveModalEl && window.bootstrap ? new bootstrap.Modal(leaveModalEl) : null;
        var pendingTargetUrl = null;

        // Tangkap semua klik tautan internal aplikasi
        document.addEventListener('click', function(e) {
            var anchor = e.target.closest('a[href]');
            if (!anchor) return;

            var href = anchor.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

            // Jika URL mengarah keluar dari lembar ujian saat ini
            if (!window.location.href.includes(href)) {
                e.preventDefault();
                e.stopPropagation();
                pendingTargetUrl = href;

                if (leaveModal) {
                    leaveModal.show();
                } else if (window.jQuery && $(leaveModalEl).modal) {
                    $(leaveModalEl).modal('show');
                }
            }
        }, true); // Gunakan parameter true (capturing phase) agar diproses lebih dulu

        // Eksekusi pindah halaman jika user klik "Yes, Leave Test"
        var confirmBtn = document.getElementById('btnConfirmLeaveTest');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function() {
                if (pendingTargetUrl) {
                    window.location.href = pendingTargetUrl;
                }
            });
        }
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
