<!doctype html>
<html lang="en">
<head>
    @include('partials.head-meta')
    @include('partials.head-style')
    @yield('style')
    @stack('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
@php
    $themeMode = request('theme') === 'dark' ? 'dark' : 'light';
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
    @stack('scripts')
    @yield('script')
</body>
</html>
