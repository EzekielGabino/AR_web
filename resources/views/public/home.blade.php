{{-- Main View Template --}}
@extends('layouts.public')

@section('title', 'SciLens AR — Teacher Portal')

@section('content')
<div class="min-h-screen bg-slate-50 font-sans text-[#091540] selection:bg-[#ABD2FA] selection:text-[#091540] relative">

    {{-- Desktop only: fixed left login column --}}
    <aside id="login-desktop" class="hidden scroll-mt-4 lg:fixed lg:top-0 lg:left-0 lg:z-50 lg:flex lg:h-screen lg:w-1/3 lg:flex-col lg:justify-center lg:overflow-y-auto lg:bg-gradient-to-b lg:from-[#091540] lg:via-[#1B2CC1]/70 lg:to-[#091540] lg:shadow-2xl">
        @include('public.partials.login-form')
    </aside>

    {{-- Main column: full width on phone · right 2/3 on desktop --}}
    <div class="flex min-h-screen flex-col bg-slate-50 lg:ml-[33.333333%] lg:w-[66.666667%]">
        <header class="sticky top-0 z-40 border-b border-[#ABD2FA]/40 bg-white/90 shadow-xs backdrop-blur-md">
            @include('public.partials.nav')
        </header>

        <main class="flex-1 space-y-12 px-4 py-8 sm:space-y-16 sm:px-6 sm:py-12 lg:px-12 lg:py-16">
            @include('public.partials.hero')

            {{-- Phone / tablet: login after hero (nav → hero → login → rest) --}}
            <div id="login" class="scroll-mt-24 overflow-hidden rounded-3xl bg-gradient-to-b from-[#091540] via-[#1B2CC1]/70 to-[#091540] shadow-xl lg:hidden">
                @include('public.partials.login-form')
            </div>

            <div class="space-y-12 transition-all duration-300 sm:space-y-16">
                @include('public.partials.overview')

                @include('public.partials.how')

                @include('public.partials.features')

                @include('public.partials.team')
            </div>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-[#ABD2FA]/40 bg-[#091540] text-white/80 py-10 transition-all">
            <div class="max-w-7xl mx-auto px-4 text-center space-y-3">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 text-[#ABD2FA] text-xs font-semibold tracking-wide uppercase border border-[#7692FF]/20">
                    <span>🔬 SciLens AR</span>
                    <span>•</span>
                    <span>Educational Research</span>
                </div>
                <p class="text-xs md:text-sm font-medium tracking-wide text-slate-300">
                    © {{ date('Y') }} SciLens AR. Manaoag National High School — STE Program Research Project.
                </p>
            </div>
        </footer>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var hash = window.location.hash || '';
    if (hash.indexOf('access_token=') !== -1 || hash.indexOf('error=') !== -1) {
        window.location.replace(@json(url('/auth/supabase/callback')) + hash);
        return;
    }
    var qs = window.location.search || '';
    if (qs.indexOf('code=') !== -1) {
        window.location.replace(@json(url('/auth/supabase/callback')) + qs);
    }
})();
</script>
<script>
    window.scrollToLogin = function (e) {
        if (e) e.preventDefault();
        var isLg = window.matchMedia('(min-width: 1024px)').matches;
        var el = document.getElementById(isLg ? 'login-desktop' : 'login');
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        if (isLg && el) el.querySelector('input[name=email]')?.focus();
    };
</script>
<script>
    window.__supabaseUrl = @json(rtrim((string) config('supabase.url'), '/'));
    window.__supabaseAnonKey = @json((string) config('supabase.anon_key'));
    window.__supabaseCallbackUrl = (window.location.origin || @json(rtrim((string) config('app.url'), '/'))) + '/auth/supabase/callback';
</script>
<script type="module">
    import { createClient } from 'https://esm.sh/@supabase/supabase-js@2';

    function wireGoogleButtons() {
        const url = window.__supabaseUrl;
        const anon = window.__supabaseAnonKey;
        const redirectTo = window.__supabaseCallbackUrl;

        document.querySelectorAll('.google-signin-btn').forEach((btn) => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const root = btn.closest('section') || document;
                const err = root.querySelector('.google-signin-error');
                const label = btn.querySelector('.google-signin-label');
                if (err) { err.classList.add('hidden'); err.textContent = ''; }
                if (!url || !anon) {
                    if (err) { err.textContent = 'Supabase is not configured (URL/anon key missing).'; err.classList.remove('hidden'); }
                    return;
                }
                if (!redirectTo) {
                    if (err) { err.textContent = 'Missing OAuth callback URL.'; err.classList.remove('hidden'); }
                    return;
                }
                try {
                    btn.disabled = true;
                    if (label) label.textContent = 'Signing in…';
                    const supabase = createClient(url, anon);
                    console.log('OAuth redirectTo', redirectTo);
                    const { data, error } = await supabase.auth.signInWithOAuth({
                        provider: 'google',
                        options: {
                            redirectTo: redirectTo,
                            skipBrowserRedirect: false,
                            queryParams: { prompt: 'select_account' },
                        },
                    });
                    if (error) throw error;
                    // If no redirect happened (blocked), show URL
                    if (data && data.url) {
                        window.location.href = data.url;
                    }
                } catch (ex) {
                    console.error(ex);
                    btn.disabled = false;
                    if (label) label.textContent = 'Continue with Google';
                    if (err) {
                        err.textContent = (ex && ex.message) ? ex.message : 'Google sign-in failed. Enable Google provider in Supabase and add redirect URL.';
                        err.classList.remove('hidden');
                    }
                }
            });
        });
    }
    wireGoogleButtons();
</script>
@endpush