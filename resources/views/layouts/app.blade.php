<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head-meta')
    @include('partials.head-style')
    @yield('style')
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('vendor/tabler-icons/tabler-icons.min.css') }}">
    <style>
        @media (max-width: 576px) {
            /* Mengunci lebar modal agar memiliki jarak aman dari pinggir layar */
            .modal-dialog {
                margin: 12px auto !important;
                max-width: calc(100% - 24px) !important;
            }
            /* Penyesuaian ukuran font dan padding modal agar pas di mobile */
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
</head>

@php
    $cookieTheme = request()->cookie('owl-theme');
    $themeMode = request('theme') ?? $cookieTheme ?? 'light';
@endphp
<body data-bs-theme="{{ $themeMode }}" class="{{ $themeMode === 'dark' ? 'dark-mode' : '' }}">
    <script src="{{ asset('Tabler/dist/js/demo-theme.min.js') }}"></script>

    <div class="page">
        @include('partials.header')
        <!-- Content Wrapper. Contains page content -->
        <div class="page-wrapper">
            @yield('content')
            @include('partials.footer')
        </div>
        <!-- Content Wrapper. -->
    </div>
    <!-- ./wrapper -->
    @include('partials.foot-script')
    @stack('scripts')
    @yield('script')
</body>

</html>
