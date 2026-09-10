<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
<meta http-equiv="X-UA-Compatible" content="ie=edge"/>
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Online Knowledge Learning</title>
<link rel="shortcut icon" href="{{ asset('owl.png') }}" type="image/x-icon">
<script>
    (function() {
        var validThemes = ['light', 'dark'];
        var params = new URLSearchParams(window.location.search);
        var urlTheme = params.get('theme');
        var normalize = function(v) {
            if (!v) return null;
            var s = String(v).trim().toLowerCase();
            if (s === 'true') return 'dark';
            if (s === 'false') return 'light';
            return validThemes.indexOf(s) !== -1 ? s : null;
        };
        var stored = normalize(localStorage.getItem('owl-dark-mode')) || normalize(localStorage.getItem('tablerTheme'));
        var theme = normalize(urlTheme) || stored;
        if (!theme) {
            theme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
        }
        localStorage.setItem('tablerTheme', theme);
        localStorage.setItem('owl-dark-mode', theme);
        document.cookie = 'owl-theme=' + theme + ';path=/;max-age=31536000;SameSite=Lax';
        document.documentElement.setAttribute('data-bs-theme', theme);
        document.documentElement.setAttribute('data-theme', theme);
        if (theme === 'dark') {
            document.documentElement.classList.add('dark-mode');
        } else {
            document.documentElement.classList.remove('dark-mode');
        }
    })();
</script>
