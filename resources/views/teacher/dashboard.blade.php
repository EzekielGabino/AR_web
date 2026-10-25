@extends('layouts.teacher')

@section('title', 'SciLens AR — Dashboard')

@section('content')
    <div x-data="{ tab: '{{ request('tab', 'classes') }}' }" class="min-h-screen">
        @include('teacher.partials.topbar')

        <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
            <nav class="mb-6 flex flex-wrap gap-2 border-b border-slate-200 pb-3">
                <button type="button"
                        @click="tab = 'classes'"
                        class="rounded-xl px-4 py-2 text-xs font-bold transition"
                        :class="tab === 'classes'
                            ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200'
                            : 'text-slate-500 hover:bg-white/60 hover:text-slate-800'">
                    Classes
                </button>
                <button type="button"
                        @click="tab = 'objects'"
                        class="rounded-xl px-4 py-2 text-xs font-bold transition"
                        :class="tab === 'objects'
                            ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200'
                            : 'text-slate-500 hover:bg-white/60 hover:text-slate-800'">
                    Periodic table
                </button>
                <button type="button"
                        @click="tab = 'notifications'"
                        class="rounded-xl px-4 py-2 text-xs font-bold transition"
                        :class="tab === 'notifications'
                            ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200'
                            : 'text-slate-500 hover:bg-white/60 hover:text-slate-800'">
                    Notifications
                </button>
            </nav>

            {{-- Default tab has no x-cloak so content is visible before Alpine boots --}}
            <div x-show="tab === 'classes'" class="rounded-2xl p-0">
                @include('teacher.partials.tab-classes')
            </div>
            <div x-show="tab === 'notifications'" x-cloak>
                @include('teacher.partials.tab-notifications')
            </div>
            <div x-show="tab === 'objects'" x-cloak>
                @include('teacher.partials.tab-objects')
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/teacher/classes.js') }}"></script>
@endpush
