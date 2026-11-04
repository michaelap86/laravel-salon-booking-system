@extends('layouts.site')
@section('title', 'My appointments')

@section('content')
@include('account._nav', ['heading' => 'Appointments'])
<section class="bg-cream pb-24 pt-6">
    <div class="mx-auto max-w-6xl px-6">
        <div class="inline-flex rounded-full bg-white p-1 shadow-sm">
            <a href="?tab=upcoming" class="rounded-full px-5 py-2 text-sm font-semibold transition {{ $tab !== 'past' ? 'bg-plum-900 text-white' : 'text-plum-700' }}">Upcoming</a>
            <a href="?tab=past" class="rounded-full px-5 py-2 text-sm font-semibold transition {{ $tab === 'past' ? 'bg-plum-900 text-white' : 'text-plum-700' }}">Past</a>
        </div>
        <div class="mt-6 space-y-4">
            @forelse ($appointments as $a)
                <article class="flex flex-wrap items-center gap-5 rounded-3xl bg-white p-5 shadow-sm">
                    <div class="grid w-16 flex-none place-items-center rounded-2xl bg-plum-50 py-2 text-center"><span class="text-[11px] font-bold uppercase tracking-wider text-plum-500">{{ $a->start_at->format('M') }}</span><span class="font-serif text-2xl font-semibold leading-none">{{ $a->start_at->format('j') }}</span></div>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('account.appointments.show', $a) }}" class="font-serif text-xl font-semibold hover:text-plum-500">{{ $a->service->name }}</a>
                        <p class="mt-0.5 text-sm text-plum-600">{{ $a->start_at->format('l, g:i A') }} · with {{ $a->staff->name }} · {{ money($a->price) }}</p>
                    </div>
                    <x-status :status="$a->status" />
                    <div class="flex gap-2">
                        <a href="{{ route('account.appointments.show', $a) }}" class="btn btn-ghost btn-sm">{{ $a->isCancellable() ? 'Manage' : 'Details' }}</a>
                        @if ($a->status->value === 'completed')<a href="{{ route('book', ['service' => $a->service->slug]) }}" class="btn btn-primary btn-sm">Rebook</a>@endif
                    </div>
                </article>
            @empty
                <div class="rounded-3xl bg-white p-12 text-center"><span class="text-5xl">🗓️</span><p class="mt-4 font-serif text-xl">Nothing here yet.</p><a href="{{ route('book') }}" class="btn btn-primary mt-5">Book an appointment</a></div>
            @endforelse
        </div>
        <div class="mt-6">{{ $appointments->links() }}</div>
    </div>
</section>
@endsection
