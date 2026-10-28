        {{-- Expanded template editor --}}
        <div x-show="tplModal" x-cloak class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4" role="dialog">
            <div class="absolute inset-0 bg-slate-900/40" @click="closeTplModal()"></div>
            <div class="relative flex max-h-[95vh] w-full max-w-3xl flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-xl sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-[#ABD2FA]/60 bg-gradient-to-r from-[#ABD2FA]/40 via-[#ABD2FA]/20 to-white px-6 py-5 text-[#091540]">
                    <div>
                        <h3 class="text-lg font-black tracking-tight text-[#091540]" x-text="tplModalTitle()"></h3>
                        <p class="text-xs font-medium text-[#091540]/70 mt-0.5" x-text="tplModalSubtitle()"></p>
                    </div>
                    <button type="button" @click="closeTplModal()" class="cursor-pointer rounded-xl bg-[#ABD2FA]/30 px-3.5 py-2 text-xs font-bold text-[#1B2CC1] hover:bg-[#ABD2FA]/50 hover:text-[#091540] transition-colors">Close</button>
                </div>

                <div class="flex flex-wrap gap-1.5 border-b border-[#ABD2FA]/60 bg-gradient-to-r from-[#ABD2FA]/20 via-[#ABD2FA]/10 to-white px-5 py-3">
                    <template x-for="sec in [
        { id: 'overview', label: 'Overview' },
        { id: 'apparatus', label: 'Apparatus' },
        { id: 'elements', label: 'Elements' },
        { id: 'steps', label: 'Steps' },
        { id: 'quiz', label: 'Quiz' },
    ]" :key="sec.id">
                        <button type="button" @click="tplSection = sec.id"
                            class="rounded-xl px-4 py-2 text-xs font-bold transition-all duration-150 cursor-pointer shadow-xs"
                            :class="tplSection === sec.id ? 'bg-[#1B2CC1] text-white shadow-sm' : 'bg-white border border-[#ABD2FA]/60 text-[#091540]/70 hover:bg-[#ABD2FA]/20 hover:text-[#091540]'">
                            <span x-text="sec.label"></span>
                        </button>
                    </template>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-4"
                    :class="tplReadOnly() && '[&_input]:pointer-events-none [&_select]:pointer-events-none [&_textarea]:pointer-events-none [&_input]:bg-slate-50 [&_select]:bg-slate-50 [&_textarea]:bg-slate-50'">
                    {{-- Overview --}}
                    <div x-show="tplSection === 'overview'" class="space-y-4">
                        <div>
                            <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Title</label>
                            <input type="text" x-model="tplForm.title" placeholder="e.g. Concentration of salt water"
                                :disabled="tplReadOnly()"
                                class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA] bg-white px-4 py-3 text-xs font-bold text-[#091540] placeholder:text-[#091540]/40 focus:border-[#1B2CC1] focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20 disabled:bg-[#ABD2FA]/10 disabled:text-[#091540]/60 transition-all">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Lab category</label>
                            <select x-model="tplForm.labCategory" :disabled="tplReadOnly()"
                                class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA] bg-white px-4 py-3 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20 disabled:bg-[#ABD2FA]/10 transition-all">
                                <template x-for="c in labCategories" :key="c">
                                    <option :value="c" x-text="c"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Description</label>
                            <textarea x-model="tplForm.description" rows="3" placeholder="What students learn in this lab"
                                :disabled="tplReadOnly()"
                                class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA] bg-white px-4 py-3 text-xs font-bold text-[#091540] placeholder:text-[#091540]/40 focus:border-[#1B2CC1] focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20 disabled:bg-[#ABD2FA]/10 transition-all"></textarea>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Safety tips</label>
                            <textarea x-model="tplForm.safetyTips" rows="2" placeholder="Wear goggles. Do not taste chemicals."
                                :disabled="tplReadOnly()"
                                class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA] bg-white px-4 py-3 text-xs font-bold text-[#091540] placeholder:text-[#091540]/40 focus:border-[#1B2CC1] focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20 disabled:bg-[#ABD2FA]/10 transition-all"></textarea>
                        </div>

                        <div class="border-t border-[#ABD2FA]/40 pt-4" x-show="tplModal === 'edit-lab'">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Attachments</p>
                                <label class="cursor-pointer rounded-xl bg-[#ABD2FA]/20 px-3.5 py-2 text-xs font-bold text-[#1B2CC1] hover:bg-[#ABD2FA]/40 transition-colors"
                                    :class="tplUploading && 'pointer-events-none opacity-50'">
                                    <span x-text="tplUploading ? 'Uploading…' : '+ File'"></span>
                                    <input type="file" class="hidden"
                                        accept=".pdf,.doc,.docx,image/png,image/jpeg,image/webp,application/pdf"
                                        :disabled="tplUploading"
                                        @change="onTplFileSelected($event)">
                                </label>
                            </div>
                            <ul class="space-y-2" x-show="(tplForm.attachments || []).length">
                                <template x-for="(file, fi) in (tplForm.attachments || [])" :key="file.storagePath || fi">
                                    <li class="flex items-center justify-between gap-2 rounded-xl border border-[#ABD2FA]/60 bg-[#ABD2FA]/10 px-3.5 py-2.5">
                                        <a :href="file.url || file.downloadURL" target="_blank" rel="noopener"
                                            class="truncate text-xs font-bold text-[#1B2CC1] hover:underline"
                                            x-text="file.name || 'Attachment'"></a>
                                        <button type="button" @click="removeTplAttachment(fi)" class="shrink-0 text-xs font-bold text-rose-500 hover:text-rose-700">Remove</button>
                                    </li>
                                </template>
                            </ul>
                            <p class="text-xs text-[#091540]/60 font-medium" x-show="!(tplForm.attachments || []).length">No files yet. PDF, Word, or image (max 15 MB).</p>
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
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Apparatus type</label>
                                        <select x-model="a.key" @change="onApparatusKeyChange(a)"
                                            class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3.5 py-2.5 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                            <template x-for="c in apparatusCatalog" :key="c.key">
                                                <option :value="c.key" x-text="c.displayName + ' (' + c.key + ')'"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Max volume (mL / slots)</label>
                                        <input type="number" x-model.number="a.maxVol" min="0"
                                            class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3.5 py-2.5 text-xs font-bold text-[#091540] placeholder:text-[#091540]/40 focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                        <p class="mt-1 text-[11px] font-medium text-[#091540]/60">Use 0 for tools with no capacity (e.g. stirring rod).</p>
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
                    <div x-show="tplSection === 'elements'" class="space-y-4">
                        <p class="text-xs font-medium text-[#091540]/70">Substances used in this lab. Color uses a picker (stored as #RRGGBBAA).</p>
                        <template x-if="elementCategoryRules[tplForm.labCategory]">
                            <div class="rounded-2xl border px-4 py-3 text-xs"
                                :class="elementGate().ok
                ? 'border-emerald-200 bg-emerald-50 text-emerald-900'
                : 'border-amber-200 bg-amber-50 text-amber-900'">
                                <p class="font-bold" x-text="'Required for ' + tplForm.labCategory + ': ' + (elementCategoryRules[tplForm.labCategory].summary || '')"></p>
                                <p x-show="!elementGate().ok" class="mt-1 font-medium">
                                    Missing:
                                    <span class="font-bold" x-text="elementGate().missing.join(', ')"></span>
                                </p>
                                <p x-show="elementGate().ok" class="mt-1 font-bold">All required roles/types present.</p>
                            </div>
                        </template>
                        <template x-if="!elementCategoryRules[tplForm.labCategory]">
                            <div class="rounded-2xl border border-[#ABD2FA]/40 bg-[#ABD2FA]/10 px-4 py-3 text-xs font-medium text-[#091540]/70">
                                No element role/type gate for <span class="font-bold text-[#091540]" x-text="tplForm.labCategory"></span> (e.g. Diffusion).
                            </div>
                        </template>

                        <div class="space-y-3">
                            <template x-for="(e, i) in tplForm.elements" :key="i">
                                <div class="space-y-3 rounded-2xl border border-[#ABD2FA]/60 bg-[#ABD2FA]/10 p-4">
                                    <div class="flex justify-between items-center border-b border-[#ABD2FA]/30 pb-2">
                                        <span class="text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]" x-text="'Element ' + (i + 1)"></span>
                                        <button type="button" x-show="!tplReadOnly()" @click="removeElement(i)" class="cursor-pointer text-xs font-bold text-rose-500 hover:text-rose-700 transition-colors">Remove</button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Substance name</label>
                                            <input type="text" x-model="e.substanceName" class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Formula</label>
                                            <input type="text" x-model="e.formula" class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Type</label>
                                            <select x-model="e.type" class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                                <template x-for="t in elementTypes" :key="t">
                                                    <option :value="t" x-text="t"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Role</label>
                                            <select x-model="e.role" class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                                <template x-for="r in elementRoles" :key="r">
                                                    <option :value="r" x-text="r"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Min volume</label>
                                            <input type="number" x-model.number="e.minVol" class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Max volume</label>
                                            <input type="number" x-model.number="e.maxVol" class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Default volume</label>
                                            <input type="number" x-model.number="e.volume" class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Molarity</label>
                                            <input type="number" step="0.001" x-model.number="e.molarity" class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Temperature (°C)</label>
                                            <input type="number" x-model.number="e.temperature" class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                        </div>
                                        <div class="col-span-2 sm:col-span-1">
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Color</label>
                                            <div class="flex items-center gap-2">
                                                <input type="color"
                                                    :value="colorToPicker(e.colorHex)"
                                                    @input="onColorPick(e, $event)"
                                                    class="h-9 w-12 cursor-pointer rounded-xl border border-[#ABD2FA] bg-white p-0.5">
                                                <span class="font-mono text-xs font-bold text-[#091540]" x-text="e.colorHex"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <p x-show="tplForm.elements.length === 0" class="text-center text-xs font-medium text-[#091540]/60 py-4">No elements yet.</p>
                        <div class="flex justify-end pt-2" x-show="!tplReadOnly()">
                            <button type="button" @click="addElement()" class="cursor-pointer rounded-xl bg-[#1B2CC1] px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#091540] transition-colors">+ Add element</button>
                        </div>
                    </div>

                    {{-- Steps --}}
                    <div x-show="tplSection === 'steps'" class="space-y-4">
                        <p class="text-xs font-medium text-[#091540]/70">Target / source apparatus and element formula come from what you added above.</p>
                        <div x-show="tplForm.apparatus.length === 0" class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-bold text-amber-900">
                            Add apparatus first so target/source dropdowns have options.
                        </div>

                        <div class="space-y-3">
                            <template x-for="(s, i) in tplForm.steps" :key="s._uid || i">
                                <div class="rounded-2xl border border-[#ABD2FA]/60 bg-[#ABD2FA]/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 bg-[#ABD2FA]/20">
                                        <button type="button" @click="s._open = !s._open"
                                            class="flex cursor-pointer min-w-0 flex-1 items-center gap-2.5 text-left">
                                            <svg class="h-4 w-4 shrink-0 text-[#1B2CC1] transition-transform duration-200" :class="s._open && 'rotate-90'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                            </svg>
                                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]" x-text="'Step ' + (i + 1)"></span>
                                            <span class="truncate text-xs font-bold text-[#091540]" x-text="s.instruction || s.action || 'Untitled step'"></span>
                                        </button>
                                        <div class="flex shrink-0 items-center gap-1.5" x-show="!tplReadOnly()">
                                            <button type="button" @click="moveStep(i, -1)" :disabled="i === 0"
                                                class="cursor-pointer rounded-lg bg-white/80 border border-[#ABD2FA] px-2 py-1 text-xs font-bold text-[#091540] hover:bg-white disabled:opacity-30 transition-colors" title="Move up">↑</button>
                                            <button type="button" @click="moveStep(i, 1)" :disabled="i === tplForm.steps.length - 1"
                                                class="cursor-pointer rounded-lg bg-white/80 border border-[#ABD2FA] px-2 py-1 text-xs font-bold text-[#091540] hover:bg-white disabled:opacity-30 transition-colors" title="Move down">↓</button>
                                            <button type="button" @click="removeStep(i)" class="cursor-pointer text-xs font-bold text-rose-500 hover:text-rose-700 transition-colors px-1.5">Remove</button>
                                        </div>
                                    </div>
                                    <div x-show="s._open" class="space-y-3.5 border-t border-[#ABD2FA]/30 px-4 py-4 bg-white">
                                        <div>
                                            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Instruction</label>
                                            <input type="text" x-model="s.instruction"
                                                class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2.5 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                        </div>
                                        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                                            <div>
                                                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Action</label>
                                                <select x-model="s.action" class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2.5 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                                    <template x-for="a in stepActions" :key="a">
                                                        <option :value="a" x-text="a"></option>
                                                    </template>
                                                </select>
                                                <p class="mt-1 text-[11px] font-medium leading-snug text-[#091540]/60" x-text="stepActionHints[s.action] || ''"></p>
                                            </div>
                                            <div>
                                                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Target apparatus</label>
                                                <select x-model="s.targetApparatusName" class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2.5 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                                    <option value="">— none —</option>
                                                    <template x-for="name in apparatusNames()" :key="'t-'+name">
                                                        <option :value="name" x-text="name"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Source apparatus</label>
                                                <select x-model="s.sourceApparatusName" class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2.5 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                                    <option value="">— none —</option>
                                                    <template x-for="name in apparatusNames()" :key="'s-'+name">
                                                        <option :value="name" x-text="name"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Element</label>
                                                <select x-model="s.elementFormula" class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2.5 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                                    <option value="">— none —</option>
                                                    <template x-for="(opt, oi) in elementOptions()" :key="'el-'+oi+'-'+opt.value">
                                                        <option :value="opt.value" x-text="opt.label"></option>
                                                    </template>
                                                </select>
                                                <p class="mt-1 text-[11px] font-medium text-[#091540]/60">Stored as formula for the AR client. Labels show substance name when formulas repeat.</p>
                                            </div>
                                            <div>
                                                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Target volume</label>
                                                <input type="number" x-model.number="s.targetVolume" class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2.5 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                            </div>
                                            <div>
                                                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Wait seconds</label>
                                                <input type="number" x-model.number="s.waitSeconds" class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2.5 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Reaction color (optional)</label>
                                                <div class="flex items-center gap-3">
                                                    <input type="color"
                                                        :value="colorToPicker(s.reactionColorHex || '#FFFFFF')"
                                                        @input="s.reactionColorHex = ($event.target.value + 'FF').toUpperCase()"
                                                        class="h-9 w-12 cursor-pointer rounded-xl border border-[#ABD2FA] bg-white p-0.5">
                                                    <button type="button" @click="s.reactionColorHex = ''" class="cursor-pointer text-xs font-bold text-[#091540]/60 hover:text-rose-500 transition-colors">Clear</button>
                                                    <span class="font-mono text-xs font-bold text-[#091540]" x-text="s.reactionColorHex || 'none'"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <p x-show="tplForm.steps.length === 0" class="text-center text-xs font-medium text-[#091540]/60 py-4">No steps yet.</p>
                        <div class="flex justify-end pt-2" x-show="!tplReadOnly()">
                            <button type="button" @click="addStep()" class="cursor-pointer rounded-xl bg-[#1B2CC1] px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#091540] transition-colors">+ Add step</button>
                        </div>
                    </div>

                    {{-- Quiz --}}
                    <div x-show="tplSection === 'quiz'" class="space-y-4">
                        <p class="text-xs font-medium text-[#091540]/70">Unlocked on mobile after experiment complete. Question ids are auto-generated (q1, q2, …).</p>
                        <div class="space-y-3">
                            <template x-for="(q, i) in tplForm.quiz" :key="q._uid || i">
                                <div class="rounded-2xl border border-[#ABD2FA]/60 bg-[#ABD2FA]/10 overflow-hidden">
                                    <div class="flex items-center gap-2 px-4 py-3 bg-[#ABD2FA]/20">
                                        <button type="button" @click="q._open = !q._open"
                                            class="flex cursor-pointer min-w-0 flex-1 items-center gap-2.5 text-left">
                                            <svg class="h-4 w-4 shrink-0 text-[#1B2CC1] transition-transform duration-200" :class="q._open && 'rotate-90'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                            </svg>
                                            <span class="rounded-lg bg-[#1B2CC1] px-2.5 py-0.5 font-mono text-[11px] font-black text-white shadow-xs" x-text="q.id"></span>
                                            <span class="truncate text-xs font-bold text-[#091540]" x-text="q.prompt || 'Untitled question'"></span>
                                        </button>
                                        <div class="flex shrink-0 items-center gap-1.5" x-show="!tplReadOnly()">
                                            <button type="button" @click="moveQuiz(i, -1)" :disabled="i === 0"
                                                class="cursor-pointer rounded-lg bg-white/80 border border-[#ABD2FA] px-2 py-1 text-xs font-bold text-[#091540] hover:bg-white disabled:opacity-30 transition-colors" title="Move up">↑</button>
                                            <button type="button" @click="moveQuiz(i, 1)" :disabled="i === tplForm.quiz.length - 1"
                                                class="cursor-pointer rounded-lg bg-white/80 border border-[#ABD2FA] px-2 py-1 text-xs font-bold text-[#091540] hover:bg-white disabled:opacity-30 transition-colors" title="Move down">↓</button>
                                            <button type="button" @click="removeQuiz(i)" class="cursor-pointer text-xs font-bold text-rose-500 hover:text-rose-700 transition-colors px-1.5">Remove</button>
                                        </div>
                                    </div>
                                    <div x-show="q._open" class="space-y-3.5 border-t border-[#ABD2FA]/30 px-4 py-4 bg-white">
                                        <div>
                                            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Question prompt</label>
                                            <input type="text" x-model="q.prompt"
                                                class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2.5 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                        </div>
                                        <template x-for="(opt, oi) in q.options" :key="oi">
                                            <div>
                                                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]" x-text="'Option ' + opt.id"></label>
                                                <input type="text" x-model="opt.text"
                                                    class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2.5 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                            </div>
                                        </template>
                                        <div>
                                            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Correct answer</label>
                                            <select x-model="q.correctOptionId" class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2.5 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20 transition-all">
                                                <template x-for="opt in q.options" :key="opt.id">
                                                    <option :value="opt.id" x-text="opt.id"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <p x-show="tplForm.quiz.length === 0" class="text-center text-xs font-medium text-[#091540]/60 py-4">No quiz questions yet.</p>
                        <div class="flex justify-end pt-2" x-show="!tplReadOnly()">
                            <button type="button" @click="addQuiz()" class="cursor-pointer rounded-xl bg-[#1B2CC1] px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#091540] transition-colors">+ Add question</button>
                        </div>
                    </div>

                    <p x-show="tplError" x-text="tplError" class="mt-3 text-xs font-bold text-red-500"></p>
                </div>

                <div class="flex justify-end gap-3 border-t border-[#ABD2FA]/30 px-6 py-4 bg-slate-50/50">
                    <button type="button" @click="closeTplModal()"
                        class="cursor-pointer rounded-xl border-[1.5px] border-[#ABD2FA] bg-white px-5 py-2.5 text-xs font-bold text-[#091540] hover:bg-[#ABD2FA]/20 transition-colors"
                        x-text="tplModal === 'edit-lab' ? 'Cancel' : 'Close'"></button>
                    <button type="button" x-show="tplModal === 'edit-lab'" @click="saveLabContent()"
                        :disabled="tplSaving || !tplForm.title.trim()"
                        class="cursor-pointer rounded-xl bg-[#1B2CC1] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#091540] disabled:opacity-50 transition-colors">
                        <span x-text="tplSaving ? 'Saving…' : 'Save content'"></span>
                    </button>
                </div>
            </div>
        </div>