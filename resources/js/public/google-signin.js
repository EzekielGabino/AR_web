/**
 * Custom Google button → account chooser popup → Laravel session.
 * Config from #google-signin-btn data-callback-url (set in Blade).
 */
import {
    getAuth,
    GoogleAuthProvider,
    signInWithPopup,
} from "https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js";

const btn = document.getElementById("google-signin-btn");
const errorBox = document.getElementById("google-signin-error");
const labelEl = document.getElementById("google-signin-label");

function showError(message) {
    console.error("Google Sign-In:", message);
    if (errorBox) {
        errorBox.textContent = message;
        errorBox.classList.remove("hidden");
    }
}

function setLoading(loading) {
    if (!btn) return;
    btn.disabled = loading;
    btn.classList.toggle("opacity-60", loading);
    btn.classList.toggle("pointer-events-none", loading);
    if (labelEl) {
        labelEl.textContent = loading ? "Signing in…" : "Continue with Google";
    }
}

function csrfToken() {
    return document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
}

async function waitForFirebaseApp(timeoutMs = 8000) {
    if (window.__firebaseApp) return window.__firebaseApp;
    return new Promise((resolve, reject) => {
        const t = setTimeout(
            () => reject(new Error("Firebase app did not initialize in time.")),
            timeoutMs
        );
        window.addEventListener(
            "firebase-ready",
            () => {
                clearTimeout(t);
                resolve(window.__firebaseApp);
            },
            { once: true }
        );
    });
}

async function signInWithGoogle() {
    if (errorBox) {
        errorBox.classList.add("hidden");
        errorBox.textContent = "";
    }

    try {
        setLoading(true);

        const app = await waitForFirebaseApp();
        const auth = getAuth(app);
        const provider = new GoogleAuthProvider();
        // Always show the account picker
        provider.setCustomParameters({ prompt: "select_account" });

        const result = await signInWithPopup(auth, provider);
        const firebaseIdToken = await result.user.getIdToken(true);

        const callbackUrl = btn?.dataset.callbackUrl;
        if (!callbackUrl) {
            throw new Error("Missing data-callback-url on Google button.");
        }

        const res = await fetch(callbackUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken() || "",
            },
            body: JSON.stringify({ id_token: firebaseIdToken }),
        });

        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch {
            throw new Error(`Laravel returned HTTP ${res.status}: ${text}`);
        }

        if (!res.ok || !data.redirect) {
            throw new Error(
                data.message ||
                    data.error ||
                    `Laravel authentication failed (HTTP ${res.status})`
            );
        }

        window.location.href = data.redirect;
    } catch (error) {
        if (error?.code === "auth/popup-closed-by-user") {
            setLoading(false);
            return;
        }
        showError(error.message || "Google Sign-In failed. Please try again.");
        setLoading(false);
    }
}

if (btn) {
    btn.addEventListener("click", (e) => {
        e.preventDefault();
        signInWithGoogle();
    });
}
