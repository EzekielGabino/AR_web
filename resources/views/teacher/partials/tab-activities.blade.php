<div class="space-y-4" x-data="{ status: 'pending' }">
    <div>
        <h2 class="text-lg font-black text-[#091540]">Class activities</h2>
        <p class="text-xs font-medium text-[#091540]/70">Assigned lab work across all your classes.</p>
    </div>

    <div class="flex flex-wrap gap-2">
        <template x-for="tab in [
            { id: 'pending', label: 'Pending', type: 'pending' },
            { id: 'completed', label: 'Completed', type: 'completed' },
            { id: 'past_due', label: 'Past due', type: 'past_due' },
            { id: 'returned', label: 'Returned', type: 'returned' },
        ]" :key="tab.id">
            <button type="button"
                @click="status = tab.id"
                class="cursor-pointer flex items-center gap-2 rounded-full px-4 py-2 text-xs font-bold transition-all duration-150 shadow-xs"
                :class="status === tab.id
                        ? 'bg-[#1B2CC1] text-white shadow-md shadow-[#1B2CC1]/30'
                        : 'border border-[#ABD2FA] bg-white text-[#091540] hover:bg-[#ABD2FA]/10'">

                {{-- Pending Icon --}}
                <svg x-show="tab.id === 'pending'" class="h-4 w-4" :class="status === tab.id ? 'text-[#ABD2FA]' : 'text-[#1B2CC1]'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{-- Completed Icon --}}
                <svg x-show="tab.id === 'completed'" class="h-4 w-4" :class="status === tab.id ? 'text-[#ABD2FA]' : 'text-[#1B2CC1]'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{-- Past Due Icon --}}
                <svg x-show="tab.id === 'past_due'" class="h-4 w-4" :class="status === tab.id ? 'text-[#ABD2FA]' : 'text-[#1B2CC1]'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                {{-- Returned Icon --}}
                <svg x-show="tab.id === 'returned'" class="h-4 w-4" :class="status === tab.id ? 'text-[#ABD2FA]' : 'text-[#1B2CC1]'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                </svg>

                <span x-text="tab.label"></span>
            </button>
        </template>
    </div>

    <div class="rounded-3xl border border-[#ABD2FA]/40 bg-white p-10 text-center shadow-sm relative overflow-hidden">
        {{-- Decorative ambient glow --}}
        <div class="pointer-events-none absolute -bottom-10 left-1/2 -translate-x-1/2 h-20 w-48 rounded-full bg-[#1B2CC1]/5 blur-xl"></div>

        <div class="relative z-10 flex flex-col items-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-[#ABD2FA]/60 bg-[#ABD2FA]/20 text-[#1B2CC1] mb-3 shadow-inner">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </div>
            <p class="text-sm font-black text-[#091540] capitalize">
                <span x-text="status.replace('_', ' ')"></span> — empty
            </p>
            <p class="mt-1 max-w-sm text-xs font-medium text-[#091540]/60 leading-relaxed">
                Activities will appear here after you assign lab templates to a class.
            </p>
        </div>
    </div>
</div>