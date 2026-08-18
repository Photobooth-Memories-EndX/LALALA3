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

        // Cegat tombol Back bawaan browser (Chrome Back Button)
        history.pushState(null, null, location.href);

        window.addEventListener('popstate', function (event) {
            history.pushState(null, null, location.href);
            pendingTargetUrl = "{{ url('/my-learning?learning_tab=assessment') }}";

            if (leaveModal) {
                leaveModal.show();
            } else if (window.jQuery && $(leaveModalEl).modal) {
                $(leaveModalEl).modal('show');
            }
        });

        // Tangkap semua klik tautan internal aplikasi (termasuk Home & Logo)
        document.addEventListener('click', function(e) {
            var anchor = e.target.closest('a[href]');
            if (!anchor) return;

            var rawHref = anchor.getAttribute('href');
            if (!rawHref || rawHref.startsWith('#') || rawHref.startsWith('javascript:')) return;

            var currentPath = window.location.pathname;
            var currentHref = window.location.href.split('#')[0];
            var targetHref = anchor.href;
            var targetHrefClean = targetHref ? targetHref.split('#')[0] : '';

            // Jika URL mengarah keluar dari lembar ujian saat ini
            if (rawHref !== currentPath && targetHrefClean !== currentHref) {
                e.preventDefault();
                e.stopPropagation();
                pendingTargetUrl = targetHref;

                if (leaveModal) {
                    leaveModal.show();
                } else if (window.jQuery && $(leaveModalEl).modal) {
                    $(leaveModalEl).modal('show');
                }
            }
        }, true); // Gunakan parameter true (capturing phase) agar diproses lebih dulu

        // Ketika user menekan tombol "Yes, Leave Test" di modal
        var confirmBtn = document.getElementById('btnConfirmLeaveTest');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                window.assessmentAutoSubmit = true;
                this.disabled = true;
                this.innerText = 'Submitting...';

                var assessmentForm = document.getElementById('formAssesment') || document.getElementById('assessmentForm') || document.querySelector('form[action*="assesment"]') || document.querySelector('form');

                if (assessmentForm) {
                    // Hapus validasi HTML5 jika ada agar form tetap bisa ter-submit meskipun belum semua soal diisi
                    assessmentForm.removeAttribute('novalidate');
                    
                    // Trigger pemicu submit form assessment secara resmi
                    if (typeof assessmentForm.requestSubmit === 'function') {
                        assessmentForm.requestSubmit();
                    } else {
                        assessmentForm.submit();
                    }
                } else {
                    // Fallback submit jika tombol Finish & Submit Answers diklik secara terprogram
                    var finishBtn = document.getElementById('submitAssesment') || document.getElementById('btnFinishSubmit') || document.querySelector('button[type="submit"]');
                    if (finishBtn) {
                        finishBtn.disabled = false; // Aktifkan bypass
                        finishBtn.click();
                    }
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
