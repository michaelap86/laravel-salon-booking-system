<div x-data class="pointer-events-none fixed bottom-5 right-5 z-[95] flex w-[min(92vw,380px)] flex-col gap-2.5">
    <template x-for="t in $store.toast.items" :key="t.id">
        <div class="toast-enter pointer-events-auto flex items-start gap-3 rounded-2xl border border-line bg-surface px-4 py-3 text-sm text-ink shadow-xl shadow-black/10">
            <span class="mt-0.5 grid size-6 flex-none place-items-center rounded-full" :class="t.type === 'error' ? 'bg-rose-500/15 text-rose-500' : 'bg-emerald-500/15 text-emerald-600'">
                <svg x-show="t.type !== 'error'" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                <svg x-show="t.type === 'error'" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </span>
            <span class="leading-snug" x-text="t.message"></span>
        </div>
    </template>
</div>
@if (session('success') || session('error'))
    <script>document.addEventListener('alpine:initialized', () => window.toast(@json(session('success') ?? session('error')), @json(session('error') ? 'error' : 'success')));</script>
@endif
