<script src="{{ asset('js/jquery.smartWizard.min.js') }}" type="text/javascript"></script>

<script>
    $(function () {
        let assessmentSubmitting = false;
        let isSaving = false;
        let hasPendingSave = false;

        function doAutosave() {
            if (assessmentSubmitting) return;
            if (isSaving) {
                hasPendingSave = true;
                return;
            }

            isSaving = true;
            hasPendingSave = false;

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
                },
                error: function() {
                    console.log('Error Autosave');
                },
                complete: function () {
                    isSaving = false;
                    if (hasPendingSave) {
                        doAutosave();
                    }
                }
            });
        }

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

        function updateNavAnswerStatus() {
            $('#formAssesment input[type="radio"]:checked').each(function () {
                let questionId = $(this).attr('name');
                if (questionId) {
                    $('.assessment-wizard .nav a[href="#step-' + questionId + '"]').addClass('done');
                }
            });
        }

        $(document).on('change', '#formAssesment input[type="radio"]', function () {
            let questionId = $(this).attr('name');
            if ($(this).is(':checked') && questionId) {
                $('.assessment-wizard .nav a[href="#step-' + questionId + '"]').addClass('done');
            }
            checkAssessmentCompletion();
            @if (!auth()->user()->isAdmin())
                doAutosave();
            @endif
        });

        updateNavAnswerStatus();
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

        // Hydrate and sync answered navigation dots immediately after SmartWizard initialization
        updateNavAnswerStatus();

        // Re-sync answered navigation dots whenever active step changes
        $("#smartwizard").on("showStep", function (e, anchorObject, stepIndex, stepDirection, stepPosition) {
            updateNavAnswerStatus();
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

            @if (!auth()->user()->isAdmin())
                doAutosave();
            @endif
        });

        @if (!auth()->user()->isAdmin())
            $(document).on('click', '.sw-btn, .assessment-wizard .nav a', function () {
                doAutosave();
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

                var timerEl = document.getElementById("timer");

                if (distance <= 0) {
                    clearInterval(x);
                    if (timerEl) {
                        timerEl.innerHTML = '<div class="h2 mb-0 text-danger fw-bold">EXPIRED</div>';
                    }

                    @if(!in_array((int) (auth()->user()->role ?? auth()->user()->role_id), [2, 3], true))
                        window.assessmentAutoSubmit = true;
                        var formEl = document.getElementById("formAssesment");
                        if (formEl) {
                            if (!formEl.querySelector('input[name="is_expired"]')) {
                                var expiredInput = document.createElement("input");
                                expiredInput.type = "hidden";
                                expiredInput.name = "is_expired";
                                expiredInput.value = "1";
                                formEl.appendChild(expiredInput);
                            }
                            formEl.requestSubmit();
                        }
                    @endif
                    return;
                }

                var totalSeconds = Math.floor(distance / 1000);
                var days = Math.floor(totalSeconds / 86400);
                var remainingSeconds = totalSeconds % 86400;
                var hours = Math.floor(remainingSeconds / 3600);
                var minutes = Math.floor((remainingSeconds % 3600) / 60);
                var seconds = remainingSeconds % 60;

                var pad = function (num) {
                    return num < 10 ? '0' + num : num;
                };

                var daysLabel = (days === 1) ? '1 DAY' : (days + ' DAYS');
                var clockString = pad(hours) + ':' + pad(minutes) + ':' + pad(seconds);

                if (timerEl) {
                    timerEl.innerHTML = '<div class="small text-uppercase text-muted fw-semibold mb-1">' + daysLabel + '</div>' +
                                        '<div class="h1 mb-0 font-monospace text-danger fw-bold">' + clockString + '</div>';
                }
            }, 1000);
        })();
    </script>
@endif
