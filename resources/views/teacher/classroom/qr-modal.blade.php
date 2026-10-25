        {{-- QR modal --}}
        <div x-show="showQr" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog">
            <div class="absolute inset-0 bg-slate-900/40" @click="showQr = false"></div>
            <div class="relative w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-xl">
                <h3 class="text-lg font-black text-slate-800">Join this class</h3>
                <div id="class-qr-modal" class="mx-auto mt-4 inline-block"></div>
                <p class="mt-3 font-mono text-2xl font-black tracking-widest text-[#50aed0]" x-text="classData?.access_code"></p>
                <button type="button" @click="showQr = false" class="mt-4 rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600">Close</button>
            </div>
        </div>
