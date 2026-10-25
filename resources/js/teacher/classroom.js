/**
 * Class workspace helpers — classes/{id}, announcements, classActivities, labActivities templates.
 */
import {
    doc,
    getDoc,
    collection,
    addDoc,
    query,
    where,
    orderBy,
    getDocs,
    deleteDoc,
    updateDoc,
} from "https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore.js";

function withDb(fn) {
    if (window.__db) return Promise.resolve(fn(window.__db));
    return new Promise((resolve, reject) => {
        const t = setTimeout(() => reject(new Error("Firebase not ready")), 8000);
        window.addEventListener(
            "firebase-ready",
            () => {
                clearTimeout(t);
                try {
                    resolve(fn(window.__db));
                } catch (e) {
                    reject(e);
                }
            },
            { once: true }
        );
    });
}

export async function loadClass(classId) {
    if (!classId) throw new Error("Missing class id.");
    return withDb(async (db) => {
        const snap = await getDoc(doc(db, "classes", classId));
        if (!snap.exists()) throw new Error("Class not found.");
        const data = { id: snap.id, ...snap.data() };
        const uid = window.__teacherUid;
        if (uid && data.teacher_uid && data.teacher_uid !== uid) {
            throw new Error("You do not own this class.");
        }
        return data;
    });
}

export async function fetchAnnouncements(classId) {
    return withDb(async (db) => {
        try {
            const q = query(
                collection(db, "classes", classId, "announcements"),
                orderBy("created_at", "desc")
            );
            const snap = await getDocs(q);
            return snap.docs.map((d) => ({ id: d.id, ...d.data() }));
        } catch {
            const snap = await getDocs(collection(db, "classes", classId, "announcements"));
            const list = snap.docs.map((d) => ({ id: d.id, ...d.data() }));
            list.sort((a, b) => (b.created_at || "").localeCompare(a.created_at || ""));
            return list;
        }
    });
}

export async function postAnnouncement(classId, body) {
    const text = (body || "").trim();
    if (!text) throw new Error("Announcement cannot be empty.");
    return withDb(async (db) => {
        const payload = {
            body: text,
            author_uid: window.__teacherUid || null,
            created_at: new Date().toISOString(),
        };
        const ref = await addDoc(collection(db, "classes", classId, "announcements"), payload);
        return { id: ref.id, ...payload };
    });
}

export async function deleteAnnouncement(classId, announcementId) {
    return withDb(async (db) => {
        await deleteDoc(doc(db, "classes", classId, "announcements", announcementId));
    });
}

export async function fetchClassActivities(classId) {
    return withDb(async (db) => {
        const q = query(
            collection(db, "classActivities"),
            where("classId", "==", classId)
        );
        const snap = await getDocs(q);
        const list = snap.docs.map((d) => ({ id: d.id, ...d.data() }));
        list.sort((a, b) => (b.created_at || "").localeCompare(a.created_at || ""));
        return list;
    });
}

export async function deleteActivity(activityId) {
    if (!activityId) throw new Error("Missing activity id.");
    return withDb(async (db) => {
        await deleteDoc(doc(db, "classActivities", activityId));
    });
}

/** Global lab templates — collection: labActivities */
export async function fetchLabTemplates() {
    return withDb(async (db) => {
        const snap = await getDocs(collection(db, "labActivities"));
        const list = snap.docs.map((d) => ({ id: d.id, ...d.data() }));
        list.sort((a, b) => (a.title || "").localeCompare(b.title || ""));
        return list;
    });
}

export async function createLabTemplate(form) {
    const title = (form.title || "").trim();
    if (!title) throw new Error("Title is required.");

    const labCategory = (form.labCategory || "Concentration").trim();
    const description = (form.description || "").trim();
    const safetyTips = (form.safetyTips || "").trim();

    const payload = {
        title,
        labCategory,
        description,
        safetyTips,
        apparatus: Array.isArray(form.apparatus) ? form.apparatus : [],
        elements: Array.isArray(form.elements) ? form.elements : [],
        steps: Array.isArray(form.steps) ? form.steps : [],
        quiz: Array.isArray(form.quiz) ? form.quiz : [],
        owner_uid: window.__teacherUid || null,
        created_at: new Date().toISOString(),
    };

    return withDb(async (db) => {
        const ref = await addDoc(collection(db, "labActivities"), payload);
        return { id: ref.id, ...payload };
    });
}

export async function updateLabTemplate(id, form) {
    if (!id) throw new Error("Missing template id.");
    const title = (form.title || "").trim();
    if (!title) throw new Error("Title is required.");

    const patch = {
        title,
        labCategory: (form.labCategory || "Concentration").trim(),
        description: (form.description || "").trim(),
        safetyTips: (form.safetyTips || "").trim(),
        apparatus: Array.isArray(form.apparatus) ? form.apparatus : [],
        elements: Array.isArray(form.elements) ? form.elements : [],
        steps: Array.isArray(form.steps) ? form.steps : [],
        quiz: Array.isArray(form.quiz) ? form.quiz : [],
        updated_at: new Date().toISOString(),
    };

    return withDb(async (db) => {
        await updateDoc(doc(db, "labActivities", id), patch);
        return { id, ...form, ...patch };
    });
}

export async function deleteLabTemplate(id) {
    if (!id) throw new Error("Missing template id.");
    return withDb(async (db) => {
        await deleteDoc(doc(db, "labActivities", id));
    });
}

window.TeacherClassroom = {
    loadClass,
    fetchAnnouncements,
    postAnnouncement,
    deleteAnnouncement,
    fetchClassActivities,
    deleteActivity,
    fetchLabTemplates,
    createLabTemplate,
    updateLabTemplate,
    deleteLabTemplate,
};
