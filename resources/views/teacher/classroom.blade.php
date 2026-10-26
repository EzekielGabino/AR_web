@extends('layouts.teacher')

@section('title', 'Class workspace — SciLens AR')

@section('content')
    @include('teacher.partials.topbar')

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6"
         x-data="{
            classId: @js($classId),
            tab: (new URLSearchParams(window.location.search).get('tab') || 'stream'),
            activityTab: 'regular',
            loading: true,
            error: '',
            classData: null,
            announcements: [],
            draftAttachments: [],
            streamUploading: false,
            activities: [],
            templates: [],
            templatesLoading: false,
            draft: '',
            posting: false,
            streamError: '',
            showQr: false,
            useConfirm: null,
            useBusy: false,
            useError: '',
            deleteConfirm: null,
            deleteBusy: false,
            deleteError: '',
            regModal: null,
            regSaving: false,
            regError: '',
            regActive: null,
            regForm: {
                title: '',
                description: '',
                questions: [],
                attachments: [],
            },
            regUploading: false,
            regDriveLink: '',
            regDriveName: '',
            tplModal: null,
            tplSaving: false,
            tplUploading: false,
            tplError: '',
            tplActive: null,
            tplSection: 'overview',
            tplForm: {
                title: '',
                labCategory: 'Concentration',
                description: '',
                safetyTips: '',
                apparatus: [],
                elements: [],
                steps: [],
                quiz: [],
            },
            labCategories: [
                'Concentration', 'Precipitation', 'Diffusion', 'GasEvolution',
                'AcidBased', 'Carbonation', 'Endothermic', 'Exothermic',
            ],
            stepActions: [
                'PlaceApparatus', 'AddElement', 'MixOrTransfer', 'InteractApparatus',
                'Wait', 'Observe', 'PlaceInRack', 'Stir', 'Drain',
            ],
            stepActionHints: {
                PlaceApparatus: 'Student places the target apparatus on the work surface (from inventory).',
                AddElement: 'Student adds the selected element (formula) into the target apparatus at the given volume.',
                MixOrTransfer: 'Pour or transfer from source apparatus into target. Use for mixing or moving liquids.',
                InteractApparatus: 'Student interacts with an apparatus (e.g. open/close or use a tool) without adding material.',
                Wait: 'Timed pause. Set Wait seconds; the simulation advances after the delay.',
                Observe: 'Student observes a result (color change, precipitate, gas). Optional reaction color.',
                PlaceInRack: 'Student places the target apparatus into a rack.',
                Stir: 'Student stirs the contents of the target apparatus. Use Wait seconds for stir duration.',
                Drain: 'Student drains or empties the target apparatus.',
            },
            elementTypes: ['liquid', 'granule', 'solid'],
            elementRoles: ['Neutral', 'Acid', 'Base', 'Salt', 'Indicator', 'Carbonate'],
            /**
             * Required elements by lab category.
             * roles / types: each must appear at least once.
             * typesOneOf: at least one of the listed types must appear.
             * Diffusion has no rule (flexible dye/water setups).
             */
            elementCategoryRules: {
                GasEvolution: {
                    roles: ['Acid', 'Carbonate'],
                    summary: 'Acid and Carbonate',
                },
                AcidBased: {
                    roles: ['Indicator', 'Acid', 'Base'],
                    summary: 'Indicator, Acid, and Base',
                },
                Concentration: {
                    types: ['liquid', 'granule'],
                    summary: 'liquid and granule types',
                },
                Carbonation: {
                    roles: ['Carbonate', 'Acid'],
                    summary: 'Carbonate and Acid',
                },
                Endothermic: {
                    roles: ['Neutral', 'Salt'],
                    summary: 'Neutral and Salt',
                },
                Exothermic: {
                    roles: ['Neutral', 'Base'],
                    summary: 'Neutral and Base',
                },
                Precipitation: {
                    roles: ['Acid', 'Salt'],
                    summary: 'Acid and Salt',
                },
                // Diffusion: no gate
            },
            apparatusCatalog: [
                { key: 'beaker', displayName: 'Beaker', defaultMaxVol: 250 },
                { key: 'test_tube', displayName: 'Test Tube', defaultMaxVol: 50 },
                { key: 'test_tube_rack', displayName: 'Test Tube Rack', defaultMaxVol: 6 },
                { key: 'stirring_rod', displayName: 'Stirring Rod', defaultMaxVol: 0 },
                { key: 'conical_flask', displayName: 'Conical Flask', defaultMaxVol: 250 },
                { key: 'pipette', displayName: 'Pipette', defaultMaxVol: 10 },
            ],
            async init() {
                await this.waitModule();
                await this.refreshAll();
                this.$nextTick(() => this.renderQr());
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
            async refreshAll() {
                this.loading = true;
                this.error = '';
                try {
                    this.classData = await window.TeacherClassroom.loadClass(this.classId);
                    await Promise.all([this.loadStream(), this.loadActivities()]);
                    this.$nextTick(() => this.renderQr());
                } catch (e) {
                    console.error(e);
                    this.error = e.message || 'Failed to load class.';
                } finally {
                    this.loading = false;
                }
            },
            async loadStream() {
                this.announcements = await window.TeacherClassroom.fetchAnnouncements(this.classId);
            },
            async loadActivities() {
                this.activities = await window.TeacherClassroom.fetchClassActivities(this.classId);
            },
            async loadTemplates() {
                this.templatesLoading = true;
                try {
                    this.templates = await window.TeacherClassroom.fetchLabTemplates();
                } catch (e) {
                    console.error(e);
                    alert(e.message || 'Failed to load templates');
                } finally {
                    this.templatesLoading = false;
                }
            },
            async selectActivityTab(id) {
                this.activityTab = id;
                if (id === 'templates' && this.templates.length === 0) await this.loadTemplates();
            },
            regularActivities() {
                return this.activities.filter((a) => (a.type || 'regular') === 'regular');
            },
            labActivities() {
                return this.activities.filter((a) => a.type === 'lab');
            },
            async post() {
                this.posting = true;
                this.streamError = '';
                try {
                    const item = await window.TeacherClassroom.postAnnouncement(this.classId, this.draft, this.draftAttachments || []);
                    this.announcements = [item, ...this.announcements];
                    this.draft = '';
                    this.draftAttachments = [];
                } catch (e) {
                    this.streamError = e.message || 'Failed to post.';
                } finally {
                    this.posting = false;
                }
            },
            openDeleteAnnouncement(a) {
                this.deleteError = '';
                this.deleteConfirm = {
                    kind: 'announcement',
                    id: a.id,
                    title: 'Delete announcement?',
                    message: 'This removes the post from the class stream. Students will no longer see it.',
                };
            },
            openDeleteActivity(act) {
                this.deleteError = '';
                this.deleteConfirm = {
                    kind: 'activity',
                    id: act.id,
                    title: 'Delete activity?',
                    message: '“' + (act.title || 'Untitled') + '” will be removed from this class. Student submissions for it may also be affected.',
                };
            },
            cancelDelete() {
                if (this.deleteBusy) return;
                this.deleteConfirm = null;
                this.deleteError = '';
            },
            async confirmDelete() {
                if (!this.deleteConfirm || this.deleteBusy) return;
                this.deleteBusy = true;
                this.deleteError = '';
                const { kind, id } = this.deleteConfirm;
                try {
                    if (kind === 'announcement') {
                        await window.TeacherClassroom.deleteAnnouncement(this.classId, id);
                        this.announcements = this.announcements.filter((x) => x.id !== id);
                    } else if (kind === 'activity') {
                        await window.TeacherClassroom.deleteActivity(id);
                        this.activities = this.activities.filter((x) => x.id !== id);
                    }
                    this.deleteConfirm = null;
                } catch (e) {
                    this.deleteError = e.message || 'Delete failed.';
                } finally {
                    this.deleteBusy = false;
                }
            },
            // Backward-compatible names used in stream/activities partials
            removeAnnouncement(a) { this.openDeleteAnnouncement(a); },
            removeActivity(act) { this.openDeleteActivity(act); },
            /** Load template into form (shared by View / Use). */
            loadTplForm(t) {
                this.tplError = '';
                this.tplActive = t;
                this.tplSection = 'overview';
                this.tplForm = {
                    title: t.title || '',
                    labCategory: t.labCategory || 'Concentration',
                    description: t.description || '',
                    safetyTips: t.safetyTips || '',
                    apparatus: JSON.parse(JSON.stringify(t.apparatus || [])),
                    elements: JSON.parse(JSON.stringify(t.elements || [])),
                    steps: JSON.parse(JSON.stringify(t.steps || [])),
                    quiz: JSON.parse(JSON.stringify(t.quiz || [])),
                    attachments: JSON.parse(JSON.stringify(t.attachments || [])),
                };
                this.tplForm.steps.forEach((s) => {
                    if (!s._uid) s._uid = this._uid();
                    if (s._open === undefined) s._open = false;
                });
                this.tplForm.quiz.forEach((q, i) => {
                    if (!q._uid) q._uid = this._uid();
                    if (q._open === undefined) q._open = false;
                    if (!q.id) q.id = 'q' + (i + 1);
                    if (!Array.isArray(q.options) || q.options.length === 0) {
                        q.options = [
                            { id: 'A', text: '' }, { id: 'B', text: '' },
                            { id: 'C', text: '' }, { id: 'D', text: '' },
                        ];
                    }
                });
            },
            openTplView(t) {
                this.loadTplForm(t);
                this.tplModal = 'view';
            },
            /** Edit draft class lab content (overview / apparatus / elements / steps / quiz). */
            openLabEdit(act) {
                if (!act || this.isPublished(act)) return;
                this.loadTplForm(act);
                this.tplActive = act;
                this.tplModal = 'edit-lab';
            },
            async saveLabContent() {
                if (this.tplModal !== 'edit-lab' || !this.tplActive?.id) return;
                this.tplSaving = true;
                this.tplError = '';
                try {
                    const contentGate = this.labContentGate();
                    if (!contentGate.ok) {
                        this.tplError = contentGate.message;
                        this.tplSection = contentGate.section;
                        return;
                    }
                    const form = this.normalizeTplForm();
                    if (!form.title) {
                        this.tplError = 'Title is required.';
                        this.tplSection = 'overview';
                        return;
                    }
                    const materials = [
                        ...(form.apparatus || []).map((a) => a.displayName || a.key || 'Apparatus'),
                        ...(form.elements || []).map((e) => {
                            const name = e.substanceName || 'Substance';
                            return e.formula ? (name + ' (' + e.formula + ')') : name;
                        }),
                    ];
                    const patch = {
                        title: form.title,
                        description: form.description,
                        labCategory: form.labCategory,
                        safetyTips: form.safetyTips,
                        apparatus: form.apparatus,
                        elements: form.elements,
                        steps: form.steps,
                        quiz: form.quiz,
                        attachments: form.attachments || [],
                        maxScore: (form.quiz || []).length || 0,
                        labExperiment: {
                            labCategory: form.labCategory,
                            materials,
                            safetyPrecautions: form.safetyTips ? [form.safetyTips] : [],
                            apparatus: form.apparatus,
                            elements: form.elements,
                            steps: form.steps,
                        },
                    };
                    await window.TeacherClassroom.updateClassActivity(this.tplActive.id, patch);
                    this.activities = this.activities.map((a) =>
                        a.id === this.tplActive.id ? { ...a, ...patch } : a
                    );
                    this.closeTplModal();
                } catch (e) {
                    console.error(e);
                    this.tplError = e.message || 'Save failed.';
                } finally {
                    this.tplSaving = false;
                }
            },
            askUseTemplate(t) {
                if (!t) return;
                this.useError = '';
                this.useConfirm = t;
            },
            cancelUseTemplate() {
                this.useConfirm = null;
                this.useBusy = false;
                this.useError = '';
            },
            /** Use = copy template into this class as an unpublished draft. */
            async confirmUseTemplate() {
                const t = this.useConfirm;
                if (!t || this.useBusy) return;
                this.useBusy = true;
                this.useError = '';
                try {
                    this.loadTplForm(t);
                    const form = this.normalizeTplForm();
                    const created = await window.TeacherClassroom.assignLabFromTemplate(
                        this.classId,
                        form,
                        t.id || null
                    );
                    this.activities = [created, ...this.activities];
                    this.useConfirm = null;
                    this.activityTab = 'lab';
                    this.tab = 'activities';
                } catch (e) {
                    console.error(e);
                    this.useError = e.message || 'Could not add draft.';
                } finally {
                    this.useBusy = false;
                }
            },
            /** Blank lab draft (not from a template) for this class. */
            async createBlankLab() {
                try {
                    const form = {
                        title: 'Untitled lab',
                        labCategory: 'Concentration',
                        description: '',
                        safetyTips: '',
                        apparatus: [],
                        elements: [],
                        steps: [],
                        quiz: [],
                        dueDate: '',
                        dueTime: '',
                    };
                    const created = await window.TeacherClassroom.assignLabFromTemplate(
                        this.classId,
                        form,
                        null
                    );
                    this.activities = [created, ...this.activities];
                    this.activityTab = 'lab';
                    this.tab = 'activities';
                    this.openLabEdit(created);
                } catch (e) {
                    console.error(e);
                    alert(e.message || 'Could not create lab draft.');
                }
            },
            closeTplModal() {
                this.tplModal = null;
                this.tplActive = null;
                this.tplError = '';
                this.tplSaving = false;
            },
            tplReadOnly() {
                return this.tplModal === 'view';
            },
            tplModalTitle() {
                if (this.tplModal === 'view') return 'View template';
                if (this.tplModal === 'edit-lab') return 'Edit lab activity';
                return 'Template';
            },
            tplModalSubtitle() {
                if (this.tplModal === 'view') return 'Read-only catalog entry. Choose Use on the card to add a draft to this class.';
                if (this.tplModal === 'edit-lab') return 'Edit overview, apparatus, elements, steps, and quiz for this class draft. Assignment deadline is set on the activity page.';
                return '';
            },
            isPublished(act) {
                return act && (act.published === true || act.status === 'published');
            },
            openActivity(act) {
                if (!act?.id) return;
                window.location.href = this.activityShowUrl(act.id);
            },
            emptyRegQuestion() {
                return {
                    _uid: this._uid(),
                    _open: true,
                    prompt: '',
                    correctOption: 'a',
                    explanation: '',
                    options: [
                        { id: 'a', text: '' },
                        { id: 'b', text: '' },
                        { id: 'c', text: '' },
                        { id: 'd', text: '' },
                    ],
                };
            },
            openRegCreate() {
                this.regError = '';
                this.regActive = null;
                this.regForm = { title: '', description: '', questions: [], attachments: [] };
                this.regModal = 'create';
            },
            openRegEdit(act) {
                if (!act || this.isPublished(act)) return;
                this.regError = '';
                this.regActive = act;
                const qs = act.quiz?.questions || [];
                this.regForm = {
                    title: act.title || '',
                    description: act.description || '',
                    attachments: JSON.parse(JSON.stringify(act.attachments || [])),
                    questions: JSON.parse(JSON.stringify(qs)).map((q) => ({
                        _uid: this._uid(),
                        _open: false,
                        prompt: q.prompt || '',
                        correctOption: (q.correctOption || 'a').toLowerCase(),
                        explanation: q.explanation || '',
                        options: (q.options && q.options.length)
                            ? q.options.map((o) => ({ id: (o.id || '').toLowerCase(), text: o.text || '' }))
                            : [
                                { id: 'a', text: '' }, { id: 'b', text: '' },
                                { id: 'c', text: '' }, { id: 'd', text: '' },
                            ],
                    })),
                };
                this.regModal = 'edit';
            },
            closeRegModal() {
                this.regModal = null;
                this.regActive = null;
                this.regError = '';
                this.regSaving = false;
                this.regUploading = false;
                this.regDriveLink = '';
                this.regDriveName = '';
            },
            addRegQuestion() {
                this.regForm.questions.push(this.emptyRegQuestion());
            },
            removeRegQuestion(i) {
                this.regForm.questions.splice(i, 1);
            },
            formatBytes(n) {
                const b = Number(n) || 0;
                if (b < 1024) return b + ' B';
                if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB';
                return (b / (1024 * 1024)).toFixed(1) + ' MB';
            },
            /** Google Picker popup (Classroom-style): browse Drive or upload, then save link. */

            async onStreamFileSelected(event) {
                const file = event.target?.files?.[0];
                event.target.value = '';
                if (!file) return;
                this.streamError = '';
                this.streamUploading = true;
                try {
                    const meta = await window.TeacherClassroom.uploadAnnouncementFile(this.classId, file);
                    this.draftAttachments = [...(this.draftAttachments || []), meta];
                } catch (e) {
                    console.error(e);
                    this.streamError = e.message || 'Could not upload file.';
                } finally {
                    this.streamUploading = false;
                }
            },
            async onTplFileSelected(event) {
                const file = event.target?.files?.[0];
                event.target.value = '';
                if (!file || !this.tplActive?.id || this.tplModal !== 'edit-lab') return;
                this.tplError = '';
                this.tplUploading = true;
                try {
                    const meta = await window.TeacherClassroom.uploadActivityAttachment(this.tplActive.id, file);
                    this.tplForm.attachments = [...(this.tplForm.attachments || []), meta];
                    this.activities = this.activities.map((a) =>
                        a.id === this.tplActive.id ? { ...a, attachments: this.tplForm.attachments } : a
                    );
                } catch (e) {
                    console.error(e);
                    this.tplError = e.message || 'Could not upload file.';
                } finally {
                    this.tplUploading = false;
                }
            },
            async removeTplAttachment(index) {
                const list = this.tplForm.attachments || [];
                const item = list[index];
                if (!item || !this.tplActive?.id) return;
                try {
                    if (item.storagePath) {
                        await window.TeacherClassroom.deleteActivityAttachment(this.tplActive.id, item.storagePath);
                    }
                    this.tplForm.attachments = list.filter((_, i) => i !== index);
                    this.activities = this.activities.map((a) =>
                        a.id === this.tplActive.id ? { ...a, attachments: this.tplForm.attachments } : a
                    );
                } catch (e) {
                    console.error(e);
                    this.tplError = e.message || 'Could not remove file.';
                }
            },
            async onRegFileSelected(event) {
                const file = event.target?.files?.[0];
                event.target.value = '';
                if (!file) return;
                this.regError = '';
                this.regUploading = true;
                try {
                    if (!this.regActive?.id) {
                        const title = (this.regForm.title || '').trim() || 'Untitled activity';
                        const created = await window.TeacherClassroom.createRegularActivity(this.classId, {
                            title,
                            description: (this.regForm.description || '').trim(),
                            questions: [],
                            attachments: [],
                        });
                        this.regActive = created;
                        this.regModal = 'edit';
                        this.activities = [created, ...this.activities];
                    }
                    const meta = await window.TeacherClassroom.uploadActivityAttachment(this.regActive.id, file);
                    this.regForm.attachments = [...(this.regForm.attachments || []), meta];
                    this.activities = this.activities.map((a) =>
                        a.id === this.regActive.id
                            ? { ...a, attachments: this.regForm.attachments }
                            : a
                    );
                } catch (e) {
                    console.error(e);
                    this.regError = e.message || 'Could not upload file.';
                } finally {
                    this.regUploading = false;
                }
            },
            async removeRegAttachment(index) {
                const list = this.regForm.attachments || [];
                const item = list[index];
                if (!item) return;
                try {
                    if (this.regActive?.id && item.storagePath) {
                        await window.TeacherClassroom.deleteActivityAttachment(this.regActive.id, item.storagePath);
                    }
                    this.regForm.attachments = list.filter((_, i) => i !== index);
                    this.activities = this.activities.map((a) =>
                        a.id === this.regActive?.id ? { ...a, attachments: this.regForm.attachments } : a
                    );
                } catch (e) {
                    console.error(e);
                    this.regError = e.message || 'Could not remove file.';
                }
            },
            async saveRegularActivity() {
                this.regSaving = true;
                this.regError = '';
                try {
                    const title = (this.regForm.title || '').trim();
                    if (!title) {
                        this.regError = 'Title is required.';
                        return;
                    }
                    const questions = (this.regForm.questions || []).map((q, i) => ({
                        prompt: (q.prompt || '').trim(),
                        order: i,
                        correctOption: (q.correctOption || 'a').toLowerCase(),
                        explanation: (q.explanation || '').trim(),
                        options: (q.options || []).map((o) => ({
                            id: (o.id || '').toLowerCase(),
                            text: (o.text || '').trim(),
                        })),
                    }));
                    const attachments = this.regForm.attachments || [];
                    if (this.regModal === 'edit' && this.regActive?.id) {
                        const patch = {
                            title,
                            description: (this.regForm.description || '').trim(),
                            quiz: { questions },
                            attachments,
                            maxScore: questions.length || 0,
                        };
                        await window.TeacherClassroom.updateClassActivity(this.regActive.id, patch);
                        this.activities = this.activities.map((a) =>
                            a.id === this.regActive.id ? { ...a, ...patch } : a
                        );
                    } else {
                        const created = await window.TeacherClassroom.createRegularActivity(this.classId, {
                            title,
                            description: (this.regForm.description || '').trim(),
                            questions,
                            attachments,
                        });
                        this.activities = [created, ...this.activities];
                    }
                    this.closeRegModal();
                    this.activityTab = 'regular';
                    this.tab = 'activities';
                } catch (e) {
                    console.error(e);
                    this.regError = e.message || 'Save failed.';
                } finally {
                    this.regSaving = false;
                }
            },
            addApparatus() {
                const used = new Set((this.tplForm.apparatus || []).map((a) => a.key));
                const pick = this.apparatusCatalog.find((c) => !used.has(c.key)) || this.apparatusCatalog[0];
                this.tplForm.apparatus.push({
                    key: pick.key,
                    displayName: pick.displayName,
                    maxVol: pick.defaultMaxVol,
                });
            },
            onApparatusKeyChange(a) {
                const cat = this.apparatusCatalog.find((c) => c.key === a.key);
                if (cat) {
                    a.displayName = cat.displayName;
                    if (a.maxVol === 0 || a.maxVol === '' || a.maxVol == null) {
                        a.maxVol = cat.defaultMaxVol;
                    }
                }
            },
            removeApparatus(i) { this.tplForm.apparatus.splice(i, 1); },
            apparatusNames() {
                return (this.tplForm.apparatus || [])
                    .map((a) => a.displayName || a.key)
                    .filter(Boolean);
            },
            /** Options for step element picker — one row per element, labels show substance name. */
            elementOptions() {
                const list = this.tplForm.elements || [];
                const formulaCount = {};
                list.forEach((e) => {
                    const f = (e.formula || '').trim();
                    if (f) formulaCount[f] = (formulaCount[f] || 0) + 1;
                });
                return list.map((e, i) => {
                    const formula = (e.formula || '').trim();
                    const name = (e.substanceName || '').trim();
                    const value = formula || name;
                    if (!value) return null;
                    let label;
                    if (name && formula) {
                        label = name + ' (' + formula + ')';
                    } else {
                        label = name || formula || ('Element ' + (i + 1));
                    }
                    // Disambiguate duplicate formulas (e.g. three H2O) with volume / index
                    if (formula && formulaCount[formula] > 1) {
                        const vol = e.volume != null && e.volume !== '' ? e.volume + ' mL' : null;
                        const mol = e.molarity != null && e.molarity !== '' && Number(e.molarity) !== 0
                            ? Number(e.molarity) + ' M' : null;
                        const extra = [vol, mol].filter(Boolean).join(', ');
                        if (extra) label += ' · ' + extra;
                        else label += ' · #' + (i + 1);
                    }
                    return { value, label };
                }).filter(Boolean);
            },
            elementFormulas() {
                // legacy helper — unique values still used where a flat list is enough
                return this.elementOptions().map((o) => o.value);
            },
            /**
             * Lab draft must have: ≥1 apparatus, elements (category gate), ≥3 steps, ≥1 quiz item.
             */
            labContentGate() {
                const apps = (this.tplForm.apparatus || []).filter((a) => (a.key || '').trim());
                if (apps.length < 1) {
                    return {
                        ok: false,
                        section: 'apparatus',
                        message: 'Add at least one apparatus before saving.',
                    };
                }

                const els = (this.tplForm.elements || []).filter((e) =>
                    (e.substanceName || '').trim() || (e.formula || '').trim()
                );
                if (els.length < 1) {
                    return {
                        ok: false,
                        section: 'elements',
                        message: 'Add at least one element before saving.',
                    };
                }

                const eg = this.elementGate();
                if (!eg.ok) {
                    return {
                        ok: false,
                        section: 'elements',
                        message: 'Elements incomplete for ' + eg.category
                            + ': need ' + eg.summary
                            + '. Missing: ' + eg.missing.join(', ') + '.',
                    };
                }

                const steps = (this.tplForm.steps || []).filter((s) =>
                    (s.instruction || '').trim() || (s.action || '').trim()
                );
                if (steps.length < 3) {
                    return {
                        ok: false,
                        section: 'steps',
                        message: 'Add at least three steps before saving (currently '
                            + steps.length + ').',
                    };
                }

                const quiz = (this.tplForm.quiz || []).filter((q) => (q.prompt || '').trim());
                if (quiz.length < 1) {
                    return {
                        ok: false,
                        section: 'quiz',
                        message: 'Add at least one quiz question with a prompt before saving.',
                    };
                }
                for (const q of quiz) {
                    const opts = (q.options || []).filter((o) => (o.text || '').trim());
                    if (opts.length < 2) {
                        return {
                            ok: false,
                            section: 'quiz',
                            message: 'Each quiz question needs at least two answer choices filled in.',
                        };
                    }
                    if (!(q.correctOptionId || '').trim()) {
                        return {
                            ok: false,
                            section: 'quiz',
                            message: 'Each quiz question needs a correct answer selected.',
                        };
                    }
                }

                return { ok: true, section: 'overview', message: '' };
            },
            /** Validate elements against current lab category requirements. */
            elementGate() {
                const cat = this.tplForm.labCategory;
                const rule = this.elementCategoryRules[cat];
                if (!rule) {
                    return { ok: true, missing: [], summary: '', category: cat };
                }
                const els = this.tplForm.elements || [];
                const roles = new Set(els.map((e) => e.role).filter(Boolean));
                const types = new Set(els.map((e) => e.type).filter(Boolean));
                const missing = [];
                (rule.roles || []).forEach((r) => {
                    if (!roles.has(r)) missing.push(r + ' (role)');
                });
                (rule.types || []).forEach((t) => {
                    if (!types.has(t)) missing.push(t + ' (type)');
                });
                if (rule.typesOneOf && rule.typesOneOf.length) {
                    const hit = rule.typesOneOf.some((t) => types.has(t));
                    if (!hit) missing.push(rule.typesOneOf.join(' or ') + ' (type)');
                }
                return {
                    ok: missing.length === 0,
                    missing,
                    summary: rule.summary || '',
                    category: cat,
                };
            },
            colorToPicker(hex) {
                if (!hex || typeof hex !== 'string') return '#80B0E0';
                const h = hex.trim();
                if (h.startsWith('#') && h.length >= 7) return h.slice(0, 7);
                return '#80B0E0';
            },
            onColorPick(e, event) {
                const rgb = event.target.value; // #rrggbb
                const prev = (e.colorHex || '#80B0E0FF').trim();
                const alpha = prev.length >= 9 ? prev.slice(7, 9) : 'FF';
                e.colorHex = rgb.toUpperCase() + alpha.toUpperCase();
            },
            addElement() {
                this.tplForm.elements.push({
                    substanceName: '', formula: '', type: 'liquid', role: 'Neutral',
                    minVol: 10, maxVol: 100, volume: 50, molarity: 0, temperature: 25,
                    colorHex: '#80B0E0FF',
                });
            },
            removeElement(i) { this.tplForm.elements.splice(i, 1); },
            _uid() { return 'u' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7); },
            addStep() {
                const apps = this.apparatusNames();
                const els = this.elementOptions();
                this.tplForm.steps.push({
                    _uid: this._uid(),
                    _open: true,
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
            removeStep(i) { this.tplForm.steps.splice(i, 1); },
            moveStep(i, dir) {
                const j = i + dir;
                const arr = this.tplForm.steps;
                if (j < 0 || j >= arr.length) return;
                const item = arr.splice(i, 1)[0];
                arr.splice(j, 0, item);
            },
            addQuiz() {
                const n = this.tplForm.quiz.length + 1;
                this.tplForm.quiz.push({
                    _uid: this._uid(),
                    _open: true,
                    id: 'q' + n,
                    prompt: '',
                    correctOptionId: 'A',
                    options: [
                        { id: 'A', text: '' }, { id: 'B', text: '' },
                        { id: 'C', text: '' }, { id: 'D', text: '' },
                    ],
                });
            },
            removeQuiz(i) {
                this.tplForm.quiz.splice(i, 1);
                this.tplForm.quiz.forEach((q, idx) => { q.id = 'q' + (idx + 1); });
            },
            moveQuiz(i, dir) {
                const j = i + dir;
                const arr = this.tplForm.quiz;
                if (j < 0 || j >= arr.length) return;
                const item = arr.splice(i, 1)[0];
                arr.splice(j, 0, item);
                arr.forEach((q, idx) => { q.id = 'q' + (idx + 1); });
            },
            normalizeTplForm() {
                const form = JSON.parse(JSON.stringify(this.tplForm));
                form.title = (form.title || '').trim();
                form.labCategory = (form.labCategory || 'Concentration').trim();
                form.description = (form.description || '').trim();
                form.safetyTips = (form.safetyTips || '').trim();
                form.apparatus = (form.apparatus || []).map((a) => ({
                    key: (a.key || '').trim(),
                    displayName: (a.displayName || '').trim(),
                    maxVol: Number(a.maxVol) || 0,
                }));
                form.elements = (form.elements || []).map((e) => ({
                    substanceName: (e.substanceName || '').trim(),
                    formula: (e.formula || '').trim(),
                    type: e.type || 'liquid',
                    role: e.role || 'Neutral',
                    minVol: Number(e.minVol) || 0,
                    maxVol: Number(e.maxVol) || 0,
                    volume: Number(e.volume) || 0,
                    molarity: Number(e.molarity) || 0,
                    temperature: Number(e.temperature) || 25,
                    colorHex: (e.colorHex || '#80B0E0FF').trim(),
                }));
                form.steps = (form.steps || []).map((s) => ({
                    instruction: (s.instruction || '').trim(),
                    action: s.action || 'PlaceApparatus',
                    elementFormula: (s.elementFormula || '').trim(),
                    targetApparatusName: (s.targetApparatusName || '').trim(),
                    sourceApparatusName: (s.sourceApparatusName || '').trim(),
                    targetVolume: Number(s.targetVolume) || 0,
                    waitSeconds: Number(s.waitSeconds) || 0,
                    reactionColorHex: (s.reactionColorHex || '').trim(),
                }));
                form.quiz = (form.quiz || []).map((q, i) => ({
                    id: q.id || ('q' + (i + 1)),
                    prompt: (q.prompt || '').trim(),
                    correctOptionId: q.correctOptionId || 'A',
                    options: (q.options || []).map((o) => ({
                        id: o.id,
                        text: (o.text || '').trim(),
                    })),
                }));
                return form;
            },
            students() {
                return Array.isArray(this.classData?.students) ? this.classData.students : [];
            },
            studentLabel(s) {
                if (!s) return 'Student';
                if (typeof s === 'string') return s;
                return s.name || s.displayName || s.email || s.uid || 'Student';
            },
            studentSub(s) {
                if (!s || typeof s === 'string') return '';
                return s.email || s.uid || '';
            },
            formatDate(iso) {
                if (!iso) return '';
                try { return new Date(iso).toLocaleString(); }
                catch { return iso; }
            },
            renderQr() {
                const code = this.classData?.access_code;
                if (!code || typeof QRCode === 'undefined') return;
                const elModal = document.getElementById('class-qr-modal');
                if (elModal) {
                    elModal.innerHTML = '';
                    new QRCode(elModal, { text: String(code), width: 220, height: 220, correctLevel: QRCode.CorrectLevel.M });
                }
            },
            openQr() {
                this.showQr = true;
                this.$nextTick(() => this.renderQr());
            },
            selectTab(id) {
                this.tab = id;
                if (id === 'activities' && this.activityTab === 'templates' && this.templates.length === 0) {
                    this.loadTemplates();
                }
            },
            activityShowUrl(activityId) {
                return @js(url('/classes')) + '/' + this.classId + '/activities/' + activityId;
            },
         }"
>
        <div x-show="loading" class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-xs font-semibold text-slate-500">Loading class…</div>
        <div x-show="!loading && error" x-cloak class="rounded-2xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-600" x-text="error"></div>

        <template x-if="!loading && !error && classData">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:gap-8">
                {{-- Left sidebar --}}
                <aside class="w-full shrink-0 lg:sticky lg:top-20 lg:w-64 lg:self-start">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                        <a href="{{ route('dashboard') }}?tab=classes"
                           class="mb-4 inline-flex text-[11px] font-bold text-slate-400 transition hover:text-[#50aed0]">← Classes</a>

                        <h1 class="text-lg font-black leading-snug text-slate-800" x-text="classData.section_name"></h1>
                        <p class="mt-1 text-xs font-semibold text-slate-500">
                            <span x-text="classData.grade_level || '—'"></span>
                            <template x-if="classData.subject">
                                <span>
                                    <span class="text-slate-300"> · </span>
                                    <span x-text="classData.subject"></span>
                                </span>
                            </template>
                        </p>

                        <div class="mt-4 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Access code</p>
                            <p class="mt-0.5 font-mono text-base font-black tracking-wider text-[#50aed0]" x-text="classData.access_code"></p>
                        </div>

                        <button type="button" @click="openQr()"
                                class="mt-3 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:border-[#7CD5F3] hover:bg-[#7CD5F3]/10 hover:text-[#439BBB]">
                            Show QR / code
                        </button>

                        <nav class="mt-5 space-y-1 border-t border-slate-100 pt-4">
                            <p class="mb-2 px-2 text-[10px] font-bold uppercase tracking-wide text-slate-400">Navigate</p>
                            <template x-for="t in [
                                { id: 'stream', label: 'Stream' },
                                { id: 'activities', label: 'Activities' },
                                { id: 'people', label: 'Students' },
                            ]" :key="t.id">
                                <button type="button"
                                        @click="selectTab(t.id)"
                                        class="flex w-full items-center rounded-xl px-3 py-2.5 text-left text-xs font-bold transition"
                                        :class="tab === t.id
                                            ? 'bg-[#7CD5F3]/20 text-[#2a7a96] ring-1 ring-[#7CD5F3]/40'
                                            : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800'">
                                    <span x-text="t.label"></span>
                                </button>
                            </template>
                        </nav>
                    </div>
                </aside>

                {{-- Main panel --}}
                <main class="min-w-0 flex-1 space-y-4">
                    <div class="hidden items-center justify-between sm:flex lg:hidden">
                        <p class="text-sm font-black text-slate-800" x-text="classData.section_name"></p>
                    </div>

                    @include('teacher.classroom.stream')
                    @include('teacher.classroom.activities')
                    @include('teacher.classroom.students')
                </main>
            </div>
        </template>

        @include('teacher.classroom.template-editor')
        @include('teacher.classroom.regular-editor')
        @include('teacher.classroom.qr-modal')


        {{-- Confirm: Delete announcement / activity --}}
        <div x-show="deleteConfirm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-slate-900/40" @click="cancelDelete()"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
                <h3 class="text-base font-black text-slate-800" x-text="deleteConfirm?.title || 'Delete?'"></h3>
                <p class="mt-2 text-xs leading-relaxed text-slate-600" x-text="deleteConfirm?.message || ''"></p>
                <p class="mt-2 text-[11px] text-slate-400">This cannot be undone.</p>
                <p x-show="deleteError" x-text="deleteError" class="mt-3 text-xs font-bold text-red-500"></p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="cancelDelete()" :disabled="deleteBusy"
                            class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 disabled:opacity-50">
                        Cancel
                    </button>
                    <button type="button" @click="confirmDelete()" :disabled="deleteBusy"
                            class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-700 disabled:opacity-50">
                        <span x-text="deleteBusy ? 'Deleting…' : 'Delete'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Confirm: Use template → draft --}}
        <div x-show="useConfirm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-slate-900/40" @click="cancelUseTemplate()"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
                <h3 class="text-base font-black text-slate-800">Add template as draft?</h3>
                <p class="mt-2 text-xs leading-relaxed text-slate-600">
                    <span class="font-bold text-slate-800" x-text="useConfirm?.title || 'This template'"></span>
                    will be copied into this class’s
                    <span class="font-semibold">Lab activities</span> as an unpublished draft.
                    Students will not see it until you set a deadline and publish.
                </p>
                <p class="mt-2 text-[11px] text-slate-400">The shared template catalog will not be changed.</p>
                <p x-show="useError" x-text="useError" class="mt-3 text-xs font-bold text-red-500"></p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="cancelUseTemplate()" :disabled="useBusy"
                            class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 disabled:opacity-50">
                        Cancel
                    </button>
                    <button type="button" @click="confirmUseTemplate()" :disabled="useBusy"
                            class="rounded-xl bg-[#7CD5F3] px-4 py-2 text-xs font-bold text-white hover:bg-[#68cbed] disabled:opacity-50">
                        <span x-text="useBusy ? 'Adding…' : 'Add draft'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @php
        $googlePickerConfig = [
            'clientId' => config('services.google.client_id'),
            'apiKey' => config('services.google.api_key'),
            'appId' => config('services.google.app_id'),
        ];
    @endphp
    <script>
        window.__googlePickerConfig = @json($googlePickerConfig);
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script type="module" src="{{ asset('js/teacher/classroom.js') }}"></script>
    <script type="module" src="{{ asset('js/teacher/google-drive-picker.js') }}"></script>
@endpush
