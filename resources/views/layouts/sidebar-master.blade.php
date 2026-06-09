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
    <script>
        (function() {
            const validThemes = ['light', 'dark'];
            const params = new URLSearchParams(window.location.search);
            const urlTheme = params.get('theme');
            const storedTheme = localStorage.getItem('owl-dark-mode');
            const normalize = (value) => {
                if (!value) return null;
                const normalized = String(value).trim().toLowerCase();
                if (normalized === 'true') return 'dark';
                if (normalized === 'false') return 'light';
                return validThemes.includes(normalized) ? normalized : null;
            };
            let theme = normalize(urlTheme) || normalize(storedTheme);
            if (!theme) {
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            localStorage.setItem('owl-dark-mode', theme);
            document.documentElement.dataset.bsTheme = theme;
            document.documentElement.setAttribute('data-theme', theme);
            document.body.dataset.bsTheme = theme;
            document.body.classList.toggle('dark-mode', theme === 'dark');
        })();
    </script>
    <script src="{{ asset('Tabler/dist/js/demo-theme.min.js') }}"></script>

    <div class="page">
        @include('layouts.header')

        <div class="page-wrapper">
            @yield('content')
        </div>
    </div>

    @include('partials.foot-script')
    @stack('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    @yield('script')
</body>
</html>
