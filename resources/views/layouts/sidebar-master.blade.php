<!doctype html>
<html lang="en">
<head>
    @include('partials.head-meta')
    @include('partials.head-style')
    @yield('style')
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('vendor/tabler-icons/tabler-icons.min.css') }}">
</head>
@php
    $cookieTheme = request()->cookie('owl-theme');
    $themeMode = request('theme') ?? $cookieTheme ?? 'light';
@endphp
<body data-bs-theme="{{ $themeMode }}" class="{{ $themeMode === 'dark' ? 'dark-mode' : '' }}">
    <script src="{{ asset('Tabler/dist/js/demo-theme.min.js') }}"></script>

    <div class="page">
        @include('layouts.header')

        <div class="page-wrapper">
            @yield('content')
        </div>
    </div>

    @include('partials.foot-script')
    @include('partials.manage-autocomplete-script')
    @stack('scripts')
    @yield('script')
</body>
</html>
