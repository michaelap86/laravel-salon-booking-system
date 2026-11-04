@extends('layouts.site')
@section('title', $staff->name)
@section('description', $staff->bio)

@section('content')
<section class="relative overflow-hidden bg-cream pb-16 pt-32">
    <div class="blob -left-24 -top-24 size-96" style="background: {{ $staff->color }}; opacity: .25"></div>
    <div class="relative mx-auto max-w-5xl px-6">
        <a href="{{ route('team.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-plum-600 hover:text-plum-900"><x-icon name="arrow-left" class="size-4" /> Our team</a>
        <div class="mt-8 flex flex-col items-center gap-8 text-center md:flex-row md:text-left">
            <x-avatar :name="$staff->name" :color="$staff->color" size="size-40 text-5xl shadow-2xl ring-8 ring-white flex-none" />
            <div>
                <h1 class="h-display text-4xl sm:text-5xl">{{ $staff->name }}</h1>
                <p class="mt-2 text-lg text-plum-600">{{ $staff->title }}</p>
                <p class="mt-3 flex items-center justify-center gap-2 md:justify-start"><x-stars :rating="$rating" /> <b>{{ number_format($rating, 1) }}</b> <span class="text-plum-700/60">({{ $reviewCount }} reviews)</span> <span class="text-plum-300">·</span> <span class="text-sm text-plum-700">{{ $staff->years }} years of experience</span></p>
                <a href="{{ route('book') }}" class="btn btn-primary mt-6">Book with {{ $staff->first_name }}</a>
            </div>
        </div>
        <p class="mt-10 max-w-3xl text-lg leading-relaxed text-plum-800/80">{{ $staff->bio }}</p>
        <div class="mt-5 flex flex-wrap gap-2">@foreach (explode(',', $staff->specialties) as $tag)<span class="rounded-full bg-white px-4 py-1.5 text-sm font-medium text-plum-700 shadow-sm">{{ trim($tag) }}</span>@endforeach</div>
    </div>
</section>

<section class="bg-white py-16">
    <div class="mx-auto max-w-5xl px-6">
        <h2 class="font-serif text-3xl font-semibold">Services by {{ $staff->first_name }}</h2>
        <div class="mt-8 divide-y divide-plum-900/5 overflow-hidden rounded-3xl border border-plum-900/5">
            @foreach ($services as $s)
                <a href="{{ route('book', ['service' => $s->slug]) }}" class="flex items-center justify-between gap-4 bg-white px-6 py-4 transition hover:bg-plum-50"><span><b class="block">{{ $s->name }}</b><span class="text-xs text-plum-600">{{ $s->category->name }} · {{ duration($s->duration) }}</span></span><span class="flex items-center gap-4"><b class="font-serif text-lg">{{ money($s->price) }}</b><x-icon name="arrow-right" class="size-4 text-plum-500" /></span></a>
            @endforeach
        </div>
    </div>
</section>

@if ($reviews->isNotEmpty())
    <section class="bg-cream py-16">
        <div class="mx-auto max-w-5xl px-6">
            <h2 class="font-serif text-3xl font-semibold">Recent reviews</h2>
            <div class="mt-8 grid gap-5 md:grid-cols-2">
                @foreach ($reviews->take(4) as $r)
                    <figure class="rounded-3xl bg-white p-6"><x-stars :rating="$r->rating" /><blockquote class="mt-3 text-sm leading-relaxed">“{{ $r->comment }}”</blockquote><figcaption class="mt-4 text-xs font-semibold text-plum-600">{{ $r->client->name }} · {{ $r->service->name }}</figcaption></figure>
                @endforeach
            </div>
        </div>
    </section>
@endif
@endsection
