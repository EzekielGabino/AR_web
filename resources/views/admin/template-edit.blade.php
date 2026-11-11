@extends('layouts.admin')

@section('title', ($templateId ? 'Edit' : 'New') . ' template — Admin')

@section('content')
<div class="min-h-screen bg-slate-50 text-[#091540]" x-data="adminTemplateEdit()" x-init="init()">
    @include('admin.partials.topbar')

    <div class="mx-auto max-w-3xl space-y-4 px-4 py-8 sm:px-6">
        <a href="{{ route('admin.dashboard') }}?tab=templates" class="text-xs font-bold text-[#1B2CC1]">← Lab templates</a>

        <div class="rounded-3xl border border-[#ABD2FA]/50 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-[#ABD2FA]/40 bg-gradient-to-r from-[#ABD2FA]/30 to-white px-5 py-4">
                <h1 class="text-lg font-extrabold" x-text="form.id ? 'Edit lab template' : 'New lab template'"></h1>
                <p class="text-xs text-slate-500">Same structure teachers use when building a lab — saved to global templates.</p>
            </div>

            <div class="flex flex-wrap gap-1.5 border-b border-[#ABD2FA]/40 bg-[#ABD2FA]/10 px-4 py-3">
                <template x-for="sec in sections" :key="sec">
                    <button type="button" @click="section = sec"
                        class="rounded-xl px-3 py-1.5 text-xs font-bold capitalize"
                        :class="section === sec ? 'bg-[#1B2CC1] text-white' : 'bg-white border border-[#ABD2FA] text-[#091540]/70'"
                        x-text="sec"></button>
                </template>
            </div>

            <div class="space-y-4 p-5">
                <p x-show="loading" class="text-xs text-slate-500">Loading…</p>
                <p x-show="error" x-text="error" class="text-xs font-bold text-rose-600"></p>
                <p x-show="message" x-text="message" class="text-xs font-bold text-emerald-600"></p>

                {{-- Overview --}}
                <div x-show="section === 'overview'" class="space-y-3">
                    <label class="block text-xs font-bold">Title
                        <input type="text" x-model="form.title" class="mt-1 w-full rounded-xl border border-[#ABD2FA] px-3 py-2 text-xs font-semibold">
                    </label>
                    <label class="block text-xs font-bold">Category
                        <select x-model="form.labCategory" class="mt-1 w-full rounded-xl border border-[#ABD2FA] px-3 py-2 text-xs font-semibold">
                            <option value="">—</option>
                            <option>Acid-Base</option>
                            <option>Gas Evolution</option>
                            <option>Carbonate</option>
                            <option>Concentration</option>
                            <option>Diffusion</option>
                            <option>Endothermic</option>
                            <option>Exothermic</option>
                            <option>Precipitation</option>
                        </select>
                    </label>
                    <label class="block text-xs font-bold">Description
                        <textarea x-model="form.description" rows="3" class="mt-1 w-full rounded-xl border border-[#ABD2FA] px-3 py-2 text-xs font-semibold"></textarea>
                    </label>
                    <label class="block text-xs font-bold">Safety tips
                        <textarea x-model="form.safetyTips" rows="2" class="mt-1 w-full rounded-xl border border-[#ABD2FA] px-3 py-2 text-xs font-semibold"></textarea>
                    </label>
                </div>

                {{-- Apparatus (same catalog as teacher lab editor) --}}
                <div x-show="section === 'apparatus'" class="space-y-3">
                    <div class="flex justify-between items-center">
                        <p class="text-xs font-bold text-[#1B2CC1]">Apparatus list</p>
                        <button type="button" @click="addApparatus()"
                            class="rounded-xl bg-[#ABD2FA]/30 px-3 py-1.5 text-xs font-bold text-[#1B2CC1]">+ Add</button>
                    </div>
                    <template x-for="(a, i) in form.apparatus" :key="i">
                        <div class="rounded-xl border border-[#ABD2FA]/50 p-3 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-[10px] font-bold text-slate-400" x-text="'#' + (i+1)"></span>
                                <button type="button" @click="form.apparatus.splice(i,1)" class="text-xs font-bold text-rose-500">Remove</button>
                            </div>
                            <label class="block text-[10px] font-bold text-slate-500">Type
                                <select class="mt-0.5 w-full rounded-lg border border-[#ABD2FA] px-2 py-1.5 text-xs font-semibold"
                                    :value="a.key"
                                    @change="onApparatusKeyChange(a, $event.target.value)">
                                    <template x-for="c in apparatusCatalog" :key="c.key">
                                        <option :value="c.key" x-text="c.displayName" :selected="a.key === c.key"></option>
                                    </template>
                                </select>
                            </label>
                            <p class="text-[11px] text-slate-500">Key: <span class="font-mono font-bold" x-text="a.key"></span></p>
                            <label class="block text-[10px] font-bold text-slate-500">Max volume
                                <input type="number" x-model.number="a.maxVol" min="0" class="mt-0.5 w-full rounded-lg border border-[#ABD2FA] px-2 py-1.5 text-xs font-semibold">
                            </label>
                        </div>
                    </template>
                </div>

                {{-- Elements --}}
                <div x-show="section === 'elements'" class="space-y-3">
                    <div class="flex justify-between items-center">
                        <p class="text-xs font-bold text-[#1B2CC1]">Elements / substances</p>
                        <button type="button" @click="form.elements.push({ name: '', substanceName: '', formula: '', type: 'liquid', role: 'Neutral', volume: 10, minVol: 2, maxVol: 50 })"
                            class="rounded-xl bg-[#ABD2FA]/30 px-3 py-1.5 text-xs font-bold text-[#1B2CC1]">+ Add</button>
                    </div>
                    <template x-for="(el, i) in form.elements" :key="i">
                        <div class="rounded-xl border border-[#ABD2FA]/50 p-3 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-[10px] font-bold text-slate-400" x-text="'#' + (i+1)"></span>
                                <button type="button" @click="form.elements.splice(i,1)" class="text-xs font-bold text-rose-500">Remove</button>
                            </div>
                            <input type="text" x-model="el.name" placeholder="Substance name" class="w-full rounded-lg border border-[#ABD2FA] px-2 py-1.5 text-xs font-semibold">
                            <input type="text" x-model="el.formula" placeholder="Formula (e.g. H2O)" class="w-full rounded-lg border border-[#ABD2FA] px-2 py-1.5 text-xs font-semibold">
                            <div class="grid grid-cols-2 gap-2">
                                <select x-model="el.type" class="rounded-lg border border-[#ABD2FA] px-2 py-1.5 text-xs font-semibold">
                                    <option value="liquid">liquid</option>
                                    <option value="granule">granule</option>
                                    <option value="solid">solid</option>
                                </select>
                                <select x-model="el.role" class="rounded-lg border border-[#ABD2FA] px-2 py-1.5 text-xs font-semibold">
                                    <option>Neutral</option>
                                    <option>Acid</option>
                                    <option>Base</option>
                                    <option>Salt</option>
                                    <option>Indicator</option>
                                    <option>Carbonate</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <label class="text-[10px] font-bold">Default vol<input type="number" x-model.number="el.volume" min="0" class="mt-0.5 w-full rounded-lg border border-[#ABD2FA] px-2 py-1 text-xs"></label>
                                <label class="text-[10px] font-bold">Min<input type="number" x-model.number="el.minVol" min="0" class="mt-0.5 w-full rounded-lg border border-[#ABD2FA] px-2 py-1 text-xs"></label>
                                <label class="text-[10px] font-bold">Max<input type="number" x-model.number="el.maxVol" min="0" class="mt-0.5 w-full rounded-lg border border-[#ABD2FA] px-2 py-1 text-xs"></label>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Steps --}}
                <div x-show="section === 'steps'" class="space-y-3">
                    <div class="flex justify-between items-center">
                        <p class="text-xs font-bold text-[#1B2CC1]">Steps (aim for ≥ 3)</p>
                        <button type="button" @click="form.steps.push({ order: form.steps.length+1, action: 'PlaceApparatus', instruction: '', targetApparatus: '', elementFormula: '', volume: null, waitSeconds: null })"
                            class="rounded-xl bg-[#ABD2FA]/30 px-3 py-1.5 text-xs font-bold text-[#1B2CC1]">+ Add</button>
                    </div>
                    <template x-for="(st, i) in form.steps" :key="i">
                        <div class="rounded-xl border border-[#ABD2FA]/50 p-3 space-y-2">
                            <div class="flex justify-between items-center gap-2">
                                <span class="text-[10px] font-bold text-slate-400" x-text="'Step ' + (i+1)"></span>
                                <div class="flex gap-1">
                                    <button type="button" @click="moveStep(i,-1)" class="text-[10px] font-bold text-[#1B2CC1]">↑</button>
                                    <button type="button" @click="moveStep(i,1)" class="text-[10px] font-bold text-[#1B2CC1]">↓</button>
                                    <button type="button" @click="form.steps.splice(i,1); renumberSteps()" class="text-xs font-bold text-rose-500">Remove</button>
                                </div>
                            </div>
                            <select x-model="st.action" class="w-full rounded-lg border border-[#ABD2FA] px-2 py-1.5 text-xs font-semibold">
                                <option>PlaceApparatus</option>
                                <option>AddElement</option>
                                <option>MixOrTransfer</option>
                                <option>InteractApparatus</option>
                                <option>Observe</option>
                                <option>PlaceInRack</option>
                                <option>Stir</option>
                                <option>Drain</option>
                                <option>Wait</option>
                            </select>
                            <textarea x-model="st.instruction" rows="2" placeholder="Student instruction" class="w-full rounded-lg border border-[#ABD2FA] px-2 py-1.5 text-xs font-semibold"></textarea>
                            <input type="text" x-model="st.targetApparatus" placeholder="Target apparatus key/name" class="w-full rounded-lg border border-[#ABD2FA] px-2 py-1.5 text-xs font-semibold">
                            <input type="text" x-model="st.elementFormula" placeholder="Element formula (if AddElement)" class="w-full rounded-lg border border-[#ABD2FA] px-2 py-1.5 text-xs font-semibold">
                        </div>
                    </template>
                </div>

                {{-- Quiz --}}
                <div x-show="section === 'quiz'" class="space-y-3">
                    <div class="flex justify-between items-center">
                        <p class="text-xs font-bold text-[#1B2CC1]">Quiz questions</p>
                        <button type="button" @click="form.quiz.push({ question: '', options: [{id:'A',text:''},{id:'B',text:''},{id:'C',text:''},{id:'D',text:''}], correctOptionId: 'A' })"
                            class="rounded-xl bg-[#ABD2FA]/30 px-3 py-1.5 text-xs font-bold text-[#1B2CC1]">+ Question</button>
                    </div>
                    <template x-for="(q, qi) in form.quiz" :key="qi">
                        <div class="rounded-xl border border-[#ABD2FA]/50 p-3 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-[10px] font-bold text-slate-400" x-text="'Q' + (qi+1)"></span>
                                <button type="button" @click="form.quiz.splice(qi,1)" class="text-xs font-bold text-rose-500">Remove</button>
                            </div>
                            <textarea x-model="q.question" rows="2" placeholder="Question text" class="w-full rounded-lg border border-[#ABD2FA] px-2 py-1.5 text-xs font-semibold"></textarea>
                            <template x-for="(opt, oi) in (q.options || [])" :key="oi">
                                <div class="flex items-center gap-2">
                                    <input type="radio" :name="'correct-'+qi" :value="opt.id" x-model="q.correctOptionId">
                                    <span class="text-xs font-bold w-4" x-text="opt.id"></span>
                                    <input type="text" x-model="opt.text" class="flex-1 rounded-lg border border-[#ABD2FA] px-2 py-1 text-xs font-semibold" placeholder="Option text">
                                </div>
                            </template>
                            <p class="text-[10px] text-slate-500">Selected radio = correct answer</p>
                        </div>
                    </template>
                </div>

                <div class="flex justify-end gap-2 border-t border-[#ABD2FA]/40 pt-4">
                    <a href="{{ route('admin.dashboard') }}?tab=templates" class="rounded-xl border px-4 py-2 text-xs font-bold">Cancel</a>
                    <button type="button" @click="save()" :disabled="saving"
                        class="rounded-xl bg-[#1B2CC1] px-5 py-2 text-xs font-bold text-white disabled:opacity-50"
                        x-text="saving ? 'Saving…' : 'Save template'"></button>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="module" src="{{ asset('js/admin/admin.js') }}"></script>
<script>
function adminTemplateEdit() {
    return {
        templateId: @js($templateId),
        sections: ['overview','apparatus','elements','steps','quiz'],
        section: 'overview',
        loading: false,
        saving: false,
        error: '',
        message: '',
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
            labCategory: '',
            description: '',
            safetyTips: '',
            apparatus: [],
            elements: [],
            steps: [],
            quiz: [],
        },
        async init() {
            while (!window.AdminApi) await new Promise(r => setTimeout(r, 40));
            if (!this.templateId) return;
            this.loading = true;
            try {
                const t = await window.AdminApi.showTemplate(this.templateId);
                this.form = {
                    id: t.id,
                    title: t.title || '',
                    labCategory: t.labCategory || '',
                    description: t.description || '',
                    safetyTips: t.safetyTips || '',
                    apparatus: Array.isArray(t.apparatus) ? t.apparatus.map((a) => this.normalizeApparatus(a)) : [],
                    elements: Array.isArray(t.elements) ? t.elements.map((e) => this.normalizeElement(e)) : [],
                    steps: Array.isArray(t.steps) ? t.steps : [],
                    quiz: Array.isArray(t.quiz) ? t.quiz : [],
                };
            } catch (e) {
                this.error = e.message || 'Load failed.';
            } finally {
                this.loading = false;
            }
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
            if (a.maxVol == null || a.maxVol === '') a.maxVol = cat.defaultMaxVol;
        },
        renumberSteps() {
            this.form.steps.forEach((s, i) => { s.order = i + 1; });
        },
        moveStep(i, dir) {
            const j = i + dir;
            if (j < 0 || j >= this.form.steps.length) return;
            const arr = this.form.steps;
            const tmp = arr[i]; arr[i] = arr[j]; arr[j] = tmp;
            this.renumberSteps();
        },
        async save() {
            this.saving = true; this.error = ''; this.message = '';
            try {
                if (!(this.form.title || '').trim()) throw new Error('Title is required.');
                this.renumberSteps();
                const elements = (this.form.elements || []).map((e) => {
                    const name = (e.name || e.substanceName || '').trim();
                    let formula = (e.formula || '').trim();
                    if (!formula && name) formula = name;
                    const minVol = Number(e.minVol ?? e.minVolume);
                    const maxVol = Number(e.maxVol ?? e.maxVolume);
                    const volume = Number(e.volume);
                    return {
                        ...e,
                        name,
                        substanceName: e.substanceName || name,
                        formula,
                        minVol: Number.isFinite(minVol) ? minVol : 2,
                        maxVol: Number.isFinite(maxVol) ? maxVol : 50,
                        volume: Number.isFinite(volume) ? volume : 10,
                        // keep aliases so older readers still work
                        minVolume: Number.isFinite(minVol) ? minVol : 2,
                        maxVolume: Number.isFinite(maxVol) ? maxVol : 50,
                    };
                });
                const steps = (this.form.steps || []).map((s) => {
                    let elementFormula = (s.elementFormula || '').trim();
                    if (!elementFormula && (s.action || '') === 'AddElement') {
                        const hit = elements.find((e) => (e.formula || e.name || '').trim());
                        if (hit) elementFormula = (hit.formula || hit.name || '').trim();
                    }
                    return { ...s, elementFormula };
                });
                const body = {
                    title: this.form.title.trim(),
                    labCategory: this.form.labCategory || '',
                    description: this.form.description || '',
                    safetyTips: this.form.safetyTips || '',
                    apparatus: this.form.apparatus || [],
                    elements,
                    steps,
                    quiz: this.form.quiz || [],
                };
                if (this.form.id) {
                    await window.AdminApi.updateTemplate(this.form.id, body);
                    this.message = 'Template updated.';
                } else {
                    const created = await window.AdminApi.createTemplate(body);
                    this.form.id = created.id;
                    this.message = 'Template created.';
                    if (created.id) {
                        window.history.replaceState({}, '', '/admin/lab-templates/' + created.id + '/edit');
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
