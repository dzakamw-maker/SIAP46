<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Agen BNI Sekolah')</title>
    <script>
        (function () {
            const storageKey = 'siap46-theme';
            const root = document.documentElement;

            function getPreferredDark() {
                try {
                    const saved = localStorage.getItem(storageKey);
                    if (saved === 'dark') return true;
                    if (saved === 'light') return false;
                } catch (e) {}
                return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            }

            function applyTheme(isDark) {
                root.classList.toggle('dark', isDark);
                root.style.colorScheme = isDark ? 'dark' : 'light';
                document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
                    btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
                    btn.setAttribute('title', isDark ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap');
                    btn.setAttribute('aria-label', isDark ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap');
                });
            }

            applyTheme(getPreferredDark());

            window.toggleTheme = function () {
                const nextIsDark = !root.classList.contains('dark');
                try {
                    localStorage.setItem(storageKey, nextIsDark ? 'dark' : 'light');
                } catch (e) {}
                applyTheme(nextIsDark);
            };

            document.addEventListener('DOMContentLoaded', function () {
                applyTheme(root.classList.contains('dark'));
            });

            if (window.matchMedia) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
                    try {
                        if (localStorage.getItem(storageKey)) return;
                    } catch (err) {}
                    applyTheme(e.matches);
                });
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-zinc-950 text-gray-800 dark:text-zinc-100 font-sans antialiased transition-colors duration-200">

    @yield('content')

</body>
</html>
