<section id="top" class="relative px-4 py-16 sm:px-6 sm:py-24">
    {{-- Ambient AR Glow Background --}}
    <div class="pointer-events-none absolute inset-x-0 -top-10 -z-10 flex transform-gpu justify-center overflow-hidden blur-3xl" aria-hidden="true">
        <div class="aspect-[1108/632] w-[69.25rem] flex-none bg-gradient-to-r from-[#ABD2FA]/40 via-[#7692FF]/20 to-[#1B2CC1]/20 opacity-60"></div>
    </div>

    <div class="mx-auto max-w-6xl text-center">
        {{-- Pill Badge --}}
        <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-[#ABD2FA]/25 px-4 py-1.5 ring-1 ring-inset ring-[#7692FF]/30">
            <span class="h-2 w-2 rounded-full bg-[#1B2CC1] animate-pulse"></span>
            <p class="text-xs font-bold uppercase tracking-wider text-[#1B2CC1]">Teacher portal</p>
        </div>

        {{-- Main Headline --}}
        <h1 class="mx-auto max-w-3xl text-3xl font-extrabold tracking-tight text-[#091540] sm:text-5xl sm:leading-tight">
            Manage classes and AR lab activities in one place
        </h1>

        {{-- Subtitle --}}
        <p class="mx-auto mt-5 max-w-2xl text-sm font-medium leading-relaxed text-[#091540]/75 sm:text-base">
            SciLens AR helps educators create classes, publish guided AR chemistry labs,
            and review student work — built for Manaoag National High School STE.
        </p>

        {{-- Call to Action Buttons --}}
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="#login" onclick="window.scrollToLogin && window.scrollToLogin(event)"
                class="inline-flex items-center justify-center rounded-2xl bg-[#1B2CC1] px-6 py-3.5 text-xs font-bold text-white shadow-lg shadow-[#1B2CC1]/25 transition-all duration-200 hover:bg-[#1B2CC1]/90 hover:shadow-xl hover:shadow-[#1B2CC1]/35 active:scale-[0.98]">
                Sign in to dashboard
            </a>
            <a href="https://drive.google.com/file/d/15KBtw6O_EJwsXAbTINS2iOp5ymcK4cyA/view?usp=sharing"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-[#1B2CC1]/40 bg-[#ABD2FA]/25 px-6 py-3.5 text-xs font-bold text-[#091540] transition-all duration-200 hover:border-[#1B2CC1] hover:bg-[#ABD2FA]/45 active:scale-[0.98]">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Download student app
            </a>
            <a href="#overview"
                class="inline-flex items-center justify-center rounded-2xl border border-[#7692FF]/40 bg-white/80 px-6 py-3.5 text-xs font-bold text-[#091540] backdrop-blur-xs transition-all duration-200 hover:border-[#1B2CC1] hover:bg-[#ABD2FA]/20 active:scale-[0.98]">
                Learn more
            </a>
        </div>
        <p class="mt-3 text-center text-[11px] font-medium text-[#091540]/55">
            Android APK (Google Drive) · allow install from browser if prompted · teachers use Sign in above
        </p>
    </div>
</section>
