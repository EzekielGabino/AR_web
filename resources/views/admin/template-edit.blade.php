@extends('layouts.admin')

@section('title', ($templateId ? 'Edit' : 'New') . ' template — Admin')

@section('content')
<div class="min-h-screen bg-slate-50 text-[#091540]" x-data="adminTemplateEdit()" x-init="init()">
    @include('admin.partials.topbar')

    <div class="mx-auto max-w-3xl space-y-4 px-4 py-8 sm:px-6">
        <a href="{{ route('admin.dashboard') }}?tab=templates" class="text-xs font-bold text-[#1B2CC1]">← Lab templates</a>

        <div class="overflow-hidden rounded-2xl border border-[#ABD2FA]/60 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-[#ABD2FA]/60 bg-gradient-to-r from-[#ABD2FA]/40 via-[#ABD2FA]/20 to-white px-6 py-5">
                <div>
                    <h1 class="text-lg font-black tracking-tight" x-text="form.id ? 'Edit lab template' : 'New lab template'"></h1>
                    <p class="mt-0.5 text-xs font-medium text-[#091540]/70">Same structure as the teacher template editor</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-1.5 border-b border-[#ABD2FA]/60 bg-gradient-to-r from-[#ABD2FA]/20 via-[#ABD2FA]/10 to-white px-5 py-3">
                <template x-for="sec in sections" :key="sec.id">
                    <button type="button" @click="section = sec.id"
                        class="cursor-pointer rounded-xl px-4 py-2 text-xs font-bold shadow-xs transition-all duration-150"
                        :class="section === sec.id ? 'bg-[#1B2CC1] text-white shadow-sm' : 'border border-[#ABD2FA]/60 bg-white text-[#091540]/70 hover:bg-[#ABD2FA]/20 hover:text-[#091540]'">
                        <span x-text="sec.label"></span>
                    </button>
                </template>
            </div>

            <div class="space-y-4 px-5 py-4" x-show="!loading">
                {{-- Overview --}}
                <div x-show="section === 'overview'" class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Title</label>
                        <input type="text" x-model="form.title" placeholder="e.g. Concentration of salt water"
                            class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA] bg-white px-4 py-3 text-xs font-bold text-[#091540] placeholder:text-[#091540]/40 focus:border-[#1B2CC1] focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Lab category</label>
                        <select x-model="form.labCategory"
                            class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA] bg-white px-4 py-3 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20">
                            <template x-for="c in labCategories" :key="c">
                                <option :value="c" x-text="c"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Description</label>
                        <textarea x-model="form.description" rows="3" placeholder="What students learn in this lab"
                            class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA] bg-white px-4 py-3 text-xs font-bold text-[#091540] placeholder:text-[#091540]/40 focus:border-[#1B2CC1] focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20"></textarea>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Safety precautions</label>
                        <div class="space-y-2 rounded-2xl border border-[#ABD2FA] bg-white p-3">
                            <template x-for="opt in safetyOptions" :key="opt">
                                <label class="flex cursor-pointer items-start gap-2 text-xs font-semibold text-[#091540]">
                                    <input type="checkbox" class="mt-0.5"
                                        :checked="!!safetySelected[opt]"
                                        @change="safetySelected[opt] = $event.target.checked; composeSafetyTips()">
                                    <span x-text="opt"></span>
                                </label>
                            </template>
                        </div>
                        <textarea x-model="safetyCustom" rows="2" placeholder="Optional custom note"
                            @input="composeSafetyTips()"
                            class="mt-2 w-full rounded-2xl border-[1.5px] border-[#ABD2FA] bg-white px-4 py-3 text-xs font-bold text-[#091540] placeholder:text-[#091540]/40 focus:border-[#1B2CC1] focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20"></textarea>
                    </div>
                </div>

                {{-- Apparatus --}}
                <div x-show="section === 'apparatus'" class="space-y-4">
                    <p class="text-xs text-slate-500">Select apparatus from the catalog. Max volume defaults from the catalog (edit if needed).</p>
                    <div class="space-y-3">
                        <template x-for="(a, i) in form.apparatus" :key="i">
                            <div class="rounded-2xl border border-[#ABD2FA]/50 bg-white p-4 shadow-sm">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-slate-400" x-text="'#' + (i + 1)"></span>
                                    <button type="button" @click="removeApparatus(i)" class="text-xs font-bold text-rose-500">Remove</button>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Apparatus</label>
                                        <select x-model="a.key" @change="onApparatusKeyChange(a, a.key)"
                                            class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20">
                                            <template x-for="c in apparatusCatalog" :key="c.key">
                                                <option :value="c.key" x-text="c.displayName"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Max volume (mL / slots)</label>
                                        <input type="number" x-model.number="a.maxVol" min="0"
                                            class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <p x-show="form.apparatus.length === 0" class="py-4 text-center text-xs font-medium text-[#091540]/60">No apparatus yet.</p>
                    <div class="flex justify-end pt-2">
                        <button type="button" @click="addApparatus()"
                            class="cursor-pointer rounded-xl bg-[#1B2CC1] px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#091540]">+ Add apparatus</button>
                    </div>
                </div>

                {{-- Elements --}}
                <div x-show="section === 'elements'" class="space-y-4">
                    <p class="text-xs text-slate-500">
                        Min volume 2–50.
                        Max volume up to <span class="font-bold" x-text="elementVolumeCeiling()"></span>
                        (1.5× largest apparatus max volume).
                        Default volume stays between min and max.
                    </p>
                    <div class="space-y-3">
                        <template x-for="(e, i) in form.elements" :key="i">
                            <div class="rounded-2xl border border-[#ABD2FA]/50 bg-white p-4 shadow-sm">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-slate-400" x-text="'#' + (i + 1)"></span>
                                    <button type="button" @click="removeElement(i)" class="text-xs font-bold text-rose-500">Remove</button>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Substance name</label>
                                        <input type="text" x-model="e.substanceName"
                                            class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Formula</label>
                                        <input type="text" x-model="e.formula" placeholder="Uses substance name if empty"
                                            class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Type</label>
                                        <select x-model="e.type"
                                            class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20">
                                            <option value="liquid">liquid</option>
                                            <option value="solid">solid</option>
                                            <option value="gas">gas</option>
                                            <option value="granule">granule</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Role</label>
                                        <select x-model="e.role"
                                            class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20">
                                            <option>Neutral</option>
                                            <option>Acid</option>
                                            <option>Base</option>
                                            <option>Salt</option>
                                            <option>Indicator</option>
                                            <option>Carbonate</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Min volume</label>
                                        <input type="number" x-model.number="e.minVol" min="2" max="50" step="1"
                                            class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Max volume</label>
                                        <input type="number" x-model.number="e.maxVol" min="0" :max="elementVolumeCeiling()" step="1"
                                            class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Default volume</label>
                                        <input type="number" x-model.number="e.volume" min="0" :max="e.maxVol || elementVolumeCeiling()" step="1"
                                            class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Molarity</label>
                                        <input type="number" step="0.001" x-model.number="e.molarity"
                                            class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Temperature (°C)</label>
                                        <input type="number" x-model.number="e.temperature"
                                            class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20">
                                    </div>
                                    <div class="sm:col-span-2">
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
                    <p x-show="form.elements.length === 0" class="py-4 text-center text-xs font-medium text-[#091540]/60">No elements yet.</p>
                    <div class="flex justify-end pt-2">
                        <button type="button" @click="addElement()"
                            class="cursor-pointer rounded-xl bg-[#1B2CC1] px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#091540]">+ Add element</button>
                    </div>
                </div>

                {{-- Steps --}}
                <div x-show="section === 'steps'" class="space-y-4">
                    <div class="space-y-3">
                        <template x-for="(st, i) in form.steps" :key="st._uid || i">
                            <div class="rounded-2xl border border-[#ABD2FA]/50 bg-white p-4 shadow-sm">
                                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                    <button type="button" class="text-left text-xs font-black text-[#1B2CC1]" @click="st._open = !st._open">
                                        <span x-text="'Step ' + (i + 1)"></span>
                                        <span class="ml-2 font-medium text-[#091540]/60" x-text="st.action || ''"></span>
                                    </button>
                                    <div class="flex gap-2">
                                        <button type="button" @click="moveStep(i, -1)" class="text-[10px] font-bold text-[#1B2CC1]">Up</button>
                                        <button type="button" @click="moveStep(i, 1)" class="text-[10px] font-bold text-[#1B2CC1]">Down</button>
                                        <button type="button" @click="removeStep(i)" class="text-xs font-bold text-rose-500">Remove</button>
                                    </div>
                                </div>
                                <div x-show="st._open !== false" class="space-y-3">
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Instruction</label>
                                        <textarea x-model="st.instruction" rows="2"
                                            class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20"></textarea>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Action</label>
                                            <select x-model="st.action"
                                                class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20">
                                                <option value="PlaceApparatus">PlaceApparatus</option>
                                                <option value="AddElement">AddElement</option>
                                                <option value="Transfer">Transfer</option>
                                                <option value="Stir">Stir</option>
                                                <option value="Heat">Heat</option>
                                                <option value="Observe">Observe</option>
                                                <option value="Drain">Drain</option>
                                                <option value="Wait">Wait</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Target apparatus</label>
                                            <select x-model="st.targetApparatusName"
                                                class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20">
                                                <option value="">—</option>
                                                <template x-for="n in apparatusNames()" :key="n">
                                                    <option :value="n" x-text="n"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div x-show="st.action === 'AddElement' || st.action === 'Transfer'">
                                            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Element</label>
                                            <select x-model="st.elementFormula"
                                                class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20">
                                                <option value="">—</option>
                                                <template x-for="opt in elementOptions()" :key="opt.value">
                                                    <option :value="opt.value" x-text="opt.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div x-show="st.action === 'Transfer'">
                                            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Source apparatus</label>
                                            <select x-model="st.sourceApparatusName"
                                                class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20">
                                                <option value="">—</option>
                                                <template x-for="n in apparatusNames()" :key="'s'+n">
                                                    <option :value="n" x-text="n"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div x-show="st.action === 'AddElement' || st.action === 'Transfer' || st.action === 'Drain'">
                                            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Target volume</label>
                                            <input type="number" x-model.number="st.targetVolume" min="0"
                                                class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20">
                                        </div>
                                        <div x-show="st.action === 'Wait'">
                                            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Wait seconds</label>
                                            <input type="number" x-model.number="st.waitSeconds" min="0"
                                                class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <p x-show="form.steps.length === 0" class="py-4 text-center text-xs font-medium text-[#091540]/60">No steps yet.</p>
                    <div class="flex justify-end pt-2">
                        <button type="button" @click="addStep()"
                            class="cursor-pointer rounded-xl bg-[#1B2CC1] px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#091540]">+ Add step</button>
                    </div>
                </div>

                {{-- Quiz --}}
                <div x-show="section === 'quiz'" class="space-y-4">
                    <div class="space-y-3">
                        <template x-for="(q, i) in form.quiz" :key="q._uid || i">
                            <div class="rounded-2xl border border-[#ABD2FA]/50 bg-white p-4 shadow-sm">
                                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                    <button type="button" class="text-left text-xs font-black text-[#1B2CC1]" @click="q._open = !q._open">
                                        <span x-text="'Question ' + (i + 1)"></span>
                                    </button>
                                    <div class="flex gap-2">
                                        <button type="button" @click="moveQuiz(i, -1)" class="text-[10px] font-bold text-[#1B2CC1]">Up</button>
                                        <button type="button" @click="moveQuiz(i, 1)" class="text-[10px] font-bold text-[#1B2CC1]">Down</button>
                                        <button type="button" @click="removeQuiz(i)" class="text-xs font-bold text-rose-500">Remove</button>
                                    </div>
                                </div>
                                <div x-show="q._open !== false" class="space-y-3">
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Prompt</label>
                                        <textarea x-model="q.prompt" rows="2"
                                            class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20"></textarea>
                                    </div>
                                    <template x-for="opt in q.options" :key="opt.id">
                                        <div class="flex items-center gap-2">
                                            <span class="w-6 text-xs font-black text-[#1B2CC1]" x-text="opt.id"></span>
                                            <input type="text" x-model="opt.text" placeholder="Option text"
                                                class="flex-1 rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20">
                                        </div>
                                    </template>
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#1B2CC1]">Correct option</label>
                                        <select x-model="q.correctOptionId"
                                            class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20">
                                            <template x-for="opt in q.options" :key="'c'+opt.id">
                                                <option :value="opt.id" x-text="opt.id"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <p x-show="form.quiz.length === 0" class="py-4 text-center text-xs font-medium text-[#091540]/60">No quiz questions yet.</p>
                    <div class="flex justify-end pt-2">
                        <button type="button" @click="addQuiz()"
                            class="cursor-pointer rounded-xl bg-[#1B2CC1] px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#091540]">+ Add question</button>
                    </div>
                </div>

                <p x-show="error" x-text="error" class="text-xs font-bold text-rose-600"></p>
                <p x-show="message" x-text="message" class="text-xs font-bold text-emerald-600"></p>
            </div>

            <div x-show="loading" class="px-5 py-8 text-center text-xs text-slate-500">Loading…</div>

            <div class="flex justify-end gap-3 border-t border-[#ABD2FA]/30 bg-slate-50/50 px-6 py-4">
                <a href="{{ route('admin.dashboard') }}?tab=templates"
                    class="cursor-pointer rounded-xl border-[1.5px] border-[#ABD2FA] bg-white px-5 py-2.5 text-xs font-bold text-[#091540] hover:bg-[#ABD2FA]/20">Cancel</a>
                <button type="button" @click="save()" :disabled="saving || !(form.title || '').trim()"
                    class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-[#1B2CC1] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#091540] disabled:opacity-50">
                    <svg x-show="saving" x-cloak class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span x-text="saving ? 'Saving…' : 'Save template'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script type="module" src="{{ asset('js/admin/admin.js') }}"></script>
<script>
function adminTemplateEdit() {
    return {
        templateId: @js($templateId),
        sections: [
            { id: 'overview', label: 'Overview' },
            { id: 'apparatus', label: 'Apparatus' },
            { id: 'elements', label: 'Elements' },
            { id: 'steps', label: 'Steps' },
            { id: 'quiz', label: 'Quiz' },
        ],
        section: 'overview',
        loading: false,
        saving: false,
        error: '',
        message: '',
        labCategories: [
            'Acid-Base', 'Concentration', 'Diffusion', 'Endothermic', 'Exothermic',
            'Gas Evolution', 'Precipitation', 'Carbonation',
        ],
        safetyOptions: [
            'Wear safety goggles at all times',
            'Wear a lab coat or apron',
            'Wear protective gloves when handling chemicals',
            'Tie back long hair',
            'Do not taste or smell chemicals directly',
            'Keep work area clean and organized',
            'Report spills and accidents to the teacher immediately',
            'Wash hands after the experiment',
        ],
        safetySelected: {},
        safetyCustom: '',
        apparatusCatalog: [
            { key: 'beaker', displayName: 'Beaker', defaultMaxVol: 250 },
            { key: 'test_tube', displayName: 'Test Tube', defaultMaxVol: 50 },
            { key: 'test_tube_rack', displayName: 'Test Tube Rack', defaultMaxVol: 6 },
            { key: 'stirring_rod', displayName: 'Stirring Rod', defaultMaxVol: 0 },
            { key: 'conical_flask', displayName: 'Conical Flask', defaultMaxVol: 250 },
            { key: 'pipette', displayName: 'Pipette', defaultMaxVol: 10 },
        ],
        form: {
            id: null,
            title: '',
            labCategory: 'Acid-Base',
            description: '',
            safetyTips: '',
            apparatus: [],
            elements: [],
            steps: [],
            quiz: [],
        },

        async init() {
            let n = 0;
            while (!window.AdminApi && n < 100) {
                await new Promise((r) => setTimeout(r, 40));
                n++;
            }
            if (!this.templateId) return;
            this.loading = true;
            this.error = '';
            try {
                if (!window.AdminApi) throw new Error('Admin module failed to load');
                const t = await window.AdminApi.showTemplate(this.templateId);
                this.form = {
                    id: t.id,
                    title: t.title || '',
                    labCategory: t.labCategory || 'Acid-Base',
                    description: t.description || '',
                    safetyTips: t.safetyTips || '',
                    apparatus: Array.isArray(t.apparatus) ? t.apparatus.map((a) => this.normalizeApparatus(a)) : [],
                    elements: Array.isArray(t.elements) ? t.elements.map((e) => this.normalizeElement(e)) : [],
                    steps: Array.isArray(t.steps) ? t.steps.map((s, i) => this.normalizeStep(s, i)) : [],
                    quiz: Array.isArray(t.quiz) ? t.quiz.map((q, i) => this.normalizeQuiz(q, i)) : [],
                };
                this.parseSafetyTips(this.form.safetyTips);
            } catch (e) {
                this.error = e.message || 'Load failed.';
            } finally {
                this.loading = false;
            }
        },

        normalizeApparatus(a) {
            a = a || {};
            const key = a.key || a.id || '';
            const cat = this.apparatusCatalog.find((c) => c.key === key);
            return {
                key,
                displayName: a.displayName || a.name || (cat && cat.displayName) || key,
                maxVol: Number(a.maxVol ?? a.maxVolume ?? (cat && cat.defaultMaxVol) ?? 0) || 0,
            };
        },
        normalizeElement(e) {
            e = e || {};
            const minVol = Number(e.minVol ?? e.minVolume ?? e.min_vol ?? 10);
            const maxVol = Number(e.maxVol ?? e.maxVolume ?? e.max_vol ?? 100);
            const volume = Number(e.volume ?? e.defaultVolume ?? 50);
            const name = e.substanceName || e.substance_name || e.name || '';
            return {
                substanceName: name,
                name,
                formula: e.formula || '',
                type: e.type || 'liquid',
                role: e.role || 'Neutral',
                minVol: Number.isFinite(minVol) ? minVol : 10,
                maxVol: Number.isFinite(maxVol) ? maxVol : 100,
                volume: Number.isFinite(volume) ? volume : 50,
                molarity: Number(e.molarity ?? 0) || 0,
                temperature: Number(e.temperature ?? 25) || 25,
                colorHex: e.colorHex || e.color_hex || '#80B0E0FF',
            };
        },
        normalizeStep(s, i) {
            s = s || {};
            return {
                _uid: s._uid || ('s' + i + '_' + Date.now().toString(36)),
                _open: true,
                order: s.order != null ? s.order : i + 1,
                instruction: s.instruction || '',
                action: s.action || s.type || 'PlaceApparatus',
                elementFormula: s.elementFormula || s.element || s.formula || '',
                targetApparatusName: s.targetApparatusName || s.targetApparatus || '',
                sourceApparatusName: s.sourceApparatusName || s.sourceApparatus || '',
                targetVolume: Number(s.targetVolume ?? s.volume ?? 0) || 0,
                waitSeconds: Number(s.waitSeconds ?? 0) || 0,
                reactionColorHex: s.reactionColorHex || '',
            };
        },
        normalizeQuiz(q, i) {
            q = q || {};
            const opts = Array.isArray(q.options) && q.options.length
                ? q.options.map((o, j) => ({
                    id: o.id || String.fromCharCode(65 + j),
                    text: o.text || '',
                }))
                : [
                    { id: 'A', text: '' },
                    { id: 'B', text: '' },
                    { id: 'C', text: '' },
                    { id: 'D', text: '' },
                ];
            return {
                _uid: q._uid || ('q' + i + '_' + Date.now().toString(36)),
                _open: true,
                id: q.id || ('q' + (i + 1)),
                prompt: q.prompt || '',
                correctOptionId: q.correctOptionId || q.correctOption || opts[0].id,
                options: opts,
            };
        },

        parseSafetyTips(text) {
            const lines = String(text || '').split(/\n+/).map((s) => s.trim()).filter(Boolean);
            const selected = {};
            this.safetyOptions.forEach((opt) => { selected[opt] = false; });
            const custom = [];
            lines.forEach((line) => {
                const cleaned = line.replace(/^[-•*]\s*/, '');
                if (this.safetyOptions.includes(cleaned)) selected[cleaned] = true;
                else if (cleaned) custom.push(cleaned);
            });
            this.safetySelected = selected;
            this.safetyCustom = custom.join('\n');
        },
        composeSafetyTips() {
            const picked = this.safetyOptions.filter((opt) => this.safetySelected[opt]);
            const custom = (this.safetyCustom || '').trim();
            const parts = [...picked];
            if (custom) parts.push(custom);
            this.form.safetyTips = parts.join('\n');
        },

        addApparatus() {
            const used = new Set((this.form.apparatus || []).map((a) => a.key));
            const pick = this.apparatusCatalog.find((c) => !used.has(c.key)) || this.apparatusCatalog[0];
            this.form.apparatus.push({
                key: pick.key,
                displayName: pick.displayName,
                maxVol: pick.defaultMaxVol,
            });
        },
        onApparatusKeyChange(a, key) {
            const cat = this.apparatusCatalog.find((c) => c.key === key);
            if (!cat) return;
            a.key = cat.key;
            a.displayName = cat.displayName;
            if (a.maxVol === 0 || a.maxVol === '' || a.maxVol == null) {
                a.maxVol = cat.defaultMaxVol;
            }
        },
        removeApparatus(i) {
            this.form.apparatus.splice(i, 1);
        },

        addElement() {
            this.form.elements.push({
                substanceName: '',
                name: '',
                formula: '',
                type: 'liquid',
                role: 'Neutral',
                minVol: 10,
                maxVol: 100,
                volume: 50,
                molarity: 0,
                temperature: 25,
                colorHex: '#80B0E0FF',
            });
        },
        removeElement(i) {
            this.form.elements.splice(i, 1);
        },
        elementVolumeCeiling() {
            const apps = this.form.apparatus || [];
            let peak = 0;
            for (const a of apps) {
                const v = Number(a.maxVol);
                if (Number.isFinite(v) && v > peak) peak = v;
            }
            if (peak <= 0) peak = 250;
            return Math.round(peak * 1.5 * 100) / 100;
        },
        clampElementVolumes(elements) {
            const ceiling = this.elementVolumeCeiling();
            const minFloor = 2;
            const minCap = 50;
            return (elements || []).map((e) => {
                let minV = Number(e.minVol);
                let maxV = Number(e.maxVol);
                let vol = Number(e.volume);
                if (!Number.isFinite(minV)) minV = minFloor;
                if (!Number.isFinite(maxV)) maxV = Math.min(ceiling, 100);
                if (!Number.isFinite(vol)) vol = minV;
                minV = Math.min(minCap, Math.max(minFloor, minV));
                maxV = Math.min(ceiling, Math.max(minV, maxV));
                if (maxV < 0) maxV = minV;
                vol = Math.min(maxV, Math.max(minV, vol));
                return { ...e, minVol: minV, maxVol: maxV, volume: vol };
            });
        },
        colorToPicker(hex) {
            if (!hex || typeof hex !== 'string') return '#80B0E0';
            const h = hex.trim();
            if (h.startsWith('#') && h.length >= 7) return h.slice(0, 7);
            return '#80B0E0';
        },
        onColorPick(e, event) {
            const rgb = event.target.value;
            const prev = (e.colorHex || '#80B0E0FF').trim();
            const alpha = prev.length >= 9 ? prev.slice(7, 9) : 'FF';
            e.colorHex = rgb.toUpperCase() + alpha.toUpperCase();
        },

        _uid() {
            return 'u' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
        },
        apparatusNames() {
            return (this.form.apparatus || [])
                .map((a) => a.displayName || a.key)
                .filter(Boolean);
        },
        elementOptions() {
            return (this.form.elements || [])
                .map((e) => {
                    const formula = (e.formula || '').trim();
                    const name = (e.substanceName || e.name || '').trim();
                    const value = formula || name;
                    if (!value) return null;
                    return { value, label: name && formula && name !== formula ? (name + ' (' + formula + ')') : value };
                })
                .filter(Boolean);
        },
        addStep() {
            const apps = this.apparatusNames();
            const els = this.elementOptions();
            this.form.steps.push({
                _uid: this._uid(),
                _open: true,
                order: this.form.steps.length + 1,
                instruction: '',
                action: 'PlaceApparatus',
                elementFormula: (els[0] && els[0].value) || '',
                targetApparatusName: apps[0] || '',
                sourceApparatusName: '',
                targetVolume: 0,
                waitSeconds: 0,
                reactionColorHex: '',
            });
        },
        removeStep(i) {
            this.form.steps.splice(i, 1);
            this.renumberSteps();
        },
        moveStep(i, dir) {
            const j = i + dir;
            const arr = this.form.steps;
            if (j < 0 || j >= arr.length) return;
            const item = arr.splice(i, 1)[0];
            arr.splice(j, 0, item);
            this.renumberSteps();
        },
        renumberSteps() {
            this.form.steps.forEach((s, i) => { s.order = i + 1; });
        },

        addQuiz() {
            const n = this.form.quiz.length + 1;
            this.form.quiz.push({
                _uid: this._uid(),
                _open: true,
                id: 'q' + n,
                prompt: '',
                correctOptionId: 'A',
                options: [
                    { id: 'A', text: '' },
                    { id: 'B', text: '' },
                    { id: 'C', text: '' },
                    { id: 'D', text: '' },
                ],
            });
        },
        removeQuiz(i) {
            this.form.quiz.splice(i, 1);
            this.form.quiz.forEach((q, idx) => { q.id = 'q' + (idx + 1); });
        },
        moveQuiz(i, dir) {
            const j = i + dir;
            const arr = this.form.quiz;
            if (j < 0 || j >= arr.length) return;
            const item = arr.splice(i, 1)[0];
            arr.splice(j, 0, item);
            arr.forEach((q, idx) => { q.id = 'q' + (idx + 1); });
        },

        buildPayload() {
            this.composeSafetyTips();
            this.renumberSteps();
            const elements = this.clampElementVolumes(this.form.elements || []).map((e) => {
                const name = (e.substanceName || e.name || '').trim();
                let formula = (e.formula || '').trim();
                if (!formula && name) formula = name;
                return {
                    substanceName: name,
                    name,
                    formula,
                    type: e.type || 'liquid',
                    role: e.role || 'Neutral',
                    minVol: Number(e.minVol) || 0,
                    maxVol: Number(e.maxVol) || 0,
                    volume: Number(e.volume) || 0,
                    molarity: Number(e.molarity) || 0,
                    temperature: Number(e.temperature) || 25,
                    colorHex: e.colorHex || '#80B0E0FF',
                    minVolume: Number(e.minVol) || 0,
                    maxVolume: Number(e.maxVol) || 0,
                };
            });
            const apparatus = (this.form.apparatus || []).map((a) => ({
                key: a.key,
                displayName: a.displayName || a.key,
                maxVol: Number(a.maxVol) || 0,
            }));
            const steps = (this.form.steps || []).map((s, i) => {
                let elementFormula = (s.elementFormula || '').trim();
                if (!elementFormula && (s.action || '') === 'AddElement') {
                    const hit = elements.find((e) => (e.formula || e.name || '').trim());
                    if (hit) elementFormula = (hit.formula || hit.name || '').trim();
                }
                return {
                    order: i + 1,
                    instruction: s.instruction || '',
                    action: s.action || 'PlaceApparatus',
                    elementFormula,
                    targetApparatusName: s.targetApparatusName || '',
                    sourceApparatusName: s.sourceApparatusName || '',
                    targetVolume: Number(s.targetVolume) || 0,
                    waitSeconds: Number(s.waitSeconds) || 0,
                    reactionColorHex: s.reactionColorHex || '',
                };
            });
            const quiz = (this.form.quiz || []).map((q, i) => ({
                id: q.id || ('q' + (i + 1)),
                prompt: q.prompt || '',
                correctOptionId: q.correctOptionId || 'A',
                options: (q.options || []).map((o) => ({ id: o.id, text: o.text || '' })),
            }));
            return {
                title: (this.form.title || '').trim(),
                labCategory: this.form.labCategory || '',
                description: this.form.description || '',
                safetyTips: this.form.safetyTips || '',
                apparatus,
                elements,
                steps,
                quiz,
            };
        },

        async save() {
            this.saving = true;
            this.error = '';
            this.message = '';
            try {
                if (!(this.form.title || '').trim()) throw new Error('Title is required.');
                if (!window.AdminApi) throw new Error('Admin module failed to load');
                const body = this.buildPayload();
                if (this.form.id) {
                    await window.AdminApi.updateTemplate(this.form.id, body);
                    this.message = 'Template saved.';
                } else {
                    const created = await window.AdminApi.createTemplate(body);
                    this.form.id = created.id || created.template?.id || null;
                    this.message = 'Template created.';
                    if (this.form.id) {
                        window.history.replaceState({}, '', '/admin/lab-templates/' + this.form.id + '/edit');
                        this.templateId = this.form.id;
                    }
                }
            } catch (e) {
                this.error = e.message || 'Save failed.';
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endsection
