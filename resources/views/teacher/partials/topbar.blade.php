<div x-data="{
            menuOpen: false,
            pwdOpen: false,
            helpOpen: false,
            helpStep: 0,
            password: '',
            password_confirmation: '',
            showPassword: false,
            loading: false,
            message: '',
            error: '',
            async submitPassword() {
                this.loading = true;
                this.message = '';
                this.error = '';
                try {
                    if (!navigator.onLine) {
                        this.error = 'No internet connection. Check your network and try again.';
                        return;
                    }
                    if ((this.password || '').length < 6) {
                        this.error = 'Password must be at least 6 characters.';
                        return;
                    }
                    if (this.password !== this.password_confirmation) {
                        this.error = 'Password confirmation does not match.';
                        return;
                    }
                    const res = await fetch(@js(route('profile.password.update')), {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            password: this.password,
                            password_confirmation: this.password_confirmation,
                        }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        let msg = data.message || 'Could not update password.';
                        if (data.errors) {
                            const first = Object.values(data.errors).flat()[0];
                            if (first) msg = first;
                        }
                        this.error = msg;
                    } else {
                        this.message = data.message || 'Password updated.';
                        this.password = '';
                        this.password_confirmation = '';
                        setTimeout(() => { this.pwdOpen = false; this.message = ''; }, 1500);
                    }
                } catch (e) {
                    const m = (e && e.message) ? String(e.message) : '';
                    const low = m.toLowerCase();
                    if (!navigator.onLine || low.includes('failed to fetch') || low.includes('network')) {
                        this.error = 'No internet connection. Check your network and try again.';
                    } else {
                        this.error = m || 'Network error.';
                    }
                } finally {
                    this.loading = false;
                }
            },
        }"
    @keydown.escape.window="menuOpen = false; pwdOpen = false; helpOpen = false">

<header class="sticky top-0 z-[100] border-b border-[#7692FF]/30 bg-[#091540]/95 backdrop-blur-md shadow-lg">
    <div class="pointer-events-none absolute -top-10 left-1/3 h-24 w-64 rounded-full bg-[#1B2CC1]/30 blur-2xl"></div>

    <div class="relative z-10 mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-[#7692FF]/40 bg-[#1B2CC1]/30 p-2 shadow-sm">
                <img src="{{ asset('icons/logo.png') }}" onerror="this.onerror=null;this.src='{{ asset('logo.png') }}'" alt="SciLens AR Logo" class="h-full w-full object-contain">
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-black tracking-tight text-white">SciLens AR</p>
                <p class="truncate text-[10px] font-bold uppercase tracking-wider text-[#ABD2FA]/70">Teacher portal</p>
            </div>
        </a>

        <div class="flex items-center gap-2">
            <button type="button"
                @click="helpOpen = true; helpStep = 0"
                class="flex h-10 w-10 items-center justify-center rounded-2xl border border-[#7692FF]/30 bg-white/5 text-white transition hover:bg-white/10"
                title="How to use SciLens"
                aria-label="Help and onboarding">
                <span class="text-sm font-black">i</span>
            </button>

            <div class="relative">
                <button type="button"
                    @click="menuOpen = !menuOpen"
                    class="flex items-center gap-2 rounded-2xl border border-[#7692FF]/30 bg-white/5 px-3 py-2 text-xs font-bold text-white transition hover:bg-white/10">
                    <span class="max-w-[10rem] truncate">{{ session('teacher_name') ?: session('teacher_email') ?: 'Account' }}</span>
                    <svg class="h-4 w-4 text-[#ABD2FA]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                <div x-show="menuOpen" x-cloak @click.outside="menuOpen = false"
                    class="absolute right-0 mt-2 w-56 overflow-hidden rounded-2xl border border-[#7692FF]/30 bg-[#091540] shadow-xl shadow-black/40">
                    <div class="border-b border-white/10 px-4 py-3">
                        <p class="truncate text-xs font-bold text-white">{{ session('teacher_name') ?: 'Teacher' }}</p>
                        <p class="truncate text-[11px] text-[#ABD2FA]/70">{{ session('teacher_email') }}</p>
                    </div>
                    <div class="p-1.5">
                        <button type="button"
                            @click="menuOpen = false; pwdOpen = true; error = ''; message = ''; password = ''; password_confirmation = ''"
                            class="flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-left text-xs font-bold text-white hover:bg-white/10">
                            Change password
                        </button>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-left text-xs font-bold text-rose-300 hover:bg-rose-500/10">
                                Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<template x-teleport="body">
    <div x-show="pwdOpen" x-cloak
        class="fixed inset-0 z-[99999] flex items-center justify-center bg-[#091540]/80 p-4 backdrop-blur-sm"
        role="dialog" aria-modal="true" aria-labelledby="pwd-modal-title">
        <div class="absolute inset-0" @click="pwdOpen = false"></div>
        <div class="relative w-full max-w-md rounded-3xl border border-[#7692FF]/40 bg-[#091540] p-6 text-white shadow-2xl shadow-[#1B2CC1]/30 sm:p-8" @click.stop>
            <h2 id="pwd-modal-title" class="text-lg font-extrabold text-white">Change password</h2>
            <p class="mt-1 text-xs font-medium text-[#ABD2FA]/80">
                Updates the password on your SciLens account so you can sign in with email.
            </p>
            <div class="mt-6 space-y-4">
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#ABD2FA]">New password</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" x-model="password" minlength="6" autocomplete="new-password"
                            placeholder="At least 6 characters"
                            class="w-full rounded-2xl border-[1.5px] border-[#7692FF]/40 bg-white/10 px-4 py-3.5 pr-12 text-xs font-bold text-white placeholder:text-white/30 focus:border-[#7692FF] focus:bg-white/15 focus:outline-none focus:ring-4 focus:ring-[#7692FF]/30">
                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 flex items-center px-3.5 text-[10px] font-bold text-[#ABD2FA]/60 hover:text-white">
                            <span x-text="showPassword ? 'Hide' : 'Show'"></span>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#ABD2FA]">Confirm password</label>
                    <input :type="showPassword ? 'text' : 'password'" x-model="password_confirmation" minlength="6" autocomplete="new-password"
                        placeholder="Re-enter password"
                        class="w-full rounded-2xl border-[1.5px] border-[#7692FF]/40 bg-white/10 px-4 py-3.5 text-xs font-bold text-white placeholder:text-white/30 focus:border-[#7692FF] focus:bg-white/15 focus:outline-none focus:ring-4 focus:ring-[#7692FF]/30">
                </div>
                <p x-show="error" x-text="error" class="rounded-xl border border-rose-500/40 bg-rose-500/20 p-3 text-xs font-bold text-rose-200"></p>
                <p x-show="message" x-text="message" class="rounded-xl border border-emerald-500/40 bg-emerald-500/20 p-3 text-xs font-bold text-emerald-200"></p>
            </div>
            <div class="mt-6 flex justify-end gap-3 border-t border-white/10 pt-4">
                <button type="button" @click="pwdOpen = false"
                    class="cursor-pointer rounded-2xl border-[1.5px] border-[#7692FF]/40 bg-white/5 px-5 py-3 text-xs font-bold text-white hover:bg-white/10">Cancel</button>
                <button type="button" @click="submitPassword()" :disabled="loading || password.length < 6"
                    class="cursor-pointer rounded-2xl bg-[#1B2CC1] px-6 py-3 text-xs font-bold text-white shadow-lg shadow-[#1B2CC1]/40 hover:bg-[#7692FF] hover:text-[#091540] disabled:opacity-50">
                    <span x-text="loading ? 'Saving…' : 'Save password'"></span>
                </button>
            </div>
        </div>
    </div>
</template>

<template x-teleport="body">
    <div x-show="helpOpen" x-cloak
        class="fixed inset-0 z-[99999] flex items-center justify-center bg-[#091540]/80 p-4 backdrop-blur-sm"
        role="dialog" aria-modal="true" aria-labelledby="help-title">
        <div class="absolute inset-0" @click="helpOpen = false"></div>
        <div class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-3xl border border-[#7692FF]/40 bg-[#091540] text-white shadow-2xl shadow-[#1B2CC1]/30"
            @click.stop>
            <div class="border-b border-white/10 px-6 py-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-wider text-[#ABD2FA]/80">Quick guide</p>
                        <h2 id="help-title" class="mt-1 text-lg font-extrabold text-white">Using the teacher portal</h2>
                    </div>
                    <button type="button" @click="helpOpen = false"
                        class="rounded-xl border border-white/10 px-2.5 py-1 text-xs font-bold text-[#ABD2FA] hover:bg-white/10">✕</button>
                </div>
                <div class="mt-4 flex gap-1.5">
                    <template x-for="i in 4" :key="i">
                        <div class="h-1 flex-1 rounded-full transition-all"
                            :class="(i - 1) <= helpStep ? 'bg-[#7692FF]' : 'bg-white/15'"></div>
                    </template>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5">
                <div x-show="helpStep === 0" class="space-y-3">
                    <p class="text-sm font-black text-white">1. Classes</p>
                    <p class="text-xs leading-relaxed text-[#ABD2FA]/90">
                        Create a class with a section name and grade. Each class gets an
                        <span class="font-bold text-white">access code</span> (and QR) students use in the mobile app.
                        Join requests appear under <span class="font-bold text-white">Students</span> — approve them before they can see stream and activities.
                    </p>
                </div>
                <div x-show="helpStep === 1" class="space-y-3">
                    <p class="text-sm font-black text-white">2. Stream &amp; announcements</p>
                    <p class="text-xs leading-relaxed text-[#ABD2FA]/90">
                        Open a class → <span class="font-bold text-white">Stream</span> to post announcements.
                        You can attach files. Students see posts after they are approved into the class.
                    </p>
                </div>
                <div x-show="helpStep === 2" class="space-y-3">
                    <p class="text-sm font-black text-white">3. Activities &amp; labs</p>
                    <p class="text-xs leading-relaxed text-[#ABD2FA]/90">
                        Add <span class="font-bold text-white">regular</span> or <span class="font-bold text-white">lab</span> activities (template or blank draft).
                        Save drafts, set a deadline, then <span class="font-bold text-white">Publish</span> so students can work in the app.
                        Review submissions from the activity page (pending / completed / returned).
                    </p>
                </div>
                <div x-show="helpStep === 3" class="space-y-3">
                    <p class="text-sm font-black text-white">4. Account tips</p>
                    <ul class="list-disc space-y-2 pl-4 text-xs leading-relaxed text-[#ABD2FA]/90">
                        <li>Use <span class="font-bold text-white">Change password</span> in the account menu to enable email sign-in.</li>
                        <li>This website is for <span class="font-bold text-white">teachers only</span>; students use the SciLens AR mobile app.</li>
                        <li>Start from the <span class="font-bold text-white">Classes</span> tab on the dashboard to create or open a section.</li>
                    </ul>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-white/10 px-6 py-4">
                <button type="button" @click="helpStep = Math.max(0, helpStep - 1)" :disabled="helpStep === 0"
                    class="rounded-2xl border border-[#7692FF]/40 px-4 py-2.5 text-xs font-bold text-white hover:bg-white/10 disabled:opacity-40">
                    Back
                </button>
                <button type="button" x-show="helpStep < 3" @click="helpStep = helpStep + 1"
                    class="rounded-2xl bg-[#1B2CC1] px-5 py-2.5 text-xs font-bold text-white shadow-lg shadow-[#1B2CC1]/40 hover:bg-[#7692FF] hover:text-[#091540]">
                    Next
                </button>
                <button type="button" x-show="helpStep === 3" @click="helpOpen = false"
                    class="rounded-2xl bg-[#1B2CC1] px-5 py-2.5 text-xs font-bold text-white shadow-lg shadow-[#1B2CC1]/40 hover:bg-[#7692FF] hover:text-[#091540]">
                    Done
                </button>
            </div>
        </div>
    </div>
</template>

</div>
