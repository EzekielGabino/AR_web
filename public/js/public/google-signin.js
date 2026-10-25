/**
 * Teacher Google sign-in via Supabase Auth (migration Step 4).
 * Replaces Firebase signInWithPopup.
 */
import { createClient } from "https://esm.sh/@supabase/supabase-js@2";

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
        labelEl.textContent = loading ? "Redirecting to Google…" : "Continue with Google";
    }
}

async function signInWithGoogle() {
    if (errorBox) {
        errorBox.classList.add("hidden");
        errorBox.textContent = "";
    }

    const url = window.__supabaseUrl;
    const anon = window.__supabaseAnonKey;
    const redirectTo = window.__supabaseCallbackUrl;

    if (!url || !anon) {
        showError("Supabase is not configured. Set SUPABASE_URL and SUPABASE_ANON_KEY.");
        return;
    }

    try {
        setLoading(true);
        const supabase = createClient(url, anon);
        const { error } = await supabase.auth.signInWithOAuth({
            provider: "google",
            options: {
                redirectTo: redirectTo || (window.location.origin + "/auth/supabase/callback"),
                queryParams: {
                    access_type: "offline",
                    prompt: "select_account",
                },
            },
        });
        if (error) throw error;
    } catch (error) {
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
