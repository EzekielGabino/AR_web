{{-- public/partials/login-form.blade.php --}}
<section class="relative flex w-full flex-col justify-center overflow-hidden px-4 py-8 text-white sm:px-8 sm:py-12 lg:px-16"
    x-data="{ showPassword: false, mode: @js(session('auth_mode', 'login')) }">

    <div class="pointer-events-none absolute -top-20 -left-20 h-64 w-64 rounded-full bg-[#1B2CC1]/50 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-20 -right-20 h-64 w-64 rounded-full bg-[#7692FF]/30 blur-3xl"></div>

    <div class="relative z-10 mx-auto w-full max-w-md">
        <div class="mb-6 flex items-center justify-center gap-3 sm:mb-8">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl border border-[#7692FF]/40 bg-[#1B2CC1]/40 p-2 shadow-lg shadow-[#1B2CC1]/30">
                <img src="{{ asset('icons/logo.png') }}" alt="SciLens AR Logo" class="h-full w-full object-contain">
            </div>
            <span class="text-xl font-black tracking-tight text-white">SciLens AR</span>
        </div>

        @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-500/40 bg-rose-500/20 p-4 text-xs font-bold leading-relaxed text-rose-200 backdrop-blur-md">
            {{ $errors->first() }}
        </div>
        @endif

        <div class="mb-6 text-center">
            <h3 class="text-2xl font-extrabold tracking-tight text-white"
                x-text="mode === 'login' ? 'Sign in to your account' : 'Create teacher account'"></h3>
            <p class="mt-1.5 text-xs font-medium text-[#ABD2FA]/80">Teacher management dashboard</p>
        </div>

        <div class="mb-5 flex rounded-2xl border border-[#7692FF]/30 bg-white/5 p-1">
            <button type="button" @click="mode = 'login'"
                class="flex-1 rounded-xl py-2.5 text-xs font-bold transition-all"
                :class="mode === 'login' ? 'bg-[#1B2CC1] text-white shadow' : 'text-[#ABD2FA]/80 hover:text-white'">
                Sign in
            </button>
            <button type="button" @click="mode = 'register'"
                class="flex-1 rounded-xl py-2.5 text-xs font-bold transition-all"
                :class="mode === 'register' ? 'bg-[#1B2CC1] text-white shadow' : 'text-[#ABD2FA]/80 hover:text-white'">
                Sign up
            </button>
        </div>

        {{-- Single form: action switches so Create account cannot hit login.post --}}
        <form method="POST" class="space-y-4"
            :action="mode === 'login' ? @js(route('login.post')) : @js(route('register.post'))">
            @csrf
            <input type="hidden" name="auth_mode" :value="mode">

            <div>
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#ABD2FA]">School email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                    placeholder="teacher@school.edu"
                    class="w-full rounded-2xl border-[1.5px] border-[#7692FF]/30 bg-white/10 px-4 py-3.5 text-xs font-bold text-white placeholder:text-white/30 focus:border-[#7692FF] focus:bg-white/15 focus:outline-none focus:ring-4 focus:ring-[#7692FF]/30 transition-all">
            </div>

            <div>
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#ABD2FA]">Password</label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'" name="password" required minlength="6"
                        autocomplete="current-password" placeholder="At least 6 characters"
                        class="w-full rounded-2xl border-[1.5px] border-[#7692FF]/30 bg-white/10 px-4 py-3.5 pr-12 text-xs font-bold text-white placeholder:text-white/30 focus:border-[#7692FF] focus:bg-white/15 focus:outline-none focus:ring-4 focus:ring-[#7692FF]/30 transition-all">
                    <button type="button" @click="showPassword = !showPassword"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-bold text-[#ABD2FA]">
                        <span x-text="showPassword ? 'Hide' : 'Show'"></span>
                    </button>
                </div>
            </div>

            <button type="submit"
                class="mt-2 w-full cursor-pointer rounded-2xl bg-[#1B2CC1] py-4 text-xs font-bold text-white shadow-lg shadow-[#1B2CC1]/40 transition-all duration-200 hover:bg-[#7692FF] hover:text-[#091540] hover:shadow-xl hover:shadow-[#7692FF]/30 active:scale-[0.98]"
                x-text="mode === 'login' ? 'Sign in' : 'Create account'">
            </button>
        </form>

        <div class="my-6 flex items-center gap-3">
            <div class="h-px flex-1 bg-white/10"></div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-[#ABD2FA]/60">or</span>
            <div class="h-px flex-1 bg-white/10"></div>
        </div>

        <button type="button"
            class="google-signin-btn flex min-h-[48px] w-full cursor-pointer items-center justify-center gap-3 rounded-2xl border-[1.5px] border-[#7692FF]/30 bg-white/5 px-4 py-4 text-xs font-bold text-white shadow-sm transition-all hover:border-[#7692FF] hover:bg-white/10 active:scale-[0.98]"
            aria-label="Sign in with Google">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z" />
                <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z" />
                <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238C29.211 35.091 26.715 36 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z" />
                <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 01-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z" />
            </svg>
            <span class="google-signin-label whitespace-nowrap">Continue with Google</span>
        </button>
        <p class="google-signin-error mt-3 hidden text-center text-xs font-bold text-rose-300"></p>

        <p class="mt-8 text-center text-[11px] font-medium text-[#ABD2FA]/60">
            Students use the SciLens AR mobile app — this portal is for teachers only.
        </p>
    </div>
</section>
