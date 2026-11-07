@extends('layouts.admin')

@section('title', 'Class students — Admin')

@section('content')
<div class="min-h-screen bg-slate-50 text-[#091540]" x-data="adminClassStudents()" x-init="init()">
    @include('admin.partials.topbar')
    <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6">
        <button type="button" @click="history.back()" class="text-xs font-bold text-[#1B2CC1]">← Back</button>
        <div x-show="loading" class="rounded-2xl border bg-white p-6 text-xs text-slate-500">Loading…</div>
        <p x-show="error" x-text="error" class="text-xs font-bold text-rose-600"></p>
        <template x-if="!loading && classInfo">
            <div class="space-y-6">
                <div class="rounded-3xl border border-[#ABD2FA]/50 bg-white p-6 shadow-sm">
                    <h1 class="text-xl font-extrabold" x-text="classInfo.section_name || 'Class'"></h1>
                    <p class="text-sm text-slate-500">
                        <span x-text="classInfo.grade_level || '—'"></span>
                        · Code <span class="font-bold" x-text="classInfo.access_code || '—'"></span>
                    </p>
                </div>
                <div>
                    <h2 class="text-lg font-black">Students</h2>
                    <ul class="mt-3 space-y-2">
                        <template x-for="s in students" :key="s.user_id">
                            <li class="flex items-center justify-between rounded-2xl border border-[#ABD2FA]/40 bg-white px-4 py-3">
                                <div>
                                    <p class="text-sm font-black" x-text="s.display_name"></p>
                                    <p class="text-xs text-slate-500" x-text="s.email"></p>
                                </div>
                                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold capitalize"
                                    :class="(s.status || '').toLowerCase() === 'approved' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800'"
                                    x-text="s.status || '—'"></span>
                            </li>
                        </template>
                    </ul>
                    <p x-show="!students.length" class="mt-3 text-xs text-slate-500">No students in this class yet.</p>
                </div>
            </div>
        </template>
    </div>
</div>
<script type="module" src="{{ asset('js/admin/admin.js') }}"></script>
<script>
function adminClassStudents() {
    return {
        classId: @js($classId),
        classInfo: null,
        students: [],
        loading: true,
        error: '',
        async init() {
            while (!window.AdminApi) await new Promise(r => setTimeout(r, 40));
            try {
                const data = await window.AdminApi.classStudents(this.classId);
                this.classInfo = data.class;
                this.students = data.students || [];
            } catch (e) {
                this.error = e.message || 'Failed to load class.';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endsection
