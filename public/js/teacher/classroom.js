/**
 * Class workspace — Supabase via Laravel API (migration Step 5).
 * Keeps window.TeacherClassroom method names used by Blade/Alpine.
 */
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || "";
}

async function api(path, options = {}) {
    // Pull headers out so ...rest cannot wipe CSRF / Accept
    const { headers: optHeaders, ...rest } = options;
    const res = await fetch(path, {
        credentials: "same-origin",
        ...rest,
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken(),
            "X-Requested-With": "XMLHttpRequest",
            ...(optHeaders || {}),
        },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        throw new Error(data.message || data.error || `Request failed (${res.status})`);
    }
    return data;
}

export async function loadClass(classId) {
    return api(`/api/teacher/classes/${classId}`);
}

export async function fetchAnnouncements(classId) {
    return api(`/api/teacher/classes/${classId}/announcements`);
}

export async function postAnnouncement(classId, body, attachments = []) {
    return api(`/api/teacher/classes/${classId}/announcements`, {
        method: "POST",
        body: JSON.stringify({ body, attachments }),
    });
}

export async function uploadAnnouncementFile(classId, file) {
    const form = new FormData();
    form.append("file", file);
    const res = await fetch(`/api/teacher/classes/${classId}/announcement-files`, {
        method: "POST",
        credentials: "same-origin",
        headers: {
            Accept: "application/json",
            "X-CSRF-TOKEN": csrfToken(),
        },
        body: form,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        throw new Error(data.message || data.error || `Upload failed (${res.status})`);
    }
    return data.attachment || data;
}

export async function deleteAnnouncement(classId, announcementId) {
    return api(`/api/teacher/classes/${classId}/announcements/${announcementId}`, {
        method: "DELETE",
    });
}

export async function fetchClassActivities(classId) {
    return api(`/api/teacher/classes/${classId}/activities`);
}

export async function deleteActivity(activityId) {
    return api(`/api/teacher/activities/${activityId}`, { method: "DELETE" });
}

export async function fetchLabTemplates() {
    return api("/api/teacher/lab-templates");
}

export async function createLabTemplate() {
    throw new Error("Creating global templates from the class page is disabled.");
}
export async function updateLabTemplate() {
    throw new Error("Editing global templates from the class page is disabled.");
}
export async function deleteLabTemplate() {
    throw new Error("Deleting global templates from the class page is disabled.");
}

export async function createRegularActivity(classId, form) {
    const qs = Array.isArray(form.questions) ? form.questions : [];
    return api("/api/teacher/activities", {
        method: "POST",
        body: JSON.stringify({
            classId,
            type: "regular",
            title: form.title,
            description: form.description || "",
            quiz: { questions: qs },
            attachments: form.attachments || [],
            maxScore: qs.length || 0,
            dueDate: form.dueDate || "",
            dueTime: form.dueTime || "",
            published: false,
            status: "draft",
        }),
    });
}

export async function assignLabFromTemplate(classId, form, templateId) {
    const apparatus = Array.isArray(form.apparatus) ? form.apparatus : [];
    const elements = Array.isArray(form.elements) ? form.elements : [];
    const steps = Array.isArray(form.steps) ? form.steps : [];
    const quiz = Array.isArray(form.quiz) ? form.quiz : [];
    const safetyTips = form.safetyTips || "";
    const labCategory = form.labCategory || "";

    const materials = [
        ...apparatus.map((a) => a.displayName || a.key || "Apparatus"),
        ...elements.map((e) => {
            const name = e.substanceName || "Substance";
            return e.formula ? `${name} (${e.formula})` : name;
        }),
    ];

    return api("/api/teacher/activities", {
        method: "POST",
        body: JSON.stringify({
            classId,
            type: "lab",
            title: form.title,
            description: form.description || "",
            labCategory,
            templateId: templateId || null,
            safetyTips,
            apparatus,
            elements,
            steps,
            quiz,
            labExperiment: {
                labCategory,
                materials,
                safetyPrecautions: safetyTips ? [safetyTips] : [],
                apparatus,
                elements,
                steps,
            },
            maxScore: quiz.length || 0,
            dueDate: form.dueDate || "",
            dueTime: form.dueTime || "",
            published: false,
            status: "draft",
        }),
    });
}

export async function getClassActivity(activityId) {
    return api(`/api/teacher/activities/${activityId}`);
}

export async function updateClassActivity(activityId, patch) {
    return api(`/api/teacher/activities/${activityId}`, {
        method: "PATCH",
        body: JSON.stringify(patch),
    });
}

export async function publishClassActivity(activityId, extra = {}) {
    return api(`/api/teacher/activities/${activityId}`, {
        method: "PATCH",
        body: JSON.stringify({
            ...extra,
            published: true,
            status: "published",
            publishedAt: new Date().toISOString(),
        }),
    });
}

export async function unpublishClassActivity(activityId) {
    return api(`/api/teacher/activities/${activityId}`, {
        method: "PATCH",
        body: JSON.stringify({
            published: false,
            status: "draft",
        }),
    });
}

export async function returnActivitySubmission(activityId, submissionId, body) {
    return api(`/api/teacher/activities/${activityId}/submissions/${submissionId}/return`, {
        method: 'PATCH',
        body: JSON.stringify(body || {}),
    });
}

export async function fetchActivitySubmissions(activityId) {
    return api(`/api/teacher/activities/${activityId}/submissions`);
}


export async function uploadActivityAttachment(activityId, file) {
    const form = new FormData();
    form.append('file', file);
    const res = await fetch(`/api/teacher/activities/${activityId}/attachments`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: form,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        throw new Error(data.message || data.error || `Upload failed (${res.status})`);
    }
    return data.attachment || data;
}

export async function deleteActivityAttachment(activityId, storagePath) {
    return api(`/api/teacher/activities/${activityId}/attachments`, {
        method: 'DELETE',
        body: JSON.stringify({ storagePath }),
    });
}

export async function approveMember(classId, userId) {
    return api(`/api/teacher/classes/${classId}/members/${userId}/approve`, {
        method: 'POST',
        body: JSON.stringify({}),
    });
}

export async function rejectMember(classId, userId) {
    return api(`/api/teacher/classes/${classId}/members/${userId}/reject`, {
        method: 'POST',
        body: JSON.stringify({}),
    });
}

window.TeacherClassroom = {
    loadClass,
    fetchAnnouncements,
    postAnnouncement,
    uploadAnnouncementFile,
    deleteAnnouncement,
    fetchClassActivities,
    deleteActivity,
    fetchLabTemplates,
    createLabTemplate,
    updateLabTemplate,
    deleteLabTemplate,
    assignLabFromTemplate,
    createRegularActivity,
    getClassActivity,
    updateClassActivity,
    publishClassActivity,
    unpublishClassActivity,
    fetchActivitySubmissions,
    returnActivitySubmission,
    uploadActivityAttachment,
    deleteActivityAttachment,
    approveMember,
    rejectMember,
};
