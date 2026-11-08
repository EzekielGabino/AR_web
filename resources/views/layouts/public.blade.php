<!DOCTYPE html>
<html data-auth="{{ session('auth_uid') || session('firebase_uid') ? '1' : '0' }}" lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- OAuth: Supabase often returns tokens on Site URL (/#access_token=). Forward immediately. --}}
    <script>
    (function () {
        try {
            var h = window.location.hash || '';
            var s = window.location.search || '';
            var path = window.location.pathname || '/';
            if (path.indexOf('/auth/supabase/callback') === 0) return;
            if (h.indexOf('access_token=') !== -1 || h.indexOf('refresh_token=') !== -1 || h.indexOf('error=') !== -1) {
                window.location.replace(window.location.origin + '/auth/supabase/callback' + h);
                return;
            }
            if (s.indexOf('code=') !== -1) {
                window.location.replace(window.location.origin + '/auth/supabase/callback' + s + h);
            }
        } catch (e) {}
    })();
    </script>

    <title>@yield('title', 'SciLens AR — Teacher Portal')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#63D1F6',
                            dark: '#585D61'
                        },
                        accent: {
                            DEFAULT: '#DEAF4B'
                        },
                        brand: {
                            500: '#63D1F6',
                            600: '#40C2F3',
                            900: '#585D61'
                        },
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        [x-cloak] {
            display: none !important;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
    </style>
    @stack('styles')
</head>

<body class="bg-slate-50 text-slate-800 font-sans antialiased min-h-screen">
    @yield('content')

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('scripts')

<script>
(function () {
    const KEY = 'scilens_auth_ping';
    const wasAuth = document.documentElement.getAttribute('data-auth') === '1';

    window.scilensAuthPing = function () {
        try { localStorage.setItem(KEY, String(Date.now())); } catch (e) {}
        try {
            if (window.BroadcastChannel) {
                const bc = new BroadcastChannel('scilens_auth');
                bc.postMessage({ t: Date.now() });
                bc.close();
            }
        } catch (e) {}
    };

    window.addEventListener('storage', function (e) {
        if (e.key === KEY) {
            window.location.reload();
        }
    });

    try {
        if (window.BroadcastChannel) {
            const bc = new BroadcastChannel('scilens_auth');
            bc.onmessage = function () {
                window.location.reload();
            };
        }
    } catch (e) {}

    let checking = false;
    async function checkAuth() {
        if (checking || document.hidden) return;
        checking = true;
        try {
            const res = await fetch(@json(url('/auth/status')), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            const data = await res.json();
            const nowAuth = !!data.authenticated;
            if (nowAuth !== wasAuth) {
                window.location.reload();
            }
        } catch (e) {
        } finally {
            checking = false;
        }
    }
    setInterval(checkAuth, 2500);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) checkAuth();
    });
})();
</script>
@if(session('auth_ping'))
<script>if (window.scilensAuthPing) window.scilensAuthPing();</script>
@endif


</body>

</html>