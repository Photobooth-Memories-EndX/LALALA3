<script src="{{ asset('js/jquery.smartWizard.min.js') }}" type="text/javascript"></script>

<script>
    // Universal Modal Opener / Closer compatible with Bootstrap 4 & 5
    function openAssessmentModal(modalId) {
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

    function closeAssessmentModal(modalId) {
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

    function getAnsweredQuestionsCount() {
        var checkedInputs = document.querySelectorAll('#formAssesment input[type="radio"]:checked, #formAssesment input[type="checkbox"]:checked, #formAssesment select, #formAssesment textarea');
        var answeredGroupNames = new Set();
        checkedInputs.forEach(function(input) {
            if (input.value && input.value.trim() !== '') {
                answeredGroupNames.add(input.name);
            }
        });
        return answeredGroupNames.size;
    }

    function getTotalQuestionsCount() {
        var questionItems = document.querySelectorAll('.question-item, [data-question-id], .assessment-wizard .tab-pane');
        if (questionItems.length > 0) return questionItems.length;

        var allInputs = document.querySelectorAll('#formAssesment input[type="radio"]');
        var allNames = new Set();
        allInputs.forEach(function(i) {
            allNames.add(i.name);
        });
        return allNames.size || {{ count($assesment_question ?? []) }} || 10;
    }

    function updateLeaveModalText() {
        var answeredEl = document.getElementById('answeredCount');
        var totalEl = document.getElementById('totalQuestionsCount');
        if (answeredEl) answeredEl.textContent = getAnsweredQuestionsCount();
        if (totalEl) totalEl.textContent = getTotalQuestionsCount();
    }

    function showLeaveModal() {
        updateLeaveModalText();
        openAssessmentModal('leaveAssessmentModal');
    }

    function checkFinishButtonState() {
        const answeredCount = getAnsweredQuestionsCount();
        const totalCount = getTotalQuestionsCount();
        const finishBtn = document.getElementById('btnFinishSubmit') || document.querySelector('.btn-submit-assessment');

        if (!finishBtn) return;

        // HANYA aktifkan jika soal terjawab SUDAH SAMA DENGAN total soal
        if (answeredCount >= totalCount && totalCount > 0) {
            finishBtn.disabled = false;
            finishBtn.classList.remove('opacity-50', 'pe-none');
            finishBtn.style.opacity = '1';
            finishBtn.style.cursor = 'pointer';
            finishBtn.style.pointerEvents = 'auto';
        } else {
            finishBtn.disabled = true;
            finishBtn.classList.add('opacity-50', 'pe-none');
            finishBtn.style.opacity = '0.5';
            finishBtn.style.cursor = 'not-allowed';
            finishBtn.style.pointerEvents = 'none';
        }
    }

    function submitAssessmentForm() {
        window.assessmentAutoSubmit = true;
        if (typeof window.updateAfkHiddenInput === 'function') {
            window.updateAfkHiddenInput();
        }
        var form = document.getElementById('formAssesment') || document.getElementById('assessmentForm') || document.querySelector('form[action*="assesment"]') || document.querySelector('form');
        if (form) {
            if (typeof window.updateAfkHiddenInput === 'function') {
                var afkInput = form.querySelector('input[name="afk_seconds"]');
                if (!afkInput) {
                    afkInput = document.createElement("input");
                    afkInput.type = "hidden";
                    afkInput.name = "afk_seconds";
                    form.appendChild(afkInput);
                }
                afkInput.value = window.updateAfkHiddenInput();
            }
            form.removeAttribute('novalidate');
            $('#btnFinishSubmit, #submitAssesment, #btnConfirmFinishSubmit, #btnConfirmLeaveTest').prop('disabled', true).text('Submitting...');
            form.submit();
        } else {
            var finishBtn = document.getElementById('btnFinishSubmit') || document.getElementById('submitAssesment') || document.querySelector('button[type="submit"]');
            if (finishBtn) {
                finishBtn.disabled = false;
                finishBtn.click();
            }
        }
    }

    $(function () {
        let assessmentSubmitting = false;
        let isSaving = false;
        let hasPendingSave = false;

        // =========================================================================
        // AFK (Away From Keyboard) Tracking Module
        // =========================================================================
        const afkThresholdSeconds = {{ (int) ($assesment->afk_threshold_seconds ?? 60) }};
        let cumulativeAfkSeconds = {{ (int) ($assesment_result_draft->afk_duration_seconds ?? 0) }};
        let lastActivityTimestamp = Date.now();
        let isAfkActive = false;
        let afkStartTimestamp = null;
        const afkAlertBanner = document.getElementById('afkAlertBanner');
        const afkElapsedCounter = document.getElementById('afkElapsedCounter');
        const inputAfkSeconds = document.getElementById('inputAfkSeconds');

        window.updateAfkHiddenInput = function () {
            let total = cumulativeAfkSeconds;
            if (isAfkActive && afkStartTimestamp) {
                total += Math.max(0, Math.floor((Date.now() - afkStartTimestamp) / 1000));
            }
            if (inputAfkSeconds) {
                inputAfkSeconds.value = total;
            }
            return total;
        };

        function showAfkState() {
            if (afkAlertBanner) {
                afkAlertBanner.classList.remove('d-none');
                afkAlertBanner.classList.add('d-flex');
            }
        }

        function hideAfkState() {
            if (afkAlertBanner) {
                afkAlertBanner.classList.remove('d-flex');
                afkAlertBanner.classList.add('d-none');
            }
        }

        function recordUserActivity() {
            const now = Date.now();
            if (isAfkActive) {
                if (afkStartTimestamp) {
                    const elapsed = Math.max(0, Math.floor((now - afkStartTimestamp) / 1000));
                    cumulativeAfkSeconds += elapsed;
                }
                isAfkActive = false;
                afkStartTimestamp = null;
                hideAfkState();
                window.updateAfkHiddenInput();
                doAutosave();
            }
            lastActivityTimestamp = now;
        }

        ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'].forEach(function (eventName) {
            window.addEventListener(eventName, recordUserActivity, { passive: true });
        });

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                if (!isAfkActive) {
                    isAfkActive = true;
                    afkStartTimestamp = Date.now();
                    showAfkState();
                    window.updateAfkHiddenInput();
                }
            } else {
                recordUserActivity();
            }
        });

        window.addEventListener('blur', function () {
            if (!isAfkActive) {
                isAfkActive = true;
                afkStartTimestamp = Date.now();
                showAfkState();
                window.updateAfkHiddenInput();
            }
        });

        window.addEventListener('focus', function () {
            recordUserActivity();
        });

        setInterval(function () {
            if (assessmentSubmitting) return;

            const now = Date.now();
            const inactiveSeconds = Math.floor((now - lastActivityTimestamp) / 1000);

            if (!isAfkActive && inactiveSeconds >= afkThresholdSeconds) {
                isAfkActive = true;
                afkStartTimestamp = now;
                showAfkState();
            }

            if (isAfkActive) {
                const currentAfkElapsed = afkStartTimestamp ? Math.max(0, Math.floor((now - afkStartTimestamp) / 1000)) : 0;
                if (afkElapsedCounter) {
                    afkElapsedCounter.textContent = (cumulativeAfkSeconds + currentAfkElapsed) + 's';
                }
                window.updateAfkHiddenInput();
            }
        }, 1000);

        setInterval(function () {
            if (assessmentSubmitting) return;
            window.updateAfkHiddenInput();
            if (isAfkActive) {
                doAutosave();
            }
        }, 30000);

        function doAutosave() {
            if (assessmentSubmitting) return;
            if (isSaving) {
                hasPendingSave = true;
                return;
            }

            isSaving = true;
            hasPendingSave = false;

            window.updateAfkHiddenInput();

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

        function updateNavAnswerStatus() {
            $('#formAssesment input[type="radio"]:checked').each(function () {
                let questionId = $(this).attr('name');
                if (questionId) {
                    $('.assessment-wizard .nav a[href="#step-' + questionId + '"]').addClass('done');
                }
            });
        }

        $(document).on('change', '#formAssesment input[type="radio"], #formAssesment input[type="checkbox"], #formAssesment select, #formAssesment textarea', function () {
            let questionId = $(this).attr('name');
            if ($(this).is(':checked') && questionId) {
                $('.assessment-wizard .nav a[href="#step-' + questionId + '"]').addClass('done');
            }
            updateLeaveModalText();
            checkFinishButtonState();
            doAutosave();
        });

        updateNavAnswerStatus();
        updateLeaveModalText();
        checkFinishButtonState();

        $('#formAssesment').on('submit', function (e) {
            if (assessmentSubmitting) {
                e.preventDefault();
                return;
            }

            if (!window.assessmentAutoSubmit) {
                e.preventDefault();
                const answered = getAnsweredQuestionsCount();
                const total = getTotalQuestionsCount();
                if (answered < total || total === 0) {
                    checkFinishButtonState();
                    return;
                }
                openAssessmentModal('finishAssessmentModal');
                return;
            }

            assessmentSubmitting = true;
            $('#btnFinishSubmit, #submitAssesment, #btnConfirmFinishSubmit, #btnConfirmLeaveTest').prop('disabled', true).text('Submitting...');
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
            checkFinishButtonState();
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

            checkFinishButtonState();

            doAutosave();
        });

        $(document).on('click', '.sw-btn, .assessment-wizard .nav a', function () {
            doAutosave();
        });

        // Trigger finish modal on button click with Strict Guard Clause
        $(document).on('click', '#btnFinishSubmit, .btn-submit-assessment', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const answered = getAnsweredQuestionsCount();
            const total = getTotalQuestionsCount();

            if (answered < total || total === 0) {
                checkFinishButtonState();
                return false;
            }

            openAssessmentModal('finishAssessmentModal');
        });

        // Trigger modal dismiss
        $(document).on('click', '[data-bs-dismiss="modal"], [data-dismiss="modal"]', function (e) {
            var modal = $(this).closest('.modal');
            if (modal.length && modal.attr('id')) {
                closeAssessmentModal(modal.attr('id'));
            }
        });

        // Execute Submit from Finish Assessment Modal
        $(document).on('click', '#btnConfirmFinishSubmit', function (e) {
            e.preventDefault();
            $(this).prop('disabled', true).text('Submitting...');
            submitAssessmentForm();
        });

        // Execute Submit from Leave Assessment Modal
        $(document).on('click', '#btnConfirmLeaveTest', function (e) {
            e.preventDefault();
            $(this).prop('disabled', true).text('Submitting...');
            submitAssessmentForm();
        });

        // =========================================================================
        // Whitelist Scoped Selector for Leave Assessment Modal
        // =========================================================================
        var leaveTriggerSelector = [
            '.owl-sidebar-brand a[href]',
            '.owl-sidebar-nav a[href]',
            '.app-topbar .dropdown-menu a.dropdown-item[href]',
            '.owl-assessment-mobile-actions .dropdown-menu a.dropdown-item[href]',
            '.owl-mobile-user-row a[href]',
            '[data-assessment-leave]',
            '[data-leave-assessment]'
        ].join(', ');

        $(document).on('click', leaveTriggerSelector, function (e) {
            var anchor = this.closest ? this.closest('a[href]') : this;
            if (!anchor) return;

            var rawHref = anchor.getAttribute('href');
            if (!rawHref || rawHref === '#' || rawHref.startsWith('#') || rawHref.startsWith('javascript:')) {
                return;
            }

            // Abaikan jika klik di dalam modal, tombol logout, atau navigasi wizard
            if (anchor.closest('.modal') || anchor.closest('.assessment-wizard') || anchor.closest('.sw-btn-group')) {
                return;
            }
            if (anchor.closest('[data-logout-trigger="true"], .owl-sidebar-logout-btn, form[action*="logout"], a[href*="logout"]')) {
                return;
            }

            var currentPath = window.location.pathname;
            var currentHref = window.location.href.split('#')[0].split('?')[0];
            var targetHrefClean = anchor.href ? anchor.href.split('#')[0].split('?')[0] : '';

            // Cegat navigasi hanya jika benar-benar mengarah ke luar lembar assessment
            if (rawHref !== currentPath && targetHrefClean !== currentHref) {
                e.preventDefault();
                e.stopPropagation();
                showLeaveModal();
            }
        });

        // =========================================================================
        // Browser Back Button Protection (Popstate)
        // =========================================================================
        history.pushState(null, null, location.href);

        window.addEventListener('popstate', function (event) {
            history.pushState(null, null, location.href);
            showLeaveModal();
        });
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
                        var formEl = document.getElementById("formAssesment") || document.getElementById("assessmentForm") || document.querySelector('form[action*="assesment"]') || document.querySelector('form');
                        if (formEl) {
                            if (!formEl.querySelector('input[name="is_expired"]')) {
                                var expiredInput = document.createElement("input");
                                expiredInput.type = "hidden";
                                expiredInput.name = "is_expired";
                                expiredInput.value = "1";
                                formEl.appendChild(expiredInput);
                            }
                        }
                        submitAssessmentForm();
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
