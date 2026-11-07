@extends('layouts.admin')

@section('title', 'Teacher — Admin')

@section('content')
<div class="min-h-screen bg-slate-50 text-[#091540]" x-data="adminTeacher()" x-init="init()">
    @include('admin.partials.topbar')
    <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6">
        <a href="{{ route('admin.dashboard') }}?tab=teachers" class="text-xs font-bold text-[#1B2CC1]">← Teachers</a>
        <div x-show="loading" class="rounded-2xl border bg-white p-6 text-xs text-slate-500">Loading…</div>
        <p x-show="error" x-text="error" class="text-xs font-bold text-rose-600"></p>
        <template x-if="!loading && teacher">
            <div class="space-y-6">
                <div class="rounded-3xl border border-[#ABD2FA]/50 bg-white p-6 shadow-sm">
                    <h1 class="text-xl font-extrabold" x-text="teacher.display_name"></h1>
                    <p class="text-sm text-slate-500" x-text="teacher.email"></p>
                </div>
                <div>
                    <h2 class="text-lg font-black">Classes</h2>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <template x-for="c in classes" :key="c.id">
                            <a :href="'{{ url('/admin/classes') }}/' + c.id + '/students'"
                                class="block rounded-2xl border border-[#ABD2FA]/50 bg-white p-4 hover:border-[#1B2CC1]">
                                <p class="text-sm font-black" x-text="c.section_name || 'Untitled'"></p>
                                <p class="text-xs text-slate-500">
                                    <span x-text="c.grade_level || '—'"></span>
                                    <span x-show="c.academic_year"> · <span x-text="c.academic_year"></span></span>
                                </p>
                                <p class="mt-1 text-[11px] font-bold text-[#1B2CC1]" x-text="(c.student_count || 0) + ' students'"></p>
                            </a>
                        </template>
                    </div>
                    <p x-show="!classes.length" class="mt-3 text-xs text-slate-500">No classes for this teacher.</p>
                </div>
            </div>
        </template>
    </div>
</div>
<script type="module" src="{{ asset('js/admin/admin.js') }}"></script>
<script>
function adminTeacher() {
    return {
        teacherId: @js($teacherId),
        teacher: null,
        classes: [],
        loading: true,
        error: '',
        async init() {
            while (!window.AdminApi) await new Promise(r => setTimeout(r, 40));
            try {
                const data = await window.AdminApi.showTeacher(this.teacherId);
                this.teacher = data.teacher;
                this.classes = data.classes || [];
            } catch (e) {
                this.error = e.message || 'Failed to load teacher.';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endsection
