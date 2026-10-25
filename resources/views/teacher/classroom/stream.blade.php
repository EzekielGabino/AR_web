                {{-- Stream --}}
                <div x-show="tab === 'stream'" x-cloak class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <label class="mb-1.5 block text-[11px] font-bold uppercase text-slate-500">New announcement</label>
                        <textarea x-model="draft" rows="3" placeholder="Post to the class stream…"
                                  class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold focus:border-[#7CD5F3] focus:outline-none focus:ring-2 focus:ring-[#7CD5F3]/20"></textarea>

                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <label class="cursor-pointer rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700 hover:bg-slate-200"
                                   :class="streamUploading && 'pointer-events-none opacity-50'">
                                <span x-text="streamUploading ? 'Uploading…' : '+ Attach file'"></span>
                                <input type="file" class="hidden"
                                       accept=".pdf,.doc,.docx,image/png,image/jpeg,image/webp,application/pdf"
                                       :disabled="streamUploading"
                                       @change="onStreamFileSelected($event)">
                            </label>
                            <span class="text-[10px] text-slate-400">PDF, Word, or image · max 15 MB</span>
                        </div>
                        <ul class="mt-2 space-y-1" x-show="(draftAttachments || []).length">
                            <template x-for="(file, fi) in (draftAttachments || [])" :key="file.storagePath || fi">
                                <li class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 bg-slate-50 px-2.5 py-1.5">
                                    <a :href="file.url || file.downloadURL" target="_blank" rel="noopener"
                                       class="truncate text-[11px] font-bold text-[#439BBB] hover:underline"
                                       x-text="file.name || 'File'"></a>
                                    <button type="button" @click="draftAttachments.splice(fi, 1)"
                                            class="shrink-0 text-[11px] font-bold text-rose-500">Remove</button>
                                </li>
                            </template>
                        </ul>

                        <p x-show="streamError" x-text="streamError" class="mt-1 text-xs font-bold text-red-500"></p>
                        <div class="mt-2 flex justify-end">
                            <button type="button" @click="post()" :disabled="posting || !draft.trim()"
                                    class="rounded-xl bg-[#7CD5F3] px-4 py-2 text-xs font-bold text-white hover:bg-[#68cbed] disabled:opacity-50">
                                <span x-text="posting ? 'Posting…' : 'Post'"></span>
                            </button>
                        </div>
                    </div>
                    <div x-show="announcements.length === 0" class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center text-xs text-slate-400">No announcements yet.</div>
                    <template x-for="a in announcements" :key="a.id">
                        <article class="rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="flex items-start justify-between gap-3">
                                <p class="whitespace-pre-wrap text-sm font-semibold text-slate-800" x-text="a.body"></p>
                                <button type="button" @click="removeAnnouncement(a)" class="shrink-0 text-[11px] font-bold text-rose-500 hover:underline">Delete</button>
                            </div>
                            <ul class="mt-2 space-y-1" x-show="(a.attachments || []).length">
                                <template x-for="(file, fi) in (a.attachments || [])" :key="file.storagePath || fi">
                                    <li>
                                        <a :href="file.url || file.downloadURL" target="_blank" rel="noopener"
                                           class="inline-flex items-center gap-1 text-[11px] font-bold text-[#439BBB] hover:underline"
                                           x-text="file.name || 'Attachment'"></a>
                                    </li>
                                </template>
                            </ul>
                            <p class="mt-2 text-[10px] font-semibold text-slate-400" x-text="formatDate(a.created_at)"></p>
                        </article>
                    </template>
                </div>
