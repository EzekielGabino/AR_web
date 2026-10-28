<section id="overview" class="relative overflow-hidden rounded-3xl border border-[#ABD2FA]/40 bg-white/90 px-4 py-16 shadow-sm backdrop-blur-sm sm:px-6">
    {{-- Ambient Background Glows --}}
    <div class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-[#ABD2FA]/25 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-20 -left-20 h-72 w-72 rounded-full bg-[#7692FF]/15 blur-3xl"></div>

    <div class="relative mx-auto max-w-6xl">
        {{-- Section Header --}}
        <div class="text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#ABD2FA]/20 px-3.5 py-1 text-xs font-semibold text-[#1B2CC1] ring-1 ring-inset ring-[#7692FF]/30">
                ✨ Classroom Management
            </span>
            <h2 class="mt-3 text-center text-2xl font-extrabold text-[#091540] sm:text-3xl">
                What you can do
            </h2>
            <p class="mx-auto mt-2 max-w-xl text-center text-sm font-medium text-[#091540]/70">
                A focused toolkit for classroom labs — not a maze of panels.
            </p>
        </div>

        {{-- Feature Cards Grid --}}
        <div class="mt-12 grid gap-6 sm:grid-cols-3">
            {{-- Card 1: Classes --}}
            <div class="group relative overflow-hidden rounded-2xl border border-[#ABD2FA]/60 bg-slate-50/70 p-6 transition-all duration-300 hover:-translate-y-1.5 hover:border-[#7692FF] hover:bg-white hover:shadow-xl hover:shadow-[#1B2CC1]/10">
                {{-- Decorative Top Accent Line --}}
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#1B2CC1] to-[#7692FF] opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-[#1B2CC1] to-[#7692FF] text-white shadow-md shadow-[#1B2CC1]/25 transition-transform duration-300 group-hover:scale-110">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a6.012 6.012 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </div>
                <h3 class="mt-5 text-base font-bold text-[#091540] transition-colors group-hover:text-[#1B2CC1]">Classes</h3>
                <p class="mt-2 text-xs font-medium leading-relaxed text-[#091540]/70">
                    Create sections, share a join code or QR, post announcements, and see your roster.
                </p>
            </div>

            {{-- Card 2: Lab Activities --}}
            <div class="group relative overflow-hidden rounded-2xl border border-[#ABD2FA]/60 bg-slate-50/70 p-6 transition-all duration-300 hover:-translate-y-1.5 hover:border-[#7692FF] hover:bg-white hover:shadow-xl hover:shadow-[#1B2CC1]/10">
                {{-- Decorative Top Accent Line --}}
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#1B2CC1] to-[#7692FF] opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-[#1B2CC1] to-[#7692FF] text-white shadow-md shadow-[#1B2CC1]/25 transition-transform duration-300 group-hover:scale-110">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19 14.5M14.25 3.104c.251.023.501.05.75.082M19 14.5l-2.4 4.8a2.25 2.25 0 01-2.013 1.2h-5.174a2.25 2.25 0 01-2.012-1.2L5 14.5m14 0H5" />
                    </svg>
                </div>
                <h3 class="mt-5 text-base font-bold text-[#091540] transition-colors group-hover:text-[#1B2CC1]">Lab activities</h3>
                <p class="mt-2 text-xs font-medium leading-relaxed text-[#091540]/70">
                    Assign AR lab activities from templates, track pending and completed work, and review submissions.
                </p>
            </div>

            {{-- Card 3: 3D Objects --}}
            <div class="group relative overflow-hidden rounded-2xl border border-[#ABD2FA]/60 bg-slate-50/70 p-6 transition-all duration-300 hover:-translate-y-1.5 hover:border-[#7692FF] hover:bg-white hover:shadow-xl hover:shadow-[#1B2CC1]/10">
                {{-- Decorative Top Accent Line --}}
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#1B2CC1] to-[#7692FF] opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-[#1B2CC1] to-[#7692FF] text-white shadow-md shadow-[#1B2CC1]/25 transition-transform duration-300 group-hover:scale-110">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                    </svg>
                </div>
                <h3 class="mt-5 text-base font-bold text-[#091540] transition-colors group-hover:text-[#1B2CC1]">3D objects</h3>
                <p class="mt-2 text-xs font-medium leading-relaxed text-[#091540]/70">
                    Search science models for class materials and teaching demos.
                </p>
            </div>
        </div>
    </div>
</section>