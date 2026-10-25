@extends('layouts.public')

@section('title', 'SciLens AR — Set password')

@section('content')
    <div class="flex min-h-screen items-center justify-center px-4 py-16">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-sm"
             x-data="{ showPassword: false, showConfirm: false }">
            <h1 class="text-center text-xl font-black text-slate-800">Set a password</h1>
            <p class="mt-2 text-center text-xs text-slate-500">
                Optional — so you can also sign in with email later.
            </p>

            @if ($errors->any())
                <div class="mt-4 rounded-2xl border border-red-500/20 bg-red-500/10 p-3 text-xs font-bold text-red-500">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('auth.set-password.submit') }}" method="POST" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase text-slate-500">Password</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'"
                               name="password"
                               required
                               minlength="6"
                               autocomplete="new-password"
                               class="w-full rounded-2xl border border-slate-200 px-4 py-3 pr-12 text-xs font-bold text-slate-700 focus:border-[#7CD5F3] focus:outline-none focus:ring-2 focus:ring-[#7CD5F3]/20">
                        <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600"
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
                    <label class="mb-1.5 block text-[11px] font-bold uppercase text-slate-500">Confirm</label>
                    <div class="relative">
                        <input :type="showConfirm ? 'text' : 'password'"
                               name="password_confirmation"
                               required
                               minlength="6"
                               autocomplete="new-password"
                               class="w-full rounded-2xl border border-slate-200 px-4 py-3 pr-12 text-xs font-bold text-slate-700 focus:border-[#7CD5F3] focus:outline-none focus:ring-2 focus:ring-[#7CD5F3]/20">
                        <button type="button"
                                @click="showConfirm = !showConfirm"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600"
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
                        class="w-full rounded-2xl bg-[#7CD5F3] py-3 text-xs font-bold text-white hover:bg-[#68cbed]">
                    Save password
                </button>
            </form>

            <form action="{{ route('auth.set-password.skip') }}" method="POST" class="mt-3">
                @csrf
                <button type="submit" class="w-full py-2 text-xs font-bold text-slate-400 hover:text-slate-600">
                    Skip for now
                </button>
            </form>
        </div>
    </div>
@endsection
