<section id="login" class="border-t border-slate-200 px-4 py-16 sm:px-6">
    <div class="mx-auto w-full max-w-md">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8"
             x-data="{ showPassword: false }">
            <div class="mb-6 flex items-center justify-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-full border border-[#7CD5F3] bg-[#7CD5F3]/20 text-sm font-black text-[#50aed0]">
                    🔬
                </div>
                <span class="text-lg font-black tracking-tight text-slate-700">SciLens AR</span>
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-500/20 bg-red-500/10 p-4 text-xs font-bold leading-relaxed text-red-500">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="mb-6 text-center">
                <h3 class="text-xl font-bold text-slate-700">Login to your account</h3>
                <p class="mt-1 text-xs font-bold text-slate-400">Teacher management dashboard</p>
            </div>

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-500">School email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           placeholder="teacher@school.edu"
                           class="w-full rounded-2xl border-[1.5px] border-slate-200 bg-white px-4 py-3 text-xs font-bold text-slate-700 placeholder:text-slate-300 focus:border-[#7CD5F3] focus:outline-none focus:ring-2 focus:ring-[#7CD5F3]/20">
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Password</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'"
                               name="password"
                               required
                               placeholder="••••••••"
                               autocomplete="current-password"
                               class="w-full rounded-2xl border-[1.5px] border-slate-200 bg-white px-4 py-3 pr-12 text-xs font-bold text-slate-700 placeholder:text-slate-300 focus:border-[#7CD5F3] focus:outline-none focus:ring-2 focus:ring-[#7CD5F3]/20">
                        <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'">
                            <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit"
                        class="mt-2 w-full cursor-pointer rounded-2xl bg-[#7CD5F3] py-3.5 text-xs font-bold text-white shadow-[0_4px_14px_0_rgba(124,213,243,0.39)] transition hover:bg-[#68cbed]">
                    Sign in
                </button>
            </form>

            <div class="my-5 flex items-center gap-3">
                <div class="h-px flex-1 bg-slate-200"></div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">or</span>
                <div class="h-px flex-1 bg-slate-200"></div>
            </div>

            <button type="button"
                    id="google-signin-btn"
                    data-callback-url="{{ route('auth.google.callback') }}"
                    class="flex w-full min-h-[48px] cursor-pointer items-center justify-center gap-3 rounded-2xl border-[1.5px] border-slate-200 bg-white px-4 py-3.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                    aria-label="Sign in with Google">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
                    <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                    <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238C29.211 35.091 26.715 36 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
                    <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 01-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
                </svg>
                <span id="google-signin-label" class="whitespace-nowrap">Continue with Google</span>
            </button>
            <p id="google-signin-error" class="mt-3 hidden text-center text-xs font-bold text-red-500"></p>

            <p class="mt-6 text-center text-[11px] text-slate-400">
                Students use the SciLens AR mobile app — this portal is for teachers only.
            </p>
        </div>
    </div>
</section>
