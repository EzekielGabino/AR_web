/**
 * Google sign-in via Supabase OAuth (not Firebase).
 * Requires window.__supabaseUrl, __supabaseAnonKey, __supabaseCallbackUrl.
 */
import { createClient } from "https://esm.sh/@supabase/supabase-js@2";

function setLoading(btn, loading) {
    if (!btn) return;
    btn.disabled = loading;
    btn.classList.toggle("opacity-60", loading);
    btn.classList.toggle("pointer-events-none", loading);
    const label = btn.querySelector(".google-signin-label");
    if (label) {
        label.textContent = loading ? "Signing in…" : "Continue with Google";
    }
}

function showError(btn, message) {
    console.error("Google Sign-In:", message);
    // nearest error under same parent section
    const root = btn.closest("section") || document;
    const errorBox = root.querySelector(".google-signin-error");
    if (errorBox) {
        errorBox.textContent = message;
        errorBox.classList.remove("hidden");
    }
}

async function signInWithGoogle(btn) {
    const url = window.__supabaseUrl;
    const anon = window.__supabaseAnonKey;
    const redirectTo = window.__supabaseCallbackUrl;

    if (!url || !anon) {
        showError(btn, "Supabase is not configured. Check SUPABASE_URL and SUPABASE_ANON_KEY.");
        return;
    }
    if (!redirectTo) {
        showError(btn, "Missing OAuth callback URL.");
        return;
    }

    try {
        setLoading(btn, true);
        const supabase = createClient(url, anon);
        const { error } = await supabase.auth.signInWithOAuth({
            provider: "google",
            options: {
                redirectTo,
                queryParams: { prompt: "select_account" },
            },
        });
        if (error) throw error;
        // Browser navigates to Google; no further action
    } catch (e) {
        console.error(e);
        setLoading(btn, false);
        showError(btn, e.message || "Google sign-in failed.");
    }
}

function wireButtons() {
    document.querySelectorAll(".google-signin-btn").forEach((btn) => {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            const root = btn.closest("section") || document;
            const errorBox = root.querySelector(".google-signin-error");
            if (errorBox) {
                errorBox.classList.add("hidden");
                errorBox.textContent = "";
            }
            signInWithGoogle(btn);
        });
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", wireButtons);
} else {
    wireButtons();
}
