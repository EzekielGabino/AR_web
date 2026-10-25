/**
 * Firestore helpers for teacher classes (collection: "classes").
 * Requires window.__db and window.__teacherUid from firebase-init.
 */
import {
    collection,
    query,
    where,
    getDocs,
    addDoc,
    updateDoc,
    deleteDoc,
    doc,
    orderBy,
} from "https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore.js";

function withDb(fn) {
    if (window.__db) return Promise.resolve(fn(window.__db));
    return new Promise((resolve, reject) => {
        const t = setTimeout(() => reject(new Error("Firebase not ready — check firebase-init / .env config")), 10000);
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

function waitForTeacherUid(timeoutMs = 10000) {
    if (window.__teacherUid) return Promise.resolve(window.__teacherUid);
    return new Promise((resolve, reject) => {
        const t = setTimeout(
            () => reject(new Error("Not signed in (missing teacher uid in session). Log out and sign in again.")),
            timeoutMs
        );
        window.addEventListener(
            "firebase-ready",
            () => {
                clearTimeout(t);
                if (window.__teacherUid) resolve(window.__teacherUid);
                else reject(new Error("Not signed in (missing teacher uid in session). Log out and sign in again."));
            },
            { once: true }
        );
    });
}

export function generateAccessCode(length = 6) {
    const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    let code = "";
    for (let i = 0; i < length; i++) {
        code += chars[Math.floor(Math.random() * chars.length)];
    }
    return code;
}

export async function fetchTeacherClasses() {
    const uid = await waitForTeacherUid();
    if (!uid) {
        throw new Error("Not signed in (missing teacher uid).");
    }

    return withDb(async (db) => {
        let snap;
        try {
            const q = query(
                collection(db, "classes"),
                where("teacher_uid", "==", uid),
                orderBy("created_at", "desc")
            );
            snap = await getDocs(q);
        } catch {
            const q = query(
                collection(db, "classes"),
                where("teacher_uid", "==", uid)
            );
            snap = await getDocs(q);
        }

        const list = snap.docs.map((d) => ({
            id: d.id,
            ...d.data(),
        }));

        list.sort((a, b) => {
            const ta = a.created_at || "";
            const tb = b.created_at || "";
            return tb.localeCompare(ta);
        });

        return list;
    });
}

function assertOwner(data) {
    const uid = window.__teacherUid;
    if (!uid || data.teacher_uid !== uid) {
        throw new Error("You can only change classes you own.");
    }
}

export async function createClass(form) {
    const uid = await waitForTeacherUid();
    if (!uid) {
        throw new Error("Not signed in (missing teacher uid).");
    }

    const section_name = (form.section_name || "").trim();
    if (!section_name) {
        throw new Error("Section name is required.");
    }

    let access_code = (form.access_code || "").toUpperCase().trim();
    if (!access_code) {
        access_code = generateAccessCode();
    }

    const payload = {
        section_name,
        grade_level: form.grade_level || "Grade 8",
        subject: (form.subject || "").trim(),
        access_code,
        teacher_uid: uid,
        students: [],
        created_at: new Date().toISOString(),
    };

    return withDb(async (db) => {
        const ref = await addDoc(collection(db, "classes"), payload);
        return { id: ref.id, ...payload };
    });
}

export async function updateClass(id, form, existing) {
    if (!id) throw new Error("Missing class id.");
    assertOwner(existing);

    const section_name = (form.section_name || "").trim();
    if (!section_name) {
        throw new Error("Section name is required.");
    }

    let access_code = (form.access_code || "").toUpperCase().trim();
    if (!access_code) {
        access_code = generateAccessCode();
    }

    const patch = {
        section_name,
        grade_level: form.grade_level || "Grade 8",
        subject: (form.subject || "").trim(),
        access_code,
        updated_at: new Date().toISOString(),
    };

    return withDb(async (db) => {
        await updateDoc(doc(db, "classes", id), patch);
        return { ...existing, ...patch, id };
    });
}

export async function deleteClass(id, existing) {
    if (!id) throw new Error("Missing class id.");
    assertOwner(existing);

    return withDb(async (db) => {
        await deleteDoc(doc(db, "classes", id));
    });
}

window.TeacherClasses = {
    fetchTeacherClasses,
    createClass,
    updateClass,
    deleteClass,
    generateAccessCode,
};
