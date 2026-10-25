@extends('layouts.teacher')

@section('title', 'Activity — SciLens AR')

@section('content')
    @include('teacher.partials.topbar')

    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6"
         x-data="{
            classId: @js($classId),
            activityId: @js($activityId),
            loading: true,
            error: '',
            saving: false,
            publishing: false,
            attUploading: false,
            message: '',
            activity: null,
            classData: null,
            submissions: [],
            panel: 'details',
            form: { title: '', description: '', dueDate: '', dueTime: '', maxScore: 0 },
            // Students bucket UI
            bucketView: 'summary', // summary | list | review
            activeBucket: null,
            reviewRow: null,
            reviewScore: '',
            reviewFeedback: '',
            returning: false,
            async init() {
                await this.waitModule();
                await this.load();
            },
            waitModule() {
                if (window.TeacherClassroom) return Promise.resolve();
                return new Promise((resolve, reject) => {
                    const t = setTimeout(() => reject(new Error('Classroom module failed to load')), 8000);
                    const check = () => {
                        if (window.TeacherClassroom) { clearTimeout(t); resolve(); }
                        else setTimeout(check, 50);
                    };
                    check();
                });
            },
            async load() {
                this.loading = true;
                this.error = '';
                this.message = '';
                try {
                    const [act, cls] = await Promise.all([
                        window.TeacherClassroom.getClassActivity(this.activityId),
                        window.TeacherClassroom.loadClass(this.classId),
                    ]);
                    if (!act) throw new Error('Activity not found.');
                    this.activity = act;
                    this.classData = cls;
                    this.form = {
                        title: act.title || '',
                        description: act.description || '',
                        dueDate: act.dueDate || '',
                        dueTime: act.dueTime || '',
                        maxScore: act.maxScore != null ? act.maxScore : (Array.isArray(act.quiz) ? act.quiz.length : 0),
                    };
                    if (this.isPublished()) {
                        this.submissions = await window.TeacherClassroom.fetchActivitySubmissions(this.activityId);
                    } else {
                        this.submissions = [];
                    }
                } catch (e) {
                    console.error(e);
                    this.error = e.message || 'Failed to load activity.';
                } finally {
                    this.loading = false;
                }
            },
            async onActivityFileSelected(event) {
                const file = event.target?.files?.[0];
                event.target.value = '';
                if (!file || !this.activityId || this.isPublished()) return;
                this.attUploading = true;
                this.error = '';
                try {
                    const meta = await window.TeacherClassroom.uploadActivityAttachment(this.activityId, file);
                    const list = [...(this.activity.attachments || []), meta];
                    this.activity = { ...this.activity, attachments: list };
                } catch (e) {
                    console.error(e);
                    this.error = e.message || 'Upload failed.';
                } finally {
                    this.attUploading = false;
                }
            },
            async removeActivityAttachment(index) {
                if (this.isPublished()) return;
                const list = this.activity?.attachments || [];
                const item = list[index];
                if (!item) return;
                try {
                    if (item.storagePath) {
                        await window.TeacherClassroom.deleteActivityAttachment(this.activityId, item.storagePath);
                    }
                    this.activity = {
                        ...this.activity,
                        attachments: list.filter((_, i) => i !== index),
                    };
                } catch (e) {
                    console.error(e);
                    this.error = e.message || 'Could not remove file.';
                }
            },
            isPublished() {
                return this.activity && (this.activity.published === true || this.activity.status === 'published');
            },
            classroomUrl() {
                return @js(url('/classes')) + '/' + this.classId;
            },
            studentList() {
                const raw = this.classData?.students;
                return Array.isArray(raw) ? raw : [];
            },
            studentId(s) {
                if (!s) return '';
                if (typeof s === 'string') return s;
                return s.uid || s.id || s.studentId || s.email || '';
            },
            studentLabel(s) {
                if (!s) return 'Student';
                if (typeof s === 'string') return s;
                return s.name || s.displayName || s.email || s.uid || 'Student';
            },
            submissionByStudent() {
                const map = {};
                (this.submissions || []).forEach((sub) => {
                    const sid = sub.studentId || sub.uid || '';
                    if (sid) map[sid] = sub;
                });
                return map;
            },
            dueMoment() {
                const d = this.activity?.dueDate || this.form.dueDate;
                if (!d) return null;
                const t = this.activity?.dueTime || this.form.dueTime || '23:59';
                const m = new Date(d + 'T' + (t.length === 5 ? t + ':00' : t));
                return isNaN(m.getTime()) ? null : m;
            },
            isPastDue() {
                const m = this.dueMoment();
                return m ? m.getTime() < Date.now() : false;
            },
            buckets() {
                const by = this.submissionByStudent();
                const pending = [];
                const completed = [];
                const pastDue = [];
                const returned = [];
                const seen = new Set();

                this.studentList().forEach((s) => {
                    const sid = this.studentId(s);
                    if (!sid || seen.has(sid)) return;
                    seen.add(sid);
                    const sub = by[sid];
                    const row = { student: s, submission: sub || null, label: (sub && sub.studentName) ? sub.studentName : this.studentLabel(s) };
                    if (!sub) {
                        if (this.isPastDue()) pastDue.push(row);
                        else pending.push(row);
                        return;
                    }
                    const st = (sub.status || '').toLowerCase();
                    if (st === 'returned' || sub.returnedAt) returned.push(row);
                    else if (st === 'submitted' || st === 'graded' || sub.submittedAt) completed.push(row);
                    else pending.push(row);
                });

                // Submissions from students not listed on the class roster
                Object.keys(by).forEach((sid) => {
                    if (seen.has(sid)) return;
                    const sub = by[sid];
                    const st = (sub.status || '').toLowerCase();
                    const row = { student: { uid: sid }, submission: sub, label: sub.studentName || sid };
                    if (st === 'returned' || sub.returnedAt) returned.push(row);
                    else completed.push(row);
                });

                return { pending, completed, pastDue, returned };
            },

            bucketMeta() {
                return [
                    { key: 'pending', title: 'Pending', empty: 'No pending students.', tone: 'slate' },
                    { key: 'completed', title: 'Completed', empty: 'No completed submissions yet.', tone: 'emerald' },
                    { key: 'pastDue', title: 'Past due', empty: 'No past-due students.', tone: 'rose' },
                    { key: 'returned', title: 'Returned', empty: 'No returned work.', tone: 'sky' },
                ];
            },
            openBucket(key) {
                this.activeBucket = key;
                this.bucketView = 'list';
                this.reviewRow = null;
            },
            backToBuckets() {
                this.bucketView = 'summary';
                this.activeBucket = null;
                this.reviewRow = null;
            },
            backToList() {
                this.bucketView = 'list';
                this.reviewRow = null;
                this.reviewScore = '';
                this.reviewFeedback = '';
            },
            openReview(row) {
                if (!row?.submission) return;
                this.reviewRow = row;
                this.reviewScore = row.submission.score != null ? String(row.submission.score) : '';
                this.reviewFeedback = row.submission.feedback || '';
                this.bucketView = 'review';
            },
            quizQuestions() {
                const q = this.activity?.quiz;
                if (Array.isArray(q)) return q;
                if (q && Array.isArray(q.questions)) return q.questions;
                return [];
            },
            answerForQuestion(sub, q, index) {
                const answers = sub?.answers;
                if (!Array.isArray(answers)) return null;
                const order = q.order != null ? Number(q.order) : index + 1;
                const hit = answers.find(a => a && (Number(a.order) === order || Number(a.order) === index + 1 || Number(a.order) === index));
                return hit ? (hit.answer ?? hit.selected ?? null) : null;
            },
            optionText(q, optionId) {
                const opts = Array.isArray(q?.options) ? q.options : [];
                const o = opts.find(x => String(x.id) === String(optionId) || String(x.text) === String(optionId));
                if (!o) return optionId || '—';
                const id = o.id != null ? String(o.id).toUpperCase() : '';
                return id ? (id + '. ' + (o.text || '')) : (o.text || optionId);
            },
            correctOptionId(q) {
                return q.correctOptionId || q.correctOption || q.correct || '';
            },
            async returnWork() {
                if (!this.reviewRow?.submission?.id) return;
                const score = Number(this.reviewScore);
                if (Number.isNaN(score) || score < 0) {
                    this.error = 'Enter a valid score.';
                    return;
                }
                const max = Number(this.activity?.maxScore || this.form.maxScore || 0);
                if (max > 0 && score > max) {
                    this.error = 'Score cannot exceed max score (' + max + ').';
                    return;
                }
                this.returning = true;
                this.error = '';
                this.message = '';
                try {
                    const updated = await window.TeacherClassroom.returnActivitySubmission(
                        this.activityId,
                        this.reviewRow.submission.id,
                        { score, feedback: (this.reviewFeedback || '').trim() || null }
                    );
                    // refresh submissions list
                    this.submissions = await window.TeacherClassroom.fetchActivitySubmissions(this.activityId);
                    this.message = 'Returned to student with score ' + score + (max ? (' / ' + max) : '') + '.';
                    this.backToBuckets();
                } catch (e) {
                    console.error(e);
                    this.error = e.message || 'Could not return submission.';
                } finally {
                    this.returning = false;
                }
            },
            async saveDetails() {
                this.saving = true;
                this.message = '';
                this.error = '';
                try {
                    const patch = {
                        title: (this.form.title || '').trim(),
                        description: (this.form.description || '').trim(),
                        dueDate: this.form.dueDate || '',
                        dueTime: this.form.dueTime || '',
                        maxScore: Number(this.form.maxScore) || 0,
                    };
                    if (!patch.title) throw new Error('Title is required.');
                    await window.TeacherClassroom.updateClassActivity(this.activityId, patch);
                    this.activity = { ...this.activity, ...patch };
                    this.message = 'Details saved.';
                } catch (e) {
                    console.error(e);
                    this.error = e.message || 'Save failed.';
                } finally {
                    this.saving = false;
                }
            },
            async publish() {
                if (!this.form.dueDate) {
                    if (!confirm('No due date set. Publish anyway?')) return;
                } else if (!confirm('Publish this lab? Students in the class will be able to complete it.')) {
                    return;
                }
                this.publishing = true;
                this.error = '';
                this.message = '';
                try {
                    await this.saveDetails();
                    const extra = {
                        title: (this.form.title || '').trim(),
                        description: (this.form.description || '').trim(),
                        dueDate: this.form.dueDate || '',
                        dueTime: this.form.dueTime || '',
                        maxScore: Number(this.form.maxScore) || 0,
                    };
                    const res = await window.TeacherClassroom.publishClassActivity(this.activityId, extra);
                    this.activity = { ...this.activity, ...res, ...extra };
                    this.submissions = await window.TeacherClassroom.fetchActivitySubmissions(this.activityId);
                    this.message = 'Published. Students can now take this lab.';
                    this.panel = 'students';
                } catch (e) {
                    console.error(e);
                    this.error = e.message || 'Publish failed.';
                } finally {
                    this.publishing = false;
                }
            },
            async unpublish() {
                if (!confirm('Unpublish? Students will no longer see this as an active assignment.')) return;
                this.publishing = true;
                this.error = '';
                try {
                    const res = await window.TeacherClassroom.unpublishClassActivity(this.activityId);
                    this.activity = { ...this.activity, ...res };
                    this.message = 'Moved back to draft.';
                    this.panel = 'details';
                } catch (e) {
                    this.error = e.message || 'Unpublish failed.';
                } finally {
                    this.publishing = false;
                }
            },
            formatWhen(v) {
                if (!v) return '—';
                try {
                    if (v.toDate) return v.toDate().toLocaleString();
                    if (v.seconds) return new Date(v.seconds * 1000).toLocaleString();
                    return new Date(v).toLocaleString();
                } catch { return String(v); }
            },
         }"
    >
        <div class="mb-4">
            <a :href="classroomUrl()" class="text-xs font-bold text-slate-500 hover:text-[#50aed0]">← Back to class</a>
        </div>

        <div x-show="loading" class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-xs font-semibold text-slate-500">Loading activity…</div>
        <div x-show="!loading && error && !activity" x-cloak class="rounded-2xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-600" x-text="error"></div>

        <template x-if="!loading && activity">
            <div class="space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-xl font-black text-slate-800" x-text="activity.title || 'Untitled lab'"></h1>
                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                                  :class="isPublished() ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800'"
                                  x-text="isPublished() ? 'Published' : 'Draft'"></span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            <span x-text="activity.labCategory || 'Lab'"></span>
                            <span x-show="activity.dueDate"> · Due <span x-text="activity.dueDate"></span><span x-show="activity.dueTime" x-text="' ' + activity.dueTime"></span></span>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" x-show="!isPublished()" @click="publish()" :disabled="publishing"
                                class="rounded-xl bg-[#7CD5F3] px-4 py-2 text-xs font-bold text-white hover:bg-[#68cbed] disabled:opacity-50">
                            <span x-text="publishing ? 'Publishing…' : 'Publish for students'"></span>
                        </button>
                        <button type="button" x-show="isPublished()" @click="unpublish()" :disabled="publishing"
                                class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 disabled:opacity-50">
                            Unpublish
                        </button>
                    </div>
                </div>

                <p x-show="message" x-text="message" class="text-xs font-bold text-emerald-600"></p>
                <p x-show="error && activity" x-text="error" class="text-xs font-bold text-red-500"></p>

                <nav class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
                    <button type="button" @click="panel = 'details'"
                            class="rounded-xl px-4 py-2 text-xs font-bold transition"
                            :class="panel === 'details' ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:bg-white/60'">
                        Activity details
                    </button>
                    <button type="button" @click="panel = 'students'"
                            class="rounded-xl px-4 py-2 text-xs font-bold transition"
                            :class="panel === 'students' ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:bg-white/60'">
                        Students
                        <span class="ml-1 text-[10px] font-semibold text-slate-400" x-show="isPublished()">(tracking)</span>
                    </button>
                </nav>

                {{-- Details --}}
                <div x-show="panel === 'details'" x-cloak class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-sm font-black text-slate-800">Assignment settings</h2>
                        <p class="mt-1 text-[11px] text-slate-500">Set a deadline and publish when ready. Drafts stay hidden from students.</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Title</label>
                                <input type="text" x-model="form.title" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Description</label>
                                <textarea x-model="form.description" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold"></textarea>
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Due date</label>
                                <input type="date" x-model="form.dueDate" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold">
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Due time</label>
                                <input type="time" x-model="form.dueTime" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold">
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Max score</label>
                                <input type="number" min="0" x-model.number="form.maxScore" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold">
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button type="button" @click="saveDetails()" :disabled="saving"
                                    class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-50">
                                <span x-text="saving ? 'Saving…' : 'Save details'"></span>
                            </button>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" x-show="activity.type === 'lab'">
                        <h2 class="text-sm font-black text-slate-800">Lab content</h2>
                        <p class="mt-1 text-[11px] text-slate-500" x-text="activity.description || 'No description.'"></p>
                        <div class="mt-3 flex flex-wrap gap-3 text-[11px] font-bold text-slate-500">
                            <span x-text="(activity.apparatus?.length || 0) + ' apparatus'"></span>
                            <span>·</span>
                            <span x-text="(activity.elements?.length || 0) + ' elements'"></span>
                            <span>·</span>
                            <span x-text="(activity.steps?.length || 0) + ' steps'"></span>
                            <span>·</span>
                            <span x-text="(activity.quiz?.length || 0) + ' quiz'"></span>
                        </div>
                        <p class="mt-2 text-[11px] text-slate-400" x-show="activity.safetyTips">
                            <span class="font-bold text-rose-600">Safety:</span>
                            <span x-text="activity.safetyTips"></span>
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" x-show="activity.type !== 'lab'">
                        <h2 class="text-sm font-black text-slate-800">Activity content</h2>
                        <p class="mt-1 text-[11px] text-slate-500" x-text="activity.description || 'No description.'"></p>
                        <p class="mt-2 text-[11px] font-bold text-slate-500"
                           x-text="(activity.quiz?.questions?.length || 0) + ' quiz question(s)'"></p>
                    </div>

                    {{-- Attachments (lab + regular): view always; add/remove when draft --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <h2 class="text-sm font-black text-slate-800">Attachments</h2>
                            <label x-show="!isPublished()"
                                   class="cursor-pointer rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700 hover:bg-slate-200"
                                   :class="attUploading && 'pointer-events-none opacity-50'">
                                <span x-text="attUploading ? 'Uploading…' : '+ Add file'"></span>
                                <input type="file" class="hidden"
                                       accept=".pdf,.doc,.docx,image/png,image/jpeg,image/webp,application/pdf"
                                       :disabled="attUploading"
                                       @change="onActivityFileSelected($event)">
                            </label>
                        </div>
                        <ul class="space-y-1.5" x-show="(activity.attachments || []).length">
                            <template x-for="(file, fi) in (activity.attachments || [])" :key="file.storagePath || file.downloadURL || fi">
                                <li class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                    <a :href="file.url || file.downloadURL" target="_blank" rel="noopener"
                                       class="truncate text-xs font-bold text-[#439BBB] hover:underline"
                                       x-text="file.name || 'Attachment'"></a>
                                    <button type="button" x-show="!isPublished()" @click="removeActivityAttachment(fi)"
                                            class="shrink-0 text-[11px] font-bold text-rose-500">Remove</button>
                                </li>
                            </template>
                        </ul>
                        <p class="text-[11px] text-slate-400" x-show="!(activity.attachments || []).length">No file attachments.</p>
                        <p class="mt-2 text-[10px] text-slate-400" x-show="isPublished()">Published activities cannot change files. Unpublish to add or remove.</p>
                    </div>
                </div>

                {{-- Students --}}
                <div x-show="panel === 'students'" x-cloak class="space-y-4">
                    <div x-show="!isPublished()" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs font-semibold text-amber-900">
                        This activity is still a draft. Publish it to track student work (pending, completed, past due, returned).
                    </div>

                    <template x-if="isPublished()">
                        <div class="space-y-4">
                            {{-- Summary: count cards only --}}
                            <div x-show="bucketView === 'summary'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <template x-for="sec in bucketMeta()" :key="sec.key">
                                    <button type="button"
                                            @click="openBucket(sec.key)"
                                            class="rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:border-indigo-300 hover:shadow-md">
                                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500" x-text="sec.title"></p>
                                        <p class="mt-2 text-3xl font-black text-slate-900" x-text="(buckets()[sec.key] || []).length"></p>
                                        <p class="mt-1 text-[11px] text-slate-400">Tap to view students</p>
                                    </button>
                                </template>
                            </div>

                            {{-- List of students in a bucket --}}
                            <div x-show="bucketView === 'list'" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="mb-4 flex items-center justify-between gap-2">
                                    <button type="button" @click="backToBuckets()" class="text-xs font-bold text-indigo-600 hover:underline">← All buckets</button>
                                    <h3 class="text-sm font-black text-slate-800"
                                        x-text="(bucketMeta().find(s => s.key === activeBucket) || {}).title || ''"></h3>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600"
                                          x-text="(buckets()[activeBucket] || []).length"></span>
                                </div>
                                <div x-show="!(buckets()[activeBucket] || []).length" class="py-8 text-center text-xs text-slate-400"
                                     x-text="(bucketMeta().find(s => s.key === activeBucket) || {}).empty || 'None'"></div>
                                <ul class="divide-y divide-slate-100">
                                    <template x-for="(row, idx) in (buckets()[activeBucket] || [])" :key="activeBucket + '-' + idx">
                                        <li class="flex items-center justify-between gap-2 py-3">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-bold text-slate-800" x-text="row.label"></p>
                                                <p class="text-[11px] text-slate-400" x-show="row.submission?.submittedAt"
                                                   x-text="'Submitted ' + formatWhen(row.submission.submittedAt)"></p>
                                                <p class="text-[11px] font-semibold text-sky-700" x-show="activeBucket === 'returned' && row.submission?.score != null"
                                                   x-text="'Score: ' + row.submission.score + ' / ' + (activity?.maxScore ?? form.maxScore ?? '—')"></p>
                                            </div>
                                            <button type="button"
                                                    x-show="activeBucket === 'completed' && row.submission"
                                                    @click="openReview(row)"
                                                    class="shrink-0 rounded-lg bg-indigo-600 px-3 py-1.5 text-[11px] font-bold text-white hover:bg-indigo-700">
                                                Review
                                            </button>
                                            <button type="button"
                                                    x-show="activeBucket === 'returned' && row.submission"
                                                    @click="openReview(row)"
                                                    class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-[11px] font-bold text-slate-600 hover:bg-slate-50">
                                                View
                                            </button>
                                        </li>
                                    </template>
                                </ul>
                            </div>

                            {{-- Review one submission --}}
                            <div x-show="bucketView === 'review' && reviewRow" class="space-y-4">
                                <div class="flex items-center justify-between gap-2">
                                    <button type="button" @click="backToList()" class="text-xs font-bold text-indigo-600 hover:underline">← Back to list</button>
                                    <p class="text-sm font-black text-slate-800" x-text="reviewRow?.label || 'Student'"></p>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <h4 class="mb-3 text-xs font-black uppercase tracking-wide text-slate-500">Quiz answers</h4>
                                    <template x-if="!quizQuestions().length">
                                        <p class="text-xs text-slate-400">No quiz questions on this activity.</p>
                                    </template>
                                    <ul class="space-y-3">
                                        <template x-for="(q, qi) in quizQuestions()" :key="'rq-' + qi">
                                            <li class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                                <p class="text-xs font-bold text-slate-800">
                                                    <span x-text="'Q' + (qi + 1) + '. '"></span>
                                                    <span x-text="q.prompt || q.question || ''"></span>
                                                </p>
                                                <p class="mt-1 text-[11px] text-slate-600">
                                                    Student:
                                                    <span class="font-semibold"
                                                          x-text="optionText(q, answerForQuestion(reviewRow?.submission, q, qi))"></span>
                                                </p>
                                                <p class="mt-0.5 text-[11px] text-emerald-700">
                                                    Correct:
                                                    <span class="font-semibold"
                                                          x-text="optionText(q, correctOptionId(q))"></span>
                                                </p>
                                            </li>
                                        </template>
                                    </ul>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-3"
                                     x-show="activeBucket === 'completed' || (reviewRow?.submission?.status || '').toLowerCase() === 'submitted'">
                                    <h4 class="text-xs font-black uppercase tracking-wide text-slate-500">Return with score</h4>
                                    <div class="flex flex-wrap items-end gap-3">
                                        <label class="block text-xs font-semibold text-slate-600">
                                            Score
                                            <input type="number" min="0" step="1" x-model="reviewScore"
                                                   class="mt-1 block w-28 rounded-lg border border-slate-200 px-3 py-2 text-sm font-bold">
                                        </label>
                                        <p class="pb-2 text-xs text-slate-400"
                                           x-text="'Max: ' + (activity?.maxScore ?? form.maxScore ?? '—')"></p>
                                    </div>
                                    <label class="block text-xs font-semibold text-slate-600">
                                        Feedback (optional)
                                        <textarea x-model="reviewFeedback" rows="2"
                                                  class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></textarea>
                                    </label>
                                    <button type="button" @click="returnWork()" :disabled="returning"
                                            class="rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-indigo-700 disabled:opacity-50">
                                        <span x-text="returning ? 'Returning…' : 'Return to student'"></span>
                                    </button>
                                </div>

                                <div class="rounded-2xl border border-sky-100 bg-sky-50 p-4 text-sm"
                                     x-show="activeBucket === 'returned' || (reviewRow?.submission?.status || '').toLowerCase() === 'returned'">
                                    <p class="font-bold text-sky-900"
                                       x-text="'Score: ' + (reviewRow?.submission?.score ?? '—') + ' / ' + (activity?.maxScore ?? form.maxScore ?? '—')"></p>
                                    <p class="mt-1 text-xs text-sky-800" x-show="reviewRow?.submission?.feedback"
                                       x-text="reviewRow?.submission?.feedback"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

        </template>
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/teacher/classroom.js') }}"></script>
@endpush
