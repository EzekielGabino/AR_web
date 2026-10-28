{{-- QR modal --}}
<div x-show="showQr" x-cloak class="fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-[#091540]/80 backdrop-blur-sm" role="dialog">
    <div class="absolute inset-0" @click="showQr = false"></div>
    <div class="relative w-full max-w-sm rounded-3xl border border-[#7692FF]/40 bg-[#091540] p-6 sm:p-8 text-center shadow-2xl shadow-[#1B2CC1]/30 text-white">
        <h3 class="text-lg font-extrabold text-white">Join this class</h3>
        <p class="mt-1 text-xs font-medium text-[#ABD2FA]/80">Students can scan this QR code or use the access code below.</p>
        <div id="class-qr-modal" class="mx-auto mt-6 inline-block rounded-2xl bg-white p-4 shadow-md"></div>
        <p class="mt-5 font-mono text-2xl font-black tracking-widest text-[#ABD2FA]" x-text="classData?.access_code"></p>
        <div class="mt-6 flex justify-center border-t border-white/10 pt-4">
            <button type="button" @click="showQr = false" class="cursor-pointer rounded-2xl border-[1.5px] border-[#7692FF]/40 bg-white/5 px-6 py-3 text-xs font-bold text-white transition-all hover:bg-white/10">Close</button>
        </div>
    </div>
</div>