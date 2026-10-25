                {{-- Activities --}}
                <div x-show="tab === 'activities'" x-cloak class="space-y-4">
                    <div class="flex flex-wrap gap-2">
                        <template x-for="st in [
                            { id: 'regular', label: 'Regular' },
                            { id: 'lab', label: 'Lab activities' },
                            { id: 'templates', label: 'Templates' },
                        ]" :key="st.id">
                            <button type="button" @click="selectActivityTab(st.id)"
                                    class="rounded-full px-3 py-1.5 text-xs font-bold transition"
                                    :class="activityTab === st.id ? 'bg-[#7CD5F3] text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
                                <span x-text="st.label"></span>
                            </button>
                        </template>
                    </div>

                    <div x-show="activityTab === 'regular'" x-cloak class="space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-xs text-slate-500">Assignments with title, description, and optional quiz. Drafts stay hidden until published.</p>
                            <button type="button" @click="openRegCreate()"
                                    class="rounded-xl bg-[#7CD5F3] px-3 py-1.5 text-xs font-bold text-white hover:bg-[#68cbed]">
                                + New regular activity
                            </button>
                        </div>
                        <div x-show="regularActivities().length === 0" class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center text-xs text-slate-400">No regular activities yet.</div>
                        <template x-for="act in regularActivities()" :key="act.id">
                            <div role="link" tabindex="0"
                                 @click="openActivity(act)"
                                 @keydown.enter.prevent="openActivity(act)"
                                 class="flex cursor-pointer flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-[#7CD5F3]/60 hover:shadow-sm">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-black text-slate-800" x-text="act.title || 'Untitled'"></p>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                              :class="isPublished(act) ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800'"
                                              x-text="isPublished(act) ? 'Published' : 'Draft'"></span>
                                    </div>
                                    <p class="mt-0.5 text-[11px] text-slate-500">
                                        <span x-show="act.dueDate">Due <span x-text="act.dueDate"></span><span x-show="act.dueTime" x-text="' ' + act.dueTime"></span></span>
                                        <span x-show="!act.dueDate">No deadline set</span>
                                        <span> · </span>
                                        <span x-text="(act.quiz?.questions?.length || 0) + ' quiz'"></span>
                                    </p>
                                </div>
                                <div class="flex gap-2" x-show="!isPublished(act)" @click.stop>
                                    <button type="button" @click="openRegEdit(act)"
                                            class="rounded-lg bg-[#7CD5F3]/15 px-3 py-1.5 text-[11px] font-bold text-[#439BBB]">Edit</button>
                                    <button type="button" @click="removeActivity(act)"
                                            class="rounded-lg bg-rose-50 px-3 py-1.5 text-[11px] font-bold text-rose-600">Delete</button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="activityTab === 'lab'" x-cloak class="space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-xs text-slate-500">
                                Drafts stay hidden from students until you set a deadline and publish.
                            </p>
                            <button type="button" @click="createBlankLab()"
                                    class="rounded-xl bg-[#7CD5F3] px-3 py-1.5 text-xs font-bold text-white hover:bg-[#68cbed]">
                                + New lab activity
                            </button>
                        </div>
                        <div x-show="labActivities().length === 0" class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center text-xs text-slate-400">
                            No lab activities yet. Use a template or create a new draft.
                        </div>
                        <template x-for="act in labActivities()" :key="act.id">
                            <div role="link" tabindex="0"
                                 @click="openActivity(act)"
                                 @keydown.enter.prevent="openActivity(act)"
                                 class="flex cursor-pointer flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-[#7CD5F3]/60 hover:shadow-sm">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-black text-slate-800" x-text="act.title || 'Untitled lab'"></p>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                              :class="isPublished(act) ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800'"
                                              x-text="isPublished(act) ? 'Published' : 'Draft'"></span>
                                    </div>
                                    <p class="mt-0.5 text-[11px] text-slate-500">
                                        <span x-text="act.labCategory || 'lab'"></span>
                                        <span x-show="act.dueDate"> · Due <span x-text="act.dueDate"></span><span x-show="act.dueTime" x-text="' ' + act.dueTime"></span></span>
                                        <span x-show="!act.dueDate && !isPublished(act)"> · No deadline set</span>
                                    </p>
                                </div>
                                {{-- Draft only: edit + delete. Published: card opens details; no edit/delete. --}}
                                <div class="flex gap-2" x-show="!isPublished(act)" @click.stop>
                                    <button type="button" @click="openLabEdit(act)"
                                            class="rounded-lg bg-[#7CD5F3]/15 px-3 py-1.5 text-[11px] font-bold text-[#439BBB]">Edit</button>
                                    <button type="button" @click="removeActivity(act)"
                                            class="rounded-lg bg-rose-50 px-3 py-1.5 text-[11px] font-bold text-rose-600">Delete</button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="activityTab === 'templates'" x-cloak class="space-y-3">
                        <p class="text-xs text-slate-500">
                            Shared catalog (read-only).
                            <span class="font-semibold text-slate-600">View</span> to inspect,
                            <span class="font-semibold text-slate-600">Use</span> to add a draft to this class’s Lab activities.
                        </p>
                        <div x-show="templatesLoading" class="text-xs font-semibold text-slate-500">Loading templates…</div>
                        <div x-show="!templatesLoading && templates.length === 0" class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center text-xs text-slate-400">No templates yet.</div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <template x-for="t in templates" :key="t.id">
                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="text-sm font-black text-slate-800" x-text="t.title"></h4>
                                        <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500" x-text="t.labCategory || '—'"></span>
                                    </div>
                                    <p class="mt-1 line-clamp-2 text-[11px] text-slate-500" x-text="t.description || 'No description'"></p>
                                    <div class="mt-3 flex flex-wrap gap-2 border-t border-slate-100 pt-3 text-[10px] font-bold text-slate-400">
                                        <span x-text="(t.apparatus?.length || 0) + ' apparatus'"></span><span>·</span>
                                        <span x-text="(t.elements?.length || 0) + ' elements'"></span><span>·</span>
                                        <span x-text="(t.steps?.length || 0) + ' steps'"></span><span>·</span>
                                        <span x-text="(t.quiz?.length || 0) + ' quiz'"></span>
                                    </div>
                                    <div class="mt-3 flex gap-2">
                                        <button type="button" @click="openTplView(t)" class="rounded-lg bg-slate-100 px-3 py-1.5 text-[11px] font-bold text-slate-700">View</button>
                                        <button type="button" @click="askUseTemplate(t)" class="rounded-lg bg-[#7CD5F3]/15 px-3 py-1.5 text-[11px] font-bold text-[#439BBB]">Use</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
