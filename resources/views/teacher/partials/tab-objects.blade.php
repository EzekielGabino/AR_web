{{-- Interactive periodic table (same data as mobile Chemistry Hub) --}}
<div class="space-y-4" x-data="periodicTable()" x-init="init()">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-black text-slate-800">Periodic table</h2>
            <p class="text-xs text-slate-500">Interactive table of the elements — same data as the student app.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <input type="search"
                   x-model="q"
                   placeholder="Search symbol or name…"
                   class="w-48 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 placeholder:text-slate-300 focus:border-[#7CD5F3] focus:outline-none focus:ring-2 focus:ring-[#7CD5F3]/20">
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <template x-for="leg in legend" :key="leg.label">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                <span class="h-2.5 w-2.5 rounded-sm" :style="'background:' + leg.color"></span>
                <span x-text="leg.label"></span>
            </span>
        </template>
    </div>

    <div x-show="loading" class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-xs text-slate-400">
        Loading elements…
    </div>
    <div x-show="error" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-800" x-text="error"></div>

    <div x-show="!loading && !error" class="overflow-auto rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <div class="inline-grid gap-1" style="grid-template-columns: repeat(18, minmax(2.75rem, 1fr));">
            <template x-for="cell in gridCells" :key="cell.key">
                <div>
                    <template x-if="!cell.el">
                        <div class="h-14 w-11 sm:h-16 sm:w-12"></div>
                    </template>
                    <template x-if="cell.el">
                        <button type="button"
                                @click="select(cell.el)"
                                class="flex h-14 w-11 flex-col items-stretch rounded-md border border-black/10 p-0.5 text-left shadow-sm transition hover:scale-105 hover:shadow-md sm:h-16 sm:w-12"
                                :class="matches(cell.el) ? 'ring-2 ring-indigo-500 ring-offset-1' : (selected?.number === cell.el.number ? 'ring-2 ring-slate-800 ring-offset-1' : '')"
                                :style="'background:' + categoryColor(cell.el.category)"
                                :title="cell.el.name">
                            <span class="text-[8px] font-bold leading-none text-white/90" x-text="cell.el.number"></span>
                            <span class="mt-0.5 text-center text-sm font-black leading-none text-white sm:text-base" x-text="cell.el.symbol"></span>
                            <span class="mt-auto truncate text-center text-[7px] font-semibold leading-none text-white/90 sm:text-[8px]" x-text="cell.el.name"></span>
                        </button>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <div x-show="selected" x-cloak class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="flex h-16 w-16 flex-col items-center justify-center rounded-xl text-white shadow"
                     :style="'background:' + categoryColor(selected?.category || '')">
                    <span class="text-xs font-bold" x-text="selected?.number"></span>
                    <span class="text-2xl font-black" x-text="selected?.symbol"></span>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900" x-text="selected?.name"></h3>
                    <p class="text-xs font-semibold capitalize text-slate-500" x-text="selected?.category"></p>
                </div>
            </div>
            <button type="button" @click="selected = null" class="text-xs font-bold text-slate-400 hover:text-slate-600">Close</button>
        </div>
        <dl class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-xl bg-slate-50 p-3">
                <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Atomic mass</dt>
                <dd class="text-sm font-black text-slate-800" x-text="selected?.atomic_mass != null ? Number(selected.atomic_mass).toFixed(3) : '—'"></dd>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Period / Group</dt>
                <dd class="text-sm font-black text-slate-800" x-text="(selected?.period ?? '—') + ' / ' + (selected?.group ?? selected?.xpos ?? '—')"></dd>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Phase</dt>
                <dd class="text-sm font-black text-slate-800" x-text="selected?.phase || '—'"></dd>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Appearance</dt>
                <dd class="text-sm font-semibold text-slate-800" x-text="selected?.appearance || '—'"></dd>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Discovered by</dt>
                <dd class="text-sm font-semibold text-slate-800" x-text="selected?.discovered_by || '—'"></dd>
            </div>
            <div class="rounded-xl bg-slate-50 p-3" x-show="selected?.source">
                <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Source</dt>
                <dd class="text-sm font-semibold">
                    <a :href="selected?.source" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">Wikipedia →</a>
                </dd>
            </div>
        </dl>
        <p class="mt-3 text-xs leading-relaxed text-slate-600" x-show="selected?.summary" x-text="selected?.summary"></p>
    </div>
</div>

<script>
function periodicTable() {
    const COLORS = {
        'alkali metal': '#fca5a5',
        'alkaline earth metal': '#fdba74',
        'transition metal': '#fbbf24',
        'post-transition metal': '#7dd3fc',
        'metalloid': '#4ade80',
        'diatomic nonmetal': '#2dd4bf',
        'polyatomic nonmetal': '#2dd4bf',
        'reactive nonmetal': '#2dd4bf',
        'nonmetal': '#2dd4bf',
        'halogen': '#c084fc',
        'noble gas': '#f9a8d4',
        'lanthanide': '#22d3ee',
        'actinide': '#a78bfa',
    };

    return {
        loading: true,
        error: '',
        elements: [],
        selected: null,
        q: '',
        legend: [
            { label: 'Alkali metal', color: COLORS['alkali metal'] },
            { label: 'Alkaline earth', color: COLORS['alkaline earth metal'] },
            { label: 'Transition', color: COLORS['transition metal'] },
            { label: 'Post-transition', color: COLORS['post-transition metal'] },
            { label: 'Metalloid', color: COLORS.metalloid },
            { label: 'Nonmetal', color: COLORS['diatomic nonmetal'] },
            { label: 'Halogen', color: COLORS.halogen },
            { label: 'Noble gas', color: COLORS['noble gas'] },
            { label: 'Lanthanide', color: COLORS.lanthanide },
            { label: 'Actinide', color: COLORS.actinide },
        ],
        async init() {
            try {
                const res = await fetch(@json(asset('data/periodic_table.json')), {
                    headers: { Accept: 'application/json' },
                });
                if (!res.ok) throw new Error('Could not load element data (' + res.status + ')');
                const data = await res.json();
                this.elements = Array.isArray(data.elements) ? data.elements : (Array.isArray(data) ? data : []);
            } catch (e) {
                console.error(e);
                this.error = e.message || 'Failed to load periodic table.';
            } finally {
                this.loading = false;
            }
        },
        categoryColor(cat) {
            const key = (cat || '').toLowerCase().trim();
            return COLORS[key] || '#94a3b8';
        },
        matches(el) {
            const q = (this.q || '').trim().toLowerCase();
            if (!q) return false;
            return (el.symbol || '').toLowerCase().includes(q)
                || (el.name || '').toLowerCase().includes(q)
                || String(el.number || '') === q;
        },
        select(el) {
            this.selected = el;
        },
        get gridCells() {
            const byPos = {};
            for (const el of this.elements) {
                const x = Number(el.xpos ?? el.group);
                const y = Number(el.ypos ?? el.period);
                if (!x || !y) continue;
                byPos[y + ',' + x] = el;
            }
            const cells = [];
            for (let y = 1; y <= 10; y++) {
                for (let x = 1; x <= 18; x++) {
                    const el = byPos[y + ',' + x] || null;
                    cells.push({ key: y + '-' + x, el });
                }
            }
            return cells;
        },
    };
}
</script>
