<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Signing in — SciLens AR</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 font-sans text-slate-800">
    <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
        <p id="status" class="text-sm font-semibold text-slate-600">Finishing Google sign-in…</p>
        <p id="error" class="mt-3 hidden text-xs font-bold text-red-500"></p>
    </div>

    <script type="module">
        import { createClient } from 'https://esm.sh/@supabase/supabase-js@2';

        const statusEl = document.getElementById('status');
        const errorEl = document.getElementById('error');

        const url = @json(rtrim((string) config('supabase.url'), '/'));
        const anon = @json((string) config('supabase.anon_key'));
        const sessionUrl = @json(route('auth.supabase.session'));

        function fail(msg) {
            statusEl.textContent = 'Sign-in failed';
            errorEl.textContent = msg;
            errorEl.classList.remove('hidden');
        }

        if (!url || !anon) {
            fail('Supabase is not configured on the server.');
        } else {
            const supabase = createClient(url, anon);

            try {
                // Prefer PKCE code exchange from query string
                const qs = new URLSearchParams(window.location.search);
                const hash = new URLSearchParams(window.location.hash.replace(/^#/, ''));

                let accessToken = hash.get('access_token') || qs.get('access_token');

                if (!accessToken) {
                    const { data, error } = await supabase.auth.getSession();
                    if (error) throw error;
                    accessToken = data.session?.access_token || null;
                }

                // If redirected with ?code=, exchange it
                if (!accessToken && qs.get('code')) {
                    const { data, error } = await supabase.auth.exchangeCodeForSession(qs.get('code'));
                    if (error) throw error;
                    accessToken = data.session?.access_token || null;
                }

                if (!accessToken) {
                    const { data, error } = await supabase.auth.getSession();
                    if (error) throw error;
                    accessToken = data.session?.access_token || null;
                }

                if (!accessToken) {
                    throw new Error('No session returned from Google. Try again.');
                }

                const res = await fetch(sessionUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ access_token: accessToken }),
                });

                const payload = await res.json().catch(() => ({}));
                if (!res.ok || !payload.redirect) {
                    throw new Error(payload.message || ('Server sign-in failed (HTTP ' + res.status + ')'));
                }

                statusEl.textContent = 'Success — redirecting…';
                window.location.href = payload.redirect;
            } catch (e) {
                console.error(e);
                fail(e.message || 'Sign-in failed.');
            }
        }
    </script>
</body>
</html>
