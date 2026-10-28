{{-- Stream --}}
<div x-show="tab === 'stream'" x-cloak class="space-y-4">
    <div class="rounded-2xl border border-[#ABD2FA]/40 bg-white p-5 shadow-sm">
        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-[#091540]">New announcement</label>
        <textarea x-model="draft" rows="3" placeholder="Post to the class stream…"
            class="w-full rounded-xl border border-[#ABD2FA] px-3.5 py-2.5 text-sm font-medium text-[#091540] placeholder:text-[#091540]/40 focus:border-[#7692FF] focus:outline-none focus:ring-2 focus:ring-[#7692FF]/20"></textarea>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <label class="cursor-pointer rounded-lg bg-[#ABD2FA]/20 px-3 py-1.5 text-xs font-bold text-[#1B2CC1] hover:bg-[#ABD2FA]/40 transition-colors duration-150"
                :class="streamUploading && 'pointer-events-none opacity-50'">
                <span x-text="streamUploading ? 'Uploading…' : '+ Attach file'"></span>
                <input type="file" class="hidden"
                    accept=".pdf,.doc,.docx,image/png,image/jpeg,image/webp,application/pdf"
                    :disabled="streamUploading"
                    @change="onStreamFileSelected($event)">
            </label>
            <span class="text-xs text-[#091540]/60">PDF, Word, or image · max 15 MB</span>
        </div>
        <ul class="mt-3 space-y-1.5" x-show="(draftAttachments || []).length">
            <template x-for="(file, fi) in (draftAttachments || [])" :key="file.storagePath || fi">
                <li class="flex items-center justify-between gap-2 rounded-lg border border-[#ABD2FA]/50 bg-[#ABD2FA]/10 px-3 py-2">
                    <a :href="file.url || file.downloadURL" target="_blank" rel="noopener"
                        class="truncate text-xs font-bold text-[#1B2CC1] hover:underline"
                        x-text="file.name || 'File'"></a>
                    <button type="button" @click="draftAttachments.splice(fi, 1)"
                        class="shrink-0 text-xs font-bold text-rose-500 hover:text-rose-700">Remove</button>
                </li>
            </template>
        </ul>

        <p x-show="streamError" x-text="streamError" class="mt-1.5 text-xs font-bold text-rose-500"></p>
        <div class="mt-3 flex justify-end">
            <button type="button" @click="post()" :disabled="posting || !draft.trim()"
                class="rounded-xl bg-[#1B2CC1] px-5 py-2.5 text-xs font-bold text-white hover:bg-[#091540] disabled:opacity-50 transition-colors duration-150 shadow-sm">
                <span x-text="posting ? 'Posting…' : 'Post Announcement'"></span>
            </button>
        </div>
    </div>
    <div x-show="announcements.length === 0" class="rounded-2xl border border-dashed border-[#ABD2FA] bg-[#ABD2FA]/10 p-8 text-center text-xs font-medium text-[#091540]/60">No announcements yet.</div>
    <template x-for="a in announcements" :key="a.id">
        <article class="rounded-2xl border border-[#ABD2FA]/40 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <p class="whitespace-pre-wrap text-sm font-medium text-[#091540] leading-relaxed" x-text="a.body"></p>
                <button type="button" @click="removeAnnouncement(a)" class="shrink-0 text-xs font-bold text-rose-500 hover:text-rose-700 hover:underline">Delete</button>
            </div>
            <ul class="mt-3 space-y-1.5" x-show="(a.attachments || []).length">
                <template x-for="(file, fi) in (a.attachments || [])" :key="file.storagePath || fi">
                    <li>
                        <a :href="file.url || file.downloadURL" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-[#1B2CC1] hover:underline"
                            x-text="file.name || 'Attachment'"></a>
                    </li>
                </template>
            </ul>
            <p class="mt-3 text-[11px] font-semibold text-[#091540]/50" x-text="formatDate(a.created_at)"></p>
        </article>
    </template>
</div>