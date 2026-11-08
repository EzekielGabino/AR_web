{{-- Student AR lab previews — phone frame (9:16), SciLens poster, no fullscreen --}}
<section id="see-the-lab" class="scroll-mt-24 relative overflow-hidden rounded-3xl border border-[#ABD2FA]/40 bg-white/90 px-4 py-14 shadow-sm sm:px-8 sm:py-16">
    <div class="relative mx-auto max-w-6xl">
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

        <div class="mt-10 grid grid-cols-1 justify-items-center gap-8 sm:grid-cols-2 lg:grid-cols-3"
            x-data="{ playing: null }">
            @foreach ($clips as $i => $clip)
                <article class="flex w-full max-w-[280px] flex-col overflow-hidden rounded-[1.75rem] border border-[#ABD2FA]/50 bg-slate-50/80 shadow-md shadow-[#1B2CC1]/5">
                    <div class="bg-[#091540] px-3 pt-3">
                        <div class="mx-auto mb-2 h-1.5 w-16 rounded-full bg-white/20"></div>
                        <div class="relative mx-auto aspect-[9/16] w-full overflow-hidden rounded-xl bg-[#091540]">
                            @if (!empty($clip['youtube']))
                                <template x-if="playing === {{ $i }}">
                                    <iframe
                                        class="absolute inset-0 h-full w-full"
                                        src="https://www.youtube-nocookie.com/embed/{{ $clip['youtube'] }}?playsinline=1&fs=0&modestbranding=1&rel=0&autoplay=1"
                                        title="{{ $clip['title'] }}"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen="false"
                                        loading="lazy"></iframe>
                                </template>
                                <button type="button"
                                    x-show="playing !== {{ $i }}"
                                    @click="playing = {{ $i }}"
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
                                    @pause="if (playing === {{ $i }}) playing = null"
                                    x-show="playing === {{ $i }}"
                                    x-cloak
                                    controls>
                                    <source src="{{ asset($clip['file']) }}" type="video/mp4">
                                </video>
                                <button type="button"
                                    x-show="playing !== {{ $i }}"
                                    @click="playing = {{ $i }}; $nextTick(() => { const v = $refs.vid{{ $i }}; if (v) { v.play().catch(() => {}); } })"
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
            Phone-shaped frame (9:16). Fullscreen is disabled. Previews use the SciLens AR icon until you press Play.
        </p>
    </div>
</section>
