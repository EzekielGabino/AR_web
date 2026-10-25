<div class="space-y-4" x-data="teacherNotifications()" x-init="init()">
    <div>
        <h2 class="text-lg font-black text-slate-800">Notifications</h2>
        <p class="text-xs text-slate-500">Announcements posted and activities published across your classes.</p>
    </div>

    <div x-show="loading" class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-xs text-slate-400">
        Loading…
    </div>

    <div x-show="!loading && error" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-800" x-text="error"></div>

    <div x-show="!loading && !error && items.length === 0"
         class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center">
        <p class="text-sm font-bold text-slate-600">No notifications yet</p>
        <p class="mt-1 text-xs text-slate-400">
            When you post an announcement or publish an activity, it will show up here.
        </p>
    </div>

    <ul x-show="!loading && items.length" class="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <template x-for="n in items" :key="n.id">
            <li>
                <a :href="hrefFor(n)"
                   @click="markRead(n)"
                   class="flex items-start gap-3 px-4 py-3 transition hover:bg-slate-50"
                   :class="!n.readAt ? 'bg-sky-50/60' : ''">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm"
                          :class="n.type === 'activity_published' ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700'"
                          x-text="n.type === 'activity_published' ? 'A' : 'S'"></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="text-xs font-black text-slate-800" x-text="n.title"></p>
                            <span x-show="!n.readAt" class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>
                        </div>
                        <p class="mt-0.5 truncate text-[12px] text-slate-600" x-text="n.body"></p>
                        <p class="mt-1 text-[10px] text-slate-400" x-text="formatWhen(n.createdAt)"></p>
                    </div>
                    <span class="shrink-0 text-[10px] font-bold text-slate-400">Open →</span>
                </a>
            </li>
        </template>
    </ul>
</div>

<script>
function teacherNotifications() {
    return {
        loading: true,
        error: '',
        items: [],
        async init() {
            await this.waitModule();
            await this.load();
        },
        waitModule() {
            if (window.TeacherClasses) return Promise.resolve();
            return new Promise((resolve, reject) => {
                const t = setTimeout(() => reject(new Error('Teacher module failed to load')), 8000);
                const check = () => {
                    if (window.TeacherClasses) { clearTimeout(t); resolve(); }
                    else setTimeout(check, 50);
                };
                check();
            });
        },
        async load() {
            this.loading = true;
            this.error = '';
            try {
                this.items = await window.TeacherClasses.fetchNotifications();
            } catch (e) {
                console.error(e);
                this.error = e.message || 'Could not load notifications.';
            } finally {
                this.loading = false;
            }
        },
        hrefFor(n) {
            if (!n.classId) return '{{ url('/dashboard') }}';
            if (n.type === 'activity_published') {
                if (n.activityId) {
                    return '{{ url('/classes') }}/' + n.classId + '/activities/' + n.activityId;
                }
                return '{{ url('/classes') }}/' + n.classId + '?tab=activities';
            }
            // announcement → stream
            return '{{ url('/classes') }}/' + n.classId + '?tab=stream';
        },
        async markRead(n) {
            if (!n || n.readAt) return;
            try {
                await window.TeacherClasses.markNotificationRead(n.id);
                n.readAt = new Date().toISOString();
            } catch (e) {
                console.error(e);
            }
        },
        formatWhen(iso) {
            if (!iso) return '';
            try {
                const d = new Date(iso);
                if (isNaN(d.getTime())) return String(iso);
                return d.toLocaleString(undefined, {
                    month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit',
                });
            } catch (_) {
                return String(iso);
            }
        },
    };
}
</script>
