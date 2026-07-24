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

        body {
            font-feature-settings: "cv03", "cv04", "cv11";
            /* Latar belakang gradasi modern & lembut menyesuaikan warna logo OWL */
            background: radial-gradient(circle at 0% 0%, rgba(32, 107, 196, 0.08) 0%, transparent 40%),
                        radial-gradient(circle at 100% 100%, rgba(247, 103, 7, 0.08) 0%, transparent 40%),
                        #f8fafc;
            min-height: 100vh;
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

                    <h2 class="h2 text-center mb-4 fw-bold text-dark" style="letter-spacing: -0.5px;">Online Knowledge Learning</h2>
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
    </script>
    <script>
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
