<header class="sticky top-0 z-[100] border-b border-[#7692FF]/30 bg-[#091540]/95 backdrop-blur-md shadow-lg">
    <div class="relative z-10 mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-[#7692FF]/40 bg-[#1B2CC1]/30 p-2 shadow-sm">
                <img src="{{ asset('icons/logo.png') }}" onerror="this.onerror=null;this.src='{{ asset('logo.png') }}'" alt="SciLens AR" class="h-full w-full object-contain">
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-black tracking-tight text-white">SciLens AR</p>
                <p class="truncate text-[10px] font-bold uppercase tracking-wider text-[#ABD2FA]/70">Admin portal</p>
            </div>
        </a>
        <div class="flex items-center gap-2">
            <span class="hidden max-w-[10rem] truncate text-xs font-bold text-[#ABD2FA] sm:inline">{{ session('teacher_name') ?: session('teacher_email') ?: 'Admin' }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-xl border border-[#7692FF]/40 bg-white/5 px-3 py-2 text-xs font-bold text-white hover:bg-white/10">Sign out</button>
            </form>
        </div>
    </div>
</header>
