@extends('layouts.admin')

@section('title', 'Admin — SciLens AR')

@section('content')
<div class="min-h-screen bg-slate-50 text-[#091540]" x-data="adminDashboard()" x-init="init()">
    @include('admin.partials.topbar')

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6">
        <div class="rounded-3xl bg-gradient-to-r from-[#091540] via-[#1B2CC1] to-[#7692FF] p-6 text-white shadow-xl">
            <p class="text-xs font-bold uppercase tracking-wider text-[#ABD2FA]/90">Admin</p>
            <h1 class="mt-1 text-2xl font-extrabold">School oversight</h1>
            <p class="mt-1 text-sm text-[#ABD2FA]/90">Manage teachers, students, roles, and global lab templates.</p>
        </div>

        <nav class="flex flex-wrap gap-2">
            <template x-for="t in ['teachers','students','templates']" :key="t">
                <button type="button" @click="tab = t"
                    class="rounded-2xl px-4 py-2.5 text-xs font-bold capitalize transition"
                    :class="tab === t ? 'bg-[#1B2CC1] text-white shadow' : 'bg-white border border-[#ABD2FA] text-[#091540]/70'"
                    x-text="t"></button>
            </template>
        </nav>

        <p x-show="error" x-text="error" class="text-xs font-bold text-rose-600"></p>
        <p x-show="message" x-text="message" class="text-xs font-bold text-emerald-600"></p>

        {{-- Teachers --}}
        <div x-show="tab === 'teachers'" class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-black">Teachers</h2>
                <div class="flex gap-2">
                    <button type="button" @click="loadTeachers()" class="text-xs font-bold text-[#1B2CC1]">Refresh</button>
                    <button type="button" @click="openCreateUser('teacher')" class="rounded-xl bg-[#1B2CC1] px-4 py-2 text-xs font-bold text-white">+ Add teacher</button>
                </div>
            </div>
            <div x-show="loadingTeachers" class="rounded-2xl border bg-white p-6 text-center text-xs text-slate-500">Loading…</div>
            <div class="space-y-2">
                <template x-for="u in teachers" :key="u.id">
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#ABD2FA]/50 bg-white p-4">
                        <a :href="'{{ url('/admin/teachers') }}/' + u.id" class="min-w-0 hover:underline">
                            <p class="text-sm font-black" x-text="u.display_name"></p>
                            <p class="text-xs text-slate-500" x-text="u.email"></p>
                            <p class="text-[11px] font-bold text-[#1B2CC1]" x-text="(u.class_count || 0) + ' class(es)'"></p>
                        </a>
                        <div class="flex flex-wrap items-center gap-2">
                            <select class="rounded-xl border border-[#ABD2FA] px-2 py-1.5 text-xs font-bold"
                                :value="u.role"
                                @change="changeRole(u, $event.target.value)">
                                <option value="teacher">teacher</option>
                                <option value="educator">educator</option>
                                <option value="student">student</option>
                                <option value="admin">admin</option>
                            </select>
                            <button type="button" @click="removeUser(u)" class="rounded-xl border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-600">Delete</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Students --}}
        <div x-show="tab === 'students'" class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-black">Students</h2>
                <div class="flex gap-2">
                    <button type="button" @click="loadStudents()" class="text-xs font-bold text-[#1B2CC1]">Refresh</button>
                    <button type="button" @click="openCreateUser('student')" class="rounded-xl bg-[#1B2CC1] px-4 py-2 text-xs font-bold text-white">+ Add student</button>
                </div>
            </div>
            <div x-show="loadingStudents" class="rounded-2xl border bg-white p-6 text-center text-xs text-slate-500">Loading…</div>
            <div class="space-y-2">
                <template x-for="u in students" :key="u.id">
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#ABD2FA]/50 bg-white p-4">
                        <div class="min-w-0">
                            <p class="text-sm font-black" x-text="u.display_name"></p>
                            <p class="text-xs text-slate-500" x-text="u.email"></p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <select class="rounded-xl border border-[#ABD2FA] px-2 py-1.5 text-xs font-bold"
                                :value="u.role"
                                @change="changeRole(u, $event.target.value)">
                                <option value="student">student</option>
                                <option value="teacher">teacher</option>
                                <option value="educator">educator</option>
                                <option value="admin">admin</option>
                            </select>
                            <button type="button" @click="removeUser(u)" class="rounded-xl border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-600">Delete</button>
                        </div>
                    </div>
                </template>
            </div>
            <p x-show="!loadingStudents && !students.length" class="text-xs text-slate-500">No student profiles yet.</p>
        </div>

        {{-- Templates --}}
        <div x-show="tab === 'templates'" class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-black">Lab templates</h2>
                <div class="flex gap-2">
                    <button type="button" @click="loadTemplates()" class="text-xs font-bold text-[#1B2CC1]">Refresh</button>
                    <a href="{{ route('admin.templates.create') }}" class="rounded-xl bg-[#1B2CC1] px-4 py-2 text-xs font-bold text-white shadow">+ New template</a>
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
                                · <span x-text="(tpl.apparatus || []).length + ' apparatus'"></span>
                                · <span x-text="(tpl.elements || []).length + ' elements'"></span>
                                · <span x-text="(tpl.steps || []).length + ' steps'"></span>
                                · <span x-text="(tpl.quiz || []).length + ' quiz'"></span>
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <a :href="'{{ url('/admin/lab-templates') }}/' + tpl.id + '/edit'"
                                class="rounded-xl border border-[#ABD2FA] px-3 py-1.5 text-xs font-bold">Edit</a>
                            <button type="button" @click="confirmDeleteTemplate(tpl)"
                                class="rounded-xl border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-600">Delete</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Create user modal --}}
    <div x-show="userModal" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center bg-[#091540]/70 p-4 backdrop-blur-sm">
        <div class="absolute inset-0" @click="userModal = false"></div>
        <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl" @click.stop>
            <h3 class="text-lg font-extrabold" x-text="'Add ' + (userForm.role || 'user')"></h3>
            <div class="mt-4 space-y-3">
                <label class="block text-xs font-bold">Display name
                    <input type="text" x-model="userForm.display_name" class="mt-1 w-full rounded-xl border border-[#ABD2FA] px-3 py-2 text-xs font-semibold">
                </label>
                <label class="block text-xs font-bold">Email
                    <input type="email" x-model="userForm.email" class="mt-1 w-full rounded-xl border border-[#ABD2FA] px-3 py-2 text-xs font-semibold">
                </label>
                <label class="block text-xs font-bold">Password (min 6)
                    <input type="text" x-model="userForm.password" class="mt-1 w-full rounded-xl border border-[#ABD2FA] px-3 py-2 text-xs font-semibold">
                </label>
                <label class="block text-xs font-bold">Role
                    <select x-model="userForm.role" class="mt-1 w-full rounded-xl border border-[#ABD2FA] px-3 py-2 text-xs font-semibold">
                        <option value="teacher">teacher</option>
                        <option value="student">student</option>
                        <option value="educator">educator</option>
                        <option value="admin">admin</option>
                    </select>
                </label>
                <p x-show="userError" x-text="userError" class="text-xs font-bold text-rose-600"></p>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="userModal = false" class="rounded-xl border px-4 py-2 text-xs font-bold">Cancel</button>
                <button type="button" @click="saveUser()" :disabled="userSaving"
                    class="rounded-xl bg-[#1B2CC1] px-4 py-2 text-xs font-bold text-white disabled:opacity-50"
                    x-text="userSaving ? 'Creating…' : 'Create'"></button>
            </div>
        </div>
    </div>
</div>

<script type="module" src="{{ asset('js/admin/admin.js') }}"></script>
<script>
function adminDashboard() {
    const tabParam = new URLSearchParams(window.location.search).get('tab') || 'teachers';
    return {
        tab: ['teachers','students','templates'].includes(tabParam) ? tabParam : 'teachers',
        teachers: [],
        students: [],
        templates: [],
        loadingTeachers: false,
        loadingStudents: false,
        loadingTemplates: false,
        error: '',
        message: '',
        userModal: false,
        userSaving: false,
        userError: '',
        userForm: { email: '', password: '', display_name: '', role: 'teacher' },
        async init() {
            while (!window.AdminApi) await new Promise(r => setTimeout(r, 40));
            if (this.tab === 'students') await this.loadStudents();
            else if (this.tab === 'templates') await this.loadTemplates();
            else await this.loadTeachers();
            this.$watch('tab', (v) => {
                if (v === 'teachers' && !this.teachers.length) this.loadTeachers();
                if (v === 'students' && !this.students.length) this.loadStudents();
                if (v === 'templates' && !this.templates.length) this.loadTemplates();
            });
        },
        async loadTeachers() {
            this.loadingTeachers = true; this.error = '';
            try { this.teachers = await window.AdminApi.listTeachers(); }
            catch (e) { this.error = e.message; }
            finally { this.loadingTeachers = false; }
        },
        async loadStudents() {
            this.loadingStudents = true; this.error = '';
            try { this.students = await window.AdminApi.listStudents(); }
            catch (e) { this.error = e.message; }
            finally { this.loadingStudents = false; }
        },
        async loadTemplates() {
            this.loadingTemplates = true; this.error = '';
            try { this.templates = await window.AdminApi.listTemplates(); }
            catch (e) { this.error = e.message; }
            finally { this.loadingTemplates = false; }
        },
        openCreateUser(role) {
            this.userError = '';
            this.userForm = { email: '', password: '', display_name: '', role: role || 'teacher' };
            this.userModal = true;
        },
        async saveUser() {
            this.userSaving = true; this.userError = '';
            try {
                await window.AdminApi.createUser(this.userForm);
                this.message = 'User created.';
                this.userModal = false;
                if (this.userForm.role === 'student') await this.loadStudents();
                else await this.loadTeachers();
            } catch (e) {
                this.userError = e.message || 'Create failed.';
            } finally {
                this.userSaving = false;
            }
        },
        async changeRole(u, role) {
            if (!role || role === u.role) return;
            if (!confirm('Change role of ' + (u.display_name || u.email) + ' to “' + role + '”?')) {
                await this.loadTeachers(); await this.loadStudents();
                return;
            }
            try {
                await window.AdminApi.updateUserRole(u.id, { role });
                this.message = 'Role updated.';
                await this.loadTeachers();
                await this.loadStudents();
            } catch (e) {
                this.error = e.message || 'Role update failed.';
                await this.loadTeachers(); await this.loadStudents();
            }
        },
        async removeUser(u) {
            if (!confirm('Delete ' + (u.display_name || u.email) + '? This removes their profile and auth account.')) return;
            try {
                await window.AdminApi.deleteUser(u.id);
                this.message = 'User deleted.';
                await this.loadTeachers();
                await this.loadStudents();
            } catch (e) {
                this.error = e.message || 'Delete failed.';
            }
        },
        async confirmDeleteTemplate(tpl) {
            if (!confirm('Delete template “' + (tpl.title || '') + '”?')) return;
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
