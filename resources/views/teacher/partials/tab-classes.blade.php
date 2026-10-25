<div class="space-y-4"
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
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-black text-slate-800">Classes</h2>
            <p class="text-xs text-slate-500">Create, edit, or remove class sections.</p>
        </div>
        <button type="button"
                @click="openCreate()"
                class="rounded-xl bg-[#7CD5F3] px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-[#68cbed]">
            + Create class
        </button>
    </div>

    <div x-show="loading" class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-xs font-semibold text-slate-500">
        Loading classes…
    </div>

    <div x-show="!loading && error" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-600" x-text="error"></div>

    <div x-show="!loading && !error && classes.length === 0"
         class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center">
        <p class="text-sm font-bold text-slate-600">No classes yet</p>
        <p class="mt-1 text-xs text-slate-400">Create a section so students can join with an access code.</p>
        <button type="button" @click="openCreate()" class="mt-4 text-xs font-bold text-[#50aed0] hover:underline">
            Create your first class
        </button>
    </div>

    <div x-show="!loading && classes.length > 0" x-cloak class="grid gap-3 sm:grid-cols-2">
        <template x-for="c in classes" :key="c.id">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-[#7CD5F3]/60">
                <div class="flex items-start justify-between gap-2">
                    <a :href="classroomUrl(c.id)" class="min-w-0 flex-1">
                        <h3 class="text-base font-black text-slate-800 hover:text-[#50aed0]" x-text="c.section_name"></h3>
                    </a>
                    <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500"
                          x-text="c.grade_level || '—'"></span>
                </div>
                <p class="mt-1 text-xs text-slate-500" x-text="c.subject || 'No subject'"></p>
                <div class="mt-3 flex items-center justify-between text-[11px] font-bold text-slate-500">
                    <span>
                        Code:
                        <span class="font-mono tracking-wider text-slate-800" x-text="c.access_code"></span>
                    </span>
                    <span x-text="studentCount(c) + ' students'"></span>
                </div>
                <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-3">
                    <a :href="classroomUrl(c.id)"
                       class="rounded-lg bg-slate-100 px-3 py-1.5 text-[11px] font-bold text-slate-700 hover:bg-slate-200">
                        Open
                    </a>
                    <button type="button"
                            @click="openEdit(c)"
                            class="rounded-lg bg-[#7CD5F3]/15 px-3 py-1.5 text-[11px] font-bold text-[#439BBB] hover:bg-[#7CD5F3]/25">
                        Edit
                    </button>
                    <button type="button"
                            @click="openDelete(c)"
                            class="rounded-lg bg-rose-50 px-3 py-1.5 text-[11px] font-bold text-rose-600 hover:bg-rose-100">
                        Delete
                    </button>
                </div>
            </div>
        </template>
    </div>

    {{-- Create / Edit modal --}}
    <div x-show="modal === 'create' || modal === 'edit'" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-slate-900/40" @click="closeModal()"></div>
        <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
            <h3 class="text-lg font-black text-slate-800"
                x-text="modal === 'edit' ? 'Edit class' : 'Create class'"></h3>
            <p class="mt-1 text-xs text-slate-500">Changes are saved to Firestore.</p>

            <div class="mt-4 space-y-3">
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase text-slate-500">Section name</label>
                    <input type="text" x-model="form.section_name" required
                           placeholder="e.g. STE 8 — Einstein"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-semibold focus:border-[#7CD5F3] focus:outline-none focus:ring-2 focus:ring-[#7CD5F3]/20">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase text-slate-500">Grade level</label>
                        <select x-model="form.grade_level"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-semibold focus:border-[#7CD5F3] focus:outline-none">
                            <option>Grade 7</option>
                            <option>Grade 8</option>
                            <option>Grade 9</option>
                            <option>Grade 10</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase text-slate-500">Access code</label>
                        <input type="text" x-model="form.access_code"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 font-mono text-xs font-bold uppercase tracking-wider focus:border-[#7CD5F3] focus:outline-none focus:ring-2 focus:ring-[#7CD5F3]/20">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase text-slate-500">Subject (optional)</label>
                    <input type="text" x-model="form.subject" placeholder="Science / Chemistry"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-semibold focus:border-[#7CD5F3] focus:outline-none focus:ring-2 focus:ring-[#7CD5F3]/20">
                </div>
                <p x-show="formError" x-text="formError" class="text-xs font-bold text-red-500"></p>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="closeModal()"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button"
                        @click="modal === 'edit' ? submitEdit() : submitCreate()"
                        :disabled="saving || !form.section_name.trim()"
                        class="rounded-xl bg-[#7CD5F3] px-4 py-2 text-xs font-bold text-white hover:bg-[#68cbed] disabled:opacity-50">
                    <span x-text="saving ? 'Saving…' : (modal === 'edit' ? 'Save changes' : 'Create')"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Delete confirm --}}
    <div x-show="modal === 'delete'" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-slate-900/40" @click="closeModal()"></div>
        <div class="relative w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
            <h3 class="text-lg font-black text-slate-800">Delete class?</h3>
            <p class="mt-2 text-xs text-slate-500">
                This removes
                <span class="font-bold text-slate-800" x-text="active?.section_name"></span>
                from Firestore. Students will no longer be able to join with its access code.
            </p>
            <p x-show="formError" x-text="formError" class="mt-2 text-xs font-bold text-red-500"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="closeModal()"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button" @click="submitDelete()" :disabled="saving"
                        class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-700 disabled:opacity-50">
                    <span x-text="saving ? 'Deleting…' : 'Delete'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
