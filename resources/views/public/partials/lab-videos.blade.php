{{-- Student AR lab previews — phone frame (9:16), no fullscreen --}}
<section id="see-the-lab" class="relative overflow-hidden rounded-3xl border border-[#ABD2FA]/40 bg-white/90 px-4 py-14 shadow-sm sm:px-8 sm:py-16">
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
            @foreach ($clips as $clip)
                <article class="flex w-full max-w-[280px] flex-col overflow-hidden rounded-[1.75rem] border border-[#ABD2FA]/50 bg-slate-50/80 shadow-md shadow-[#1B2CC1]/5">
                    {{-- Phone bezel --}}
                    <div class="bg-[#091540] px-3 pt-3">
                        <div class="mx-auto mb-2 h-1.5 w-16 rounded-full bg-white/20"></div>
                        <div class="relative mx-auto aspect-[9/16] w-full overflow-hidden rounded-xl bg-black">
                            @if (!empty($clip['youtube']))
                                <iframe
                                    class="absolute inset-0 h-full w-full"
                                    src="https://www.youtube-nocookie.com/embed/{{ $clip['youtube'] }}?playsinline=1&fs=0&modestbranding=1&rel=0"
                                    title="{{ $clip['title'] }}"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen="false"
                                    loading="lazy"></iframe>
                            @else
                                <video
                                    class="absolute inset-0 h-full w-full object-cover"
                                    controls
                                    playsinline
                                    preload="metadata"
                                    controlslist="nofullscreen nodownload noremoteplayback"
                                    disablepictureinpicture
                                    disableremoteplayback
                                    poster="{{ asset('icons/logo.png') }}">
                                    <source src="{{ asset($clip['file']) }}" type="video/mp4">
                                    Your browser does not support embedded video.
                                </video>
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
            Clips use a phone-shaped frame (9:16). Add MP4s under <code class="rounded bg-slate-100 px-1">public/videos/</code>
            or set a YouTube id in this partial.
        </p>
    </div>
</section>
