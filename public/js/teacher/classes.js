/**
 * Teacher classes — Supabase via Laravel API (migration Step 5).
 * Same window.TeacherClasses surface as the old Firestore module.
 */
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || "";
}

async function api(path, options = {}) {
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

export async function fetchTeacherClasses() {
    return api("/api/teacher/classes");
}

export async function createClass(form) {
    return api("/api/teacher/classes", {
        method: "POST",
        body: JSON.stringify({
            section_name: form.section_name,
            grade_level: form.grade_level,
            subject: form.subject,
            access_code: form.access_code,
        }),
    });
}

export async function updateClass(id, form) {
    return api(`/api/teacher/classes/${id}`, {
        method: "PATCH",
        body: JSON.stringify({
            section_name: form.section_name,
            grade_level: form.grade_level,
            subject: form.subject,
            access_code: form.access_code,
        }),
    });
}

export async function deleteClass(id) {
    return api(`/api/teacher/classes/${id}`, { method: "DELETE" });
}

/** Used by tab-classes openCreate() to prefill the access code field. */
export function generateAccessCode() {
    const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    let code = "";
    for (let i = 0; i < 6; i++) {
        code += chars[Math.floor(Math.random() * chars.length)];
    }
    return code;
}

export async function fetchNotifications() {
    return api("/api/teacher/notifications");
}

export async function markNotificationRead(id) {
    return api(`/api/teacher/notifications/${id}/read`, { method: "PATCH" });
}

window.TeacherClasses = {
    fetchTeacherClasses,
    createClass,
    updateClass,
    deleteClass,
    generateAccessCode,
    fetchNotifications,
    markNotificationRead,
};
