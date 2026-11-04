{{-- The developer's "I'm a guy, I don't know what women's services cost" disclaimer. Driven by Alpine.store('joke') in common.js --}}
<div x-data x-cloak x-show="$store.joke.open" class="fixed inset-0 z-[90] grid place-items-center p-4" @keydown.escape.window="$store.joke.close()">
    <div class="absolute inset-0 bg-plum-950/60 backdrop-blur-sm" x-show="$store.joke.open" x-transition.opacity.duration.300ms @click="$store.joke.close()"></div>

    <div x-show="$store.joke.open" x-trap.noscroll="$store.joke.open"
         x-transition:enter="transition ease-[cubic-bezier(.34,1.56,.64,1)] duration-500" x-transition:enter-start="opacity-0 scale-50 translate-y-10 rotate-3"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0 rotate-0" x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-90"
         class="relative w-full max-w-md overflow-hidden rounded-3xl bg-surface text-ink shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="joke-title">

        <div class="relative overflow-hidden bg-gradient-to-br from-rose-400 via-fuchsia-500 to-violet-600 px-6 pb-9 pt-8 text-center">
            <span class="joke-sparkle left-6 top-5 text-2xl" style="animation-delay:.2s">✨</span>
            <span class="joke-sparkle right-8 top-8 text-xl" style="animation-delay:1.1s">💖</span>
            <span class="joke-sparkle bottom-4 left-10 text-xl" style="animation-delay:2s">✨</span>
            <span class="joke-sparkle bottom-6 right-12 text-2xl" style="animation-delay:.7s">🌸</span>
            <div class="relative flex items-end justify-center gap-3 text-5xl">
                <template x-for="(e, i) in $store.joke.current.emojis" :key="i">
                    <span class="joke-emoji drop-shadow-lg" :style="`animation-delay:${i * 0.25}s; font-size:${i === 1 ? '4.2rem' : '3rem'}`" x-text="e"></span>
                </template>
            </div>
        </div>

        <div class="-mt-5 rounded-t-3xl bg-surface px-7 pb-7 pt-6 text-center">
            <h2 id="joke-title" class="font-serif text-2xl font-semibold" x-text="$store.joke.current.title"></h2>
            <p class="mt-3 text-sm leading-relaxed text-muted [&_b]:font-semibold [&_b]:text-ink" x-html="$store.joke.current.body"></p>
            <div class="mt-6 flex flex-col gap-2.5 sm:flex-row">
                <button type="button" class="a-btn a-btn-primary flex-1" @click="$store.joke.close()" x-text="$store.joke.current.yes"></button>
                <button type="button" class="a-btn a-btn-ghost flex-1" @click="$store.joke.decline()" x-text="$store.joke.current.no"></button>
            </div>
            <p class="mt-4 text-[11px] uppercase tracking-widest text-muted/70">Developer disclaimer · prices are demo data</p>
        </div>
    </div>
</div>
