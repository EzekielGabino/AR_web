{{-- Student AR lab previews — phone frame (9:16), SciLens poster, phone-sized fullscreen overlay --}}
<section id="see-the-lab" class="scroll-mt-24 relative overflow-hidden rounded-3xl border border-[#ABD2FA]/40 bg-white/90 px-4 py-14 shadow-sm sm:px-8 sm:py-16">
    <div class="relative mx-auto max-w-6xl"
        x-data="{
            playing: null,
            fs: null,
            openFs(i) {
                this.fs = i;
                this.playing = i;
                this.$nextTick(() => {
                    const inline = this.$refs['vid' + i];
                    if (inline) {
                        try { inline.pause(); } catch (e) {}
                    }
                    const v = this.$refs['fsvid' + i];
                    if (v) {
                        try { v.currentTime = inline ? inline.currentTime : 0; } catch (e) {}
                        v.play().catch(() => {});
                    }
                });
            },
            closeFs() {
                const i = this.fs;
                if (i === null || i === undefined) return;
                const fsvid = this.$refs['fsvid' + i];
                const inline = this.$refs['vid' + i];
                let t = 0;
                if (fsvid) {
                    try { t = fsvid.currentTime || 0; fsvid.pause(); } catch (e) {}
                }
                this.fs = null;
                this.$nextTick(() => {
                    if (inline) {
                        try { inline.currentTime = t; inline.play().catch(() => {}); } catch (e) {}
                    }
                });
            },
            playInline(i) {
                this.playing = i;
                this.$nextTick(() => {
                    const v = this.$refs['vid' + i];
                    if (v) { v.play().catch(() => {}); }
                });
            },
            blockNativeFs(e) {
                e.preventDefault();
                try { if (document.fullscreenElement) document.exitFullscreen(); } catch (err) {}
            },
        }"
        @keydown.escape.window="if (fs !== null) closeFs()">
        <div class="mx-auto max-w-2xl text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#ABD2FA]/25 px-3.5 py-1 text-xs font-semibold text-[#1B2CC1] ring-1 ring-inset ring-[#7692FF]/30">
                Student view
            </span>
            <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-[#091540] sm:text-3xl">
                What students see during a lab
            </h2>
            <p class="mt-3 text-sm font-medium leading-relaxed text-[#091540]/70">
                Short clips from the mobile AR experience — placing apparatus, working through steps, and completing the activity.
            </p>
        </div>

        @php
            $logoUrl = asset('icons/logo.png');
            $clips = [
                [
                    'title' => 'Place apparatus',
                    'blurb' => 'Students select glassware and place it on a detected surface in AR.',
                    'file' => 'videos/lab-place-apparatus.mp4',
                    'youtube' => '',
                ],
                [
                    'title' => 'Add elements & follow steps',
                    'blurb' => 'Guided steps show what to add, pour, or observe next.',
                    'file' => 'videos/lab-steps.mp4',
                    'youtube' => '',
                ],
                [
                    'title' => 'Complete the lab',
                    'blurb' => 'When steps are done, the quiz unlocks and work can be submitted.',
                    'file' => 'videos/lab-complete.mp4',
                    'youtube' => '',
                ],
            ];
        @endphp

        <div class="mt-10 grid grid-cols-1 justify-items-center gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($clips as $i => $clip)
                <article class="flex w-full max-w-[280px] flex-col overflow-hidden rounded-[1.75rem] border border-[#ABD2FA]/50 bg-slate-50/80 shadow-md shadow-[#1B2CC1]/5">
                    <div class="bg-[#091540] px-3 pt-3">
                        <div class="mx-auto mb-2 h-1.5 w-16 rounded-full bg-white/20"></div>
                        <div class="relative mx-auto aspect-[9/16] w-full overflow-hidden rounded-xl bg-[#091540]">
                            @if (!empty($clip['youtube']))
                                <button type="button"
                                    @click="openFs({{ $i }})"
                                    class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-gradient-to-b from-[#091540] via-[#1B2CC1] to-[#091540] p-4">
                                    <img src="{{ $logoUrl }}"
                                        onerror="this.onerror=null;this.src='{{ asset('logo.png') }}'"
                                        alt="SciLens AR"
                                        class="h-20 w-20 object-contain drop-shadow-lg sm:h-24 sm:w-24">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 text-[11px] font-bold text-white ring-1 ring-white/25">
                                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7L8 5z"/></svg>
                                        Play
                                    </span>
                                </button>
                            @else
                                <video
                                    x-ref="vid{{ $i }}"
                                    class="absolute inset-0 h-full w-full object-cover"
                                    playsinline
                                    preload="none"
                                    controlslist="nofullscreen nodownload noremoteplayback"
                                    disablepictureinpicture
                                    disableremoteplayback
                                    @play="playing = {{ $i }}"
                                    @fullscreenchange="blockNativeFs($event)"
                                    @webkitfullscreenchange="blockNativeFs($event)"
                                    x-show="playing === {{ $i }} && fs === null"
                                    x-cloak
                                    controls>
                                    <source src="{{ asset($clip['file']) }}" type="video/mp4">
                                </video>
                                <button type="button"
                                    x-show="playing !== {{ $i }} || fs !== null"
                                    @click="playInline({{ $i }})"
                                    class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-gradient-to-b from-[#091540] via-[#1B2CC1] to-[#091540] p-4"
                                    x-cloak>
                                    <img src="{{ $logoUrl }}"
                                        onerror="this.onerror=null;this.src='{{ asset('logo.png') }}'"
                                        alt="SciLens AR"
                                        class="h-20 w-20 object-contain drop-shadow-lg sm:h-24 sm:w-24">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 text-[11px] font-bold text-white ring-1 ring-white/25">
                                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7L8 5z"/></svg>
                                        Play
                                    </span>
                                </button>
                                {{-- Phone-sized fullscreen control (icon only) --}}
                                <button type="button"
                                    x-show="playing === {{ $i }} && fs === null"
                                    @click.stop="openFs({{ $i }})"
                                    class="absolute bottom-12 right-2 z-10 flex h-9 w-9 items-center justify-center rounded-lg bg-black/55 text-white ring-1 ring-white/20 hover:bg-black/75"
                                    x-cloak
                                    title="Fullscreen (phone view)"
                                    aria-label="Fullscreen phone view">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9V5.25A1.5 1.5 0 015.25 3.75H9M15 3.75h3.75a1.5 1.5 0 011.5 1.5V9M20.25 15v3.75a1.5 1.5 0 01-1.5 1.5H15M9 20.25H5.25a1.5 1.5 0 01-1.5-1.5V15" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-1 flex-col p-4">
                        <h3 class="text-sm font-black text-[#091540]">{{ $clip['title'] }}</h3>
                        <p class="mt-1.5 text-xs font-medium leading-relaxed text-[#091540]/70">{{ $clip['blurb'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>

        <p class="mt-6 text-center text-[11px] font-medium text-[#091540]/50">
            Phone-shaped frame (9:16). The fullscreen control opens a larger phone-sized view (not landscape browser fullscreen).
        </p>

        {{-- Phone-sized fullscreen overlay --}}
        <template x-teleport="body">
            <div x-show="fs !== null" x-cloak
                class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/90 p-3 sm:p-6"
                role="dialog" aria-modal="true">
                <div class="absolute inset-0" @click="closeFs()"></div>
                <div class="relative flex h-full max-h-[96vh] w-full max-w-[min(100%,420px)] flex-col items-center justify-center">
                    <button type="button" @click="closeFs()"
                        class="absolute right-0 top-0 z-20 rounded-xl border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-bold text-white hover:bg-white/20">
                        Close
                    </button>
                    {{-- Phone bezel fills height while keeping 9:16 --}}
                    <div class="relative flex h-full max-h-[92vh] w-full items-center justify-center">
                        <div class="relative aspect-[9/16] h-full max-h-[92vh] w-auto max-w-full overflow-hidden rounded-[1.75rem] border border-white/15 bg-[#091540] shadow-2xl shadow-black/60">
                            <div class="flex h-full flex-col p-2 sm:p-3">
                                <div class="mx-auto mb-1.5 h-1.5 w-14 shrink-0 rounded-full bg-white/20"></div>
                                <div class="relative min-h-0 flex-1 overflow-hidden rounded-xl bg-black">
                                    @foreach ($clips as $i => $clip)
                                        @if (!empty($clip['youtube']))
                                            <iframe
                                                x-show="fs === {{ $i }}"
                                                class="absolute inset-0 h-full w-full"
                                                :src="fs === {{ $i }} ? 'https://www.youtube-nocookie.com/embed/{{ $clip['youtube'] }}?playsinline=1&fs=0&modestbranding=1&rel=0&autoplay=1' : ''"
                                                title="{{ $clip['title'] }}"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope"
                                                allowfullscreen="false"></iframe>
                                        @else
                                            <video
                                                x-ref="fsvid{{ $i }}"
                                                x-show="fs === {{ $i }}"
                                                class="absolute inset-0 h-full w-full object-cover"
                                                playsinline
                                                controls
                                                controlslist="nofullscreen nodownload noremoteplayback"
                                                disablepictureinpicture
                                                @fullscreenchange="blockNativeFs($event)"
                                                @webkitfullscreenchange="blockNativeFs($event)">
                                                <source src="{{ asset($clip['file']) }}" type="video/mp4">
                                            </video>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</section>
