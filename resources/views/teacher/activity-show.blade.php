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
            publishValidation: {
                open: false,
                title: '',
                message: '',
                issues: [],
            },
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

            showPublishValidation(issues) {
                this.publishValidation = {
                    open: true,
                    title: 'Cannot publish activity yet',
                    message: 'Please fix the following settings before publishing this activity to students.',
                    issues,
                };
            },

            closePublishValidation() {
                this.publishValidation.open = false;
            },

            validateForPublish() {
                const issues = [];

                    // Title
                    if (!(this.form.title || '').trim()) {
                        issues.push('Enter an activity title.');
                    }

                    // Due date
                    if (!this.form.dueDate) {
                        issues.push('Set a due date.');
                    }

                    // Due time
                    if (!this.form.dueTime) {
                        issues.push('Set a due time.');
                    }

                    // Check the deadline only when both fields are present.
                    if (this.form.dueDate && this.form.dueTime) {
                        const time = this.form.dueTime.length === 5
                            ? this.form.dueTime + ':00'
                            : this.form.dueTime;

                    // Uses the teacher's local time zone.
                    const deadline = new Date(
                        this.form.dueDate + 'T' + time
                    );

                    if (Number.isNaN(deadline.getTime())) {
                        issues.push('The due date or due time is invalid.');
                    } else if (deadline.getTime() <= Date.now()) {
                        issues.push('The deadline must be in the future. Choose a later date and time.');
                    }
                }

                // Max score
                const maxScore = Number(this.form.maxScore);

                if (
                    this.form.maxScore === '' ||
                    this.form.maxScore === null ||
                    this.form.maxScore === undefined ||
                    !Number.isFinite(maxScore) ||
                    maxScore <= 0
                ) {
                    issues.push('Set a valid max score greater than zero.');
                }

                return {
                    valid: issues.length === 0,
                    issues,
                };
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

                    if (!patch.title) {
                        throw new Error('Title is required.');
                    }

                    await window.TeacherClassroom.updateClassActivity(
                        this.activityId,
                        patch
                    );

                    this.activity = {
                        ...this.activity,
                        ...patch,
                    };

                    this.message = 'Details saved.';
                    return true;
                } catch (e) {
                    console.error(e);
                    this.error = e.message || 'Save failed.';
                    return false;
                } finally {
                    this.saving = false;
                }
            },

            async publish() {
                // Prevent publishing if required fields are invalid.
                const validation = this.validateForPublish();

                if (!validation.valid) {
                    this.showPublishValidation(validation.issues);
                    return;
                }

                if (
                    !confirm(
                        'Publish this lab? Students in the class will be able to complete it.'
                    )
                ) {
                    return;
                }

                this.publishing = true;
                this.error = '';
                this.message = '';

                try {
                    // Save the latest settings first.
                    const saved = await this.saveDetails();

                    // Do not publish if saving failed.
                    if (!saved) {
                        return;
                    }

                    const extra = {
                        title: (this.form.title || '').trim(),
                        description: (this.form.description || '').trim(),
                        dueDate: this.form.dueDate,
                        dueTime: this.form.dueTime,
                        maxScore: Number(this.form.maxScore),
                    };

                    const res = await window.TeacherClassroom.publishClassActivity(
                        this.activityId,
                        extra
                    );

                    this.activity = {
                        ...this.activity,
                        ...res,
                        ...extra,
                    };

                    this.submissions =
                        await window.TeacherClassroom.fetchActivitySubmissions(
                            this.activityId
                        );

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
         }">
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
                        class="cursor-pointer rounded-xl bg-[#1B2CC1] px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#091540] disabled:opacity-50 transition-colors">
                        <span x-text="publishing ? 'Publishing…' : 'Publish for students'"></span>
                    </button>
                    <button type="button" x-show="isPublished()" @click="unpublish()" :disabled="publishing"
                        class="cursor-pointer rounded-xl border-[1.5px] border-[#ABD2FA] bg-white px-5 py-2.5 text-xs font-bold text-[#091540] hover:bg-[#ABD2FA]/20 disabled:opacity-50 transition-colors">
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
                    <button type="button" @click="panel = 'students'"
                        class="rounded-xl px-4 py-2 text-xs font-bold transition"
                        :class="panel === 'students' ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:bg-white/60'">
                        Students
                        <span class="ml-1 text-[10px] font-semibold text-slate-400" x-show="isPublished()">(tracking)</span>
                    </button>
            </nav>

            {{-- Details --}}
            <div x-show="panel === 'details'" x-cloak class="space-y-4">
                <div class="rounded-2xl border border-[#ABD2FA]/40 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-black text-[#091540]">Assignment settings</h2>
                    <p class="mt-1 text-xs font-medium text-[#091540]/70">Set a deadline and publish when ready. Drafts stay hidden from students.</p>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Title</label>
                            <input type="text" x-model="form.title" class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA] bg-white px-4 py-3 text-xs font-bold text-[#091540] placeholder:text-[#091540]/40 focus:border-[#1B2CC1] focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20 transition-all">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Description</label>
                            <textarea x-model="form.description" rows="3" class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA] bg-white px-4 py-3 text-xs font-bold text-[#091540] placeholder:text-[#091540]/40 focus:border-[#1B2CC1] focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20 transition-all"></textarea>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Due date</label>
                            <input type="date" x-model="form.dueDate" class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA] bg-white px-4 py-3 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20 transition-all">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Due time</label>
                            <input type="time" x-model="form.dueTime" class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA] bg-white px-4 py-3 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20 transition-all">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Max score</label>
                            <input type="number" min="0" x-model.number="form.maxScore" class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA] bg-white px-4 py-3 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20 transition-all">
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button type="button" @click="saveDetails()" :disabled="saving"
                            class="cursor-pointer rounded-xl bg-[#1B2CC1] px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#091540] disabled:opacity-50 transition-colors">
                            <span x-text="saving ? 'Saving…' : 'Save details'"></span>
                        </button>
                    </div>
                </div>

                <div class="rounded-2xl border border-[#ABD2FA]/40 bg-white p-6 shadow-sm space-y-4" x-show="activity.type === 'lab'">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-black text-[#091540]">Lab content</h2>
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#ABD2FA]/20 text-[#1B2CC1]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.571.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.229.693L5 14.5m14.8.8l.75 3.75a1.5 1.5 0 01-1.47 1.75H4.92a1.5 1.5 0 01-1.47-1.75l.75-3.75m14.8 0H4.2" />
                            </svg>
                        </div>
                    </div>

                    <p class="text-xs font-medium text-[#091540]/70" x-text="activity.description || 'No description.'"></p>

                    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                        {{-- Apparatus Pill --}}
                        <div class="flex items-center gap-2.5 rounded-xl border border-[#ABD2FA]/40 bg-[#ABD2FA]/10 p-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#1B2CC1] text-white">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-[#1B2CC1]">Apparatus</p>
                                <p class="text-xs font-black text-[#091540]" x-text="(activity.apparatus?.length || 0)"></p>
                            </div>
                        </div>

                        {{-- Elements Pill --}}
                        <div class="flex items-center gap-2.5 rounded-xl border border-[#ABD2FA]/40 bg-[#ABD2FA]/10 p-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#1B2CC1] text-white">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-[#1B2CC1]">Elements</p>
                                <p class="text-xs font-black text-[#091540]" x-text="(activity.elements?.length || 0)"></p>
                            </div>
                        </div>

                        {{-- Steps Pill --}}
                        <div class="flex items-center gap-2.5 rounded-xl border border-[#ABD2FA]/40 bg-[#ABD2FA]/10 p-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#1B2CC1] text-white">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-[#1B2CC1]">Steps</p>
                                <p class="text-xs font-black text-[#091540]" x-text="(activity.steps?.length || 0)"></p>
                            </div>
                        </div>

                        {{-- Quiz Pill --}}
                        <div class="flex items-center gap-2.5 rounded-xl border border-[#ABD2FA]/40 bg-[#ABD2FA]/10 p-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#1B2CC1] text-white">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.875 7.5h4.25M9.875 11.25h4.25M11.25 15h1.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-[#1B2CC1]">Quiz</p>
                                <p class="text-xs font-black text-[#091540]" x-text="(activity.quiz?.length || 0)"></p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-3.5 flex items-start gap-3" x-show="activity.safetyTips">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-rose-500 text-white mt-0.5">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-extrabold uppercase tracking-wider text-rose-600">Safety Precautions</p>
                            <p class="mt-0.5 text-xs font-medium text-rose-900 leading-relaxed" x-text="activity.safetyTips"></p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-[#ABD2FA]/40 bg-white p-6 shadow-sm" x-show="activity.type !== 'lab'">
                    <h2 class="text-sm font-black text-[#091540]">Activity content</h2>
                    <p class="mt-1 text-xs font-medium text-[#091540]/70" x-text="activity.description || 'No description.'"></p>
                    <p class="mt-3 text-xs font-bold text-[#1B2CC1]"
                        x-text="(activity.quiz?.questions?.length || 0) + ' quiz question(s)'"></p>
                </div>

                {{-- Attachments (lab + regular): view always; add/remove when draft --}}
                <div class="rounded-2xl border border-[#ABD2FA]/40 bg-white p-6 shadow-sm">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h2 class="text-sm font-black text-[#091540]">Attachments</h2>
                        <label x-show="!isPublished()"
                            class="cursor-pointer rounded-xl bg-[#ABD2FA]/20 px-3.5 py-2 text-xs font-bold text-[#1B2CC1] hover:bg-[#ABD2FA]/40 transition-colors"
                            :class="attUploading && 'pointer-events-none opacity-50'">
                            <span x-text="attUploading ? 'Uploading…' : '+ Add file'"></span>
                            <input type="file" class="hidden"
                                accept=".pdf,.doc,.docx,image/png,image/jpeg,image/webp,application/pdf"
                                :disabled="attUploading"
                                @change="onActivityFileSelected($event)">
                        </label>
                    </div>
                    <ul class="space-y-2" x-show="(activity.attachments || []).length">
                        <template x-for="(file, fi) in (activity.attachments || [])" :key="file.storagePath || file.downloadURL || fi">
                            <li class="flex items-center justify-between gap-2 rounded-xl border border-[#ABD2FA]/60 bg-[#ABD2FA]/10 px-3.5 py-2.5">
                                <a :href="file.url || file.downloadURL" target="_blank" rel="noopener"
                                    class="truncate text-xs font-bold text-[#1B2CC1] hover:underline"
                                    x-text="file.name || 'Attachment'"></a>
                                <button type="button" x-show="!isPublished()" @click="removeActivityAttachment(fi)"
                                    class="cursor-pointer shrink-0 text-xs font-bold text-rose-500 hover:text-rose-700 transition-colors">Remove</button>
                            </li>
                        </template>
                    </ul>
                    <p class="text-xs font-medium text-[#091540]/60" x-show="!(activity.attachments || []).length">No file attachments.</p>
                    <p class="mt-2 text-[11px] font-medium text-[#091540]/50" x-show="isPublished()">Published activities cannot change files. Unpublish to add or remove.</p>
                </div>
            </div>

            {{-- Students --}}
            <div x-show="panel === 'students'" x-cloak class="space-y-4">
                <div x-show="!isPublished()" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs font-bold text-amber-900 shadow-sm">
                    This activity is still a draft. Publish it to track student work (pending, completed, past due, returned).
                </div>

                <template x-if="isPublished()">
                    <div class="space-y-4">
                        {{-- Summary: count cards only --}}
                        <div x-show="bucketView === 'summary'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <template x-for="sec in bucketMeta()" :key="sec.key">
                                <button type="button"
                                    @click="openBucket(sec.key)"
                                    class="cursor-pointer rounded-2xl border border-[#ABD2FA]/40 bg-white p-5 text-left shadow-sm transition-all hover:border-[#1B2CC1] hover:shadow-md">
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]" x-text="sec.title"></p>
                                    <p class="mt-2 text-3xl font-black text-[#091540]" x-text="(buckets()[sec.key] || []).length"></p>
                                    <p class="mt-1 text-xs font-medium text-[#091540]/60">Tap to view students</p>
                                </button>
                            </template>
                        </div>

                        {{-- List of students in a bucket --}}
                        <div x-show="bucketView === 'list'" class="rounded-2xl border border-[#ABD2FA]/40 bg-white p-6 shadow-sm">
                            <div class="mb-4 flex items-center justify-between gap-2">
                                <button type="button" @click="backToBuckets()" class="cursor-pointer text-xs font-bold text-[#1B2CC1] hover:underline">← All buckets</button>
                                <h3 class="text-sm font-black text-[#091540]"
                                    x-text="(bucketMeta().find(s => s.key === activeBucket) || {}).title || ''"></h3>
                                <span class="rounded-full bg-[#ABD2FA]/20 px-2.5 py-0.5 text-[10px] font-bold text-[#1B2CC1]"
                                    x-text="(buckets()[activeBucket] || []).length"></span>
                            </div>
                            <div x-show="!(buckets()[activeBucket] || []).length" class="py-12 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#ABD2FA]/20 text-[#1B2CC1] mb-2.5">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                    </svg>
                                </div>
                                <p class="text-xs font-bold text-[#091540]" x-text="(bucketMeta().find(s => s.key === activeBucket) || {}).empty || 'No students in this bucket.'"></p>
                            </div>
                            <ul class="divide-y divide-[#ABD2FA]/30">
                                <template x-for="(row, idx) in (buckets()[activeBucket] || [])" :key="activeBucket + '-' + idx">
                                    <li class="flex items-center justify-between gap-3 py-3.5 group">
                                        <div class="flex items-center gap-3 min-w-0">
                                            {{-- Student Avatar / Initial Fallback --}}
                                            <div class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[#1B2CC1] text-xs font-black text-white shadow-xs overflow-hidden border border-[#7692FF]/30">
                                                <template x-if="row.avatar || row.photoUrl">
                                                    <img :src="row.avatar || row.photoUrl" alt="" class="h-full w-full object-cover">
                                                </template>
                                                <template x-if="!row.avatar && !row.photoUrl">
                                                    <span x-text="row.label ? row.label.charAt(0).toUpperCase() : 'S'"></span>
                                                </template>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate text-xs font-bold text-[#091540]" x-text="row.label"></p>
                                                <p class="text-[11px] font-medium text-[#091540]/60" x-show="row.submission?.submittedAt"
                                                    x-text="'Submitted ' + formatWhen(row.submission.submittedAt)"></p>
                                                <p class="mt-0.5 text-[11px] font-bold text-[#1B2CC1]" x-show="activeBucket === 'returned' && row.submission?.score != null"
                                                    x-text="'Score: ' + row.submission.score + ' / ' + (activity?.maxScore ?? form.maxScore ?? '—')"></p>
                                            </div>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-2">
                                            <button type="button"
                                                x-show="activeBucket === 'completed' && row.submission"
                                                @click="openReview(row)"
                                                class="cursor-pointer shrink-0 rounded-xl bg-[#1B2CC1] px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#091540] transition-colors">
                                                Review
                                            </button>
                                            <button type="button"
                                                x-show="activeBucket === 'returned' && row.submission"
                                                @click="openReview(row)"
                                                class="cursor-pointer shrink-0 rounded-xl border-[1.5px] border-[#ABD2FA] bg-white px-3.5 py-2 text-xs font-bold text-[#091540] hover:bg-[#ABD2FA]/20 transition-colors">
                                                View
                                            </button>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        {{-- Review one submission --}}
                        <div x-show="bucketView === 'review' && reviewRow" class="space-y-4">
                            <div class="flex items-center justify-between gap-2">
                                <button type="button" @click="backToList()" class="cursor-pointer text-xs font-bold text-[#1B2CC1] hover:underline">← Back to list</button>
                                <p class="text-sm font-black text-[#091540]" x-text="reviewRow?.label || 'Student'"></p>
                            </div>

                            <div class="rounded-2xl border border-[#ABD2FA]/40 bg-white p-6 shadow-sm">
                                <h4 class="mb-3 text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Quiz answers</h4>
                                <template x-if="!quizQuestions().length">
                                    <p class="text-xs font-medium text-[#091540]/60">No quiz questions on this activity.</p>
                                </template>
                                <ul class="space-y-3">
                                    <template x-for="(q, qi) in quizQuestions()" :key="'rq-' + qi">
                                        <li class="rounded-2xl border border-[#ABD2FA]/60 bg-[#ABD2FA]/10 p-4 space-y-1">
                                            <p class="text-xs font-bold text-[#091540]">
                                                <span x-text="'Q' + (qi + 1) + '. '"></span>
                                                <span x-text="q.prompt || q.question || ''"></span>
                                            </p>
                                            <p class="text-xs font-medium text-[#091540]/70">
                                                Student:
                                                <span class="font-bold text-[#091540]"
                                                    x-text="optionText(q, answerForQuestion(reviewRow?.submission, q, qi))"></span>
                                            </p>
                                            <p class="text-xs font-medium text-emerald-700">
                                                Correct:
                                                <span class="font-bold text-emerald-800"
                                                    x-text="optionText(q, correctOptionId(q))"></span>
                                            </p>
                                        </li>
                                    </template>
                                </ul>
                            </div>

                            <div class="rounded-2xl border border-[#ABD2FA]/40 bg-white p-6 shadow-sm space-y-4"
                                x-show="activeBucket === 'completed' || (reviewRow?.submission?.status || '').toLowerCase() === 'submitted'">
                                <h4 class="text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Return with score</h4>
                                <div class="flex flex-wrap items-end gap-3">
                                    <label class="block text-xs font-bold text-[#091540]">
                                        Score
                                        <input type="number" min="0" step="1" x-model="reviewScore"
                                            class="mt-1.5 block w-32 rounded-xl border-[1.5px] border-[#ABD2FA] bg-white px-3.5 py-2.5 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20 transition-all">
                                    </label>
                                    <p class="pb-3 text-xs font-medium text-[#091540]/60"
                                        x-text="'Max: ' + (activity?.maxScore ?? form.maxScore ?? '—')"></p>
                                </div>
                                <label class="block text-xs font-bold text-[#091540]">
                                    Feedback (optional)
                                    <textarea x-model="reviewFeedback" rows="2"
                                        class="mt-1.5 w-full rounded-xl border-[1.5px] border-[#ABD2FA] bg-white px-4 py-3 text-xs font-bold text-[#091540] placeholder:text-[#091540]/40 focus:border-[#1B2CC1] focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20 transition-all" placeholder="Add optional feedback for the student..."></textarea>
                                </label>
                                <div class="flex justify-end">
                                    <button type="button" @click="returnWork()" :disabled="returning"
                                        class="cursor-pointer rounded-xl bg-[#1B2CC1] px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#091540] disabled:opacity-50 transition-colors">
                                        <span x-text="returning ? 'Returning…' : 'Return to student'"></span>
                                    </button>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-[#ABD2FA]/40 bg-[#ABD2FA]/10 p-5 space-y-1.5"
                                x-show="activeBucket === 'returned' || (reviewRow?.submission?.status || '').toLowerCase() === 'returned'">
                                <p class="text-xs font-black text-[#091540]"
                                    x-text="'Score: ' + (reviewRow?.submission?.score ?? '—') + ' / ' + (activity?.maxScore ?? form.maxScore ?? '—')"></p>
                                <p class="text-xs font-medium text-[#091540]/70" x-show="reviewRow?.submission?.feedback"
                                    x-text="reviewRow?.submission?.feedback"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

    </template>

    {{-- Publish validation modal --}}
    <div
        x-show="publishValidation.open"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="publish-validation-title"
        @keydown.escape.window="closePublishValidation()"
    >
        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm"
            @click="closePublishValidation()"
        ></div>

        {{-- Modal --}}
        <div
            x-show="publishValidation.open"
            x-transition
            class="relative w-full max-w-md overflow-hidden rounded-2xl border border-[#ABD2FA]/60 bg-white shadow-2xl"
        >
            {{-- Header --}}
            <div class="border-b border-[#ABD2FA]/40 bg-[#ABD2FA]/15 px-5 py-4">
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#1B2CC1]/10 text-[#1B2CC1]"
                    >
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v3.75m0 3h.008M10.29 3.86 1.82 18.14A1.75 1.75 0 0 0 3.32 20.8h17.36a1.75 1.75 0 0 0 1.5-2.66L13.71 3.86a2 2 0 0 0-3.42 0Z"
                            />
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <h2
                            id="publish-validation-title"
                            class="text-base font-black text-[#091540]"
                            x-text="publishValidation.title"
                        ></h2>

                        <p
                            class="mt-1 text-xs leading-relaxed text-slate-600"
                            x-text="publishValidation.message"
                        ></p>
                    </div>

                    <button
                        type="button"
                        @click="closePublishValidation()"
                        class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-white hover:text-slate-700"
                        aria-label="Close validation dialog"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 6l12 12M18 6 6 18"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Validation issues --}}
            <div class="px-5 py-5">
                <p class="mb-3 text-[11px] font-extrabold uppercase tracking-wider text-[#1B2CC1]">
                    Required corrections
                </p>

                <ul class="space-y-3">
                    <template
                        x-for="(issue, index) in publishValidation.issues"
                        :key="index"
                    >
                        <li class="flex items-start gap-2.5 text-xs leading-relaxed text-slate-700">
                            <span
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-rose-50 text-[10px] font-black text-rose-600"
                                x-text="'!'"
                            ></span>

                            <span x-text="issue"></span>
                        </li>
                    </template>
                </ul>

                <p class="mt-4 rounded-xl border border-[#ABD2FA]/50 bg-[#ABD2FA]/10 p-3 text-[11px] leading-relaxed text-[#091540]/80">
                    Set a future deadline and a valid max score in
                    <strong>Activity details</strong>. Your activity will remain a
                    draft until you successfully publish it.
                </p>

                <div class="mt-5 flex justify-end">
                    <button
                        type="button"
                        @click="closePublishValidation()"
                        class="cursor-pointer rounded-xl bg-[#1B2CC1] px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-[#091540]"
                    >
                        Return to settings
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('js/teacher/classroom.js') }}"></script>
@endpush