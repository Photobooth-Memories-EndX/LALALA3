<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Sign in - Online Knowledge Learning</title>

    <!-- CSS files -->
    <link href="Tabler/dist/css/tabler.min.css?1684106062" rel="stylesheet" />
    <link href="Tabler/dist/css/tabler-flags.min.css?1684106062" rel="stylesheet" />
    <link href="Tabler/dist/css/tabler-payments.min.css?1684106062" rel="stylesheet" />
    <link href="Tabler/dist/css/tabler-vendors.min.css?1684106062" rel="stylesheet" />
    <link href="Tabler/dist/css/demo.min.css?1684106062" rel="stylesheet" />
    <link rel="shortcut icon" href="{{ asset('owl.png') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        @import url('https://rsms.me/inter/inter.css');

        :root {
            --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }

        body, .page, .page-center {
            font-feature-settings: "cv03", "cv04", "cv11";
            background: linear-gradient(135deg, #eef2f7 0%, #f8fafc 50%, #fff7ed 100%) !important;
            border: none !important;
            min-height: 100vh;
        }

        body::before, body::after,
        .page::before, .page::after,
        .page-center::before, .page-center::after {
            display: none !important;
            content: none !important;
            border: none !important;
            background: transparent !important;
        }

        .login-card {
            border: none !important;
            box-shadow: 0 20px 27px rgba(0, 0, 0, 0.05) !important;
            border-radius: 16px !important;
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.95) !important;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        @media (max-width: 576px) {
            .modal-dialog {
                margin: 12px auto !important;
                max-width: calc(100% - 24px) !important;
            }
            .modal-content {
                padding: 0.5rem !important;
            }
            .modal-body {
                padding: 0.75rem 1rem !important;
            }
            .modal-title {
                font-size: 1.05rem !important;
            }
            .assessment-title-wrapper h5 {
                font-size: 1rem !important;
            }
        }
    </style>

    <script src="{{ asset('js/jquery.min.js') }}"></script>
</head>

<body class="d-flex flex-column justify-content-center">
    <script src="Tabler/dist/js/demo-theme.min.js?1684106062"></script>
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="text-center mb-4">
                <a href="." class="navbar-brand navbar-brand-autodark"><img src="owl.png" height="250" alt="OWL Learning Hub"></a>
            </div>
            <div class="card card-md login-card">
                <div class="card-body p-4 p-md-5">

                    <x-alert type='success' />
                    <x-alert type='warning' />

                    <h2 class="h2 text-center mb-2 fw-bold text-dark" style="letter-spacing: -0.5px;">Corporate Learning Hub</h2>
                    
                    <!-- Tagline tambahan -->
                    <div class="tagline-subtext fs-5 fw-semibold text-center mb-3" style="font-size: 0.95rem !important;">
                        <span style="color: #0b2545 !important;">Open Mind</span>
                        <span style="color: #0d6efd !important;" class="mx-1">•</span>
                        <span style="color: #0d6efd !important;">Wide Growth</span>
                        <span style="color: #fd7e14 !important;" class="mx-1">•</span>
                        <span style="color: #fd7e14 !important;">Lead Impact</span>
                    </div>
                    <form action="{{ route('authuser') }}" id="form-authentication" method="post" autocomplete="off" novalidate>
                        @csrf
                        <input type="hidden" name="submit_type" id="submit-type" value="assessment">
                        <div class="mb-3">
                            <label for="id-emp" class="form-label">Employee ID</label>
                            <input type="text" name="id_emp" class="form-control" id="id-emp" placeholder="Employee ID" @if (session()->has('id-emp'))
                                value="{{ session('id-emp') }}"
                            @else
                                value="{{ old('id_emp') }}"
                            @endif autocomplete="off">
                            @error('id_emp')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group input-group-flat">
                                <input type="password" name="password" class="form-control" id="password" placeholder="Your password"
                                    autocomplete="off">
                                <span class="input-group-text">
                                    <a href="javascript:void(0)" id="show-hide-password" class="link-secondary" title="Show / Hide" data-bs-toggle="tooltip" data-bs-placement="top">
                                        <!-- Download SVG icon from http://tabler-icons.io/i/eye -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                            <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                        </svg>
                                    </a>
                                </span>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="unique-code" class="form-label">Assessment Unique Code</label>
                            <input type="text" name="unique_code" class="form-control" id="unique-code" placeholder="Unique Code" @if (session()->has('id-emp'))
                                value="{{ session('unique-code') }}"
                            @else
                                value="{{ old('unique_code') }}"
                            @endif autocomplete="off">
                            @error('unique_code')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-footer mt-0">
                            <button type="submit" class="btn btn-warning w-100"><i class="fas fa-clipboard-list me-2"></i>Assessment</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <x-shared.assessment-access-modal />
    @include('components.shared.assessment-result-modal')

    <script src="Tabler/dist/js/tabler.min.js?1684106062" defer></script>
    <script src="Tabler/dist/js/demo.min.js?1684106062" defer></script>
    <script>
        $('#show-hide-password').on('click', function () {
            var passwordInput = $('input[name="password"]');

            if (passwordInput.attr('type') == 'password') {
                passwordInput.attr('type', 'text');
            } else {
                passwordInput.attr('type', 'password');
            }
        });

        function populateAndShowResultModal(response) {
            var $resModal = $('#assessmentResultModal');
            if (!$resModal.length) return false;

            var isPassed = !!response.assessment_is_passed;
            var scoreVal = response.assessment_score !== undefined ? response.assessment_score : 0;
            var passingGradeVal = response.assessment_passing_grade !== undefined ? response.assessment_passing_grade : '-';
            var typeStr = response.assessment_type_label || response.assessment_type || 'content';
            var isGlobal = String(typeStr).trim().toLowerCase().indexOf('global') !== -1 || String(response.assessment_type).trim().toLowerCase() === 'global';

            $resModal.find('#assessmentResultModalName').text(response.assessment_title || '-');
            $resModal.find('#assessmentResultModalScore').text(scoreVal);
            $resModal.find('#assessmentResultModalScore').attr('class', isPassed ? 'text-success display-4 fw-extrabold mb-1' : 'text-danger display-4 fw-extrabold mb-1');
            $resModal.find('#assessmentResultModalPassingGrade').text(passingGradeVal);

            // Single Status Badge
            var badgeHtml = isPassed 
                ? '<span class="badge bg-success-subtle text-success px-3 py-2 fs-6 rounded-pill fw-bold"><i class="ti ti-check me-1"></i>Passed</span>'
                : '<span class="badge bg-danger-subtle text-danger px-3 py-2 fs-6 rounded-pill fw-bold"><i class="ti ti-x me-1"></i>Failed</span>';
            $resModal.find('#assessmentResultModalBadgeContainer').html(badgeHtml);

            // Header Icon Box & Icon
            $resModal.find('#assessmentResultModalIconBox').attr('class', isPassed ? 'p-2 rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center' : 'p-2 rounded-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center');
            $resModal.find('#assessmentResultModalIcon').attr('class', isPassed ? 'ti ti-trophy fs-2' : 'ti ti-alert-circle fs-2');

            // Type Display
            var headerLabel = 'Content Assessment';
            var lowerType = String(typeStr).trim().toLowerCase();
            if (lowerType === 'trainingplan' || lowerType === 'playlist') {
                headerLabel = 'Playlist Assessment';
            } else if (lowerType.indexOf('global') !== -1) {
                headerLabel = 'Global Assessment';
            } else if (lowerType === 'youtube') {
                headerLabel = 'MOOC Assessment';
            } else if (typeStr && typeStr !== '-') {
                headerLabel = typeStr.indexOf('Assessment') !== -1 ? typeStr : typeStr + ' Assessment';
            }
            $resModal.find('#assessmentResultModalType').text(headerLabel);

            // Batch vs Attempt Visibility
            if (isGlobal) {
                $resModal.find('#assessmentResultModalBatchRow').removeClass('d-none');
                $resModal.find('#assessmentResultModalAttemptRow').addClass('d-none');
                var batchDisplay = response.assessment_batch && response.assessment_batch !== '-' 
                    ? (String(response.assessment_batch).toLowerCase().indexOf('batch') !== -1 ? response.assessment_batch : 'Batch ' + response.assessment_batch) 
                    : '-';
                $resModal.find('#assessmentResultModalBatch').text(batchDisplay);
            } else {
                $resModal.find('#assessmentResultModalBatchRow').addClass('d-none');
                $resModal.find('#assessmentResultModalAttemptRow').removeClass('d-none');
                var currAtt = response.assessment_current_attempt || response.assessment_user_attempts || 1;
                var maxAtt = response.assessment_max_attempts || 1;
                $resModal.find('#assessmentResultModalAttempt').text(currAtt + ' / ' + maxAtt);
            }

            // Assessment Period
            var periodVal = response.assessment_period || '-';
            if (periodVal === '-' && (response.assessment_from_date || response.assessment_to_date)) {
                var f = (response.assessment_from_date && response.assessment_from_date !== '-') ? response.assessment_from_date : '';
                var t = (response.assessment_to_date && response.assessment_to_date !== '-') ? response.assessment_to_date : '';
                periodVal = (f && t) ? f + ' - ' + t : (f ? 'From ' + f : 'Until ' + t);
            }
            $resModal.find('#assessmentResultModalPeriod').text(periodVal);

            // Status Message Row
            $resModal.find('#assessmentResultModalStatusRow').attr('class', isPassed ? 'p-3 rounded-3 bg-success-subtle text-success' : 'p-3 rounded-3 bg-danger-subtle text-danger');
            $resModal.find('#assessmentResultModalStatusMessage').text(isPassed ? 'Congratulations, you have passed this test.' : 'Sorry, you have not passed this test.');

            // Notice
            if (response.is_expired) {
                $resModal.find('#assessmentResultModalExpiredNotice').removeClass('d-none').addClass('d-flex');
            } else {
                $resModal.find('#assessmentResultModalExpiredNotice').addClass('d-none').removeClass('d-flex');
            }

            if (typeof $.fn.modal === 'function') {
                $resModal.modal('show');
            } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var bsModal = bootstrap.Modal.getOrCreateInstance($resModal[0]);
                bsModal.show();
            }
            return true;
        }

        $(document).on('click', '#btnStartAssessmentAccess', function (e) {
            e.preventDefault();
            var targetUrl = $(this).data('target-url') || $(this).attr('data-target-url');
            if (!targetUrl) {
                targetUrl = $('#assessmentAccessModal').data('target-url') || $('#assessmentAccessModal').attr('data-target-url');
            }
            if (targetUrl) {
                window.location.href = targetUrl;
            }
        });

        function showAssessmentStatusModal(status, response) {
            var $modal = $('#assessmentAccessModal');
            if (!$modal.length) return false;

            var typeToMap = response.assessment_type_label || response.assessment_type || 'content';
            var headerLabel = 'ASSESSMENT CONTENT';
            if (typeToMap && typeToMap !== '-') {
                var lowerType = String(typeToMap).trim().toLowerCase();
                if (lowerType === 'trainingplan' || lowerType === 'playlist') {
                    headerLabel = 'ASSESSMENT PLAYLIST';
                } else if (lowerType === 'global') {
                    headerLabel = 'ASSESSMENT GLOBAL';
                } else if (lowerType === 'content') {
                    headerLabel = 'ASSESSMENT CONTENT';
                } else if (lowerType === 'youtube') {
                    headerLabel = 'ASSESSMENT MOOC';
                } else {
                    headerLabel = 'ASSESSMENT ' + String(typeToMap).toUpperCase();
                }
            }

            $modal.find('#assessmentAccessModalTypeBadge').text(headerLabel);
            $modal.find('#assessmentAccessModalName').text(response.assessment_title || '-');

            $modal.find('#assessmentAccessModalDetailsGrid').hide();
            $modal.find('#assessmentAccessModalNoticeSection').hide();

            $modal.find('#assessmentAccessModalStatusBox').show();
            $modal.find('#assessmentAccessModalActiveFooter').attr('style', 'display: none !important;');
            $modal.find('#assessmentAccessModalStatusFooter').show().attr('style', '');

            if (status === 'inactive') {
                $modal.find('#assessmentAccessModalTitle').text('Assessment Unavailable').attr('style', 'color: #dc3545 !important;');
                $modal.find('#assessmentAccessModalSubtitle').text('Inactive');
                $modal.find('#assessmentAccessModalIconBox').attr('style', 'width: 40px; height: 40px; background-color: rgba(220, 53, 69, 0.12); color: #dc3545;');
                $modal.find('#assessmentAccessModalIconInner').attr('class', 'ti ti-circle-x fs-2');

                $modal.find('#assessmentAccessModalStatusAlert').attr('class', 'alert alert-danger border-0 rounded-3 p-3 mb-0 d-flex align-items-start gap-2');
                $modal.find('#assessmentAccessModalStatusAlertIcon').attr('class', 'ti ti-alert-triangle fs-2 flex-shrink-0 mt-1');
                $modal.find('#assessmentAccessModalStatusAlertTitle').text('Assessment Inactive');
                $modal.find('#assessmentAccessModalStatusText').text(response.status_message || 'This assessment is currently inactive and cannot be started.');
            } else if (status === 'expired') {
                $modal.find('#assessmentAccessModalTitle').text('Assessment Expired').attr('style', 'color: #fd7e14 !important;');
                $modal.find('#assessmentAccessModalSubtitle').text('Expired');
                $modal.find('#assessmentAccessModalIconBox').attr('style', 'width: 40px; height: 40px; background-color: rgba(253, 126, 20, 0.12); color: #fd7e14;');
                $modal.find('#assessmentAccessModalIconInner').attr('class', 'ti ti-calendar-x fs-2');

                $modal.find('#assessmentAccessModalStatusAlert').attr('class', 'alert alert-warning border-0 rounded-3 p-3 mb-0 d-flex align-items-start gap-2');
                $modal.find('#assessmentAccessModalStatusAlertIcon').attr('class', 'ti ti-clock-off fs-2 flex-shrink-0 mt-1');
                $modal.find('#assessmentAccessModalStatusAlertTitle').text('Assessment Expired');
                $modal.find('#assessmentAccessModalStatusText').text(response.status_message || 'This assessment has expired and can no longer be started.');
            }

            if (typeof $.fn.modal === 'function') {
                $modal.modal('show');
            } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var bsModal = bootstrap.Modal.getOrCreateInstance($modal[0]);
                bsModal.show();
            } else {
                $modal.addClass('show').css('display', 'block');
            }

            return true;
        }

        @if(session()->has('open_assessment_code'))
        document.addEventListener("DOMContentLoaded", function () {
            setTimeout(function () {
                var code = "{{ session('open_assessment_code') }}";
                $.ajax({
                    url: "{{ route('home.user.access.assessment.by.unique.code') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        unique_code: code,
                    },
                    success: function (response) {
                        // PRIORITY 1: INACTIVE MODAL
                        if (response.assessment_status === 'inactive' || response.is_inactive || response.denial_reason === 'inactive') {
                            showAssessmentStatusModal('inactive', response);
                            return;
                        }

                        // PRIORITY 2: EXPIRED MODAL
                        if (response.assessment_status === 'expired' || response.is_expired || response.denial_reason === 'expired') {
                            showAssessmentStatusModal('expired', response);
                            return;
                        }

                        // PRIORITY 3: RESULT MODAL
                        if (response.show_result) {
                            if (populateAndShowResultModal(response)) {
                                return;
                            }
                        }

                        var $modal = $('#assessmentAccessModal');
                        if ($modal.length && response.can_start) {
                            var typeToMap = response.assessment_type_label || response.assessment_type || 'content';
                            var headerLabel = 'ASSESSMENT CONTENT';
                            var lowerType = String(typeToMap).trim().toLowerCase();
                            if (lowerType === 'trainingplan' || lowerType === 'playlist') {
                                headerLabel = 'ASSESSMENT PLAYLIST';
                            } else if (lowerType === 'global') {
                                headerLabel = 'ASSESSMENT GLOBAL';
                            } else if (lowerType === 'youtube') {
                                headerLabel = 'ASSESSMENT MOOC';
                            } else if (typeToMap && typeToMap !== '-') {
                                headerLabel = 'ASSESSMENT ' + String(typeToMap).toUpperCase();
                            }

                            var userAttempts = response.assessment_user_attempts !== undefined ? response.assessment_user_attempts : 0;
                            var maxAttempts = response.assessment_max_attempts !== undefined ? response.assessment_max_attempts : 1;

                            $modal.find('#assessmentAccessModalStatusBox').hide();
                            $modal.find('#assessmentAccessModalStatusFooter').attr('style', 'display: none !important;');
                            $modal.find('#assessmentAccessModalActiveFooter').show().attr('style', '');
                            $modal.find('#assessmentAccessModalDetailsGrid').show();
                            $modal.find('#assessmentAccessModalNoticeSection').show();

                            $modal.find('#assessmentAccessModalTypeBadge').text(headerLabel);
                            $modal.find('#assessmentAccessModalName').text(response.assessment_title || '-');

                            var batchDisplay = response.assessment_batch && response.assessment_batch !== '-' 
                                ? (String(response.assessment_batch).toLowerCase().indexOf('batch') !== -1 ? response.assessment_batch : 'Batch ' + response.assessment_batch) 
                                : '-';
                            $modal.find('#assessmentAccessModalSubText').text(batchDisplay);
                            $modal.find('#assessmentAccessModalBatch').text(batchDisplay);

                            $modal.find('#assessmentAccessModalFromDate').text(response.assessment_from_date || '-');
                            $modal.find('#assessmentAccessModalToDate').text(response.assessment_to_date || '-');
                            $modal.find('#assessmentAccessModalPassingGrade').text(response.assessment_passing_grade !== undefined && response.assessment_passing_grade !== '' ? response.assessment_passing_grade : '-');
                            $modal.find('#assessmentAccessModalAttempt').text(userAttempts + ' / ' + maxAttempts + ' Attempts');

                            var targetUrl = response.assessment_url || ("{{ url('/assesment') }}/" + response.assessment_slug);
                            $('#btnStartAssessmentAccess').data('target-url', targetUrl).attr('data-target-url', targetUrl);
                            $modal.data('target-url', targetUrl).attr('data-target-url', targetUrl);

                            if (typeof $.fn.modal === 'function') {
                                $modal.modal('show');
                            } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                var bsModal = bootstrap.Modal.getOrCreateInstance($modal[0]);
                                bsModal.show();
                            }
                            return;
                        }

                        var targetUrl = response.assessment_url || ("{{ url('/assesment') }}/" + response.assessment_slug);
                        window.location.href = targetUrl;
                    }
                });
            }, 250);
        });
        @endif
    </script>
</body>

</html>
