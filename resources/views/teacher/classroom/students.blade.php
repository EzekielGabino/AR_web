{{-- Students + pending join requests --}}
<div x-show="tab === 'people'" x-cloak class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs font-medium text-[#091540]/70">Approve join requests, then manage enrolled students.</p>
        <div class="flex flex-wrap gap-2">
            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800"
                x-text="pendingStudents().length + ' pending'"></span>
            <span class="rounded-full bg-[#ABD2FA]/20 px-2.5 py-0.5 text-xs font-bold text-[#1B2CC1]"
                x-text="students().length + ' enrolled'"></span>
        </div>
    </div>

    <div class="space-y-3">
        <h3 class="text-sm font-black text-[#091540]">Join requests</h3>
        <div x-show="pendingStudents().length === 0"
            class="rounded-2xl border border-dashed border-[#ABD2FA]/80 bg-white/80 px-4 py-6 text-center text-xs font-medium text-[#091540]/60">
            No pending requests.
        </div>
        <div class="divide-y divide-amber-100 overflow-hidden rounded-2xl border border-amber-200 bg-amber-50/40 shadow-sm"
            x-show="pendingStudents().length > 0">
            <template x-for="(s, idx) in pendingStudents()" :key="'p-' + (s.uid || idx)">
                <div class="flex flex-col gap-3 px-4 py-3.5 sm:flex-row sm:items-center sm:gap-3.5">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <div class="relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-amber-200 bg-amber-100 text-xs font-black text-amber-900">
                            <template x-if="s.photoUrl">
                                <img :src="s.photoUrl" alt="" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!s.photoUrl">
                                <span x-text="studentLabel(s) ? studentLabel(s).charAt(0).toUpperCase() : 'S'"></span>
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold text-[#091540]" x-text="studentLabel(s)"></p>
                            <p class="truncate text-[11px] font-medium text-[#091540]/60" x-text="studentSub(s)"></p>
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <button type="button" @click="rejectStudent(s)"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-[11px] font-bold text-slate-600 hover:bg-slate-50">
                            Decline
                        </button>
                        <button type="button" @click="approveStudent(s)"
                            class="rounded-xl bg-[#1B2CC1] px-3 py-2 text-[11px] font-bold text-white hover:bg-[#1625a8]">
                            Approve
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="space-y-3">
        <h3 class="text-sm font-black text-[#091540]">Enrolled students</h3>
        <div x-show="students().length === 0"
            class="relative overflow-hidden rounded-3xl border border-dashed border-[#ABD2FA] bg-[#ABD2FA]/10 p-10 text-center">
            <p class="text-xs font-bold text-[#091540]">No enrolled students yet.</p>
            <p class="mt-1 text-[11px] font-medium text-[#091540]/60">Approved students appear here.</p>
        </div>
        <div class="divide-y divide-[#ABD2FA]/30 overflow-hidden rounded-2xl border border-[#ABD2FA]/40 bg-white shadow-sm"
            x-show="students().length > 0">
            <template x-for="(s, idx) in students()" :key="'e-' + (s.uid || idx)">
                <div class="flex items-center gap-3.5 px-5 py-4 transition-colors hover:bg-[#ABD2FA]/5">
                    <div class="relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-[#7692FF]/30 bg-[#1B2CC1] text-xs font-black text-white shadow-xs">
                        <template x-if="s.avatar || s.photoUrl || s.photo">
                            <img :src="s.avatar || s.photoUrl || s.photo" alt="" class="h-full w-full object-cover">
                        </template>
                        <template x-if="!s.avatar && !s.photoUrl && !s.photo">
                            <span x-text="studentLabel(s) ? studentLabel(s).charAt(0).toUpperCase() : 'S'"></span>
                        </template>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-bold text-[#091540]" x-text="studentLabel(s)"></p>
                        <p class="mt-0.5 truncate text-[11px] font-medium text-[#091540]/60" x-text="studentSub(s)"></p>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
