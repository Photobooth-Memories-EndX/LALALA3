<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head-meta')
    @include('partials.head-style')
    @yield('style')
     <link rel="stylesheet" href="{{ asset('vendor/tabler-icons/tabler-icons.min.css') }}">
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
