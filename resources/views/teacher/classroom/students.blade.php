                {{-- Students --}}
                <div x-show="tab === 'people'" x-cloak class="space-y-4">
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-slate-500">Students who joined with the access code.</p>
                        <span class="text-[11px] font-bold text-slate-600" x-text="students().length + ' enrolled'"></span>
                    </div>
                    <div x-show="students().length === 0" class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center text-xs text-slate-400">No students have joined yet.</div>
                    <div class="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <template x-for="(s, idx) in students()" :key="idx">
                            <div class="px-4 py-3">
                                <p class="text-sm font-bold text-slate-800" x-text="studentLabel(s)"></p>
                                <p class="text-[11px] text-slate-400" x-text="studentSub(s)"></p>
                            </div>
                        </template>
                    </div>
                </div>
