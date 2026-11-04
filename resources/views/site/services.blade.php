@extends('layouts.site')
@section('title', 'Services & prices')
@section('description', 'Browse every hair, nail, skin, makeup, lash and massage service at Lumière Studio.')

@section('content')
<x-page-header eyebrow="The menu" title="Services & prices" subtitle="Everything we offer, with honest durations and prices. Prices are fictional demo data." />

<section class="bg-cream pb-24" x-data="servicesBrowser">
    <div class="mx-auto max-w-7xl px-6">
        <div class="sticky top-20 z-20 -mx-6 flex flex-col gap-3 bg-cream/90 px-6 py-4 backdrop-blur-lg md:flex-row md:items-center md:justify-between">
            <div class="thin-scroll flex gap-2 overflow-x-auto pb-1">
                <button type="button" @click="cat = 'all'" class="flex-none rounded-full border px-4 py-2 text-sm font-semibold transition" :class="cat === 'all' ? 'border-plum-900 bg-plum-900 text-white' : 'border-plum-900/10 bg-white hover:border-plum-300'">All</button>
                @foreach ($categories as $c)
                    <button type="button" @click="cat = '{{ $c->slug }}'" class="flex-none rounded-full border px-4 py-2 text-sm font-semibold transition" :class="cat === '{{ $c->slug }}' ? 'border-plum-900 bg-plum-900 text-white' : 'border-plum-900/10 bg-white hover:border-plum-300'">{{ $c->icon }} {{ $c->name }}</button>
                @endforeach
            </div>
            <div class="relative md:w-72"><x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-plum-500" /><input type="search" x-model="q" placeholder="Search services…" class="field !rounded-full !pl-10"></div>
        </div>

        <div class="mt-6 space-y-14">
            @foreach ($categories as $c)
                <div x-show="cat === 'all' || cat === '{{ $c->slug }}'" x-transition.opacity>
                    <div class="mb-5 flex items-center gap-4">
                        <span class="theme-{{ $c->theme }} theme-fill grid size-12 place-items-center rounded-2xl text-2xl shadow">{{ $c->icon }}</span>
                        <div><h2 class="font-serif text-2xl font-semibold">{{ $c->name }}</h2><p class="text-sm text-plum-700/70">{{ $c->blurb }}</p></div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach ($c->services as $s)
                            <div x-show="visible($el)" data-cat="{{ $c->slug }}" data-name="{{ $s->name }}" class="group flex items-center gap-5 rounded-2xl border border-plum-900/5 bg-white p-5 transition hover:border-plum-200 hover:shadow-lg hover:shadow-plum-900/5">
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('services.show', $s) }}" class="font-serif text-lg font-semibold transition hover:text-plum-500">{{ $s->name }}</a>
                                    <p class="mt-1 line-clamp-2 text-sm text-plum-800/65">{{ $s->description }}</p>
                                    <p class="mt-2 flex items-center gap-1.5 text-xs font-medium text-plum-600"><x-icon name="clock" class="size-3.5" /> {{ duration($s->duration) }}</p>
                                </div>
                                <div class="flex flex-none flex-col items-end gap-2"><b class="font-serif text-2xl">{{ money($s->price) }}</b><a href="{{ route('book', ['service' => $s->slug]) }}" class="btn btn-primary btn-sm">Book</a></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
