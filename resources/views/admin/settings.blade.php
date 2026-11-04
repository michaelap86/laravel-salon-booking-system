@extends('layouts.admin')
@section('title', 'Settings')
@section('heading', 'Settings')
@section('subheading', 'Booking rules, opening hours, closures and promo codes')

@section('content')
<div class="grid gap-6 xl:grid-cols-2">
    <form method="post" action="{{ route('admin.settings.booking') }}" class="a-card space-y-5 p-6">
        @csrf @method('PUT')
        <div><h2 class="font-serif text-xl font-semibold">Booking rules</h2><p class="text-sm text-muted">Apply instantly to the online booking form.</p></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="a-label">Time slot interval</label><select name="slot_interval" class="a-input">@foreach ([5, 10, 15, 20, 30, 60] as $v)<option value="{{ $v }}" @selected((int) $settings['slot_interval'] === $v)>Every {{ $v }} minutes</option>@endforeach</select></div>
            <div><label class="a-label">Booking window (days)</label><input type="number" name="booking_window_days" value="{{ $settings['booking_window_days'] }}" class="a-input"></div>
            <div><label class="a-label">Minimum notice (hours)</label><input type="number" name="min_notice_hours" value="{{ $settings['min_notice_hours'] }}" class="a-input"></div>
            <div><label class="a-label">Deposit (% of price)</label><input type="number" name="deposit_percent" value="{{ $settings['deposit_percent'] }}" class="a-input"></div>
            <div><label class="a-label">Free cancellation (hours)</label><input type="number" name="free_cancel_hours" value="{{ $settings['free_cancel_hours'] }}" class="a-input"></div>
            <div><label class="a-label">Dollars per loyalty point</label><input type="number" name="loyalty_dollars_per_point" value="{{ $settings['loyalty_dollars_per_point'] }}" class="a-input"></div>
        </div>
        @if ($errors->any())<p class="a-error">{{ $errors->first() }}</p>@endif
        <button class="a-btn a-btn-primary">Save rules</button>
    </form>

    <form method="post" action="{{ route('admin.settings.hours') }}" class="a-card space-y-4 p-6">
        @csrf @method('PUT')
        <div><h2 class="font-serif text-xl font-semibold">Opening hours</h2><p class="text-sm text-muted">Stylists can only be booked inside these hours.</p></div>
        <div class="space-y-2.5">
            @foreach ($hours as $h)
                <div class="flex flex-wrap items-center gap-3 rounded-xl border border-line px-4 py-2.5" x-data="{ on: {{ $h->is_open ? 'true' : 'false' }} }">
                    <label class="flex w-32 cursor-pointer items-center gap-3 text-sm font-semibold"><input type="checkbox" name="days[{{ $h->weekday }}][open]" value="1" x-model="on" class="size-4 rounded"> {{ \App\Models\BusinessHour::dayName($h->weekday) }}</label>
                    <div class="flex items-center gap-2" x-show="on"><input type="time" name="days[{{ $h->weekday }}][opens]" value="{{ substr($h->opens ?? '09:00', 0, 5) }}" class="a-input !w-32 !py-1.5"><span class="text-muted">to</span><input type="time" name="days[{{ $h->weekday }}][closes]" value="{{ substr($h->closes ?? '18:00', 0, 5) }}" class="a-input !w-32 !py-1.5"></div>
                    <span x-show="!on" x-cloak class="text-sm text-muted">Closed</span>
                </div>
            @endforeach
        </div>
        <button class="a-btn a-btn-primary">Save hours</button>
    </form>

    <div class="a-card p-6">
        <h2 class="font-serif text-xl font-semibold">Closures</h2><p class="mb-4 text-sm text-muted">Holidays and special days when the salon is closed.</p>
        <ul class="divide-y divide-line">
            @forelse ($closures as $c)
                <li class="flex items-center justify-between py-2.5 text-sm"><span><b>{{ $c->date->format('l, M j, Y') }}</b><span class="block text-xs text-muted">{{ $c->reason }}</span></span><form method="post" action="{{ route('admin.settings.closures.remove', $c) }}" data-confirm="Remove this closure?">@csrf @method('DELETE')<button class="a-btn a-btn-ghost a-btn-sm" aria-label="Remove"><x-icon name="trash" class="size-3.5" /></button></form></li>
            @empty
                <li class="py-3 text-sm text-muted">No upcoming closures.</li>
            @endforelse
        </ul>
        <form method="post" action="{{ route('admin.settings.closures.add') }}" class="mt-4 grid gap-3 border-t border-line pt-4 sm:grid-cols-[1fr_1.4fr_auto] sm:items-end">@csrf
            <div><label class="a-label">Date</label><input type="date" name="date" min="{{ today()->toDateString() }}" class="a-input" required></div>
            <div><label class="a-label">Reason</label><input name="reason" class="a-input" placeholder="Public holiday" required></div>
            <button class="a-btn a-btn-soft">Add</button>
        </form>
    </div>

    <div class="a-card p-6">
        <h2 class="font-serif text-xl font-semibold">Promo codes</h2><p class="mb-4 text-sm text-muted">Clients can enter these at checkout.</p>
        <ul class="divide-y divide-line">
            @foreach ($promos as $p)
                <li class="flex items-center gap-3 py-2.5 text-sm"><span class="rounded-lg bg-brand-soft px-2.5 py-1 font-mono text-xs font-bold tracking-wider text-brand">{{ $p->code }}</span><span class="min-w-0 flex-1"><b>{{ $p->percent }}% off</b><span class="block truncate text-xs text-muted">{{ $p->description }} · used {{ $p->uses }}×</span></span>
                    <form method="post" action="{{ route('admin.settings.promos.toggle', $p) }}">@csrf<button class="a-badge cursor-pointer {{ $p->active ? 'b-emerald' : 'b-slate' }}">{{ $p->active ? 'Active' : 'Off' }}</button></form></li>
            @endforeach
        </ul>
        <form method="post" action="{{ route('admin.settings.promos.add') }}" class="mt-4 grid gap-3 border-t border-line pt-4 sm:grid-cols-[1fr_90px_1.4fr_auto] sm:items-end">@csrf
            <div><label class="a-label">Code</label><input name="code" class="a-input uppercase" required></div>
            <div><label class="a-label">% off</label><input type="number" name="percent" min="1" max="90" class="a-input" required></div>
            <div><label class="a-label">Description</label><input name="description" class="a-input" required></div>
            <button class="a-btn a-btn-soft">Add</button>
        </form>
        @if ($errors->has('code') || $errors->has('percent'))<p class="a-error">{{ $errors->first('code') ?: $errors->first('percent') }}</p>@endif
    </div>
</div>
@endsection
