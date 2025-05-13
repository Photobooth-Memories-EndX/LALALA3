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
        }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>

<body class=" d-flex flex-column">
    <script src="Tabler/dist/js/demo-theme.min.js?1684106062"></script>
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="text-center mb-4">
                <a href="." class="navbar-brand navbar-brand-autodark"><img src="owl.png" height="150" alt=""></a>
            </div>
            <div class="card card-md">
                <div class="card-body">

                    <x-alert type='success' />
                    <x-alert type='warning' />

                    <h2 class="h2 text-center mb-4">Online Knowledge Learning</h2>
                    <form action="{{ route('authuser') }}" id="form-authentication" method="post" autocomplete="off" novalidate>
                        @csrf
                        <input type="hidden" name="submit_type" id="submit-type">
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
                        <div class="mb-3 @if (session()->has('submit-type') && session('submit-type') == 'assessment')
                                d-block
                            @else
                                d-none
                            @endif" id="unique-code-field">
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
                            <div class="d-flex gap-2 @if (session()->has('submit-type') && session('submit-type') == 'assessment')
                                d-none
                            @else
                                d-block
                            @endif" id="button-submit-field">
                                <button type="button" onclick="submitFormAuthentication('login')" class="btn btn-primary w-50"><i class="fas fa-sign-in-alt me-2"></i>Sign in</button>
                                <button type="button" onclick="submitFormAuthentication('assessment')" class="btn btn-warning w-50"><i class="fas fa-clipboard-list me-2"></i>Assessment</button>
                            </div>
                            <div class="d-flex gap-2 @if (session()->has('submit-type') && session('submit-type') == 'assessment')
                                d-block
                            @else
                                d-none
                            @endif" id="button-assessment-field">
                                <button type="button" onclick="submitAssessment('cancel')" class="btn btn-secondary w-50"><i class="fas fa-times me-2"></i>Cancel</button>
                                <button type="button" onclick="submitAssessment('assessment')" class="btn btn-warning w-50"><i class="fas fa-clipboard-list me-2"></i>Assessment</button>
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
