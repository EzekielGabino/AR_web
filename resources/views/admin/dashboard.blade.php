@extends('layouts.admin')

@section('title', 'Admin — SciLens AR')

@section('content')
<div class="min-h-screen bg-slate-50 text-[#091540]" x-data="adminDashboard()" x-init="init()">
    @include('admin.partials.topbar')

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6">
        <div class="rounded-3xl bg-gradient-to-r from-[#091540] via-[#1B2CC1] to-[#7692FF] p-6 text-white shadow-xl">
            <p class="text-xs font-bold uppercase tracking-wider text-[#ABD2FA]/90">Admin</p>
            <h1 class="mt-1 text-2xl font-extrabold">School oversight</h1>
            <p class="mt-1 text-sm text-[#ABD2FA]/90">Teachers, their classes, enrolled students, and global lab templates.</p>
        </div>

        <nav class="flex flex-wrap gap-2">
            <button type="button" @click="tab = 'teachers'"
                class="rounded-2xl px-4 py-2.5 text-xs font-bold transition"
                :class="tab === 'teachers' ? 'bg-[#1B2CC1] text-white shadow' : 'bg-white border border-[#ABD2FA] text-[#091540]/70'">
                Teachers
            </button>
            <button type="button" @click="tab = 'templates'"
                class="rounded-2xl px-4 py-2.5 text-xs font-bold transition"
                :class="tab === 'templates' ? 'bg-[#1B2CC1] text-white shadow' : 'bg-white border border-[#ABD2FA] text-[#091540]/70'">
                Lab templates
            </button>
        </nav>

        <p x-show="error" x-text="error" class="text-xs font-bold text-rose-600"></p>
        <p x-show="message" x-text="message" class="text-xs font-bold text-emerald-600"></p>

        {{-- Teachers --}}
        <div x-show="tab === 'teachers'" class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-black">Teachers</h2>
                <button type="button" @click="loadTeachers()" class="text-xs font-bold text-[#1B2CC1]">Refresh</button>
            </div>
            <div x-show="loadingTeachers" class="rounded-2xl border bg-white p-6 text-center text-xs text-slate-500">Loading…</div>
            <div x-show="!loadingTeachers && !teachers.length" class="rounded-2xl border bg-white p-6 text-center text-xs text-slate-500">No teacher profiles found.</div>
            <div class="grid gap-3 sm:grid-cols-2">
                <template x-for="t in teachers" :key="t.id">
                    <a :href="'{{ url('/admin/teachers') }}/' + t.id"
                        class="block rounded-2xl border border-[#ABD2FA]/50 bg-white p-4 shadow-sm transition hover:border-[#1B2CC1] hover:shadow-md">
                        <p class="text-sm font-black text-[#091540]" x-text="t.display_name"></p>
                        <p class="mt-0.5 text-xs text-slate-500" x-text="t.email"></p>
                        <p class="mt-2 text-[11px] font-bold text-[#1B2CC1]" x-text="(t.class_count || 0) + ' class(es)'"></p>
                    </a>
                </template>
            </div>
        </div>

        {{-- Templates --}}
        <div x-show="tab === 'templates'" class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-black">Lab templates</h2>
                <div class="flex gap-2">
                    <button type="button" @click="loadTemplates()" class="text-xs font-bold text-[#1B2CC1]">Refresh</button>
                    <button type="button" @click="openCreateTemplate()"
                        class="rounded-xl bg-[#1B2CC1] px-4 py-2 text-xs font-bold text-white shadow">+ New template</button>
                </div>
            </div>
            <div x-show="loadingTemplates" class="rounded-2xl border bg-white p-6 text-center text-xs text-slate-500">Loading…</div>
            <div class="space-y-2">
                <template x-for="tpl in templates" :key="tpl.id">
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#ABD2FA]/50 bg-white p-4">
                        <div class="min-w-0">
                            <p class="text-sm font-black" x-text="tpl.title"></p>
                            <p class="text-[11px] text-slate-500">
                                <span x-text="tpl.labCategory || 'Uncategorized'"></span>
                                · <span x-text="(tpl.steps || []).length + ' steps'"></span>
                                · <span x-text="(tpl.quiz || []).length + ' quiz'"></span>
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="openEditTemplate(tpl)"
                                class="rounded-xl border border-[#ABD2FA] px-3 py-1.5 text-xs font-bold">Edit</button>
                            <button type="button" @click="confirmDeleteTemplate(tpl)"
                                class="rounded-xl border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-600">Delete</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Template modal --}}
    <div x-show="tplModal" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center bg-[#091540]/70 p-4 backdrop-blur-sm">
        <div class="absolute inset-0" @click="tplModal = null"></div>
        <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-3xl border border-[#7692FF]/40 bg-white p-6 shadow-2xl" @click.stop>
            <h3 class="text-lg font-extrabold" x-text="tplForm.id ? 'Edit template' : 'New template'"></h3>
            <div class="mt-4 space-y-3">
                <label class="block text-xs font-bold">Title
                    <input type="text" x-model="tplForm.title" class="mt-1 w-full rounded-xl border border-[#ABD2FA] px-3 py-2 text-xs font-semibold">
                </label>
                <label class="block text-xs font-bold">Category
                    <input type="text" x-model="tplForm.labCategory" class="mt-1 w-full rounded-xl border border-[#ABD2FA] px-3 py-2 text-xs font-semibold" placeholder="e.g. Acid-Base">
                </label>
                <label class="block text-xs font-bold">Description
                    <textarea x-model="tplForm.description" rows="2" class="mt-1 w-full rounded-xl border border-[#ABD2FA] px-3 py-2 text-xs font-semibold"></textarea>
                </label>
                <label class="block text-xs font-bold">Safety tips
                    <textarea x-model="tplForm.safetyTips" rows="2" class="mt-1 w-full rounded-xl border border-[#ABD2FA] px-3 py-2 text-xs font-semibold"></textarea>
                </label>
                <label class="block text-xs font-bold">Apparatus (JSON array)
                    <textarea x-model="tplForm.apparatusJson" rows="3" class="mt-1 w-full rounded-xl border border-[#ABD2FA] px-3 py-2 font-mono text-[11px]"></textarea>
                </label>
                <label class="block text-xs font-bold">Elements (JSON array)
                    <textarea x-model="tplForm.elementsJson" rows="3" class="mt-1 w-full rounded-xl border border-[#ABD2FA] px-3 py-2 font-mono text-[11px]"></textarea>
                </label>
                <label class="block text-xs font-bold">Steps (JSON array)
                    <textarea x-model="tplForm.stepsJson" rows="3" class="mt-1 w-full rounded-xl border border-[#ABD2FA] px-3 py-2 font-mono text-[11px]"></textarea>
                </label>
                <label class="block text-xs font-bold">Quiz (JSON array)
                    <textarea x-model="tplForm.quizJson" rows="3" class="mt-1 w-full rounded-xl border border-[#ABD2FA] px-3 py-2 font-mono text-[11px]"></textarea>
                </label>
                <p x-show="tplError" x-text="tplError" class="text-xs font-bold text-rose-600"></p>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="tplModal = null" class="rounded-xl border px-4 py-2 text-xs font-bold">Cancel</button>
                <button type="button" @click="saveTemplate()" :disabled="tplSaving"
                    class="rounded-xl bg-[#1B2CC1] px-4 py-2 text-xs font-bold text-white disabled:opacity-50"
                    x-text="tplSaving ? 'Saving…' : 'Save'"></button>
            </div>
        </div>
    </div>
</div>

<script type="module" src="{{ asset('js/admin/admin.js') }}"></script>
<script>
function adminDashboard() {
    const tabParam = new URLSearchParams(window.location.search).get('tab') || 'teachers';
    return {
        tab: tabParam === 'templates' ? 'templates' : 'teachers',
        teachers: [],
        templates: [],
        loadingTeachers: false,
        loadingTemplates: false,
        error: '',
        message: '',
        tplModal: null,
        tplSaving: false,
        tplError: '',
        tplForm: {},
        async init() {
            await this.waitApi();
            if (this.tab === 'templates') await this.loadTemplates();
            else await this.loadTeachers();
            this.$watch('tab', (v) => {
                if (v === 'templates' && !this.templates.length) this.loadTemplates();
                if (v === 'teachers' && !this.teachers.length) this.loadTeachers();
            });
        },
        waitApi() {
            if (window.AdminApi) return Promise.resolve();
            return new Promise((resolve, reject) => {
                const t = setTimeout(() => reject(new Error('Admin API failed to load')), 8000);
                const c = () => { if (window.AdminApi) { clearTimeout(t); resolve(); } else setTimeout(c, 40); };
                c();
            });
        },
        async loadTeachers() {
            this.loadingTeachers = true; this.error = '';
            try { this.teachers = await window.AdminApi.listTeachers(); }
            catch (e) { this.error = e.message || 'Failed to load teachers.'; }
            finally { this.loadingTeachers = false; }
        },
        async loadTemplates() {
            this.loadingTemplates = true; this.error = '';
            try { this.templates = await window.AdminApi.listTemplates(); }
            catch (e) { this.error = e.message || 'Failed to load templates.'; }
            finally { this.loadingTemplates = false; }
        },
        openCreateTemplate() {
            this.tplError = '';
            this.tplForm = {
                id: null, title: '', labCategory: '', description: '', safetyTips: '',
                apparatusJson: '[]', elementsJson: '[]', stepsJson: '[]', quizJson: '[]',
            };
            this.tplModal = true;
        },
        openEditTemplate(tpl) {
            this.tplError = '';
            this.tplForm = {
                id: tpl.id,
                title: tpl.title || '',
                labCategory: tpl.labCategory || '',
                description: tpl.description || '',
                safetyTips: tpl.safetyTips || '',
                apparatusJson: JSON.stringify(tpl.apparatus || [], null, 2),
                elementsJson: JSON.stringify(tpl.elements || [], null, 2),
                stepsJson: JSON.stringify(tpl.steps || [], null, 2),
                quizJson: JSON.stringify(tpl.quiz || [], null, 2),
            };
            this.tplModal = true;
        },
        parseJsonField(raw, label) {
            try {
                const v = JSON.parse(raw || '[]');
                if (!Array.isArray(v)) throw new Error(label + ' must be a JSON array');
                return v;
            } catch (e) {
                throw new Error(label + ': ' + (e.message || 'invalid JSON'));
            }
        },
        async saveTemplate() {
            this.tplSaving = true; this.tplError = '';
            try {
                if (!(this.tplForm.title || '').trim()) throw new Error('Title is required.');
                const body = {
                    title: this.tplForm.title.trim(),
                    labCategory: this.tplForm.labCategory || '',
                    description: this.tplForm.description || '',
                    safetyTips: this.tplForm.safetyTips || '',
                    apparatus: this.parseJsonField(this.tplForm.apparatusJson, 'Apparatus'),
                    elements: this.parseJsonField(this.tplForm.elementsJson, 'Elements'),
                    steps: this.parseJsonField(this.tplForm.stepsJson, 'Steps'),
                    quiz: this.parseJsonField(this.tplForm.quizJson, 'Quiz'),
                };
                if (this.tplForm.id) {
                    await window.AdminApi.updateTemplate(this.tplForm.id, body);
                    this.message = 'Template updated.';
                } else {
                    await window.AdminApi.createTemplate(body);
                    this.message = 'Template created.';
                }
                this.tplModal = null;
                await this.loadTemplates();
            } catch (e) {
                this.tplError = e.message || 'Save failed.';
            } finally {
                this.tplSaving = false;
            }
        },
        async confirmDeleteTemplate(tpl) {
            if (!confirm('Delete template “' + (tpl.title || '') + '”? This cannot be undone.')) return;
            try {
                await window.AdminApi.deleteTemplate(tpl.id);
                this.message = 'Template deleted.';
                await this.loadTemplates();
            } catch (e) {
                this.error = e.message || 'Delete failed.';
            }
        },
    };
}
</script>
@endsection
