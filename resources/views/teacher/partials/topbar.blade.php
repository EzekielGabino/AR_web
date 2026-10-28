<header class="sticky top-0 z-[100] border-b border-[#7692FF]/30 bg-[#091540]/95 backdrop-blur-md shadow-lg"
    x-data="{
            menuOpen: false,
            pwdOpen: false,
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
                    const res = await fetch(@js(route('profile.password.update')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                        },
                        body: JSON.stringify({
                            password: this.password,
                            password_confirmation: this.password_confirmation,
                        }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        this.error = data.message || 'Could not update password.';
                    } else {
                        this.message = data.message || 'Password updated.';
                        this.password = '';
                        this.password_confirmation = '';
                        setTimeout(() => { this.pwdOpen = false; this.message = ''; }, 1500);
                    }
                } catch (e) {
                    this.error = e.message || 'Network error.';
                } finally {
                    this.loading = false;
                }
            },
        }"
    @keydown.escape.window="menuOpen = false; pwdOpen = false">

    {{-- Ambient Glow Orb --}}
    <div class="pointer-events-none absolute -top-10 left-1/3 h-24 w-64 rounded-full bg-[#1B2CC1]/30 blur-2xl"></div>

    <div class="relative z-10 mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl border border-[#7692FF]/40 bg-[#1B2CC1]/30 p-2 shadow-sm">
                <img src="{{ asset('icons/logo.png') }}" alt="SciLens AR Logo" class="h-full w-full object-contain">
            </div>
            <div>
                <p class="text-sm font-black tracking-tight text-white">SciLens AR</p>
                <p class="text-[10px] font-bold uppercase tracking-wider text-[#ABD2FA]">Teacher dashboard</p>
            </div>
        </div>

        <div class="relative">
            <button type="button"
                @click="menuOpen = !menuOpen"
                class="flex cursor-pointer items-center gap-2.5 rounded-2xl border border-[#7692FF]/30 bg-white/5 px-3.5 py-2 text-left transition-all hover:bg-white/10 hover:border-[#7692FF]">
                <div class="hidden text-right sm:block">
                    <p class="text-xs font-extrabold text-white">{{ session('teacher_name', 'Teacher') }}</p>
                    <p class="text-[10px] font-semibold text-[#ABD2FA]/70">{{ session('teacher_email') }}</p>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#1B2CC1] text-xs font-extrabold text-white sm:hidden border border-[#7692FF]/40 shadow-xs">
                    {{ strtoupper(substr(session('teacher_name', 'T'), 0, 1)) }}
                </div>
                <svg class="h-4 w-4 text-[#ABD2FA] transition-transform duration-200" :class="menuOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="menuOpen"
                x-cloak
                @click.outside="menuOpen = false"
                class="absolute right-0 mt-2 w-56 rounded-2xl border border-[#7692FF]/40 bg-[#091540] py-1.5 shadow-2xl shadow-[#091540]/80 backdrop-blur-md z-[9999]">
                <div class="border-b border-white/10 px-4 py-2.5 sm:hidden">
                    <p class="text-xs font-extrabold text-white">{{ session('teacher_name', 'Teacher') }}</p>
                    <p class="text-[10px] font-medium text-[#ABD2FA]/70 truncate">{{ session('teacher_email') }}</p>
                </div>
                <button type="button"
                    @click="menuOpen = false; pwdOpen = true; error = ''; message = ''; password = ''; password_confirmation = ''"
                    class="flex w-full cursor-pointer items-center gap-2.5 px-4 py-2.5 text-left text-xs font-bold text-white hover:bg-white/10 transition-colors">
                    <svg class="h-4 w-4 text-[#7692FF]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    Change password
                </button>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="flex w-full cursor-pointer items-center gap-2.5 px-4 py-2.5 text-left text-xs font-bold text-rose-400 hover:bg-rose-500/10 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                        </svg>
                        Sign out
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Change password modal --}}
    <div x-show="pwdOpen"
        x-cloak
        class="fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-[#091540]/80 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="pwd-modal-title">
        <div class="absolute inset-0" @click="pwdOpen = false"></div>
        <div class="relative w-full max-w-md rounded-3xl border border-[#7692FF]/40 bg-[#091540] p-6 sm:p-8 shadow-2xl shadow-[#1B2CC1]/30 text-white">
            <h2 id="pwd-modal-title" class="text-lg font-extrabold text-white">Change password</h2>
            <p class="mt-1 text-xs font-medium text-[#ABD2FA]/80">
                Updates the password on your Firebase account so you can sign in with email.
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#ABD2FA]">New password</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'"
                            x-model="password"
                            minlength="6"
                            autocomplete="new-password"
                            placeholder="••••••••"
                            class="w-full rounded-2xl border-[1.5px] border-[#7692FF]/40 bg-white/10 px-4 py-3.5 pr-12 text-xs font-bold text-white placeholder:text-white/30 focus:border-[#7692FF] focus:bg-white/15 focus:outline-none focus:ring-4 focus:ring-[#7692FF]/30 transition-all">
                        <button type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 flex items-center px-3.5 text-[#ABD2FA]/60 hover:text-white transition-colors"
                            :aria-label="showPassword ? 'Hide password' : 'Show password'">
                            <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#ABD2FA]">Confirm password</label>
                    <input :type="showPassword ? 'text' : 'password'"
                        x-model="password_confirmation"
                        minlength="6"
                        autocomplete="new-password"
                        placeholder="••••••••"
                        class="w-full rounded-2xl border-[1.5px] border-[#7692FF]/40 bg-white/10 px-4 py-3.5 text-xs font-bold text-white placeholder:text-white/30 focus:border-[#7692FF] focus:bg-white/15 focus:outline-none focus:ring-4 focus:ring-[#7692FF]/30 transition-all">
                </div>

                <p x-show="error" x-text="error" class="rounded-xl bg-rose-500/20 p-3 text-xs font-bold text-rose-200 border border-rose-500/40"></p>
                <p x-show="message" x-text="message" class="rounded-xl bg-emerald-500/20 p-3 text-xs font-bold text-emerald-200 border border-emerald-500/40"></p>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-white/10 pt-4">
                <button type="button"
                    @click="pwdOpen = false"
                    class="cursor-pointer rounded-2xl border-[1.5px] border-[#7692FF]/40 bg-white/5 px-5 py-3 text-xs font-bold text-white transition-all hover:bg-white/10">
                    Cancel
                </button>
                <button type="button"
                    @click="submitPassword()"
                    :disabled="loading || password.length < 6"
                    class="cursor-pointer rounded-2xl bg-[#1B2CC1] px-6 py-3 text-xs font-bold text-white shadow-lg shadow-[#1B2CC1]/40 transition-all hover:bg-[#7692FF] hover:text-[#091540] disabled:opacity-50">
                    <span x-text="loading ? 'Saving…' : 'Save password'"></span>
                </button>
            </div>
        </div>
    </div>
</header>