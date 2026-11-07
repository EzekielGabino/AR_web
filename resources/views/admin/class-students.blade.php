@extends('layouts.admin')

@section('title', 'Class students — Admin')

@section('content')
<div class="min-h-screen bg-slate-50 text-[#091540]" x-data="adminClassStudents()" x-init="init()">
    @include('admin.partials.topbar')
    <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6">
        <button type="button" @click="history.back()" class="text-xs font-bold text-[#1B2CC1]">← Back</button>
        <div x-show="loading" class="rounded-2xl border bg-white p-6 text-xs text-slate-500">Loading…</div>
        <p x-show="error" x-text="error" class="text-xs font-bold text-rose-600"></p>
        <p x-show="message" x-text="message" class="text-xs font-bold text-emerald-600"></p>

        <template x-if="!loading && classInfo">
            <div class="space-y-6">
                <div class="flex flex-wrap items-start justify-between gap-3 rounded-3xl border border-[#ABD2FA]/50 bg-white p-6 shadow-sm">
                    <div>
                        <h1 class="text-xl font-extrabold" x-text="classInfo.section_name || 'Class'"></h1>
                        <p class="text-sm text-slate-500">
                            <span x-text="classInfo.grade_level || '—'"></span>
                            · Code <span class="font-bold" x-text="classInfo.access_code || '—'"></span>
                        </p>
                    </div>
                    <button type="button" @click="openAdd()"
                        class="rounded-xl bg-[#1B2CC1] px-4 py-2 text-xs font-bold text-white shadow">+ Add student</button>
                </div>

                <div>
                    <h2 class="text-lg font-black">Students</h2>
                    <ul class="mt-3 space-y-2">
                        <template x-for="s in students" :key="s.user_id">
                            <li class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#ABD2FA]/40 bg-white px-4 py-3">
                                <div>
                                    <p class="text-sm font-black" x-text="s.display_name"></p>
                                    <p class="text-xs text-slate-500" x-text="s.email"></p>
                                    <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-[10px] font-bold capitalize"
                                        :class="(s.status || '').toLowerCase() === 'approved' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800'"
                                        x-text="s.status || '—'"></span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <select class="rounded-xl border border-[#ABD2FA] px-2 py-1.5 text-xs font-bold"
                                        :value="s.role || 'student'"
                                        @change="changeRole(s, $event.target.value)">
                                        <option value="student">student</option>
                                        <option value="teacher">teacher</option>
                                        <option value="educator">educator</option>
                                        <option value="admin">admin</option>
                                    </select>
                                    <button type="button" @click="removeFromClass(s)"
                                        class="rounded-xl border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-600">Remove</button>
                                    <button type="button" @click="deleteAccount(s)"
                                        class="rounded-xl border border-rose-300 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700">Delete account</button>
                                </div>
                            </li>
                        </template>
                    </ul>
                    <p x-show="!students.length" class="mt-3 text-xs text-slate-500">No students in this class yet.</p>
                </div>
            </div>
        </template>
    </div>

    <div x-show="addOpen" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center bg-[#091540]/70 p-4 backdrop-blur-sm">
        <div class="absolute inset-0" @click="addOpen = false"></div>
        <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl" @click.stop>
            <h3 class="text-lg font-extrabold">Add student to class</h3>
            <p class="mt-1 text-xs text-slate-500">Students use Google sign-in. Enter the Google email of a student who has already opened the mobile app once.</p>
            <div class="mt-4 space-y-3">
                <label class="block text-xs font-bold">Student Google email
                    <input type="email" x-model="addForm.email" class="mt-1 w-full rounded-xl border border-[#ABD2FA] px-3 py-2 text-xs font-semibold" placeholder="student@gmail.com">
                </label>
                <p x-show="addError" x-text="addError" class="text-xs font-bold text-rose-600"></p>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="addOpen = false" class="rounded-xl border px-4 py-2 text-xs font-bold">Cancel</button>
                <button type="button" @click="submitAdd()" :disabled="adding"
                    class="rounded-xl bg-[#1B2CC1] px-4 py-2 text-xs font-bold text-white disabled:opacity-50"
                    x-text="adding ? 'Adding…' : 'Add to class'"></button>
            </div>
        </div>
    </div>
</div>
<script type="module" src="{{ asset('js/admin/admin.js') }}"></script>
<script>
function adminClassStudents() {
    return {
        classId: @js($classId),
        classInfo: null,
        students: [],
        loading: true,
        error: '',
        message: '',
        addOpen: false,
        adding: false,
        addError: '',
        addForm: { email: '' },
        async init() {
            while (!window.AdminApi) await new Promise(r => setTimeout(r, 40));
            await this.reload();
        },
        async reload() {
            this.loading = true; this.error = '';
            try {
                const data = await window.AdminApi.classStudents(this.classId);
                this.classInfo = data.class;
                this.students = data.students || [];
            } catch (e) {
                this.error = e.message || 'Failed to load class.';
            } finally {
                this.loading = false;
            }
        },
        openAdd() {
            this.addError = '';
            this.addForm = { email: '' };
            this.addOpen = true;
        },
        async submitAdd() {
            this.adding = true; this.addError = '';
            try {
                const data = await window.AdminApi.addClassStudent(this.classId, this.addForm);
                this.students = data.students || [];
                this.classInfo = data.class || this.classInfo;
                this.addOpen = false;
                this.message = 'Student added to class.';
            } catch (e) {
                this.addError = e.message || 'Could not add student.';
            } finally {
                this.adding = false;
            }
        },
        async changeRole(s, role) {
            if (!role) return;
            if (!confirm('Change account role for ' + (s.display_name || s.email) + ' to “' + role + '”?')) return;
            try {
                await window.AdminApi.updateUserRole(s.user_id, { role });
                this.message = 'Role updated.';
                await this.reload();
            } catch (e) {
                this.error = e.message || 'Role update failed.';
            }
        },
        async removeFromClass(s) {
            if (!confirm('Remove ' + (s.display_name || s.email) + ' from this class?')) return;
            try {
                await window.AdminApi.removeClassStudent(this.classId, s.user_id);
                this.message = 'Removed from class.';
                await this.reload();
            } catch (e) {
                this.error = e.message || 'Remove failed.';
            }
        },
        async deleteAccount(s) {
            if (!confirm('Permanently delete account for ' + (s.display_name || s.email) + '?')) return;
            try {
                await window.AdminApi.removeClassStudent(this.classId, s.user_id);
                await window.AdminApi.deleteUser(s.user_id);
                this.message = 'Account deleted.';
                await this.reload();
            } catch (e) {
                this.error = e.message || 'Delete failed.';
            }
        },
    };
}
</script>
@endsection
