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

        (function () {
            const storageKey = 'siap46-sidebar';
            const root = document.documentElement;
            const desktopQuery = window.matchMedia ? window.matchMedia('(min-width: 768px)') : null;

            function isDesktop() {
                return desktopQuery ? desktopQuery.matches : window.innerWidth >= 768;
            }

            function getDesktopOpen() {
                try {
                    return localStorage.getItem(storageKey) !== 'closed';
                } catch (e) {
                    return true;
                }
            }

            function isSidebarOpen() {
                return isDesktop()
                    ? !root.classList.contains('sidebar-collapsed')
                    : root.classList.contains('sidebar-mobile-open');
            }

            function syncSidebarUI() {
                const open = isSidebarOpen();
                const label = open ? 'Tutup Sidebar' : 'Buka Sidebar';

                document.querySelectorAll('[data-sidebar-toggle]').forEach(function (btn) {
                    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                    btn.setAttribute('title', label);
                    btn.setAttribute('aria-label', label);
                });

                document.querySelectorAll('[data-sidebar]').forEach(function (aside) {
                    aside.setAttribute('aria-hidden', open ? 'false' : 'true');
                    aside.inert = !open;
                });

                document.querySelectorAll('[data-sidebar-backdrop]').forEach(function (backdrop) {
                    backdrop.setAttribute('aria-hidden', !isDesktop() && open ? 'false' : 'true');
                });
            }

            if (!getDesktopOpen()) {
                root.classList.add('sidebar-collapsed');
            }

            window.toggleSidebar = function (forceState) {
                if (isDesktop()) {
                    const nextOpen = typeof forceState === 'boolean'
                        ? forceState
                        : root.classList.contains('sidebar-collapsed');

                    root.classList.toggle('sidebar-collapsed', !nextOpen);
                    try {
                        localStorage.setItem(storageKey, nextOpen ? 'open' : 'closed');
                    } catch (e) {}
                } else {
                    const nextOpen = typeof forceState === 'boolean'
                        ? forceState
                        : !root.classList.contains('sidebar-mobile-open');

                    root.classList.toggle('sidebar-mobile-open', nextOpen);
                }

                syncSidebarUI();
            };

            document.addEventListener('DOMContentLoaded', syncSidebarUI);

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !isDesktop() && root.classList.contains('sidebar-mobile-open')) {
                    window.toggleSidebar(false);
                    const toggleBtn = document.querySelector('[data-sidebar-toggle]');
                    if (toggleBtn) toggleBtn.focus();
                }
            });

            if (desktopQuery) {
                desktopQuery.addEventListener('change', function (e) {
                    if (e.matches) {
                        root.classList.remove('sidebar-mobile-open');
                    }
                    syncSidebarUI();
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
