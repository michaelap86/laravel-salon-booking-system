@extends('layouts.site')
@section('title', 'My account')

@section('content')
@include('account._nav', ['heading' => 'Hello, '.explode(' ', $client->name)[0].' 👋'])

<section class="bg-cream pb-24 pt-6">
    <div class="mx-auto grid max-w-6xl gap-6 px-6 lg:grid-cols-[1.6fr_1fr]">
        <div class="space-y-6">
            @php $next = $upcoming->first(); @endphp
            @if ($next)
                <div class="theme-{{ $next->service->category->theme }} theme-fill relative overflow-hidden rounded-3xl p-7 shadow-xl shadow-plum-900/10">
                    <div class="absolute -right-10 -top-10 size-44 rounded-full bg-white/25"></div>
                    <p class="relative text-xs font-bold uppercase tracking-[0.2em] text-plum-900/70">Your next visit · {{ $next->start_at->diffForHumans() }}</p>
                    <h2 class="relative mt-2 font-serif text-3xl font-semibold text-plum-950">{{ $next->service->name }}</h2>
                    <p class="relative mt-2 flex flex-wrap items-center gap-x-5 gap-y-1 text-plum-900/80"><span class="flex items-center gap-1.5"><x-icon name="calendar" class="size-4" /> {{ $next->start_at->format('l, M j') }} at {{ $next->start_at->format('g:i A') }}</span><span class="flex items-center gap-1.5"><x-icon name="user" class="size-4" /> {{ $next->staff->name }}</span></p>
                    <div class="relative mt-6 flex flex-wrap gap-3"><a href="{{ route('account.appointments.show', $next) }}" class="btn btn-light btn-sm">Manage</a><a href="{{ route('booking.ics', $next->reference) }}" class="btn btn-sm bg-white/40 text-plum-950 hover:bg-white/60">Add to calendar</a></div>
                </div>
            @else
                <div class="rounded-3xl bg-white p-8 text-center shadow-sm"><span class="text-5xl">🌸</span><h2 class="mt-4 font-serif text-2xl font-semibold">Nothing booked yet</h2><p class="mt-2 text-plum-700/70">Treat yourself, your next appointment is a minute away.</p><a href="{{ route('book') }}" class="btn btn-primary mt-6">Book now</a></div>
            @endif

            @if ($toReview)
                <div class="rounded-3xl border border-gold-300 bg-gradient-to-br from-gold-100 to-white p-7" x-data="{ rating: 0, hover: 0 }">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-gold-600">How was it?</p>
                    <h2 class="mt-1 font-serif text-2xl font-semibold">Rate your {{ $toReview->service->name }} with {{ explode(' ', $toReview->staff->name)[0] }}</h2>
                    <form method="post" action="{{ route('account.appointments.review', $toReview) }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="rating" :value="rating">
                        <div class="flex gap-1.5">
                            @for ($i = 1; $i <= 5; $i++)
                                <button type="button" @click="rating = {{ $i }}" @mouseenter="hover = {{ $i }}" @mouseleave="hover = 0" class="transition hover:scale-125" aria-label="{{ $i }} stars">
                                    <svg class="size-9 transition" :class="(hover || rating) >= {{ $i }} ? 'fill-gold-500 text-gold-500' : 'fill-transparent text-plum-300'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                </button>
                            @endfor
                        </div>
                        <textarea name="comment" rows="3" class="field mt-4" placeholder="Tell others about your visit…" required minlength="10"></textarea>
                        @error('comment')<p class="field-error">{{ $message }}</p>@enderror @error('rating')<p class="field-error">Please choose a star rating.</p>@enderror
                        <button class="btn btn-gold mt-4 btn-sm" :disabled="!rating">Submit review</button>
                    </form>
                </div>
            @endif

            <div class="rounded-3xl bg-white p-7 shadow-sm">
                <div class="flex items-center justify-between"><h2 class="font-serif text-2xl font-semibold">Recent visits</h2><a href="{{ route('account.appointments', ['tab' => 'past']) }}" class="text-sm font-semibold text-plum-600 hover:text-plum-900">View all</a></div>
                <ul class="mt-4 divide-y divide-plum-900/5">
                    @forelse ($recent as $a)
                        <li class="flex items-center gap-4 py-4">
                            <span class="theme-{{ $a->service->category->theme }} theme-fill grid size-12 flex-none place-items-center rounded-2xl text-xl">{{ $a->service->category->icon }}</span>
                            <div class="min-w-0 flex-1"><a href="{{ route('account.appointments.show', $a) }}" class="font-semibold hover:text-plum-500">{{ $a->service->name }}</a><p class="text-xs text-plum-600">{{ $a->start_at->format('M j, Y') }} · {{ $a->staff->name }}</p></div>
                            <a href="{{ route('book', ['service' => $a->service->slug]) }}" class="btn btn-ghost btn-sm">Rebook</a>
                        </li>
                    @empty
                        <li class="py-8 text-center text-plum-600">Your visits will appear here.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <aside class="space-y-6">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-plum-900 to-plum-700 p-7 text-white shadow-xl shadow-plum-900/20">
                <div class="blob -right-10 -top-10 size-44 bg-gold-500 !opacity-30"></div>
                <p class="relative text-xs font-bold uppercase tracking-[0.2em] text-gold-300">Lumière Rewards</p>
                <p class="relative mt-3 font-serif text-5xl font-semibold">{{ $tier['points'] }} <span class="text-lg font-normal text-plum-200">points</span></p>
                <p class="relative mt-1 flex items-center gap-2 text-sm"><span class="rounded-full bg-gold-400 px-3 py-0.5 text-xs font-bold text-plum-950">{{ $tier['current']['name'] }}</span> {{ $tier['current']['perk'] }}</p>
                @if ($tier['next'])
                    @php $pct = min(100, max(4, ($tier['points'] - $tier['current']['min']) / max(1, $tier['next']['min'] - $tier['current']['min']) * 100)); @endphp
                    <div class="relative mt-6"><div class="h-2.5 overflow-hidden rounded-full bg-white/15"><div class="h-full rounded-full bg-gradient-to-r from-gold-300 to-gold-500" style="width: {{ $pct }}%"></div></div>
                    <p class="mt-2 text-xs text-plum-200">{{ $tier['next']['min'] - $tier['points'] }} points to {{ $tier['next']['name'] }}: {{ $tier['next']['perk'] }}</p></div>
                @else
                    <p class="relative mt-5 text-sm text-gold-200">You've reached our highest tier. Thank you! 💛</p>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-3xl bg-white p-5 text-center shadow-sm"><p class="font-serif text-4xl font-semibold">{{ $visits }}</p><p class="mt-1 text-xs uppercase tracking-wider text-plum-600">Visits</p></div>
                <div class="rounded-3xl bg-white p-5 text-center shadow-sm"><p class="font-serif text-4xl font-semibold">{{ money($spent) }}</p><p class="mt-1 text-xs uppercase tracking-wider text-plum-600">Spent</p></div>
            </div>

            @if ($upcoming->count() > 1)
                <div class="rounded-3xl bg-white p-6 shadow-sm">
                    <h3 class="font-serif text-lg font-semibold">Also coming up</h3>
                    <ul class="mt-3 space-y-3">@foreach ($upcoming->skip(1) as $a)<li><a href="{{ route('account.appointments.show', $a) }}" class="flex items-center justify-between rounded-xl bg-cream px-4 py-3 text-sm transition hover:bg-plum-50"><span class="font-medium">{{ $a->service->name }}</span><span class="text-xs text-plum-600">{{ $a->start_at->format('M j, g:i A') }}</span></a></li>@endforeach</ul>
                </div>
            @endif
        </aside>
    </div>
</section>
@endsection
