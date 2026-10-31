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
