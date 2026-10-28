@extends('layouts.public')

@section('title', 'SciLens AR — Set password')

@section('content')
<div class="relative flex min-h-screen items-center justify-center bg-slate-50/50 px-4 py-16 overflow-hidden">
    {{-- Soft Light Glow Orbs --}}
    <div class="pointer-events-none absolute -top-20 left-1/2 -translate-x-1/2 h-96 w-96 rounded-full bg-[#ABD2FA]/30 blur-3xl"></div>
    <div class="pointer-events-none absolute top-1/3 -right-20 h-72 w-72 rounded-full bg-[#7692FF]/15 blur-3xl"></div>

    <div class="relative z-10 w-full max-w-md rounded-3xl border border-[#ABD2FA]/50 bg-white p-8 sm:p-10 shadow-xl shadow-[#091540]/5"
        x-data="{ showPassword: false, showConfirm: false }">

        <div class="text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-[#ABD2FA] bg-[#ABD2FA]/20 p-2.5 shadow-xs mb-4">
                <svg class="h-7 w-7 text-[#1B2CC1]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
            </div>
            <h1 class="text-xl font-black text-[#091540]">Set a password</h1>
            <p class="mt-1.5 text-xs font-medium text-[#091540]/60">
                Optional — so you can also sign in with email later.
            </p>
        </div>

        @if ($errors->any())
        <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 p-3.5 text-xs font-bold text-rose-600">
            {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ route('auth.set-password.submit') }}" method="POST" class="mt-6 space-y-4">
            @csrf

            <div>
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Password</label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'"
                        name="password"
                        required
                        minlength="6"
                        autocomplete="new-password"
                        placeholder="••••••••"
                        class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA] bg-slate-50/50 px-4 py-3.5 pr-12 text-xs font-bold text-[#091540] placeholder:text-[#091540]/30 focus:border-[#1B2CC1] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20 transition-all">
                    <button type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 flex items-center px-3.5 text-[#091540]/40 hover:text-[#1B2CC1] transition-colors cursor-pointer"
                        :aria-label="showPassword ? 'Hide password' : 'Show password'">
                        {{-- eye --}}
                        <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{-- eye-off --}}
                        <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Confirm password</label>
                <div class="relative">
                    <input :type="showConfirm ? 'text' : 'password'"
                        name="password_confirmation"
                        required
                        minlength="6"
                        autocomplete="new-password"
                        placeholder="••••••••"
                        class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA] bg-slate-50/50 px-4 py-3.5 pr-12 text-xs font-bold text-[#091540] placeholder:text-[#091540]/30 focus:border-[#1B2CC1] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20 transition-all">
                    <button type="button"
                        @click="showConfirm = !showConfirm"
                        class="absolute inset-y-0 right-0 flex items-center px-3.5 text-[#091540]/40 hover:text-[#1B2CC1] transition-colors cursor-pointer"
                        :aria-label="showConfirm ? 'Hide password' : 'Show password'">
                        <svg x-show="!showConfirm" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="showConfirm" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit"
                class="cursor-pointer w-full rounded-2xl bg-[#1B2CC1] py-3.5 text-xs font-bold text-white shadow-md shadow-[#1B2CC1]/25 hover:bg-[#091540] transition-all">
                Save password
            </button>
        </form>

        <form action="{{ route('auth.set-password.skip') }}" method="POST" class="mt-3">
            @csrf
            <button type="submit" class="cursor-pointer w-full py-2.5 text-xs font-bold text-[#091540]/50 hover:text-[#091540] transition-colors">
                Skip for now
            </button>
        </form>
    </div>
</div>
@endsection