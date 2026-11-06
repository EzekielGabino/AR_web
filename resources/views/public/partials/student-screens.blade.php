{{-- Student classroom screenshots — phone frames --}}
<section id="student-app" class="scroll-mt-24 relative overflow-hidden rounded-3xl border border-[#ABD2FA]/40 bg-white/90 px-4 py-14 shadow-sm sm:px-8 sm:py-16">
    <div class="relative mx-auto max-w-6xl">
        <div class="mx-auto max-w-2xl text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#ABD2FA]/25 px-3.5 py-1 text-xs font-semibold text-[#1B2CC1] ring-1 ring-inset ring-[#7692FF]/30">
                Student app
            </span>
            <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-[#091540] sm:text-3xl">
                Inside the student classroom
            </h2>
            <p class="mt-3 text-sm font-medium leading-relaxed text-[#091540]/70">
                From the class stream to a guided AR lab — what learners see on their phones.
            </p>
        </div>

        @php
            $screens = [
                [
                    'file' => 'students-screens/stream.jpg',
                    'title' => 'Class stream',
                    'blurb' => 'Announcements and updates from the teacher.',
                ],
                [
                    'file' => 'students-screens/activities.jpg',
                    'title' => 'Activities',
                    'blurb' => 'Published labs and deadlines in one list.',
                ],
                [
                    'file' => 'students-screens/lab-detail.jpg',
                    'title' => 'Lab brief',
                    'blurb' => 'Safety, apparatus, and start when ready.',
                ],
                [
                    'file' => 'students-screens/lab-start.jpg',
                    'title' => 'AR lab',
                    'blurb' => 'Place apparatus and follow guided steps.',
                ],
            ];
        @endphp

        <div class="mt-10 grid grid-cols-1 justify-items-center gap-8 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($screens as $screen)
                <figure class="flex w-full max-w-[240px] flex-col">
                    {{-- Phone bezel --}}
                    <div class="overflow-hidden rounded-[1.75rem] border border-[#091540]/15 bg-[#091540] p-2.5 shadow-lg shadow-[#1B2CC1]/10">
                        <div class="mx-auto mb-2 h-1.5 w-14 rounded-full bg-white/20"></div>
                        <div class="relative aspect-[9/19.5] overflow-hidden rounded-[1.25rem] bg-black">
                            <img
                                src="{{ asset($screen['file']) }}"
                                alt="{{ $screen['title'] }} — SciLens AR student app"
                                class="absolute inset-0 h-full w-full object-cover object-top"
                                loading="lazy"
                                width="922"
                                height="2048">
                        </div>
                    </div>
                    <figcaption class="mt-4 text-center">
                        <p class="text-sm font-black text-[#091540]">{{ $screen['title'] }}</p>
                        <p class="mt-1 text-xs font-medium leading-relaxed text-[#091540]/65">{{ $screen['blurb'] }}</p>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
