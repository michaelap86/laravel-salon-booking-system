@extends('layouts.site')
@section('title', 'Our team')

@section('content')
<x-page-header eyebrow="The artists" title="Meet our team" subtitle="Licensed specialists with years of craft behind every cut, color, set and glow." />

<section class="bg-cream pb-24">
    <div class="mx-auto grid max-w-7xl gap-8 px-6 md:grid-cols-2">
        @foreach ($staff as $s)
            <article class="lift flex gap-6 rounded-3xl border border-plum-900/5 bg-white p-6" data-reveal>
                <x-avatar :name="$s->name" :color="$s->color" size="size-24 text-3xl flex-none shadow-lg" />
                <div class="min-w-0">
                    <h2 class="font-serif text-2xl font-semibold">{{ $s->name }}</h2>
                    <p class="text-sm font-medium text-plum-600">{{ $s->title }} · {{ $s->years }} yrs</p>
                    <p class="mt-2 flex items-center gap-1.5 text-sm"><x-stars :rating="$s->rating" /> <b>{{ number_format((float) $s->rating, 1) }}</b> <span class="text-plum-700/60">({{ $s->review_count }})</span></p>
                    <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-plum-800/70">{{ $s->bio }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">@foreach (explode(',', $s->specialties) as $tag)<span class="rounded-full bg-plum-50 px-3 py-1 text-xs font-medium text-plum-700">{{ trim($tag) }}</span>@endforeach</div>
                    <div class="mt-5 flex gap-3"><a href="{{ route('team.show', $s) }}" class="btn btn-ghost btn-sm">Profile</a><a href="{{ route('book') }}" class="btn btn-primary btn-sm">Book {{ $s->first_name }}</a></div>
                </div>
            </article>
        @endforeach
    </div>
</section>
@endsection
