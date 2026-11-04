@extends('layouts.site')
@section('title', 'Booking '.$appointment->reference)

@section('content')
@php $cancelled = in_array($appointment->status->value, ['cancelled', 'no_show']); @endphp
<section class="relative min-h-screen overflow-hidden bg-cream pb-24 pt-32">
    <div class="blob -left-24 top-0 size-96 {{ $cancelled ? 'bg-slate-200' : 'bg-rose-200' }}"></div>
    <div class="blob -right-24 bottom-0 size-96 bg-gold-100"></div>

    <div class="relative mx-auto max-w-2xl px-6">
        <div class="text-center">
            @if ($cancelled)
                <span class="mx-auto grid size-20 place-items-center rounded-full bg-slate-200 text-4xl">🌙</span>
                <h1 class="h-display mt-6 text-4xl">Appointment cancelled</h1>
                <p class="mt-3 text-plum-800/70">We hope to see you again soon.</p>
            @else
                <span class="mx-auto grid size-20 animate-pop place-items-center rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-xl shadow-emerald-500/30"><x-icon name="check" class="size-10" /></span>
                <h1 class="h-display mt-6 text-4xl sm:text-5xl">{{ $isNew ? 'You\'re all booked!' : 'Your appointment' }}</h1>
                <p class="mt-3 text-plum-800/70">{{ $isNew ? 'A confirmation email is on its way to '.$appointment->client->email.'.' : 'Everything you need to know about your visit.' }}</p>
            @endif
        </div>

        <div class="mt-10 overflow-hidden rounded-3xl bg-white shadow-2xl shadow-plum-900/10">
            <div class="theme-{{ $appointment->service->category->theme }} theme-fill relative px-8 py-7">
                <div class="absolute -right-8 -top-8 size-32 rounded-full bg-white/25"></div>
                <p class="relative text-xs font-semibold uppercase tracking-[0.2em] text-plum-900/70">{{ $appointment->service->category->icon }} {{ $appointment->service->category->name }}</p>
                <h2 class="relative mt-1 font-serif text-3xl font-semibold text-plum-950">{{ $appointment->service->name }}</h2>
            </div>
            <dl class="divide-y divide-plum-900/5 px-8">
                <div class="flex items-center justify-between py-4"><dt class="flex items-center gap-3 text-plum-600"><x-icon name="calendar" class="size-5" /> Date</dt><dd class="font-semibold">{{ $appointment->start_at->format('l, F j, Y') }}</dd></div>
                <div class="flex items-center justify-between py-4"><dt class="flex items-center gap-3 text-plum-600"><x-icon name="clock" class="size-5" /> Time</dt><dd class="font-semibold">{{ $appointment->start_at->format('g:i A') }} – {{ $appointment->end_at->format('g:i A') }}</dd></div>
                <div class="flex items-center justify-between py-4"><dt class="flex items-center gap-3 text-plum-600"><x-icon name="user" class="size-5" /> With</dt><dd class="flex items-center gap-2 font-semibold"><x-avatar :name="$appointment->staff->name" :color="$appointment->staff->color" size="size-7 text-[10px]" /> {{ $appointment->staff->name }}</dd></div>
                <div class="flex items-center justify-between py-4"><dt class="flex items-center gap-3 text-plum-600"><x-icon name="pin" class="size-5" /> Where</dt><dd class="text-right text-sm font-semibold">{{ config('salon.address') }}</dd></div>
                <div class="flex items-center justify-between py-4"><dt class="flex items-center gap-3 text-plum-600"><x-icon name="wallet" class="size-5" /> Total</dt><dd class="font-semibold">{{ money($appointment->price) }} @if ($appointment->deposit_paid)<span class="ml-1 text-xs font-medium text-emerald-600">({{ money($appointment->deposit) }} deposit paid)</span>@endif</dd></div>
                <div class="flex items-center justify-between py-4"><dt class="text-plum-600">Reference</dt><dd class="rounded-lg bg-plum-50 px-3 py-1 font-mono text-sm font-bold tracking-widest text-plum-800">{{ $appointment->reference }}</dd></div>
            </dl>
            <div class="flex flex-wrap gap-3 border-t border-plum-900/5 bg-plum-50/50 px-8 py-5">
                @unless ($cancelled)
                    <a href="{{ route('booking.ics', $appointment->reference) }}" class="btn btn-primary btn-sm"><x-icon name="calendar" class="size-4" /> Add to calendar</a>
                @endunless
                <a href="{{ route('book') }}" class="btn btn-ghost btn-sm">Book another</a>
                @auth<a href="{{ route('account.dashboard') }}" class="btn btn-ghost btn-sm">My account</a>@endauth
                @if ($canManage)
                    <form method="post" action="{{ route('booking.cancel', $appointment->reference) }}" data-confirm="Cancel this appointment?" class="ml-auto">@csrf
                        <button class="btn btn-sm text-rose-600 hover:bg-rose-50">Cancel appointment</button>
                    </form>
                @endif
            </div>
        </div>

        @if ($canManage)
            <p class="mt-5 text-center text-sm text-plum-700/70">
                @if ($appointment->cancellableFree()) Free cancellation is available until {{ $appointment->start_at->copy()->subHours(\App\Models\Setting::int('free_cancel_hours'))->format('M j, g:i A') }}.
                @else This appointment is within {{ \App\Models\Setting::int('free_cancel_hours') }} hours, so cancelling will forfeit the deposit. @endif
            </p>
        @endif
    </div>
</section>
@endsection
