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
</body>

</html>
