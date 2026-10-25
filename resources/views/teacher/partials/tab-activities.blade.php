<div class="space-y-4" x-data="{ status: 'pending' }">
    <div>
        <h2 class="text-lg font-black text-slate-800">Class activities</h2>
        <p class="text-xs text-slate-500">Assigned lab work across all your classes.</p>
    </div>

    <div class="flex flex-wrap gap-2">
        <template x-for="tab in [
            { id: 'pending', label: 'Pending' },
            { id: 'completed', label: 'Completed' },
            { id: 'past_due', label: 'Past due' },
            { id: 'returned', label: 'Returned' },
        ]" :key="tab.id">
            <button type="button"
                    @click="status = tab.id"
                    class="rounded-full px-3 py-1.5 text-xs font-bold transition"
                    :class="status === tab.id
                        ? 'bg-[#7CD5F3] text-white'
                        : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'">
                <span x-text="tab.label"></span>
            </button>
        </template>
    </div>

    <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center">
        <p class="text-sm font-bold text-slate-600">
            <span x-text="status.replace('_', ' ')"></span> — empty
        </p>
        <p class="mt-1 text-xs text-slate-400">
            Activities will appear here after you assign lab templates to a class.
        </p>
    </div>
</div>
