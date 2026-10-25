        {{-- Expanded template editor --}}
        <div x-show="tplModal" x-cloak class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4" role="dialog">
            <div class="absolute inset-0 bg-slate-900/40" @click="closeTplModal()"></div>
            <div class="relative flex max-h-[95vh] w-full max-w-3xl flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-xl sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="text-lg font-black text-slate-800" x-text="tplModalTitle()"></h3>
                        <p class="text-[11px] text-slate-500" x-text="tplModalSubtitle()"></p>
                    </div>
                    <button type="button" @click="closeTplModal()" class="text-xs font-bold text-slate-400 hover:text-slate-600">Close</button>
                </div>

                <div class="flex flex-wrap gap-1 border-b border-slate-100 px-3 py-2">
                    <template x-for="sec in [
                        { id: 'overview', label: 'Overview' },
                        { id: 'apparatus', label: 'Apparatus' },
                        { id: 'elements', label: 'Elements' },
                        { id: 'steps', label: 'Steps' },
                        { id: 'quiz', label: 'Quiz' },
                    ]" :key="sec.id">
                        <button type="button" @click="tplSection = sec.id"
                                class="rounded-lg px-3 py-1.5 text-[11px] font-bold transition"
                                :class="tplSection === sec.id ? 'bg-[#7CD5F3] text-white' : 'text-slate-500 hover:bg-slate-50'">
                            <span x-text="sec.label"></span>
                        </button>
                    </template>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-4"
                     :class="tplReadOnly() && '[&_input]:pointer-events-none [&_select]:pointer-events-none [&_textarea]:pointer-events-none [&_input]:bg-slate-50 [&_select]:bg-slate-50 [&_textarea]:bg-slate-50'">
                    {{-- Overview --}}
                    <div x-show="tplSection === 'overview'" class="space-y-3">
                        <div>
                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Title</label>
                            <input type="text" x-model="tplForm.title" placeholder="e.g. Concentration of salt water"
                                   :disabled="tplReadOnly()"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold focus:border-[#7CD5F3] focus:outline-none focus:ring-2 focus:ring-[#7CD5F3]/20 disabled:bg-slate-50 disabled:text-slate-600">
                        </div>
                        <div>
                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Lab category</label>
                            <select x-model="tplForm.labCategory" :disabled="tplReadOnly()"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold disabled:bg-slate-50">
                                <template x-for="c in labCategories" :key="c"><option :value="c" x-text="c"></option></template>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Description</label>
                            <textarea x-model="tplForm.description" rows="3" placeholder="What students learn in this lab"
                                      :disabled="tplReadOnly()"
                                      class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold focus:border-[#7CD5F3] focus:outline-none disabled:bg-slate-50"></textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Safety tips</label>
                            <textarea x-model="tplForm.safetyTips" rows="2" placeholder="Wear goggles. Do not taste chemicals."
                                      :disabled="tplReadOnly()"
                                      class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold focus:border-[#7CD5F3] focus:outline-none disabled:bg-slate-50"></textarea>
                        </div>

                        <div class="border-t border-slate-100 pt-3" x-show="tplModal === 'edit-lab'">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Attachments</p>
                                <label class="cursor-pointer rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700 hover:bg-slate-200"
                                       :class="tplUploading && 'pointer-events-none opacity-50'">
                                    <span x-text="tplUploading ? 'Uploading…' : '+ File'"></span>
                                    <input type="file" class="hidden"
                                           accept=".pdf,.doc,.docx,image/png,image/jpeg,image/webp,application/pdf"
                                           :disabled="tplUploading"
                                           @change="onTplFileSelected($event)">
                                </label>
                            </div>
                            <ul class="space-y-1.5" x-show="(tplForm.attachments || []).length">
                                <template x-for="(file, fi) in (tplForm.attachments || [])" :key="file.storagePath || fi">
                                    <li class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                        <a :href="file.url || file.downloadURL" target="_blank" rel="noopener"
                                           class="truncate text-xs font-bold text-[#439BBB] hover:underline"
                                           x-text="file.name || 'Attachment'"></a>
                                        <button type="button" @click="removeTplAttachment(fi)" class="shrink-0 text-[11px] font-bold text-rose-500">Remove</button>
                                    </li>
                                </template>
                            </ul>
                            <p class="text-[11px] text-slate-400" x-show="!(tplForm.attachments || []).length">No files yet. PDF, Word, or image (max 15 MB).</p>
                        </div>
                    </div>

                    {{-- Apparatus --}}
                    <div x-show="tplSection === 'apparatus'" class="space-y-3">
                        <p class="text-xs text-slate-500">Select apparatus from the list. Max volume defaults from the catalog (edit if needed).</p>
                        <template x-for="(a, i) in tplForm.apparatus" :key="i">
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase text-slate-400" x-text="'Apparatus ' + (i + 1)"></span>
                                    <button type="button" x-show="!tplReadOnly()" @click="removeApparatus(i)" class="text-[11px] font-bold text-rose-500">Remove</button>
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Apparatus type</label>
                                        <select x-model="a.key" @change="onApparatusKeyChange(a)"
                                                class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                            <template x-for="c in apparatusCatalog" :key="c.key">
                                                <option :value="c.key" x-text="c.displayName + ' (' + c.key + ')'"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Max volume (mL / slots)</label>
                                        <input type="number" x-model.number="a.maxVol" min="0"
                                               class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                        <p class="mt-0.5 text-[10px] text-slate-400">Use 0 for tools with no capacity (e.g. stirring rod).</p>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <p x-show="tplForm.apparatus.length === 0" class="text-center text-xs text-slate-400">No apparatus yet. Click + Add and pick a type.</p>
                        <div class="flex justify-end" x-show="!tplReadOnly()">
                            <button type="button" @click="addApparatus()" class="text-xs font-bold text-[#50aed0]">+ Add</button>
                        </div>
                    </div>

                    {{-- Elements --}}
                    <div x-show="tplSection === 'elements'" class="space-y-3">
                        <p class="text-xs text-slate-500">Substances used in this lab. Color uses a picker (stored as #RRGGBBAA).</p>
                        <template x-if="elementCategoryRules[tplForm.labCategory]">
                            <div class="rounded-lg border px-3 py-2 text-[11px]"
                                 :class="elementGate().ok
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                                    : 'border-amber-200 bg-amber-50 text-amber-900'">
                                <p class="font-bold" x-text="'Required for ' + tplForm.labCategory + ': ' + (elementCategoryRules[tplForm.labCategory].summary || '')"></p>
                                <p x-show="!elementGate().ok" class="mt-0.5">
                                    Missing:
                                    <span class="font-semibold" x-text="elementGate().missing.join(', ')"></span>
                                </p>
                                <p x-show="elementGate().ok" class="mt-0.5 font-semibold">All required roles/types present.</p>
                            </div>
                        </template>
                        <template x-if="!elementCategoryRules[tplForm.labCategory]">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] text-slate-500">
                                No element role/type gate for <span class="font-semibold" x-text="tplForm.labCategory"></span> (e.g. Diffusion).
                            </div>
                        </template>
                        <template x-for="(e, i) in tplForm.elements" :key="i">
                            <div class="space-y-2 rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <div class="flex justify-between">
                                    <span class="text-[10px] font-bold uppercase text-slate-400" x-text="'Element ' + (i + 1)"></span>
                                    <button type="button" x-show="!tplReadOnly()" @click="removeElement(i)" class="text-[11px] font-bold text-rose-500">Remove</button>
                                </div>
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Substance name</label>
                                        <input type="text" x-model="e.substanceName" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Formula</label>
                                        <input type="text" x-model="e.formula" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Type</label>
                                        <select x-model="e.type" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                            <template x-for="t in elementTypes" :key="t"><option :value="t" x-text="t"></option></template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Role</label>
                                        <select x-model="e.role" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                            <template x-for="r in elementRoles" :key="r"><option :value="r" x-text="r"></option></template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Min volume</label>
                                        <input type="number" x-model.number="e.minVol" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Max volume</label>
                                        <input type="number" x-model.number="e.maxVol" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Default volume</label>
                                        <input type="number" x-model.number="e.volume" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Molarity</label>
                                        <input type="number" step="0.001" x-model.number="e.molarity" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Temperature (°C)</label>
                                        <input type="number" x-model.number="e.temperature" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                    </div>
                                    <div class="col-span-2 sm:col-span-1">
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Color</label>
                                        <div class="flex items-center gap-2">
                                            <input type="color"
                                                   :value="colorToPicker(e.colorHex)"
                                                   @input="onColorPick(e, $event)"
                                                   class="h-9 w-12 cursor-pointer rounded border border-slate-200 bg-white p-0.5">
                                            <span class="font-mono text-[11px] font-semibold text-slate-600" x-text="e.colorHex"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <p x-show="tplForm.elements.length === 0" class="text-center text-xs text-slate-400">No elements yet.</p>
                        <div class="flex justify-end" x-show="!tplReadOnly()">
                            <button type="button" @click="addElement()" class="text-xs font-bold text-[#50aed0]">+ Add</button>
                        </div>
                    </div>

                    {{-- Steps --}}
                    <div x-show="tplSection === 'steps'" class="space-y-3">
                        <p class="text-xs text-slate-500">Target / source apparatus and element formula come from what you added above.</p>
                        <div x-show="tplForm.apparatus.length === 0" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] font-semibold text-amber-800">
                            Add apparatus first so target/source dropdowns have options.
                        </div>
                        <template x-for="(s, i) in tplForm.steps" :key="s._uid || i">
                            <div class="rounded-xl border border-slate-100 bg-slate-50">
                                <div class="flex items-center gap-2 px-3 py-2">
                                    <button type="button" @click="s._open = !s._open"
                                            class="flex min-w-0 flex-1 items-center gap-2 text-left">
                                        <svg class="h-3.5 w-3.5 shrink-0 text-slate-400 transition-transform" :class="s._open && 'rotate-90'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-[10px] font-bold uppercase text-slate-400" x-text="'Step ' + (i + 1)"></span>
                                        <span class="truncate text-xs font-semibold text-slate-600" x-text="s.instruction || s.action || 'Untitled step'"></span>
                                    </button>
                                    <div class="flex shrink-0 items-center gap-1" x-show="!tplReadOnly()">
                                        <button type="button" @click="moveStep(i, -1)" :disabled="i === 0"
                                                class="rounded px-1.5 py-0.5 text-[11px] font-bold text-slate-500 hover:bg-slate-200 disabled:opacity-30" title="Move up">↑</button>
                                        <button type="button" @click="moveStep(i, 1)" :disabled="i === tplForm.steps.length - 1"
                                                class="rounded px-1.5 py-0.5 text-[11px] font-bold text-slate-500 hover:bg-slate-200 disabled:opacity-30" title="Move down">↓</button>
                                        <button type="button" @click="removeStep(i)" class="text-[11px] font-bold text-rose-500">Remove</button>
                                    </div>
                                </div>
                                <div x-show="s._open" class="space-y-2 border-t border-slate-100 px-3 pb-3 pt-2">
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Instruction</label>
                                        <input type="text" x-model="s.instruction"
                                               class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                    </div>
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Action</label>
                                            <select x-model="s.action" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                                <template x-for="a in stepActions" :key="a"><option :value="a" x-text="a"></option></template>
                                            </select>
                                            <p class="mt-1 text-[10px] leading-snug text-slate-400" x-text="stepActionHints[s.action] || ''"></p>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Target apparatus</label>
                                            <select x-model="s.targetApparatusName" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                                <option value="">— none —</option>
                                                <template x-for="name in apparatusNames()" :key="'t-'+name">
                                                    <option :value="name" x-text="name"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Source apparatus</label>
                                            <select x-model="s.sourceApparatusName" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                                <option value="">— none —</option>
                                                <template x-for="name in apparatusNames()" :key="'s-'+name">
                                                    <option :value="name" x-text="name"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Element</label>
                                            <select x-model="s.elementFormula" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                                <option value="">— none —</option>
                                                <template x-for="(opt, oi) in elementOptions()" :key="'el-'+oi+'-'+opt.value">
                                                    <option :value="opt.value" x-text="opt.label"></option>
                                                </template>
                                            </select>
                                            <p class="mt-0.5 text-[10px] text-slate-400">Stored as formula for the AR client. Labels show substance name when formulas repeat.</p>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Target volume</label>
                                            <input type="number" x-model.number="s.targetVolume" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Wait seconds</label>
                                            <input type="number" x-model.number="s.waitSeconds" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Reaction color (optional)</label>
                                            <div class="flex items-center gap-2">
                                                <input type="color"
                                                       :value="colorToPicker(s.reactionColorHex || '#FFFFFF')"
                                                       @input="s.reactionColorHex = ($event.target.value + 'FF').toUpperCase()"
                                                       class="h-9 w-12 cursor-pointer rounded border border-slate-200 bg-white p-0.5">
                                                <button type="button" @click="s.reactionColorHex = ''" class="text-[11px] font-bold text-slate-500 hover:underline">Clear</button>
                                                <span class="font-mono text-[11px] text-slate-500" x-text="s.reactionColorHex || 'none'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <p x-show="tplForm.steps.length === 0" class="text-center text-xs text-slate-400">No steps yet.</p>
                        <div class="flex justify-end" x-show="!tplReadOnly()">
                            <button type="button" @click="addStep()" class="text-xs font-bold text-[#50aed0]">+ Add step</button>
                        </div>
                    </div>

                    {{-- Quiz --}}
                    <div x-show="tplSection === 'quiz'" class="space-y-3">
                        <p class="text-xs text-slate-500">Unlocked on mobile after experiment complete. Question ids are auto-generated (q1, q2, …).</p>
                        <template x-for="(q, i) in tplForm.quiz" :key="q._uid || i">
                            <div class="rounded-xl border border-slate-100 bg-slate-50">
                                <div class="flex items-center gap-2 px-3 py-2">
                                    <button type="button" @click="q._open = !q._open"
                                            class="flex min-w-0 flex-1 items-center gap-2 text-left">
                                        <svg class="h-3.5 w-3.5 shrink-0 text-slate-400 transition-transform" :class="q._open && 'rotate-90'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="rounded-full bg-slate-200 px-2 py-0.5 font-mono text-[11px] font-bold text-slate-600" x-text="q.id"></span>
                                        <span class="truncate text-xs font-semibold text-slate-600" x-text="q.prompt || 'Untitled question'"></span>
                                    </button>
                                    <div class="flex shrink-0 items-center gap-1" x-show="!tplReadOnly()">
                                        <button type="button" @click="moveQuiz(i, -1)" :disabled="i === 0"
                                                class="rounded px-1.5 py-0.5 text-[11px] font-bold text-slate-500 hover:bg-slate-200 disabled:opacity-30" title="Move up">↑</button>
                                        <button type="button" @click="moveQuiz(i, 1)" :disabled="i === tplForm.quiz.length - 1"
                                                class="rounded px-1.5 py-0.5 text-[11px] font-bold text-slate-500 hover:bg-slate-200 disabled:opacity-30" title="Move down">↓</button>
                                        <button type="button" @click="removeQuiz(i)" class="text-[11px] font-bold text-rose-500">Remove</button>
                                    </div>
                                </div>
                                <div x-show="q._open" class="space-y-2 border-t border-slate-100 px-3 pb-3 pt-2">
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Question prompt</label>
                                        <input type="text" x-model="q.prompt"
                                               class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                    </div>
                                    <template x-for="(opt, oi) in q.options" :key="oi">
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500" x-text="'Option ' + opt.id"></label>
                                            <input type="text" x-model="opt.text"
                                                   class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                        </div>
                                    </template>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Correct answer</label>
                                        <select x-model="q.correctOptionId" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                            <template x-for="opt in q.options" :key="opt.id">
                                                <option :value="opt.id" x-text="opt.id"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <p x-show="tplForm.quiz.length === 0" class="text-center text-xs text-slate-400">No quiz questions yet.</p>
                        <div class="flex justify-end" x-show="!tplReadOnly()">
                            <button type="button" @click="addQuiz()" class="text-xs font-bold text-[#50aed0]">+ Add question</button>
                        </div>
                    </div>

                    <p x-show="tplError" x-text="tplError" class="mt-3 text-xs font-bold text-red-500"></p>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-3">
                    <button type="button" @click="closeTplModal()" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50"
                            x-text="tplModal === 'edit-lab' ? 'Cancel' : 'Close'"></button>
                    <button type="button" x-show="tplModal === 'edit-lab'" @click="saveLabContent()"
                            :disabled="tplSaving || !tplForm.title.trim()"
                            class="rounded-xl bg-[#7CD5F3] px-4 py-2 text-xs font-bold text-white hover:bg-[#68cbed] disabled:opacity-50">
                        <span x-text="tplSaving ? 'Saving…' : 'Save content'"></span>
                    </button>
                </div>
            </div>
        </div>
