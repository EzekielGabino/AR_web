<header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur"
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
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <div class="flex items-center gap-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-full border border-[#7CD5F3] bg-[#7CD5F3]/20 text-sm">🔬</span>
            <div>
                <p class="text-sm font-black tracking-tight text-slate-800">SciLens AR</p>
                <p class="text-[10px] font-semibold text-slate-400">Teacher dashboard</p>
            </div>
        </div>

        <div class="relative">
            <button type="button"
                    @click="menuOpen = !menuOpen"
                    class="flex items-center gap-2 rounded-xl border border-transparent px-2 py-1.5 text-left transition hover:border-slate-200 hover:bg-slate-50">
                <div class="hidden text-right sm:block">
                    <p class="text-xs font-bold text-slate-800">{{ session('teacher_name', 'Teacher') }}</p>
                    <p class="text-[10px] text-slate-500">{{ session('teacher_email') }}</p>
                </div>
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 text-xs font-black text-slate-600 sm:hidden">
                    {{ strtoupper(substr(session('teacher_name', 'T'), 0, 1)) }}
                </div>
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="menuOpen"
                 x-cloak
                 @click.outside="menuOpen = false"
                 class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                <div class="border-b border-slate-100 px-3 py-2 sm:hidden">
                    <p class="text-xs font-bold text-slate-800">{{ session('teacher_name', 'Teacher') }}</p>
                    <p class="text-[10px] text-slate-500">{{ session('teacher_email') }}</p>
                </div>
                <button type="button"
                        @click="menuOpen = false; pwdOpen = true; error = ''; message = ''; password = ''; password_confirmation = ''"
                        class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                    Change password
                </button>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-xs font-semibold text-rose-600 hover:bg-rose-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
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
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         role="dialog"
         aria-modal="true"
         aria-labelledby="pwd-modal-title">
        <div class="absolute inset-0 bg-slate-900/40" @click="pwdOpen = false"></div>
        <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
            <h2 id="pwd-modal-title" class="text-lg font-black text-slate-800">Change password</h2>
            <p class="mt-1 text-xs text-slate-500">
                Updates the password on your Firebase account so you can sign in with email.
            </p>

            <div class="mt-4 space-y-3">
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase text-slate-500">New password</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'"
                               x-model="password"
                               minlength="6"
                               autocomplete="new-password"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 pr-10 text-xs font-semibold focus:border-[#7CD5F3] focus:outline-none focus:ring-2 focus:ring-[#7CD5F3]/20">
                        <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600">
                            <svg x-show="!showPassword" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <svg x-show="showPassword" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase text-slate-500">Confirm password</label>
                    <input :type="showPassword ? 'text' : 'password'"
                           x-model="password_confirmation"
                           minlength="6"
                           autocomplete="new-password"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-semibold focus:border-[#7CD5F3] focus:outline-none focus:ring-2 focus:ring-[#7CD5F3]/20">
                </div>

                <p x-show="error" x-text="error" class="text-xs font-bold text-red-500"></p>
                <p x-show="message" x-text="message" class="text-xs font-bold text-emerald-600"></p>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button"
                        @click="pwdOpen = false"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button"
                        @click="submitPassword()"
                        :disabled="loading || password.length < 6"
                        class="rounded-xl bg-[#7CD5F3] px-4 py-2 text-xs font-bold text-white hover:bg-[#68cbed] disabled:opacity-50">
                    <span x-text="loading ? 'Saving…' : 'Save password'"></span>
                </button>
            </div>
        </div>
    </div>
</header>
