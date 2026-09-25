<script src="{{ asset('js/jquery.smartWizard.min.js') }}" type="text/javascript"></script>

<script>
    // Universal Modal Opener / Closer compatible with Bootstrap 4 & 5
    function openPreviewModal(modalId) {
        var modalElement = document.getElementById(modalId);
        if (!modalElement) return;

        if (typeof $ !== 'undefined' && $.fn && typeof $.fn.modal === 'function') {
            $(modalElement).modal('show');
        } else if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            var modalInstance = window.bootstrap.Modal.getInstance ? window.bootstrap.Modal.getInstance(modalElement) : null;
            if (!modalInstance) {
                modalInstance = new window.bootstrap.Modal(modalElement);
            }
            modalInstance.show();
        } else {
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
            document.body.classList.add('modal-open');
            var backdrop = document.querySelector('.modal-backdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
            }
        }
    }

    function closePreviewModal(modalId) {
        var modalElement = document.getElementById(modalId);
        if (!modalElement) return;

        if (typeof $ !== 'undefined' && $.fn && typeof $.fn.modal === 'function') {
            $(modalElement).modal('hide');
        } else if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            var modalInstance = window.bootstrap.Modal.getInstance ? window.bootstrap.Modal.getInstance(modalElement) : null;
            if (modalInstance) {
                modalInstance.hide();
            }
        } else {
            modalElement.style.display = 'none';
            modalElement.classList.remove('show');
            document.body.classList.remove('modal-open');
            var backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
        }
    }

    $(function () {
        // Initialize SmartWizard for Question Navigation
        $('#smartwizard').smartWizard({
            selected: 0,
            theme: 'dots',
            enableUrlHash: false,
            anchor: {
                enableNavigationAlways: true
            }
        });

        // Sync answered status to navigation dots (Client-Side Only / No DB Persistence)
        function updatePreviewNavAnswerStatus() {
            $('#previewQuestionsContainer input[type="radio"]:checked').each(function () {
                let questionId = $(this).attr('name');
                if (questionId) {
                    $('.assessment-wizard .nav a[href="#step-' + questionId + '"]').addClass('done');
                }
            });
        }

        // Mark question as answered when clicking radio option
        $(document).on('change', '#previewQuestionsContainer input[type="radio"]', function () {
            let questionId = $(this).attr('name');
            if ($(this).is(':checked') && questionId) {
                $('.assessment-wizard .nav a[href="#step-' + questionId + '"]').addClass('done');
            }
        });

        // Re-sync dots on step transition
        $('#smartwizard').on('showStep', function () {
            updatePreviewNavAnswerStatus();
        });

        // Finish Preview button click handler (Opens modal, NO backend request)
        $(document).on('click', '#btnFinishPreview', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openPreviewModal('finishPreviewModal');
        });

        // Confirm Finish Preview: Submit temporary selected answers to preview result route
        $(document).on('click', '#btnConfirmFinishPreview', function (e) {
            e.preventDefault();
            var form = document.getElementById('formPreviewAssessment');
            if (form) {
                var $btn = $(this);
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Calculating Result...');
                form.submit();
            }
        });

        // Modal dismiss handlers
        $(document).on('click', '[data-bs-dismiss="modal"], [data-dismiss="modal"]', function () {
            var modal = $(this).closest('.modal');
            if (modal.length && modal.attr('id')) {
                closePreviewModal(modal.attr('id'));
            }
        });
    });
</script>

{{-- Visual-Only Countdown Timer Simulation (No Auto-Submit, No Backend POST) --}}
@if (!empty($countDownDate) || !empty($expiresAtIso))
    <script>
        (function() {
            var targetTimeString = "{{ $expiresAtIso ?? $countDownDate }}";
            var countDownDate = new Date(targetTimeString).getTime();

            function renderCountdownTick() {
                var now = new Date().getTime();
                var distance = countDownDate - now;

                var timerEl = document.getElementById("timer");

                if (distance <= 0) {
                    clearInterval(x);
                    if (timerEl) {
                        timerEl.innerHTML = '<div class="h2 mb-0 text-danger fw-bold">PREVIEW TIME ENDED</div>';
                    }
                    // PREVIEW MODE: ZERO database write, ZERO auto-submit to backend!
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
            }

            renderCountdownTick();
            var x = setInterval(renderCountdownTick, 1000);
        })();
    </script>
@endif
