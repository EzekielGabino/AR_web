@extends('layouts.admin')

@section('title', 'Teacher - Admin')

@section('content')
<div class="min-h-screen bg-slate-50 text-[#091540]" x-data="adminTeacher(@js($teacherId))" x-init="init()">
    @include('admin.partials.topbar')
    <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6">
        <a href="{{ route('admin.dashboard') }}?tab=teachers" class="text-xs font-bold text-[#1B2CC1]">← Teachers</a>
        <div x-show="loading" class="rounded-2xl border bg-white p-6 text-xs text-slate-500">Loading…</div>
        <p x-show="error" x-text="error" class="text-xs font-bold text-rose-600"></p>
        <p x-show="message" x-text="message" class="text-xs font-bold text-emerald-600"></p>
        <template x-if="!loading && teacher">
            <div class="space-y-6">
                <div class="rounded-3xl border border-[#ABD2FA]/50 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h1 class="text-xl font-extrabold" x-text="teacher.display_name"></h1>
                            <p class="text-sm text-slate-500" x-text="teacher.email"></p>
                        </div>
                        <button type="button" @click="openEmailModal()"
                            class="rounded-xl border border-[#ABD2FA] px-3 py-1.5 text-xs font-bold text-[#1B2CC1]">
                            Edit email
                        </button>
                    </div>
                    <p class="mt-2 text-[11px] text-slate-500">
                        Set this to the Google account email the teacher will use to sign in on the web.
                    </p>
                </div>
                <div>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-lg font-black">Classes</h2>
                        <button type="button" @click="openClassModal()"
                            class="rounded-xl bg-[#1B2CC1] px-4 py-2 text-xs font-bold text-white shadow">+ Add class</button>
                    </div>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <template x-for="c in classes" :key="c.id">
                            <div class="rounded-2xl border border-[#ABD2FA]/50 bg-white p-4 shadow-sm">
                                <p class="text-sm font-black" x-text="c.section_name || 'Untitled'"></p>
                                <p class="text-xs text-slate-500">
                                    <span x-text="c.grade_level || '-'"></span>
                                    <span x-show="c.academic_year"> · <span x-text="c.academic_year"></span></span>
                                </p>
                                <p class="mt-1 font-mono text-[11px] font-bold text-[#1B2CC1]" x-text="'Code: ' + (c.access_code || '-')"></p>
                                <p class="mt-1 text-[11px] font-bold text-slate-500" x-text="(c.student_count || 0) + ' students'"></p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <a :href="'{{ url('/admin/classes') }}/' + c.id + '/students'"
                                        class="rounded-xl border border-[#ABD2FA] px-3 py-1.5 text-xs font-bold text-[#1B2CC1]">Students</a>
                                    <button type="button" @click="openClassModal(c)"
                                        class="rounded-xl border border-[#ABD2FA] px-3 py-1.5 text-xs font-bold text-[#091540]">Edit</button>
                                    <button type="button" @click="removeClass(c)"
                                        class="rounded-xl border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-600">Delete</button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <p x-show="!classes.length" class="mt-3 text-xs text-slate-500">No classes for this teacher.</p>
                </div>
            </div>
        </template>
    </div>

    <div x-show="classModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div class="absolute inset-0" @click="classModal = false"></div>
        <div class="relative w-full max-w-md rounded-3xl border border-[#ABD2FA]/60 bg-white p-6 shadow-2xl">
            <h3 class="text-lg font-extrabold" x-text="classForm.id ? 'Edit class' : 'Add class'"></h3>
            <div class="mt-4 space-y-3">
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-[#091540]/70">Section name</label>
                    <input type="text" x-model="classForm.section_name" class="w-full rounded-xl border border-[#ABD2FA] px-3 py-2 text-xs font-bold">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-[#091540]/70">Grade level</label>
                        <select x-model="classForm.grade_level" class="w-full rounded-xl border border-[#ABD2FA] px-3 py-2 text-xs font-bold">
                            <option>Grade 7</option>
                            <option>Grade 8</option>
                            <option>Grade 9</option>
                            <option>Grade 10</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-[#091540]/70">Academic year</label>
                        <select x-model="classForm.academic_year"
                            class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold">
                            <option value="" disabled>Select school year</option>
                            <template x-for="y in academicYearOptions()" :key="y">
                                <option :value="y" x-text="y"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-[#091540]/70">Access code</label>
                    <input type="text" x-model="classForm.access_code" class="w-full rounded-xl border border-[#ABD2FA] px-3 py-2 font-mono text-xs font-bold uppercase">
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-[#091540]/70">Subject (optional)</label>
                    <input type="text" x-model="classForm.subject" class="w-full rounded-xl border border-[#ABD2FA] px-3 py-2 text-xs font-bold">
                </div>
            </div>
            <p x-show="classError" x-text="classError" class="mt-3 text-xs font-bold text-rose-600"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="classModal = false" class="rounded-xl border border-[#ABD2FA] px-4 py-2 text-xs font-bold">Cancel</button>
                <button type="button" @click="saveClass()" :disabled="classSaving || !(classForm.section_name || '').trim()"
                    class="rounded-xl bg-[#1B2CC1] px-4 py-2 text-xs font-bold text-white disabled:opacity-50">
                    <span x-text="classSaving ? 'Saving…' : 'Save'"></span>
                </button>
            </div>
        </div>
    </div>

    <div x-show="emailModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div class="absolute inset-0" @click="emailModal = false"></div>
        <div class="relative w-full max-w-md rounded-3xl border border-[#ABD2FA]/60 bg-white p-6 shadow-2xl">
            <h3 class="text-lg font-extrabold">Edit teacher email</h3>
            <p class="mt-1 text-xs text-slate-500">
                Updates Supabase Auth and the profile. The teacher should sign in with Google using this email.
            </p>
            <div class="mt-4">
                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-[#091540]/70">Email</label>
                <input type="email" x-model="emailForm.email"
                    class="w-full rounded-xl border border-[#ABD2FA] px-3 py-2 text-xs font-bold">
            </div>
            <p x-show="emailError" x-text="emailError" class="mt-3 text-xs font-bold text-rose-600"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="emailModal = false"
                    class="rounded-xl border border-[#ABD2FA] px-4 py-2 text-xs font-bold">Cancel</button>
                <button type="button" @click="saveEmail()" :disabled="emailSaving || !(emailForm.email || '').trim()"
                    class="rounded-xl bg-[#1B2CC1] px-4 py-2 text-xs font-bold text-white disabled:opacity-50">
                    <span x-text="emailSaving ? 'Saving…' : 'Save email'"></span>
                </button>
            </div>
        </div>
    </div>

</div>

<script type="module" src="{{ asset('js/admin/admin.js') }}"></script>
<script>
window.adminTeacher = function (teacherId) {
    return {
        teacherId: teacherId,
        loading: true,
        error: '',
        message: '',
        teacher: null,
        classes: [],
        classModal: false,
        classSaving: false,
        classError: '',
        emailModal: false,
        emailSaving: false,
        emailError: '',
        emailForm: { email: '' },
        classForm: {
            id: null,
            section_name: '',
            grade_level: 'Grade 8',
            academic_year: '',
            subject: '',
            access_code: '',
        },
        academicYearOptions() {
            const now = new Date();
            const y = now.getFullYear();
            const m = now.getMonth();
            const latestStart = m >= 5 ? y : y - 1;
            const years = [];
            for (let start = 2015; start <= latestStart; start++) {
                years.push(start + '-' + (start + 1));
            }
            return years.reverse();
        },
        defaultAcademicYear() {
            const opts = this.academicYearOptions();
            return opts.length ? opts[0] : '';
        },
        async init() {
            this.loading = true;
            this.error = '';
            try {
                let n = 0;
                while (!window.AdminApi && n < 100) {
                    await new Promise((r) => setTimeout(r, 40));
                    n++;
                }
                if (!window.AdminApi) throw new Error('Admin module failed to load');
                await this.reload();
            } catch (e) {
                this.error = e.message || 'Failed to load teacher.';
            } finally {
                this.loading = false;
            }
        },
        async reload() {
            const data = await window.AdminApi.showTeacher(this.teacherId);
            this.teacher = data.teacher || data;
            this.classes = data.classes || this.teacher.classes || [];
        },
        openClassModal(c) {
            this.classError = '';
            this.message = '';
            if (c && c.id) {
                this.classForm = {
                    id: c.id,
                    section_name: c.section_name || '',
                    grade_level: c.grade_level || 'Grade 8',
                    academic_year: c.academic_year || this.defaultAcademicYear(),
                    subject: c.subject || '',
                    access_code: c.access_code || '',
                };
            } else {
                this.classForm = {
                    id: null,
                    section_name: '',
                    grade_level: 'Grade 8',
                    academic_year: this.defaultAcademicYear(),
                    subject: '',
                    access_code: '',
                };
            }
            this.classModal = true;
        },
        async saveClass() {
            this.classSaving = true;
            this.classError = '';
            try {
                if (this.classForm.id) {
                    await window.AdminApi.updateClass(this.classForm.id, {
                        section_name: this.classForm.section_name,
                        grade_level: this.classForm.grade_level,
                        academic_year: this.classForm.academic_year,
                        subject: this.classForm.subject,
                        access_code: this.classForm.access_code,
                    });
                    this.message = 'Class updated.';
                } else {
                    await window.AdminApi.createClass({
                        teacher_id: this.teacherId,
                        section_name: this.classForm.section_name,
                        grade_level: this.classForm.grade_level,
                        academic_year: this.classForm.academic_year,
                        subject: this.classForm.subject,
                        access_code: this.classForm.access_code,
                    });
                    this.message = 'Class created.';
                }
                this.classModal = false;
                await this.reload();
            } catch (e) {
                this.classError = e.message || 'Save failed.';
            } finally {
                this.classSaving = false;
            }
        },
        openEmailModal() {
            this.emailError = '';
            this.message = '';
            this.emailForm = { email: (this.teacher && this.teacher.email) ? this.teacher.email : '' };
            this.emailModal = true;
        },
        async saveEmail() {
            this.emailSaving = true;
            this.emailError = '';
            try {
                const res = await window.AdminApi.updateUserEmail(this.teacherId, {
                    email: (this.emailForm.email || '').trim(),
                });
                this.message = res.message || 'Email updated.';
                this.emailModal = false;
                await this.reload();
            } catch (e) {
                this.emailError = e.message || 'Could not update email.';
            } finally {
                this.emailSaving = false;
            }
        },
        async removeClass(c) {
            if (!confirm('Delete class "' + (c.section_name || '') + '"? Students in this class will be unassigned.')) return;
            try {
                await window.AdminApi.deleteClass(c.id);
                this.message = 'Class deleted.';
                await this.reload();
            } catch (e) {
                this.error = e.message || 'Delete failed.';
            }
        },
    };
};
</script>
@endsection
