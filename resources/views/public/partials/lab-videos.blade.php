{{-- Student AR lab previews — add files under public/videos/ or set YouTube IDs below --}}
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
            // Option A: local files in public/videos/ (mp4)
            // Option B: set youtube id strings; leave '' to use local path
            $clips = [
                [
                    'title' => 'Place apparatus',
                    'blurb' => 'Students select glassware and place it on a detected surface in AR.',
                    'file' => 'videos/lab-place-apparatus.mp4',
                    'youtube' => '', // e.g. 'dQw4w9WgXcQ'
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

        <div class="mt-10 grid gap-6 md:grid-cols-3">
            @foreach ($clips as $clip)
                <article class="flex flex-col overflow-hidden rounded-2xl border border-[#ABD2FA]/50 bg-slate-50/80 shadow-sm">
                    <div class="relative aspect-video bg-[#091540]">
                        @if (!empty($clip['youtube']))
                            <iframe
                                class="absolute inset-0 h-full w-full"
                                src="https://www.youtube-nocookie.com/embed/{{ $clip['youtube'] }}"
                                title="{{ $clip['title'] }}"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                                loading="lazy"></iframe>
                        @else
                            <video
                                class="absolute inset-0 h-full w-full object-cover"
                                controls
                                playsinline
                                preload="metadata"
                                poster="{{ asset('icons/logo.png') }}">
                                <source src="{{ asset($clip['file']) }}" type="video/mp4">
                                Your browser does not support embedded video.
                            </video>
                            {{-- If the file is missing, the player stays empty; add mp4s under public/videos/ --}}
                        @endif
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                        <h3 class="text-sm font-black text-[#091540]">{{ $clip['title'] }}</h3>
                        <p class="mt-1.5 text-xs font-medium leading-relaxed text-[#091540]/70">{{ $clip['blurb'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>

        <p class="mt-6 text-center text-[11px] font-medium text-[#091540]/50">
            Tip for admins: put MP4 files in <code class="rounded bg-slate-100 px-1">public/videos/</code>
            or set a YouTube id on each clip in <code class="rounded bg-slate-100 px-1">lab-videos.blade.php</code>.
        </p>
    </div>
</section>
