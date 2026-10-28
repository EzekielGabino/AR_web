<div class="space-y-6" x-data="teacherNotifications()" x-init="init()">

    {{-- Main Container Card --}}
    <div class="relative overflow-hidden rounded-[2.5rem] border border-[#ABD2FA]/60 bg-white/95 p-6 sm:p-8 shadow-xl shadow-[#1B2CC1]/5 backdrop-blur-md">

        {{-- Section Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-[#ABD2FA]/40 pb-6">
            <div>
                <h2 class="text-xl font-extrabold text-[#091540] tracking-tight">Notifications</h2>
                <p class="mt-1 text-xs font-semibold text-[#091540]/60">Announcements posted and activities published across your classes.</p>
            </div>

            {{-- Quick Filter Badge / Status Pill --}}
            <div x-show="!loading && items.length" class="inline-flex items-center gap-2 rounded-2xl bg-[#ABD2FA]/20 px-4 py-2 border border-[#7692FF]/30">
                <span class="h-2 w-2 rounded-full bg-[#1B2CC1] animate-pulse"></span>
                <span class="text-xs font-bold text-[#1B2CC1]" x-text="items.filter(i => !i.readAt).length + ' unread'"></span>
            </div>
        </div>

        {{-- Loading State --}}
        <div x-show="loading" class="mt-8 rounded-3xl border border-[#ABD2FA]/40 bg-slate-50/50 p-12 text-center text-xs font-bold text-[#091540]/60">
            Loading…
        </div>

        {{-- Error State --}}
        <div x-show="!loading && error" class="mt-8 rounded-2xl border border-rose-500/30 bg-rose-50/80 p-4 text-xs font-bold text-rose-600" x-text="error"></div>

        {{-- Empty State --}}
        <div x-show="!loading && !error && items.length === 0"
            class="mt-8 rounded-3xl border border-dashed border-[#ABD2FA]/80 bg-slate-50/50 p-12 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#ABD2FA]/20 text-[#1B2CC1] mb-3">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
            </div>
            <p class="text-sm font-extrabold text-[#091540]">No notifications yet</p>
            <p class="mt-1 text-xs font-medium text-[#091540]/60 max-w-sm mx-auto leading-relaxed">
                When you post an announcement or publish an activity, it will show up here.
            </p>
        </div>

        {{-- Notifications List --}}
        <div x-show="!loading && items.length" class="mt-6 overflow-hidden rounded-3xl border border-[#ABD2FA]/60 bg-white/80 shadow-xs backdrop-blur-xs">
            <ul class="divide-y divide-[#ABD2FA]/30">
                <template x-for="n in items" :key="n.id">
                    <li class="group transition-all duration-200 hover:bg-[#ABD2FA]/10">
                        <a :href="hrefFor(n)"
                            @click="markRead(n)"
                            class="flex items-start gap-4 px-5 py-4 transition-colors"
                            :class="!n.readAt ? 'bg-[#ABD2FA]/15' : ''">

                            {{-- Type Icon Badge --}}
                            <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl text-xs font-extrabold shadow-xs transition-transform group-hover:scale-105"
                                :class="n.type === 'activity_published' 
                                      ? 'bg-[#1B2CC1] text-white shadow-[#1B2CC1]/20' 
                                      : 'bg-[#ABD2FA] text-[#091540] shadow-[#ABD2FA]/30'"
                                x-text="n.type === 'activity_published' ? 'A' : 'S'"></span>

                            {{-- Notification Details --}}
                            <div class="min-w-0 flex-1 space-y-1">
                                <div class="flex items-center gap-2">
                                    <p class="text-xs font-extrabold text-[#091540] transition-colors group-hover:text-[#1B2CC1]" x-text="n.title"></p>
                                    <span x-show="!n.readAt" class="h-2 w-2 rounded-full bg-[#1B2CC1]"></span>
                                </div>
                                <p class="truncate text-xs font-semibold text-[#091540]/70" x-text="n.body"></p>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-[#091540]/50" x-text="formatWhen(n.createdAt)"></p>
                            </div>

                            {{-- Action Link Indicator --}}
                            <span class="shrink-0 inline-flex items-center gap-1 text-[11px] font-extrabold text-[#1B2CC1] opacity-70 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all">
                                Open →
                            </span>
                        </a>
                    </li>
                </template>
            </ul>
        </div>
    </div>
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
                        if (window.TeacherClasses) {
                            clearTimeout(t);
                            resolve();
                        } else setTimeout(check, 50);
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
                if (!n.classId) return '/dashboard';
                if (n.type === 'activity_published') {
                    if (n.activityId) {
                        return '/classes/' + n.classId + '/activities/' + n.activityId;
                    }
                    return '/classes/' + n.classId + '?tab=activities';
                }
                // announcement → stream
                return '/classes/' + n.classId + '?tab=stream';
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
                        month: 'short',
                        day: 'numeric',
                        hour: 'numeric',
                        minute: '2-digit',
                    });
                } catch (_) {
                    return String(iso);
                }
            },
        };
    }
</script>