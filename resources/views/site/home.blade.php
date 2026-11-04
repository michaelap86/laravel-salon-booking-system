@extends('layouts.site')

@section('content')
@php
    $gallery = [
        ['Soft balayage', 'by Sofia', 'linear-gradient(160deg,#fde2e4,#e8a5b3 55%,#b8567a)', '🎨', 'md:row-span-2'],
        ['Chrome gel set', 'by Chloe', 'linear-gradient(135deg,#e0e7ff,#a5b4fc 55%,#7c3aed)', '💅', ''],
        ['Glow facial', 'by Mei Lin', 'linear-gradient(135deg,#fef3c7,#fcd34d 60%,#f59e0b)', '✨', ''],
        ['Bridal updo', 'by Amara', 'linear-gradient(170deg,#fce7f3,#f9a8d4 55%,#be185d)', '👰‍♀️', 'md:row-span-2'],
        ['Brow lamination', 'by Layla', 'linear-gradient(135deg,#d1fae5,#6ee7b7 55%,#059669)', '🦋', ''],
        ['Classic fade', 'by Marco', 'linear-gradient(135deg,#e0f2fe,#7dd3fc 55%,#0369a1)', '💈', ''],
        ['Hot stone ritual', 'by Priya', 'linear-gradient(135deg,#ccfbf1,#5eead4 55%,#0f766e)', '🪨', 'col-span-2 md:col-span-1'],
    ];
    $faqs = [
        ['How does online booking work?', 'Choose a service, pick your favorite stylist (or let us assign the best available one), select a time that suits you and confirm with a small deposit. You will receive an email confirmation right away.'],
        ['Why do you take a deposit?', 'A '.\App\Models\Setting::int('deposit_percent').'% deposit holds your time and covers the stylist if plans change. It is deducted from your final bill.'],
        ['What is your cancellation policy?', 'You can cancel or reschedule for free up to '.\App\Models\Setting::int('free_cancel_hours').' hours before your appointment. Later changes forfeit the deposit.'],
        ['Can I book several services in one visit?', 'Absolutely. Book your first service online, then add the next one right after it on the same day, or call us and we will line them up.'],
        ['Is this a real salon?', 'No. Lumière Studio is a fictional demo built to showcase a full booking system. Everything you see, including prices, clients and reviews, is made-up data that resets every day.'],
    ];
@endphp

{{-- ============================== HERO ============================== --}}
<section class="relative overflow-hidden bg-cream pb-20 pt-32 lg:pb-28 lg:pt-40">
    <div class="blob -left-32 -top-32 size-[520px] bg-rose-200"></div>
    <div class="blob right-[-8rem] top-24 size-[480px] bg-gold-100"></div>
    <div class="blob bottom-[-10rem] left-1/3 size-[420px] bg-plum-100"></div>

    <div class="relative mx-auto grid max-w-7xl items-center gap-14 px-6 lg:grid-cols-[1.05fr_1fr]">
        <div class="animate-fade-up">
            <span class="eyebrow"><x-icon name="star" class="size-3.5 text-gold-500" /> {{ number_format($stats['rating'], 1) }} rated by {{ number_format($stats['reviews']) }} clients</span>
            <h1 class="h-display mt-6 text-5xl leading-[1.05] sm:text-6xl lg:text-7xl">Beauty, <span class="text-gradient italic">beautifully</span> booked.</h1>
            <p class="mt-6 max-w-xl text-lg leading-relaxed text-plum-800/80">Hair, nails, skin and makeup by a team of artists who actually listen. Pick your stylist, see their real-time availability and book in under a minute.</p>
            <div class="mt-9 flex flex-wrap items-center gap-4">
                <a href="{{ route('book') }}" class="btn btn-primary px-8 py-4 text-base">Book your visit <x-icon name="arrow-right" class="size-4" /></a>
                <a href="{{ route('services.index') }}" class="btn btn-ghost px-8 py-4 text-base">Explore services</a>
            </div>
            <div class="mt-10 flex items-center gap-4">
                <div class="flex -space-x-3">
                    @foreach ($staff->take(5) as $s)
                        <x-avatar :name="$s->name" :color="$s->color" size="size-11 text-xs ring-4 ring-cream" />
                    @endforeach
                </div>
                <p class="text-sm text-plum-800/75"><b class="text-plum-900">{{ $staff->count() }} specialists</b> ready to look after you,<br>Tuesday to Sunday.</p>
            </div>
        </div>

        {{-- Illustration --}}
        <div class="relative mx-auto h-[500px] w-full max-w-[520px] sm:h-[560px]" aria-hidden="true">
            <div class="arch absolute inset-x-[19%] top-0 h-full overflow-hidden bg-gradient-to-b from-plum-100 via-rose-200 to-plum-400 shadow-2xl shadow-plum-900/20">
                <div class="absolute -top-16 left-1/2 size-72 -translate-x-1/2 rounded-full bg-white/50 blur-2xl"></div>
                <div class="absolute inset-0 grid place-items-center text-[9rem] drop-shadow-xl sm:text-[11rem]"><span class="animate-float-slow">💇‍♀️</span></div>
                <div class="absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-plum-900/40 to-transparent"></div>
            </div>
            <div class="arch absolute bottom-0 left-0 h-[52%] w-[34%] overflow-hidden bg-gradient-to-b from-gold-100 to-gold-400 shadow-xl shadow-gold-600/20">
                <div class="absolute inset-0 grid place-items-center text-6xl"><span class="animate-float" style="--r:-8deg">💅</span></div>
            </div>
            <div class="arch absolute right-0 top-[9%] h-[48%] w-[31%] overflow-hidden bg-gradient-to-b from-rose-100 to-rose-400 shadow-xl shadow-rose-500/20">
                <div class="absolute inset-0 grid place-items-center text-6xl"><span class="animate-float-slow" style="--r:8deg">💄</span></div>
            </div>
            <div class="absolute -left-4 top-[30%] size-16 rounded-full border-[6px] border-gold-400/70"></div>
            <div class="absolute right-6 bottom-[22%] size-5 rounded-full bg-gold-500"></div>

            @if ($next)
                <div class="glass animate-float absolute -left-2 top-[7%] z-10 w-56 rounded-2xl p-4 sm:-left-8">
                    <p class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-widest text-plum-600"><span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span></span> Next available</p>
                    <p class="mt-1.5 font-serif text-lg font-semibold leading-tight text-plum-900">{{ $next['when'] }}</p>
                    <p class="text-xs text-plum-700/70">{{ $next['service'] }}</p>
                </div>
            @endif
            <div class="glass animate-float-slow absolute -right-2 bottom-[8%] z-10 w-60 rounded-2xl p-4 sm:-right-6" style="--r:2deg">
                <x-stars :rating="5" />
                <p class="mt-2 text-sm leading-snug text-plum-900">“Best balayage I have ever had. I left feeling brand new.”</p>
                <p class="mt-2 text-xs font-semibold text-plum-600">Olivia M. · Balayage</p>
            </div>
        </div>
    </div>
</section>

{{-- ============================== MARQUEE ============================== --}}
<section class="overflow-hidden border-y border-plum-900/10 bg-plum-900 py-4 text-plum-100" aria-hidden="true">
    <div class="flex w-max animate-marquee gap-10 whitespace-nowrap font-serif text-xl italic">
        @for ($i = 0; $i < 2; $i++)
            @foreach (['Balayage', 'Gel manicure', 'Signature facial', 'Bridal makeup', 'Lash lift', 'Silk blowout', 'Hot stone massage', 'Brow lamination', 'Nail art', 'Keratin smoothing'] as $w)
                <span class="flex items-center gap-10">{{ $w }} <span class="text-gold-400">✦</span></span>
            @endforeach
        @endfor
    </div>
</section>

{{-- ============================== STATS ============================== --}}
<section class="bg-white py-14">
    <div class="mx-auto grid max-w-7xl grid-cols-2 gap-8 px-6 text-center lg:grid-cols-4">
        @foreach ([[$stats['clients'], 'Happy clients', '+', '0'], [$stats['reviews'], 'Five-star reviews', '', '0'], [$stats['rating'], 'Average rating', '', '1'], [$staff->count(), 'Expert artists', '', '0']] as [$n, $label, $suffix])
            <div data-reveal>
                <p class="font-serif text-5xl font-semibold text-plum-900" data-count="{{ $n }}" data-suffix="{{ $suffix }}">0</p>
                <p class="mt-2 text-sm uppercase tracking-[0.18em] text-plum-600">{{ $label }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ============================== SERVICES ============================== --}}
<section id="services" class="bg-cream py-24" x-data="{ cat: '{{ $categories->first()->slug }}' }">
    <div class="mx-auto max-w-7xl px-6">
        <div class="mx-auto max-w-2xl text-center" data-reveal>
            <span class="eyebrow">Our services</span>
            <h2 class="h-display mt-5 text-4xl sm:text-5xl">Everything you need, <span class="italic text-plum-500">all in one place</span></h2>
            <p class="mt-4 text-plum-800/75">From a quick manicure to a full transformation, every service is delivered by a specialist who loves what they do.</p>
        </div>

        <div class="thin-scroll mt-12 flex gap-3 overflow-x-auto pb-3 md:flex-wrap md:justify-center" data-reveal>
            @foreach ($categories as $c)
                <button type="button" @click="cat = '{{ $c->slug }}'" class="flex flex-none items-center gap-2 rounded-full border px-5 py-2.5 text-sm font-semibold transition"
                        :class="cat === '{{ $c->slug }}' ? 'border-plum-900 bg-plum-900 text-white shadow-lg shadow-plum-900/20' : 'border-plum-900/10 bg-white text-plum-800 hover:border-plum-300'">
                    <span>{{ $c->icon }}</span> {{ $c->name }}
                </button>
            @endforeach
        </div>

        @foreach ($categories as $c)
            <div x-show="cat === '{{ $c->slug }}'" @if (! $loop->first) x-cloak @endif x-transition.opacity.duration.300ms class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($c->services as $s)
                    <a href="{{ route('book', ['service' => $s->slug]) }}" class="group lift relative overflow-hidden rounded-3xl border border-plum-900/5 bg-white p-6">
                        <div class="theme-{{ $c->theme }} theme-fill absolute -right-10 -top-10 size-32 rounded-full opacity-25 transition duration-500 group-hover:scale-150"></div>
                        <div class="relative">
                            <span class="theme-{{ $c->theme }} theme-fill grid size-12 place-items-center rounded-2xl text-2xl shadow-md">{{ $c->icon }}</span>
                            <h3 class="mt-5 font-serif text-xl font-semibold">{{ $s->name }}</h3>
                            <p class="mt-1.5 line-clamp-2 text-sm leading-relaxed text-plum-800/70">{{ $s->description }}</p>
                            <div class="mt-5 flex items-center justify-between border-t border-plum-900/5 pt-4">
                                <span class="flex items-center gap-1.5 text-xs font-medium text-plum-700/70"><x-icon name="clock" class="size-3.5" /> {{ duration($s->duration) }}</span>
                                <span class="flex items-center gap-2"><b class="font-serif text-xl">{{ money($s->price) }}</b><span class="grid size-8 place-items-center rounded-full bg-plum-50 text-plum-700 transition group-hover:bg-plum-900 group-hover:text-white"><x-icon name="arrow-right" class="size-4" /></span></span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endforeach
        <div class="mt-10 text-center"><a href="{{ route('services.index') }}" class="btn btn-ghost">See the full menu <x-icon name="arrow-right" class="size-4" /></a></div>
    </div>
</section>

{{-- ============================== WHY ============================== --}}
<section class="relative overflow-hidden bg-white py-24">
    <div class="mx-auto max-w-7xl px-6">
        <div class="grid gap-14 lg:grid-cols-[1fr_1.15fr] lg:items-center">
            <div data-reveal>
                <span class="eyebrow">Why Lumière</span>
                <h2 class="h-display mt-5 text-4xl sm:text-5xl">A salon that <span class="italic text-plum-500">respects your time</span></h2>
                <p class="mt-5 max-w-md text-plum-800/75">We built the booking experience we always wanted: honest availability, no phone tag and no surprises.</p>
                <a href="{{ route('book') }}" class="btn btn-primary mt-8">Book online <x-icon name="arrow-right" class="size-4" /></a>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                @foreach ([
                    ['calendar', 'Book 24/7', 'Real-time availability for every stylist, any hour of the day.', 'from-rose-100 to-rose-50'],
                    ['wallet', 'Small deposit', 'Hold your slot with just '.\App\Models\Setting::int('deposit_percent').'%, deducted from your bill.', 'from-gold-100 to-amber-50'],
                    ['refresh', 'Free rescheduling', 'Change plans up to '.\App\Models\Setting::int('free_cancel_hours').' hours before, no questions asked.', 'from-sky-100 to-sky-50'],
                    ['award', 'Loyalty rewards', 'Earn points on every visit and unlock Silver and Gold perks.', 'from-plum-100 to-plum-50'],
                ] as $i => [$icon, $title, $text, $bg])
                    <div class="lift rounded-3xl bg-gradient-to-br {{ $bg }} p-6" data-reveal style="--d: {{ $i * 90 }}ms">
                        <span class="grid size-12 place-items-center rounded-2xl bg-white text-plum-700 shadow-sm"><x-icon :name="$icon" /></span>
                        <h3 class="mt-5 font-serif text-xl font-semibold">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-plum-800/75">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ============================== HOW IT WORKS ============================== --}}
<section class="bg-sand py-24">
    <div class="mx-auto max-w-6xl px-6">
        <div class="mx-auto max-w-2xl text-center" data-reveal>
            <span class="eyebrow">How it works</span>
            <h2 class="h-display mt-5 text-4xl sm:text-5xl">Booked in <span class="italic text-plum-500">three easy steps</span></h2>
        </div>
        <div class="relative mt-16 grid gap-10 md:grid-cols-3">
            <div class="absolute left-[16%] right-[16%] top-8 hidden border-t-2 border-dashed border-plum-300 md:block"></div>
            @foreach ([['1', 'Choose your service', 'Browse the menu and pick what you fancy, from a quick brow tint to a full color.'], ['2', 'Pick a stylist and time', 'See live availability, choose your favorite artist or let us match you.'], ['3', 'Confirm and relax', 'Secure your visit with a small deposit and get an instant confirmation email.']] as $i => [$n, $t, $d])
                <div class="relative text-center" data-reveal style="--d: {{ $i * 120 }}ms">
                    <span class="relative mx-auto grid size-16 place-items-center rounded-full bg-plum-900 font-serif text-2xl text-gold-300 shadow-xl shadow-plum-900/25 ring-8 ring-sand">{{ $n }}</span>
                    <h3 class="mt-6 font-serif text-xl font-semibold">{{ $t }}</h3>
                    <p class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-plum-800/75">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================== SIGNATURE ============================== --}}
<section class="bg-cream py-24">
    <div class="mx-auto max-w-7xl px-6">
        <div class="flex flex-wrap items-end justify-between gap-4" data-reveal>
            <div><span class="eyebrow">Client favorites</span><h2 class="h-display mt-5 text-4xl sm:text-5xl">Our signature <span class="italic text-plum-500">treatments</span></h2></div>
            <a href="{{ route('services.index') }}" class="btn btn-ghost">View all <x-icon name="arrow-right" class="size-4" /></a>
        </div>
        <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($featured->take(6) as $i => $s)
                <a href="{{ route('services.show', $s) }}" class="group lift overflow-hidden rounded-3xl bg-white" data-reveal style="--d: {{ ($i % 3) * 100 }}ms">
                    <div class="theme-{{ $s->category->theme }} theme-fill relative grid h-44 place-items-center overflow-hidden">
                        <div class="absolute -left-10 -top-10 size-40 rounded-full bg-white/25"></div><div class="absolute -bottom-12 -right-6 size-44 rounded-full bg-white/20"></div>
                        <span class="relative text-7xl drop-shadow-lg transition duration-500 group-hover:scale-110 group-hover:-rotate-6">{{ $s->category->icon }}</span>
                        <span class="absolute left-4 top-4 rounded-full bg-white/85 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-plum-800">Favorite</span>
                    </div>
                    <div class="p-6">
                        <p class="text-xs font-semibold uppercase tracking-widest text-plum-500">{{ $s->category->name }}</p>
                        <h3 class="mt-1.5 font-serif text-2xl font-semibold">{{ $s->name }}</h3>
                        <p class="mt-2 line-clamp-2 text-sm text-plum-800/70">{{ $s->description }}</p>
                        <div class="mt-5 flex items-center justify-between"><span class="text-sm text-plum-700/70">{{ duration($s->duration) }}</span><b class="font-serif text-2xl">{{ money($s->price) }}</b></div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================== TEAM ============================== --}}
<section id="team" class="bg-white py-24">
    <div class="mx-auto max-w-7xl px-6">
        <div class="mx-auto max-w-2xl text-center" data-reveal>
            <span class="eyebrow">Meet the artists</span>
            <h2 class="h-display mt-5 text-4xl sm:text-5xl">Hands you can <span class="italic text-plum-500">trust</span></h2>
        </div>
        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($staff as $i => $s)
                <a href="{{ route('team.show', $s) }}" class="group lift rounded-3xl bg-cream p-6 text-center" data-reveal style="--d: {{ ($i % 4) * 90 }}ms">
                    <div class="relative mx-auto size-28">
                        <div class="absolute inset-0 rounded-full opacity-30 blur-md transition group-hover:opacity-60" style="background: {{ $s->color }}"></div>
                        <x-avatar :name="$s->name" :color="$s->color" size="relative size-28 text-3xl ring-4 ring-white" />
                    </div>
                    <h3 class="mt-5 font-serif text-xl font-semibold">{{ $s->name }}</h3>
                    <p class="mt-1 text-sm text-plum-600">{{ $s->title }}</p>
                    <p class="mt-3 flex items-center justify-center gap-1.5 text-sm"><x-icon name="star" class="size-4 fill-gold-500 text-gold-500" /> <b>{{ number_format((float) $s->rating, 1) }}</b> <span class="text-plum-700/60">({{ $s->review_count }})</span></p>
                    <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-plum-700 transition group-hover:gap-2">View profile <x-icon name="arrow-right" class="size-4" /></span>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================== GALLERY ============================== --}}
<section id="gallery" class="bg-plum-950 py-24 text-white">
    <div class="mx-auto max-w-7xl px-6">
        <div class="mx-auto max-w-2xl text-center" data-reveal>
            <span class="eyebrow !bg-white/10 !text-gold-300">The mood board</span>
            <h2 class="mt-5 font-serif text-4xl font-medium sm:text-5xl">Looks we <span class="italic text-gold-300">love</span></h2>
            <p class="mt-4 text-plum-200/75">A peek at what our artists are creating this season.</p>
        </div>
        <div class="mt-14 grid auto-rows-[190px] grid-cols-2 gap-4 md:grid-cols-4">
            @foreach ($gallery as $i => [$title, $by, $bg, $emoji, $span])
                <div class="group relative overflow-hidden rounded-3xl {{ $span }}" style="background: {{ $bg }}" data-reveal style="--d: {{ $i * 60 }}ms">
                    <div class="absolute -right-8 -top-8 size-36 rounded-full bg-white/20 transition duration-500 group-hover:scale-150"></div>
                    <div class="absolute inset-0 grid place-items-center text-7xl opacity-90 drop-shadow-xl transition duration-500 group-hover:scale-110 md:text-8xl">{{ $emoji }}</div>
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/55 to-transparent p-4">
                        <p class="font-serif text-lg font-semibold">{{ $title }}</p><p class="text-xs text-white/75">{{ $by }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================== REVIEWS ============================== --}}
<section id="reviews" class="bg-cream py-24" x-data="carousel" @mouseenter="pause()" @mouseleave="start()">
    <div class="mx-auto max-w-4xl px-6 text-center">
        <div data-reveal>
            <span class="eyebrow">Kind words</span>
            <h2 class="h-display mt-5 text-4xl sm:text-5xl">Loved by <span class="italic text-plum-500">{{ number_format($stats['clients']) }}+ clients</span></h2>
        </div>
        <div class="relative mt-12 min-h-[290px]" x-ref="track">
            @foreach ($reviews as $i => $r)
                <figure x-show="i === {{ $i }}" @if ($i) x-cloak @endif x-transition:enter="transition duration-500" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="absolute inset-x-0 top-0">
                    <div class="font-serif text-7xl leading-none text-gold-400">“</div>
                    <x-stars :rating="$r->rating" size="size-5" class="mt-1" />
                    <blockquote class="mx-auto mt-5 max-w-2xl font-serif text-2xl leading-relaxed text-plum-900 sm:text-3xl">{{ $r->comment }}</blockquote>
                    <figcaption class="mt-7 flex items-center justify-center gap-3">
                        <x-avatar :name="$r->client->name" size="size-11 text-sm" />
                        <span class="text-left text-sm"><b class="block text-plum-900">{{ $r->client->name }}</b><span class="text-plum-600">{{ $r->service->name }} with {{ explode(' ', $r->staff->name)[0] }}</span></span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
        <div class="mt-6 flex justify-center gap-2">
            @foreach ($reviews as $i => $r)
                <button type="button" @click="go({{ $i }})" class="h-2 rounded-full transition-all" :class="i === {{ $i }} ? 'w-8 bg-plum-700' : 'w-2 bg-plum-300 hover:bg-plum-400'" aria-label="Review {{ $i + 1 }}"></button>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================== LOYALTY ============================== --}}
<section class="bg-white py-24">
    <div class="mx-auto max-w-7xl px-6">
        <div class="relative overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-plum-900 via-plum-800 to-plum-700 px-8 py-14 text-white sm:px-14" data-reveal>
            <div class="blob -right-16 -top-16 size-80 bg-gold-500 !opacity-30"></div>
            <div class="relative grid gap-10 lg:grid-cols-[1fr_1.3fr] lg:items-center">
                <div>
                    <span class="eyebrow !bg-white/10 !text-gold-300">Lumière Rewards</span>
                    <h2 class="mt-5 font-serif text-4xl font-medium sm:text-5xl">Every visit <span class="italic text-gold-300">earns you more</span></h2>
                    <p class="mt-4 max-w-md text-plum-100/80">Earn a point for every $10 you spend. Reach Silver and Gold for lasting perks. Free to join, automatic from your first visit.</p>
                    <a href="{{ route('register') }}" class="btn btn-gold mt-7">Join for free</a>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach (config('salon.loyalty_tiers') as $t)
                        <div class="rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur">
                            <span class="grid size-11 place-items-center rounded-full {{ ['Bronze' => 'bg-amber-700/60', 'Silver' => 'bg-slate-300/50', 'Gold' => 'bg-gold-400/70'][$t['name']] }} text-lg">🏅</span>
                            <h3 class="mt-4 font-serif text-xl font-semibold">{{ $t['name'] }}</h3>
                            <p class="text-xs uppercase tracking-wider text-gold-300">{{ $t['min'] }}+ points</p>
                            <p class="mt-3 text-sm text-plum-100/85">{{ $t['perk'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================== FAQ ============================== --}}
<section class="bg-cream py-24">
    <div class="mx-auto max-w-3xl px-6">
        <div class="text-center" data-reveal><span class="eyebrow">Good to know</span><h2 class="h-display mt-5 text-4xl sm:text-5xl">Questions, <span class="italic text-plum-500">answered</span></h2></div>
        <div class="mt-12 space-y-3" x-data="{ open: 0 }">
            @foreach ($faqs as $i => [$q, $a])
                <div class="overflow-hidden rounded-2xl border border-plum-900/5 bg-white" data-reveal>
                    <button type="button" class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left font-semibold" @click="open = open === {{ $i }} ? null : {{ $i }}">
                        {{ $q }} <x-icon name="chevron-down" class="size-5 flex-none text-plum-500 transition" ::class="open === {{ $i }} && 'rotate-180'" />
                    </button>
                    <div x-show="open === {{ $i }}" x-collapse><p class="px-6 pb-6 text-sm leading-relaxed text-plum-800/75">{{ $a }}</p></div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================== CTA ============================== --}}
<section class="relative overflow-hidden bg-gradient-to-br from-plum-900 to-plum-700 py-24 text-center text-white">
    <div class="blob -left-20 top-0 size-96 bg-rose-400 !opacity-25"></div><div class="blob -right-20 bottom-0 size-96 bg-gold-500 !opacity-25"></div>
    <div class="relative mx-auto max-w-3xl px-6" data-reveal>
        <h2 class="font-serif text-4xl font-medium sm:text-6xl">Ready for your <span class="italic text-gold-300">glow-up?</span></h2>
        <p class="mx-auto mt-5 max-w-xl text-lg text-plum-100/80">Your next appointment is a minute away. Pick a service, choose a time, and we will take care of the rest.</p>
        <a href="{{ route('book') }}" class="btn btn-gold mt-9 px-10 py-4 text-base">Book an appointment <x-icon name="arrow-right" class="size-4" /></a>
    </div>
</section>
@endsection
