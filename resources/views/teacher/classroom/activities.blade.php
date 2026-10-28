{{-- Activities --}}
<div x-show="tab === 'activities'" x-cloak class="space-y-4">
    <div class="flex flex-wrap gap-2">
        <template x-for="st in [
            { id: 'regular', label: 'Regular' },
            { id: 'lab', label: 'Lab activities' },
            { id: 'templates', label: 'Templates' },
        ]" :key="st.id">
            <button type="button" @click="selectActivityTab(st.id)"
                class="rounded-full px-3.5 py-1.5 text-xs font-bold transition-all duration-150 cursor-pointer"
                :class="activityTab === st.id ? 'bg-[#1B2CC1] text-white shadow-sm' : 'border border-[#ABD2FA] bg-white text-[#091540] hover:bg-[#ABD2FA]/10'">
                <span x-text="st.label"></span>
            </button>
        </template>
    </div>

    <div x-show="activityTab === 'regular'" x-cloak class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-xs font-medium text-[#091540]/70">Assignments with title, description, and optional quiz. Drafts stay hidden until published.</p>
            <button type="button" @click="openRegCreate()"
                class="rounded-xl bg-[#1B2CC1] px-3.5 py-2 text-xs font-bold text-white hover:bg-[#091540] transition-colors duration-150 shadow-sm cursor-pointer">
                + New regular activity
            </button>
        </div>
        <div x-show="regularActivities().length === 0" class="rounded-2xl border border-dashed border-[#ABD2FA] bg-[#ABD2FA]/10 p-8 text-center text-xs font-medium text-[#091540]/60">No regular activities yet.</div>
        <template x-for="act in regularActivities()" :key="act.id">
            <div role="link" tabindex="0"
                @click="openActivity(act)"
                @keydown.enter.prevent="openActivity(act)"
                class="flex cursor-pointer flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#ABD2FA]/40 bg-white p-4 transition-all hover:border-[#7692FF] hover:shadow-sm">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-bold text-[#091540]" x-text="act.title || 'Untitled'"></p>
                        <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                            :class="isPublished(act) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/50' : 'bg-amber-50 text-amber-800 border border-amber-200/50'"
                            x-text="isPublished(act) ? 'Published' : 'Draft'"></span>
                    </div>
                    <p class="mt-1 text-xs text-[#091540]/70 font-medium">
                        <span x-show="act.dueDate">Due <span x-text="act.dueDate"></span><span x-show="act.dueTime" x-text="' ' + act.dueTime"></span></span>
                        <span x-show="!act.dueDate">No deadline set</span>
                        <span> · </span>
                        <span x-text="(act.quiz?.questions?.length || 0) + ' quiz questions'"></span>
                    </p>
                </div>
                <div class="flex flex-wrap gap-2" x-show="!isPublished(act)" @click.stop>
                    <button type="button" @click="openRegEdit(act)"
                        class="rounded-lg bg-[#ABD2FA]/30 px-3 py-1.5 text-xs font-bold text-[#1B2CC1] hover:bg-[#ABD2FA]/50 transition-colors cursor-pointer">Edit</button>
                    <button type="button" @click="removeActivity(act)"
                        class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-100 transition-colors cursor-pointer">Delete</button>
                </div>
            </div>
        </template>
    </div>

    <div x-show="activityTab === 'lab'" x-cloak class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-xs font-medium text-[#091540]/70">
                Drafts stay hidden from students until you set a deadline and publish.
            </p>
            <button type="button" @click="createBlankLab()"
                class="rounded-xl bg-[#1B2CC1] px-3.5 py-2 text-xs font-bold text-white hover:bg-[#091540] transition-colors duration-150 shadow-sm cursor-pointer">
                + New lab activity
            </button>
        </div>
        <div x-show="labActivities().length === 0" class="rounded-2xl border border-dashed border-[#ABD2FA] bg-[#ABD2FA]/10 p-8 text-center text-xs font-medium text-[#091540]/60">
            No lab activities yet. Use a template or create a new draft.
        </div>
        <template x-for="act in labActivities()" :key="act.id">
            <div role="link" tabindex="0"
                @click="openActivity(act)"
                @keydown.enter.prevent="openActivity(act)"
                class="flex cursor-pointer flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#ABD2FA]/40 bg-white p-4 transition-all hover:border-[#7692FF] hover:shadow-sm">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-bold text-[#091540]" x-text="act.title || 'Untitled lab'"></p>
                        <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                            :class="isPublished(act) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/50' : 'bg-amber-50 text-amber-800 border border-amber-200/50'"
                            x-text="isPublished(act) ? 'Published' : 'Draft'"></span>
                    </div>
                    <p class="mt-1 text-xs text-[#091540]/70 font-medium">
                        <span x-text="act.labCategory || 'lab'"></span>
                        <span x-show="act.dueDate"> · Due <span x-text="act.dueDate"></span><span x-show="act.dueTime" x-text="' ' + act.dueTime"></span></span>
                        <span x-show="!act.dueDate && !isPublished(act)"> · No deadline set</span>
                    </p>
                </div>
                {{-- Draft only: edit + delete. Published: card opens details; no edit/delete. --}}
                <div class="flex flex-wrap gap-2" x-show="!isPublished(act)" @click.stop>
                    <button type="button" @click="openLabEdit(act)"
                        class="rounded-lg bg-[#ABD2FA]/30 px-3 py-1.5 text-xs font-bold text-[#1B2CC1] hover:bg-[#ABD2FA]/50 transition-colors cursor-pointer">Edit</button>
                    <button type="button" @click="removeActivity(act)"
                        class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-100 transition-colors cursor-pointer">Delete</button>
                </div>
            </div>
        </template>
    </div>

    <div x-show="activityTab === 'templates'" x-cloak class="space-y-3">
        <p class="text-xs font-medium text-[#091540]/70">
            Shared catalog (read-only).
            <span class="font-bold text-[#1B2CC1]">View</span> to inspect,
            <span class="font-bold text-[#1B2CC1]">Use</span> to add a draft to this class’s Lab activities.
        </p>
        <div x-show="templatesLoading" class="text-xs font-bold text-[#1B2CC1]">Loading templates…</div>
        <div x-show="!templatesLoading && templates.length === 0" class="rounded-2xl border border-dashed border-[#ABD2FA] bg-[#ABD2FA]/10 p-8 text-center text-xs font-medium text-[#091540]/60">No templates yet.</div>
        <div class="grid gap-3 sm:grid-cols-2">
            <template x-for="t in templates" :key="t.id">
                <div class="rounded-2xl border border-[#ABD2FA]/40 bg-white p-4 shadow-sm hover:border-[#7692FF] transition-all">
                    <div class="flex items-start justify-between gap-2">
                        <h4 class="text-sm font-bold text-[#091540]" x-text="t.title"></h4>
                        <span class="shrink-0 rounded-full bg-[#ABD2FA]/20 px-2.5 py-0.5 text-[10px] font-bold text-[#1B2CC1]" x-text="t.labCategory || '—'"></span>
                    </div>
                    <p class="mt-1.5 line-clamp-2 text-xs text-[#091540]/70 leading-relaxed" x-text="t.description || 'No description'"></p>
                    <div class="mt-3 flex flex-wrap gap-2 border-t border-[#ABD2FA]/30 pt-3 text-[11px] font-bold text-[#091540]/60">
                        <span x-text="(t.apparatus?.length || 0) + ' apparatus'"></span><span>·</span>
                        <span x-text="(t.elements?.length || 0) + ' elements'"></span><span>·</span>
                        <span x-text="(t.steps?.length || 0) + ' steps'"></span><span>·</span>
                        <span x-text="(t.quiz?.length || 0) + ' quiz'"></span>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button type="button" @click="openTplView(t)" class="rounded-lg bg-[#ABD2FA]/20 px-3 py-1.5 text-xs font-bold text-[#1B2CC1] hover:bg-[#ABD2FA]/40 transition-colors cursor-pointer">View</button>
                        <button type="button" @click="askUseTemplate(t)" class="rounded-lg bg-[#1B2CC1] px-3 py-1.5 text-xs font-bold text-white hover:bg-[#091540] transition-colors cursor-pointer">Use</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>