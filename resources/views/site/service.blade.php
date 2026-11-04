@extends('layouts.site')
@section('title', $service->name)
@section('description', $service->description)

@section('content')
<section class="relative overflow-hidden bg-cream pb-20 pt-32">
    <div class="blob -left-24 -top-24 size-96 bg-rose-200"></div>
    <div class="relative mx-auto max-w-7xl px-6">
        <a href="{{ route('services.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-plum-600 hover:text-plum-900"><x-icon name="arrow-left" class="size-4" /> All services</a>
        <div class="mt-8 grid gap-10 lg:grid-cols-[1.2fr_1fr]">
            <div>
                <span class="eyebrow">{{ $service->category->icon }} {{ $service->category->name }}</span>
                <h1 class="h-display mt-5 text-4xl sm:text-6xl">{{ $service->name }}</h1>
                <p class="mt-5 max-w-xl text-lg leading-relaxed text-plum-800/75">{{ $service->description }}</p>
                <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm">
                    <span class="flex items-center gap-2"><x-icon name="clock" class="size-4 text-plum-500" /> {{ duration($service->duration) }}</span>
                    @if ($reviewCount)<span class="flex items-center gap-2"><x-stars :rating="$rating" /> <b>{{ number_format($rating, 1) }}</b> <span class="text-plum-700/60">({{ $reviewCount }} reviews)</span></span>@endif
                    @if ($service->deposit_required)<span class="flex items-center gap-2"><x-icon name="wallet" class="size-4 text-plum-500" /> {{ \App\Models\Setting::int('deposit_percent') }}% deposit</span>@endif
                </div>

                <h2 class="mt-12 font-serif text-2xl font-semibold">Who can do this for you</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    @foreach ($staff as $s)
                        <a href="{{ route('team.show', $s) }}" class="lift flex items-center gap-4 rounded-2xl border border-plum-900/5 bg-white p-4"><x-avatar :name="$s->name" :color="$s->color" size="size-14 text-lg" /><span><b class="block">{{ $s->name }}</b><span class="text-sm text-plum-600">{{ $s->title }}</span></span></a>
                    @endforeach
                </div>
            </div>

            <aside class="lg:pl-6">
                <div class="glass sticky top-28 rounded-3xl p-7">
                    <p class="text-xs font-semibold uppercase tracking-widest text-plum-600">Price</p>
                    <p class="mt-1 font-serif text-5xl font-semibold">{{ money($service->price) }}</p>
                    <ul class="mt-5 space-y-2.5 text-sm text-plum-800/80">
                        <li class="flex items-center gap-2.5"><x-icon name="check-circle" class="size-4 text-emerald-600" /> Free cancellation up to {{ \App\Models\Setting::int('free_cancel_hours') }} h before</li>
                        <li class="flex items-center gap-2.5"><x-icon name="check-circle" class="size-4 text-emerald-600" /> Instant email confirmation</li>
                        <li class="flex items-center gap-2.5"><x-icon name="check-circle" class="size-4 text-emerald-600" /> Earn loyalty points</li>
                    </ul>
                    <a href="{{ route('book', ['service' => $service->slug]) }}" class="btn btn-primary mt-7 w-full py-4">Book this service</a>
                </div>
            </aside>
        </div>
    </div>
</section>

@if ($reviews->isNotEmpty())
    <section class="bg-white py-20">
        <div class="mx-auto max-w-7xl px-6">
            <h2 class="font-serif text-3xl font-semibold">What clients say</h2>
            <div class="mt-8 grid gap-5 md:grid-cols-3">
                @foreach ($reviews->take(3) as $r)
                    <figure class="rounded-3xl bg-cream p-6"><x-stars :rating="$r->rating" /><blockquote class="mt-3 text-sm leading-relaxed">“{{ $r->comment }}”</blockquote><figcaption class="mt-4 text-xs font-semibold text-plum-600">{{ $r->client->name }} · with {{ explode(' ', $r->staff->name)[0] }}</figcaption></figure>
                @endforeach
            </div>
        </div>
    </section>
@endif

<section class="bg-cream py-20">
    <div class="mx-auto max-w-7xl px-6">
        <h2 class="font-serif text-3xl font-semibold">You might also like</h2>
        <div class="mt-8 grid gap-5 md:grid-cols-3">
            @foreach ($related as $s)
                <a href="{{ route('services.show', $s) }}" class="lift rounded-3xl bg-white p-6"><h3 class="font-serif text-xl font-semibold">{{ $s->name }}</h3><p class="mt-1 line-clamp-2 text-sm text-plum-800/70">{{ $s->description }}</p><div class="mt-4 flex justify-between text-sm"><span class="text-plum-600">{{ duration($s->duration) }}</span><b class="font-serif text-lg">{{ money($s->price) }}</b></div></a>
            @endforeach
        </div>
    </div>
</section>
@endsection
