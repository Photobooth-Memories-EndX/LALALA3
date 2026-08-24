<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Sign in - Corporate Learning Hub</title>

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

        /* Mempercantik tampilan card login */
        .login-card {
            border: none !important;
            box-shadow: 0 20px 27px rgba(0, 0, 0, 0.05) !important;
            border-radius: 16px !important;
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.95) !important;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        /* Animasi halus pada input focus */
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(32, 107, 196, 0.15) !important;
            border-color: #05507D !important;
        }

        /* Hover effect pada tombol */
        .btn {
            border-radius: 8px !important;
            font-weight: 600 !important;
            transition: all 0.2s ease-in-out !important;
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Logo styling */
        .brand-logo {
            transition: transform 0.3s ease;
        }
        .brand-logo:hover {
            transform: scale(1.03);
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
                <a href="." class="navbar-brand navbar-brand-autodark brand-logo">
                    <img src="owl.png" height="250" alt="OWL Learning Hub">
                </a>
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
                        <input type="hidden" name="submit_type" id="submit-type">
                        
                        <div class="mb-3">
                            <label for="id-emp" class="form-label fw-medium text-secondary">Employee ID</label>
                            <input type="text" name="id_emp" class="form-control form-control-lg" id="id-emp" placeholder="Enter your employee ID" @if (session()->has('id-emp'))
                                value="{{ session('id-emp') }}"
                            @else
                                value="{{ old('id_emp') }}"
                            @endif autocomplete="off">
                            @error('id_emp')
                                <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-medium text-secondary">Password</label>
                            <div class="input-group input-group-flat">
                                <input type="password" name="password" class="form-control form-control-lg" id="password" placeholder="Enter your password"
                                    autocomplete="off">
                                <span class="input-group-text px-3">
                                    <a href="javascript:void(0)" id="show-hide-password" class="link-secondary" title="Show / Hide" data-bs-toggle="tooltip" data-bs-placement="top">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                            <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                        </svg>
                                    </a>
                                </span>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4 @if (session()->has('submit-type') && session('submit-type') == 'assessment')
                                d-block
                            @else
                                d-none
                            @endif" id="unique-code-field">
                            <label for="unique-code" class="form-label fw-medium text-secondary">Assessment Unique Code</label>
                            <input type="text" name="unique_code" class="form-control form-control-lg" id="unique-code" placeholder="Enter unique code" @if (session()->has('id-emp'))
                                value="{{ session('unique-code') }}"
                            @else
                                value="{{ old('unique_code') }}"
                            @endif autocomplete="off">
                            @error('unique_code')
                                <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-footer mt-2">
                            <div class="d-flex gap-3 @if (session()->has('submit-type') && session('submit-type') == 'assessment')
                                d-none
                            @else
                                d-block
                            @endif" id="button-submit-field">
                                <button type="button" onclick="submitFormAuthentication('login')" class="btn btn-primary btn-lg w-50 py-2"><i class="fas fa-sign-in-alt me-2"></i>Sign in</button>
                                <button type="button" onclick="submitFormAuthentication('assessment')" class="btn btn-warning btn-lg w-50 py-2"><i class="fas fa-clipboard-list me-2"></i>Assessment</button>
                            </div>
                            <div class="d-flex gap-3 @if (session()->has('submit-type') && session('submit-type') == 'assessment')
                                d-block
                            @else
                                d-none
                            @endif" id="button-assessment-field">
                                <button type="button" onclick="submitAssessment('cancel')" class="btn btn-secondary btn-lg w-50 py-2"><i class="fas fa-times me-2"></i>Cancel</button>
                                <button type="button" onclick="submitAssessment('assessment')" class="btn btn-warning btn-lg w-50 py-2"><i class="fas fa-clipboard-list me-2"></i>Assessment</button>
                            </div>
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

        let submitFormAuthentication = (type) => {
            if (type == 'login') {
                $('#submit-type').val('login');
                $('#form-authentication').submit();
            } else {
                $('#submit-type').val('');
                $('#unique-code').val('');

                $('.invalid-feedback').addClass('d-none');
                $('.invalid-feedback').removeClass('d-block');
                $('#unique-code-field').removeClass('d-none');
                $('#unique-code-field').addClass('d-block');
                $('#button-submit-field').addClass('d-none');
                $('#button-submit-field').removeClass('d-block');
                $('#button-assessment-field').removeClass('d-none');
                $('#button-assessment-field').addClass('d-block');
            }
        }

        let submitAssessment = (type) => {
            if (type == 'cancel') {
                $('#submit-type').val('');
                $('#unique-code').val('');

                $('.invalid-feedback').addClass('d-none');
                $('.invalid-feedback').removeClass('d-block');
                $('#unique-code-field').removeClass('d-block');
                $('#unique-code-field').addClass('d-none');
                $('#button-submit-field').addClass('d-block');
                $('#button-submit-field').removeClass('d-none');
                $('#button-assessment-field').removeClass('d-block');
                $('#button-assessment-field').addClass('d-none');
            } else {
                $('#submit-type').val('assessment');
                $('#form-authentication').submit();
            }
        }
    </script>
</body>

</html>