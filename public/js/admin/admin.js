function csrfToken() {
    return document.querySelector('meta[name=csrf-token]')?.content || '';
}

async function api(url, options = {}) {
    const res = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.headers || {}),
        },
        ...options,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        throw new Error(data.message || data.error || `Request failed (${res.status})`);
    }
    return data;
}

export async function listTeachers() {
    return api('/api/admin/teachers');
}
export async function showTeacher(id) {
    return api('/api/admin/teachers/' + encodeURIComponent(id));
}
export async function listStudents() {
    return api('/api/admin/students');
}
export async function listUnassignedStudents() {
    return api('/api/admin/students/unassigned');
}
export async function createClass(body) {
    return api('/api/admin/classes', { method: 'POST', body: JSON.stringify(body) });
}
export async function updateClass(id, body) {
    return api('/api/admin/classes/' + encodeURIComponent(id), {
        method: 'PATCH',
        body: JSON.stringify(body),
    });
}
export async function deleteClass(id) {
    return api('/api/admin/classes/' + encodeURIComponent(id), { method: 'DELETE' });
}
export async function createUser(body) {
    return api('/api/admin/users', { method: 'POST', body: JSON.stringify(body) });
}
export async function updateUserRole(id, body) {
    return api('/api/admin/users/' + encodeURIComponent(id) + '/role', {
        method: 'PATCH',
        body: JSON.stringify(body),
    });
}
export async function updateUserEmail(id, body) {
    return api('/api/admin/users/' + encodeURIComponent(id) + '/email', {
        method: 'PATCH',
        body: JSON.stringify(body),
    });
}
export async function deleteUser(id) {
    return api('/api/admin/users/' + encodeURIComponent(id), { method: 'DELETE' });
}
export async function classStudents(classId) {
    return api('/api/admin/classes/' + encodeURIComponent(classId) + '/students');
}
export async function addClassStudent(classId, body) {
    return api('/api/admin/classes/' + encodeURIComponent(classId) + '/students', {
        method: 'POST',
        body: JSON.stringify(body),
    });
}
export async function removeClassStudent(classId, userId) {
    return api('/api/admin/classes/' + encodeURIComponent(classId) + '/students/' + encodeURIComponent(userId), {
        method: 'DELETE',
    });
}
export async function listTemplates() {
    return api('/api/admin/lab-templates');
}
export async function showTemplate(id) {
    return api('/api/admin/lab-templates/' + encodeURIComponent(id));
}
export async function createTemplate(body) {
    return api('/api/admin/lab-templates', { method: 'POST', body: JSON.stringify(body) });
}
export async function updateTemplate(id, body) {
    return api('/api/admin/lab-templates/' + encodeURIComponent(id), {
        method: 'PUT',
        body: JSON.stringify(body),
    });
}
export async function deleteTemplate(id) {
    return api('/api/admin/lab-templates/' + encodeURIComponent(id), { method: 'DELETE' });
}

window.AdminApi = {
    listTeachers,
    showTeacher,
    listStudents,
    listUnassignedStudents,
    createUser,
    updateUserRole,
    updateUserEmail,
    deleteUser,
    createClass,
    updateClass,
    deleteClass,
    classStudents,
    addClassStudent,
    removeClassStudent,
    listTemplates,
    showTemplate,
    createTemplate,
    updateTemplate,
    deleteTemplate,
};
