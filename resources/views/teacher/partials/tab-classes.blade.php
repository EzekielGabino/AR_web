<div class="space-y-6"
    x-data="{
        classes: [],
        loading: true,
        error: '',
        modal: null, // 'create' | 'edit' | 'delete' | null
        saving: false,
        formError: '',
        active: null,
        form: {
            section_name: '',
            grade_level: 'Grade 8',
            subject: '',
            access_code: '',
        },
        async init() {
            await this.load();
        },
        async load() {
            this.loading = true;
            this.error = '';
            try {
                await this.waitForModule();
                this.classes = await window.TeacherClasses.fetchTeacherClasses();
            } catch (e) {
                console.error(e);
                this.error = e.message || 'Failed to load classes.';
            } finally {
                this.loading = false;
            }
        },
        waitForModule() {
            if (window.TeacherClasses) return Promise.resolve();
            return new Promise((resolve, reject) => {
                const t = setTimeout(() => reject(new Error('Classes module failed to load')), 8000);
                const check = () => {
                    if (window.TeacherClasses) {
                        clearTimeout(t);
                        resolve();
                    } else setTimeout(check, 50);
                };
                check();
            });
        },
        openCreate() {
            this.formError = '';
            this.active = null;
            this.form = {
                section_name: '',
                grade_level: 'Grade 8',
                subject: '',
                access_code: window.TeacherClasses.generateAccessCode(),
            };
            this.modal = 'create';
        },
        openEdit(c) {
            this.formError = '';
            this.active = c;
            this.form = {
                section_name: c.section_name || '',
                grade_level: c.grade_level || 'Grade 8',
                subject: c.subject || '',
                access_code: c.access_code || '',
            };
            this.modal = 'edit';
        },
        openDelete(c) {
            this.formError = '';
            this.active = c;
            this.modal = 'delete';
        },
        closeModal() {
            this.modal = null;
            this.active = null;
            this.formError = '';
            this.saving = false;
        },
        async submitCreate() {
            this.saving = true;
            this.formError = '';
            try {
                const created = await window.TeacherClasses.createClass(this.form);
                this.classes = [created, ...this.classes];
                this.closeModal();
            } catch (e) {
                console.error(e);
                this.formError = e.message || 'Failed to create class.';
            } finally {
                this.saving = false;
            }
        },
        async submitEdit() {
            this.saving = true;
            this.formError = '';
            try {
                const updated = await window.TeacherClasses.updateClass(
                    this.active.id,
                    this.form,
                    this.active
                );
                this.classes = this.classes.map((c) =>
                    c.id === updated.id ? updated : c
                );
                this.closeModal();
            } catch (e) {
                console.error(e);
                this.formError = e.message || 'Failed to update class.';
            } finally {
                this.saving = false;
            }
        },
        async submitDelete() {
            this.saving = true;
            this.formError = '';
            try {
                const id = this.active.id;
                await window.TeacherClasses.deleteClass(id, this.active);
                this.classes = this.classes.filter((c) => c.id !== id);
                this.closeModal();
            } catch (e) {
                console.error(e);
                this.formError = e.message || 'Failed to delete class.';
            } finally {
                this.saving = false;
            }
        },
        studentCount(c) {
            return Array.isArray(c.students) ? c.students.length : 0;
        },
        classroomUrl(id) {
            return @js(url('/classes')) + '/' + id;
        },
     }">

    {{-- Header Section --}}
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#ABD2FA]/40 pb-5">
        <div>
            <h2 class="text-xl font-extrabold text-[#091540] tracking-tight">Classes</h2>
            <p class="mt-0.5 text-xs font-semibold text-[#091540]/60">Create, edit, or manage class sections and student access.</p>
        </div>
        <button type="button"
            @click="openCreate()"
            class="cursor-pointer rounded-2xl bg-[#1B2CC1] px-5 py-3 text-xs font-bold text-white shadow-md shadow-[#1B2CC1]/20 transition-all hover:bg-[#1B2CC1]/90 hover:shadow-lg hover:shadow-[#1B2CC1]/30 active:scale-[0.98]">
            + Create class
        </button>
    </div>

    {{-- Loading State --}}
    <div x-show="loading" class="rounded-3xl border border-[#ABD2FA]/40 bg-white/95 p-12 text-center text-xs font-bold text-[#091540]/60 shadow-sm backdrop-blur-sm">
        Loading classes…
    </div>

    {{-- Error State --}}
    <div x-show="!loading && error" class="rounded-2xl border border-rose-500/30 bg-rose-50/80 p-4 text-xs font-bold text-rose-600" x-text="error"></div>

    {{-- Empty State --}}
    <div x-show="!loading && !error && classes.length === 0"
        class="rounded-3xl border border-dashed border-[#ABD2FA]/80 bg-white/80 p-12 text-center backdrop-blur-sm">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#ABD2FA]/20 text-[#1B2CC1] mb-3">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
            </svg>
        </div>
        <p class="text-sm font-extrabold text-[#091540]">No classes yet</p>
        <p class="mt-1 text-xs font-medium text-[#091540]/60">Create a section so students can join with an access code.</p>
        <button type="button" @click="openCreate()" class="mt-4 cursor-pointer rounded-xl bg-[#ABD2FA]/30 px-4 py-2.5 text-xs font-bold text-[#1B2CC1] transition-all hover:bg-[#ABD2FA]/50">
            Create your first class
        </button>
    </div>

    {{-- Classes Grid --}}
    <div x-show="!loading && classes.length > 0" x-cloak class="grid gap-4 sm:grid-cols-2">
        <template x-for="c in classes" :key="c.id">
            <div class="group relative overflow-hidden rounded-3xl border border-[#ABD2FA]/60 bg-white/95 p-6 shadow-sm shadow-[#1B2CC1]/5 backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#7692FF] hover:shadow-xl hover:shadow-[#1B2CC1]/10">
                {{-- Top Accent Line on Hover --}}
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#1B2CC1] to-[#7692FF] opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>

                <div class="flex items-start justify-between gap-3">
                    <a :href="classroomUrl(c.id)" class="min-w-0 flex-1">
                        <h3 class="text-base font-extrabold text-[#091540] transition-colors group-hover:text-[#1B2CC1]" x-text="c.section_name"></h3>
                    </a>
                    <span class="shrink-0 rounded-full bg-[#ABD2FA]/20 px-3 py-1 text-[10px] font-bold tracking-wide uppercase text-[#1B2CC1] ring-1 ring-inset ring-[#7692FF]/30"
                        x-text="c.grade_level || '—'"></span>
                </div>

                <p class="mt-1.5 text-xs font-medium text-[#091540]/60" x-text="c.subject || 'No subject'"></p>

                <div class="mt-4 flex items-center justify-between rounded-2xl bg-slate-50/80 px-4 py-3 border border-[#ABD2FA]/30 text-[11px] font-bold text-[#091540]/80">
                    <span>
                        Code:
                        <span class="font-mono font-extrabold tracking-wider text-[#1B2CC1]" x-text="c.access_code"></span>
                    </span>
                    <span class="text-[#091540]/70" x-text="studentCount(c) + ' students'"></span>
                </div>

                <div class="mt-5 flex flex-wrap gap-2.5 border-t border-[#ABD2FA]/30 pt-4">
                    <a :href="classroomUrl(c.id)"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-[#1B2CC1] px-4 py-2 text-[11px] font-bold text-white shadow-xs transition-all hover:bg-[#1B2CC1]/90">
                        Open
                    </a>
                    <button type="button"
                        @click="openEdit(c)"
                        class="cursor-pointer rounded-xl border-[1.5px] border-[#ABD2FA]/80 bg-white px-4 py-2 text-[11px] font-bold text-[#091540] transition-all hover:border-[#7692FF] hover:bg-[#ABD2FA]/10">
                        Edit
                    </button>
                    <button type="button"
                        @click="openDelete(c)"
                        class="cursor-pointer rounded-xl border border-rose-200 bg-rose-50/60 px-4 py-2 text-[11px] font-bold text-rose-600 transition-all hover:bg-rose-100">
                        Delete
                    </button>
                </div>
            </div>
        </template>
    </div>

    {{-- Create / Edit Modal --}}
    <div x-show="modal === 'create' || modal === 'edit'" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" role="dialog" aria-modal="true">
        <div class="absolute inset-0" @click="closeModal()"></div>
        <div class="relative w-full max-w-md rounded-3xl border border-[#ABD2FA]/60 bg-white p-6 sm:p-8 shadow-2xl shadow-[#091540]/20">
            <h3 class="text-lg font-extrabold text-[#091540]"
                x-text="modal === 'edit' ? 'Edit class' : 'Create class'"></h3>
            <p class="mt-1 text-xs font-medium text-[#091540]/60">Configure your class section details.</p>

            <div class="mt-6 space-y-4">
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#091540]/70">Section name</label>
                    <input type="text" x-model="form.section_name" required
                        placeholder="e.g. STE 8 — Einstein"
                        class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA]/80 bg-slate-50/50 px-4 py-3 text-xs font-bold text-[#091540] placeholder:text-slate-400 focus:border-[#1B2CC1] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20 transition-all">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#091540]/70">Grade level</label>
                        <select x-model="form.grade_level"
                            class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA]/80 bg-slate-50/50 px-4 py-3 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20 transition-all">
                            <option>Grade 7</option>
                            <option>Grade 8</option>
                            <option>Grade 9</option>
                            <option>Grade 10</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#091540]/70">Access code</label>
                        <input type="text" x-model="form.access_code"
                            class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA]/80 bg-slate-50/50 px-4 py-3 font-mono text-xs font-bold uppercase tracking-wider text-[#1B2CC1] focus:border-[#1B2CC1] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20 transition-all">
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#091540]/70">Subject (optional)</label>
                    <input type="text" x-model="form.subject" placeholder="Science / Chemistry"
                        class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA]/80 bg-slate-50/50 px-4 py-3 text-xs font-bold text-[#091540] placeholder:text-slate-400 focus:border-[#1B2CC1] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20 transition-all">
                </div>

                <p x-show="formError" x-text="formError" class="rounded-xl bg-rose-50 p-3 text-xs font-bold text-rose-600 border border-rose-200"></p>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-[#ABD2FA]/30 pt-4">
                <button type="button" @click="closeModal()"
                    class="cursor-pointer rounded-2xl border-[1.5px] border-[#ABD2FA]/80 bg-white px-5 py-3 text-xs font-bold text-[#091540] transition-all hover:bg-[#ABD2FA]/10">
                    Cancel
                </button>
                <button type="button"
                    @click="modal === 'edit' ? submitEdit() : submitCreate()"
                    :disabled="saving || !form.section_name.trim()"
                    class="cursor-pointer rounded-2xl bg-[#1B2CC1] px-6 py-3 text-xs font-bold text-white shadow-md shadow-[#1B2CC1]/20 transition-all hover:bg-[#1B2CC1]/90 disabled:opacity-50">
                    <span x-text="saving ? 'Saving…' : (modal === 'edit' ? 'Save changes' : 'Create')"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Delete Confirm Modal --}}
    <div x-show="modal === 'delete'" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" role="dialog" aria-modal="true">
        <div class="absolute inset-0" @click="closeModal()"></div>
        <div class="relative w-full max-w-sm rounded-3xl border border-rose-200 bg-white p-6 sm:p-8 shadow-2xl shadow-rose-950/10">
            <h3 class="text-lg font-extrabold text-[#091540]">Delete class?</h3>
            <p class="mt-2 text-xs font-medium text-[#091540]/70 leading-relaxed">
                This removes
                <span class="font-bold text-[#091540]" x-text="active?.section_name"></span>
                from Firestore. Students will no longer be able to join with its access code.
            </p>

            <p x-show="formError" x-text="formError" class="mt-3 rounded-xl bg-rose-50 p-3 text-xs font-bold text-rose-600 border border-rose-200"></p>

            <div class="mt-6 flex justify-end gap-3 border-t border-[#ABD2FA]/30 pt-4">
                <button type="button" @click="closeModal()"
                    class="cursor-pointer rounded-2xl border-[1.5px] border-[#ABD2FA]/80 bg-white px-5 py-3 text-xs font-bold text-[#091540] transition-all hover:bg-[#ABD2FA]/10">
                    Cancel
                </button>
                <button type="button" @click="submitDelete()" :disabled="saving"
                    class="cursor-pointer rounded-2xl bg-rose-600 px-6 py-3 text-xs font-bold text-white shadow-md shadow-rose-600/20 transition-all hover:bg-rose-700 disabled:opacity-50">
                    <span x-text="saving ? 'Deleting…' : 'Delete'"></span>
                </button>
            </div>
        </div>
    </div>
</div>