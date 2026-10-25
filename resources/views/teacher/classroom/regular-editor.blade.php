        {{-- Regular activity create / edit --}}
        <div x-show="regModal" x-cloak class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4" role="dialog">
            <div class="absolute inset-0 bg-slate-900/40" @click="closeRegModal()"></div>
            <div class="relative flex max-h-[95vh] w-full max-w-xl flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-xl sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="text-lg font-black text-slate-800"
                            x-text="regModal === 'edit' ? 'Edit regular activity' : 'New regular activity'"></h3>
                        <p class="text-[11px] text-slate-500">Title and description required for the assignment. Quiz is optional.</p>
                    </div>
                    <button type="button" @click="closeRegModal()" class="text-xs font-bold text-slate-400 hover:text-slate-600">Close</button>
                </div>

                <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
                    <div>
                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Title</label>
                        <input type="text" x-model="regForm.title" placeholder="e.g. Week 3 reading check"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-semibold focus:border-[#7CD5F3] focus:outline-none focus:ring-2 focus:ring-[#7CD5F3]/20">
                    </div>
                    <div>
                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Description</label>
                        <textarea x-model="regForm.description" rows="3" placeholder="Instructions for students…"
                                  class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-semibold focus:border-[#7CD5F3] focus:outline-none"></textarea>
                    </div>

                    <div class="border-t border-slate-100 pt-3">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Attachments (optional)</p>
                            <label class="cursor-pointer rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700 hover:bg-slate-200"
                                   :class="regUploading && 'pointer-events-none opacity-50'">
                                <span x-text="regUploading ? 'Uploading…' : '+ PDF / Word / Image'"></span>
                                <input type="file" class="hidden"
                                       accept=".pdf,.doc,.docx,image/png,image/jpeg,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                       :disabled="regUploading"
                                       @change="onRegFileSelected($event)">
                            </label>
                        </div>
                        <p class="mb-2 text-[11px] text-slate-400">
                            Stored in Supabase Storage (max 15 MB). Students open the link from the activity.
                        </p>
                        <ul class="space-y-1.5" x-show="(regForm.attachments || []).length">
                            <template x-for="(file, fi) in (regForm.attachments || [])" :key="file.storagePath || file.downloadURL || fi">
                                <li class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                    <div class="min-w-0">
                                        <a :href="file.url || file.downloadURL" target="_blank" rel="noopener"
                                           class="block truncate text-xs font-bold text-[#439BBB] hover:underline"
                                           x-text="file.name || 'Attachment'"></a>
                                        <p class="text-[10px] text-slate-400">
                                            <span x-text="file.source === 'supabase_storage' ? 'Supabase Storage' : (file.mimeType || 'File')"></span>
                                            <span x-show="file.size"> · <span x-text="formatBytes(file.size)"></span></span>
                                        </p>
                                    </div>
                                    <button type="button" @click="removeRegAttachment(fi)" class="shrink-0 text-[11px] font-bold text-rose-500">Remove</button>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <div class="border-t border-slate-100 pt-3">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Quiz (optional)</p>
                            <button type="button" @click="addRegQuestion()" class="text-xs font-bold text-[#50aed0]">+ Add question</button>
                        </div>
                        <p x-show="!regForm.questions.length" class="text-center text-[11px] text-slate-400">No quiz questions. Students can still complete the activity from the description alone.</p>

                        <div class="space-y-2">
                            <template x-for="(q, i) in regForm.questions" :key="q._uid || i">
                                <div class="rounded-xl border border-slate-100 bg-slate-50">
                                    <div class="flex items-center gap-2 px-3 py-2">
                                        <button type="button" @click="q._open = !q._open" class="flex min-w-0 flex-1 items-center gap-2 text-left">
                                            <svg class="h-3.5 w-3.5 shrink-0 text-slate-400 transition-transform" :class="q._open && 'rotate-90'" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="text-[10px] font-bold uppercase text-slate-400" x-text="'Q' + (i + 1)"></span>
                                            <span class="truncate text-xs font-semibold text-slate-600" x-text="q.prompt || 'Untitled question'"></span>
                                        </button>
                                        <button type="button" @click="removeRegQuestion(i)" class="text-[11px] font-bold text-rose-500">Remove</button>
                                    </div>
                                    <div x-show="q._open" class="space-y-2 border-t border-slate-100 px-3 pb-3 pt-2">
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Prompt</label>
                                            <input type="text" x-model="q.prompt" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                        </div>
                                        <template x-for="opt in q.options" :key="opt.id">
                                            <div>
                                                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500" x-text="'Option ' + (opt.id || '').toUpperCase()"></label>
                                                <input type="text" x-model="opt.text" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                            </div>
                                        </template>
                                        <div>
                                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Correct answer</label>
                                            <select x-model="q.correctOption" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold">
                                                <template x-for="opt in q.options" :key="'c-'+opt.id">
                                                    <option :value="opt.id" x-text="(opt.id || '').toUpperCase()"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <p x-show="regError" x-text="regError" class="text-xs font-bold text-red-500"></p>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-3">
                    <button type="button" @click="closeRegModal()" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="button" @click="saveRegularActivity()" :disabled="regSaving || !regForm.title.trim()"
                            class="rounded-xl bg-[#7CD5F3] px-4 py-2 text-xs font-bold text-white hover:bg-[#68cbed] disabled:opacity-50">
                        <span x-text="regSaving ? 'Saving…' : (regModal === 'edit' ? 'Save changes' : 'Save draft')"></span>
                    </button>
                </div>
            </div>
        </div>
