@props(['eyebrow' => null, 'title', 'subtitle' => null])
<section class="relative overflow-hidden bg-cream pb-14 pt-36">
    <div class="blob -left-24 -top-24 size-96 bg-rose-200"></div>
    <div class="blob -right-24 top-10 size-80 bg-gold-100"></div>
    <div class="relative mx-auto max-w-4xl px-6 text-center animate-fade-up">
        @if ($eyebrow)<span class="eyebrow">{{ $eyebrow }}</span>@endif
        <h1 class="h-display mt-5 text-4xl sm:text-6xl">{{ $title }}</h1>
        @if ($subtitle)<p class="mx-auto mt-4 max-w-2xl text-lg text-plum-800/75">{{ $subtitle }}</p>@endif
        {{ $slot }}
    </div>
</section>
