{{-- Regular activity create / edit --}}
<div x-show="regModal" x-cloak class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4" role="dialog">
    <div class="absolute inset-0 bg-[#091540]/60 backdrop-blur-xs" @click="closeRegModal()"></div>
    <div class="relative flex max-h-[95vh] w-full max-w-xl flex-col overflow-hidden rounded-t-3xl border border-[#7692FF]/40 bg-white shadow-2xl shadow-[#091540]/30 sm:rounded-3xl">
        <div class="flex items-center justify-between border-b border-[#ABD2FA]/60 bg-gradient-to-r from-[#ABD2FA]/40 via-[#ABD2FA]/20 to-white px-6 py-5 text-[#091540]">
            <div>
                <h3 class="text-lg font-black tracking-tight text-[#091540]"
                    x-text="regModal === 'edit' ? 'Edit regular activity' : 'New regular activity'"></h3>
                <p class="text-xs font-medium text-[#091540]/70 mt-0.5">Title and description required for the assignment. Quiz is optional.</p>
            </div>
            <button type="button" @click="closeRegModal()" class="cursor-pointer rounded-xl bg-[#ABD2FA]/30 px-3.5 py-2 text-xs font-bold text-[#1B2CC1] hover:bg-[#ABD2FA]/50 hover:text-[#091540] transition-colors">Close</button>
        </div>

        <div class="flex-1 space-y-4 overflow-y-auto px-6 py-5">
            <div>
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Title</label>
                <input type="text" x-model="regForm.title" placeholder="e.g. Week 3 reading check"
                    class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA] bg-white px-4 py-3 text-xs font-bold text-[#091540] placeholder:text-[#091540]/40 focus:border-[#1B2CC1] focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20 transition-all">
            </div>
            <div>
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Description</label>
                <textarea x-model="regForm.description" rows="3" placeholder="Instructions for students…"
                    class="w-full rounded-2xl border-[1.5px] border-[#ABD2FA] bg-white px-4 py-3 text-xs font-bold text-[#091540] placeholder:text-[#091540]/40 focus:border-[#1B2CC1] focus:outline-none focus:ring-4 focus:ring-[#7692FF]/20 transition-all"></textarea>
            </div>

            <div class="border-t border-[#ABD2FA]/40 pt-4">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Attachments (optional)</p>
                    <label class="cursor-pointer rounded-xl bg-[#ABD2FA]/20 px-3.5 py-2 text-xs font-bold text-[#1B2CC1] hover:bg-[#ABD2FA]/40 transition-colors"
                        :class="regUploading && 'pointer-events-none opacity-50'">
                        <span x-text="regUploading ? 'Uploading…' : '+ PDF / Word / Image'"></span>
                        <input type="file" class="hidden"
                            accept=".pdf,.doc,.docx,image/png,image/jpeg,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                            :disabled="regUploading"
                            @change="onRegFileSelected($event)">
                    </label>
                </div>
                <p class="mb-3 text-xs font-medium text-[#091540]/70">
                    Stored in Supabase Storage (max 15 MB). Students open the link from the activity.
                </p>
                <ul class="space-y-2" x-show="(regForm.attachments || []).length">
                    <template x-for="(file, fi) in (regForm.attachments || [])" :key="file.storagePath || file.downloadURL || fi">
                        <li class="flex items-center justify-between gap-2 rounded-xl border border-[#ABD2FA]/60 bg-[#ABD2FA]/10 px-3.5 py-2.5">
                            <div class="min-w-0">
                                <a :href="file.url || file.downloadURL" target="_blank" rel="noopener"
                                    class="block truncate text-xs font-bold text-[#1B2CC1] hover:underline"
                                    x-text="file.name || 'Attachment'"></a>
                                <p class="text-[11px] text-[#091540]/60 font-medium mt-0.5">
                                    <span x-text="file.source === 'supabase_storage' ? 'Supabase Storage' : (file.mimeType || 'File')"></span>
                                    <span x-show="file.size"> · <span x-text="formatBytes(file.size)"></span></span>
                                </p>
                            </div>
                            <button type="button" @click="removeRegAttachment(fi)" class="shrink-0 text-xs font-bold text-rose-500 hover:text-rose-700">Remove</button>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="border-t border-[#ABD2FA]/40 pt-4">
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-[#1B2CC1]">Quiz (optional)</p>
                    <button type="button" @click="addRegQuestion()" class="rounded-xl bg-[#1B2CC1]/10 px-3.5 py-2 text-xs font-bold text-[#1B2CC1] hover:bg-[#1B2CC1]/20 transition-colors cursor-pointer">+ Add question</button>
                </div>
                <p x-show="!regForm.questions.length" class="rounded-2xl border border-dashed border-[#ABD2FA] bg-[#ABD2FA]/10 p-6 text-center text-xs font-medium text-[#091540]/60">No quiz questions. Students can still complete the activity from the description alone.</p>

                <div class="space-y-3">
                    <template x-for="(q, i) in regForm.questions" :key="q._uid || i">
                        <div class="rounded-2xl border border-[#ABD2FA]/60 bg-[#ABD2FA]/10 shadow-xs overflow-hidden">
                            <div class="flex items-center gap-2 px-4 py-3">
                                <button type="button" @click="q._open = !q._open" class="flex min-w-0 flex-1 items-center gap-2.5 text-left cursor-pointer">
                                    <svg class="h-4 w-4 shrink-0 text-[#1B2CC1] transition-transform" :class="q._open && 'rotate-90'" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="rounded-lg bg-[#1B2CC1]/10 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-[#1B2CC1]" x-text="'Q' + (i + 1)"></span>
                                    <span class="truncate text-xs font-bold text-[#091540]" x-text="q.prompt || 'Untitled question'"></span>
                                </button>
                                <button type="button" @click="removeRegQuestion(i)" class="text-xs font-bold text-rose-500 hover:text-rose-700">Remove</button>
                            </div>
                            <div x-show="q._open" class="space-y-3 border-t border-[#ABD2FA]/40 px-4 pb-4 pt-3 bg-white">
                                <div>
                                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#091540]/70">Prompt</label>
                                    <input type="text" x-model="q.prompt" class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none">
                                </div>
                                <template x-for="opt in q.options" :key="opt.id">
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#091540]/70" x-text="'Option ' + (opt.id || '').toUpperCase()"></label>
                                        <input type="text" x-model="opt.text" class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none">
                                    </div>
                                </template>
                                <div>
                                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-[#091540]/70">Correct answer</label>
                                    <select x-model="q.correctOption" class="w-full rounded-xl border border-[#ABD2FA] bg-white px-3 py-2 text-xs font-bold text-[#091540] focus:border-[#1B2CC1] focus:outline-none">
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

            <p x-show="regError" x-text="regError" class="rounded-xl bg-rose-50 p-3 text-xs font-bold text-rose-600 border border-rose-200"></p>
        </div>

        <div class="flex justify-end gap-3 border-t border-[#ABD2FA]/40 px-6 py-4 bg-[#ABD2FA]/10">
            <button type="button" @click="closeRegModal()" class="cursor-pointer rounded-xl border-[1.5px] border-[#ABD2FA] bg-white px-5 py-2.5 text-xs font-bold text-[#091540] hover:bg-[#ABD2FA]/20 transition-colors">Cancel</button>
            <button type="button" @click="saveRegularActivity()" :disabled="regSaving || !regForm.title.trim()"
                class="cursor-pointer rounded-xl bg-[#1B2CC1] px-5 py-2.5 text-xs font-bold text-white hover:bg-[#091540] disabled:opacity-50 transition-colors shadow-sm">
                <span x-text="regSaving ? 'Saving…' : (regModal === 'edit' ? 'Save changes' : 'Save draft')"></span>
            </button>
        </div>
    </div>
</div>