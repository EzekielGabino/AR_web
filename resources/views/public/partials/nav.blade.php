{{-- public nav --}}
<div x-data="{ homeHelpOpen: false }" @keydown.escape.window="homeHelpOpen = false">
<div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
    <a href="{{ route('welcome') }}#top" class="flex min-w-0 items-center gap-2 group">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-[#ABD2FA]/60 bg-[#ABD2FA]/20 p-1.5 shadow-xs transition-transform group-hover:scale-105">
            <img src="{{ asset('icons/logo.png') }}" onerror="this.onerror=null;this.src='{{ asset('logo.png') }}'" alt="SciLens AR Logo" class="h-full w-full object-contain">
        </div>
        <span class="truncate text-sm font-black tracking-tight text-[#091540]">SciLens AR</span>
    </a>
    <nav class="flex shrink-0 items-center gap-2 text-xs font-bold text-[#091540]/80 sm:gap-4">
        <a href="#overview" class="hidden transition-colors hover:text-[#1B2CC1] sm:inline">Overview</a>
        <a href="#login" onclick="window.scrollToLogin && window.scrollToLogin(event)"
            class="rounded-xl bg-[#1B2CC1] px-3 py-2 text-white shadow-md shadow-[#1B2CC1]/20 transition-all hover:bg-[#1B2CC1]/90 hover:shadow-[#1B2CC1]/30 active:scale-[0.98] sm:px-4">
            Sign in
        </a>
    </nav>
</div>

    <button type="button"
        @click="homeHelpOpen = true"
        class="fixed bottom-5 right-5 z-50 flex h-12 w-12 items-center justify-center rounded-full bg-[#1B2CC1] text-sm font-black text-white shadow-lg shadow-[#1B2CC1]/40 ring-2 ring-white/40 hover:bg-[#7692FF] hover:text-[#091540] lg:bottom-8 lg:right-8"
        title="How to sign in"
        aria-label="Help signing in">
        i
    </button>
    <template x-teleport="body">
        <div x-show="homeHelpOpen" x-cloak
            class="fixed inset-0 z-[99999] flex items-center justify-center bg-[#091540]/80 p-4 backdrop-blur-sm"
            role="dialog" aria-modal="true">
            <div class="absolute inset-0" @click="homeHelpOpen = false"></div>
            <div class="relative w-full max-w-md rounded-3xl border border-[#7692FF]/40 bg-[#091540] p-6 text-white shadow-2xl sm:p-8" @click.stop>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-wider text-[#ABD2FA]/80">Welcome</p>
                        <h2 class="mt-1 text-lg font-extrabold">Sign in to the teacher portal</h2>
                    </div>
                    <button type="button" @click="homeHelpOpen = false" class="rounded-xl border border-white/10 px-2 py-1 text-xs font-bold text-[#ABD2FA]">✕</button>
                </div>
                <ol class="mt-5 list-decimal space-y-3 pl-5 text-xs font-medium leading-relaxed text-[#ABD2FA]/95">
                    <li><span class="font-bold text-white">Sign in</span> — use the form on this page (left on desktop, below the intro on phone) with your school email and password.</li>
                    <li><span class="font-bold text-white">Sign up</span> — switch to the Sign up tab to create a <span class="font-bold text-white">teacher</span> account (email + password).</li>
                    <li><span class="font-bold text-white">Google</span> — Continue with Google if your school account is allowed. You will return here after Google finishes.</li>
                    <li><span class="font-bold text-white">Students</span> — use the SciLens AR <span class="font-bold text-white">mobile app</span>, not this website.</li>
                </ol>
                <button type="button"
                    @click="homeHelpOpen = false; window.scrollToLogin && window.scrollToLogin()"
                    class="mt-6 w-full rounded-2xl bg-[#1B2CC1] py-3 text-xs font-bold text-white shadow-lg shadow-[#1B2CC1]/40 hover:bg-[#7692FF] hover:text-[#091540]">
                    Go to sign in
                </button>
            </div>
        </div>
    </template>
</div>
