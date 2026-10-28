@extends('layouts.teacher')

@section('title', 'SciLens AR — Dashboard')

@section('content')
<div x-data="{ tab: '{{ request('tab', 'classes') }}' }" class="min-h-screen bg-slate-50 text-[#091540] relative overflow-hidden">

    {{-- Ambient Background Glows --}}
    <div class="pointer-events-none absolute top-0 right-0 h-96 w-96 rounded-full bg-[#ABD2FA]/20 blur-3xl"></div>
    <div class="pointer-events-none absolute bottom-10 left-10 h-96 w-96 rounded-full bg-[#7692FF]/10 blur-3xl"></div>

    @include('teacher.partials.topbar')

    <div class="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 space-y-8">

        {{-- Dashboard Hero Welcome Banner --}}
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#091540] via-[#1B2CC1] to-[#7692FF] p-6 sm:p-8 text-white shadow-xl shadow-[#1B2CC1]/20">
            <div class="pointer-events-none absolute -right-12 -top-12 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>

            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3.5 py-1 text-xs font-semibold backdrop-blur-md border border-white/20">
                        <span>🔬 Teacher Management Portal</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                        Welcome back, {{ session('teacher_name', 'Teacher') }}!
                    </h1>
                    <p class="text-xs sm:text-sm font-medium text-[#ABD2FA]/90 max-w-xl">
                        Monitor your classroom sections, assign AR chemistry lab activities, and explore interactive 3D molecular structures.
                    </p>
                </div>

            </div>
        </div>

        {{-- Navigation Tabs Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-[#ABD2FA]/40 pb-5">
            <nav class="flex gap-2.5 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:flex-wrap sm:overflow-visible">
                {{-- Classes Tab --}}
                <button type="button"
                    @click="tab = 'classes'"
                    class="cursor-pointer inline-flex shrink-0 items-center gap-2.5 rounded-2xl px-4 py-2.5 text-xs font-bold transition-all sm:px-5 sm:py-3"
                    :class="tab === 'classes'
                            ? 'bg-[#1B2CC1] text-white shadow-lg shadow-[#1B2CC1]/30 ring-2 ring-[#7692FF]/50'
                            : 'bg-white/90 text-[#091540]/70 border border-[#ABD2FA]/60 hover:bg-white hover:text-[#091540] hover:border-[#7692FF]'">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                    Classes
                </button>

                {{-- Periodic Table Tab --}}
                <button type="button"
                    @click="tab = 'objects'"
                    class="cursor-pointer inline-flex shrink-0 items-center gap-2.5 rounded-2xl px-4 py-2.5 text-xs font-bold transition-all sm:px-5 sm:py-3"
                    :class="tab === 'objects'
                            ? 'bg-[#1B2CC1] text-white shadow-lg shadow-[#1B2CC1]/30 ring-2 ring-[#7692FF]/50'
                            : 'bg-white/90 text-[#091540]/70 border border-[#ABD2FA]/60 hover:bg-white hover:text-[#091540] hover:border-[#7692FF]'">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                    </svg>
                    Periodic Table
                </button>

                {{-- Notifications Tab --}}
                <button type="button"
                    @click="tab = 'notifications'"
                    class="cursor-pointer inline-flex items-center gap-2.5 rounded-2xl px-5 py-3 text-xs font-bold transition-all relative"
                    :class="tab === 'notifications'
                            ? 'bg-[#1B2CC1] text-white shadow-lg shadow-[#1B2CC1]/30 ring-2 ring-[#7692FF]/50'
                            : 'bg-white/90 text-[#091540]/70 border border-[#ABD2FA]/60 hover:bg-white hover:text-[#091540] hover:border-[#7692FF]'">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                    Notifications
                </button>
            </nav>

            {{-- Live Indicator --}}
            <div class="hidden md:flex items-center gap-2 text-xs font-semibold text-[#091540]/70 bg-white px-4 py-2.5 rounded-2xl border border-[#ABD2FA]/50 shadow-xs">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Active Workspace</span>
            </div>
        </div>

        {{-- Tab Content Container --}}
        <div class="relative">
            <div x-show="tab === 'classes'" class="transition-all duration-300">
                @include('teacher.partials.tab-classes')
            </div>
            <div x-show="tab === 'notifications'" x-cloak class="transition-all duration-300">
                @include('teacher.partials.tab-notifications')
            </div>
            <div x-show="tab === 'objects'" x-cloak class="transition-all duration-300">
                @include('teacher.partials.tab-objects')
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('js/teacher/classes.js') }}"></script>
@endpush