@extends('layouts.admin')
@section('title', 'Team day')
@section('heading', 'Team day')
@section('subheading', $date->format('l, F j, Y'))
@section('actions')
    @if ($canCreate)<a href="{{ route('admin.appointments.create', ['date' => $date->toDateString()]) }}" class="a-btn a-btn-primary"><x-icon name="plus" class="size-4" /><span class="hidden sm:inline">New appointment</span></a>@endif
@endsection

@section('content')
@php
    $pm = $perMinute;
    $height = ($close - $open) * $pm;
    $fmt = fn (int $m) => \Illuminate\Support\Carbon::createFromTime(intdiv($m, 60), $m % 60)->format('g:i A');
    $nowMin = $date->isToday() ? now()->hour * 60 + now()->minute : null;
@endphp
<div class="flex flex-wrap items-center justify-between gap-3">
    <div class="inline-flex rounded-xl bg-surface p-1 ring-1 ring-line text-sm font-semibold">
        <a href="{{ route('admin.calendar') }}" class="rounded-lg px-4 py-1.5 text-muted transition hover:text-ink">Week calendar</a>
        <a href="{{ route('admin.calendar.day') }}" class="rounded-lg bg-brand px-4 py-1.5 text-brand-ink">Team day</a>
    </div>
    <form method="get" class="flex items-center gap-2">
        <a href="?date={{ $date->copy()->subDay()->toDateString() }}" class="a-btn a-btn-ghost !px-2.5" aria-label="Previous day"><x-icon name="chevron-left" /></a>
        <input type="date" name="date" value="{{ $date->toDateString() }}" class="a-input !w-auto" onchange="this.form.submit()">
        <a href="?date={{ $date->copy()->addDay()->toDateString() }}" class="a-btn a-btn-ghost !px-2.5" aria-label="Next day"><x-icon name="chevron-right" /></a>
        <a href="?date={{ today()->toDateString() }}" class="a-btn a-btn-ghost">Today</a>
    </form>
</div>

@if ($closedReason)
    <div class="a-card flex items-center gap-4 p-6"><span class="text-3xl">🌙</span><div><p class="font-serif text-xl font-semibold">Closed</p><p class="text-sm text-muted">{{ $closedReason }}. Bookings are not available on this day.</p></div></div>
@endif

<div class="a-card overflow-x-auto">
    <div class="flex min-w-[860px]">
        {{-- time axis --}}
        <div class="sticky left-0 z-10 w-16 flex-none border-r border-line bg-surface">
            <div class="h-[73px] border-b border-line"></div>
            <div class="relative" style="height: {{ $height }}px">
                @for ($m = (int) (ceil($open / 60) * 60); $m < $close; $m += 60)
                    <span class="absolute right-2 -translate-y-1/2 text-[11px] font-medium text-muted" style="top: {{ ($m - $open) * $pm }}px">{{ \Illuminate\Support\Carbon::createFromTime(intdiv($m, 60))->format('g A') }}</span>
                @endfor
            </div>
        </div>

        @foreach ($columns as $col)
            @php $s = $col['staff']; @endphp
            <div class="min-w-[150px] flex-1 border-r border-line last:border-r-0">
                <div class="flex h-[73px] flex-col items-center justify-center gap-1 border-b border-line px-2 text-center">
                    <x-avatar :name="$s->name" :color="$s->color" size="size-8 text-[11px]" />
                    <span class="max-w-full truncate text-sm font-semibold">{{ $s->first_name }}</span>
                    <span class="text-[10px] uppercase tracking-wider {{ $col['off'] ? 'text-rose-500' : 'text-muted' }}">{{ $col['off'] ?? $fmt($col['from']).' – '.$fmt($col['to']) }}</span>
                </div>
                <div class="relative" style="height: {{ $height }}px; background-image: repeating-linear-gradient(to bottom, transparent 0, transparent {{ 60 * $pm - 1 }}px, var(--line) {{ 60 * $pm - 1 }}px, var(--line) {{ 60 * $pm }}px); background-position: 0 {{ (ceil($open / 60) * 60 - $open) * $pm - 0 }}px;">
                    {{-- outside the shift, or off --}}
                    @if ($col['off'] || $closedReason)
                        <div class="absolute inset-0 bg-[repeating-linear-gradient(135deg,transparent,transparent_6px,color-mix(in_srgb,var(--muted)_10%,transparent)_6px,color-mix(in_srgb,var(--muted)_10%,transparent)_12px)]"></div>
                    @else
                        @if ($col['from'] > $open)<div class="absolute inset-x-0 top-0 bg-surface-2/90" style="height: {{ ($col['from'] - $open) * $pm }}px"></div>@endif
                        @if ($col['to'] < $close)<div class="absolute inset-x-0 bottom-0 bg-surface-2/90" style="height: {{ ($close - $col['to']) * $pm }}px"></div>@endif
                        @if ($col['break'])<div class="absolute inset-x-0 grid place-items-center bg-[repeating-linear-gradient(135deg,transparent,transparent_5px,color-mix(in_srgb,var(--muted)_14%,transparent)_5px,color-mix(in_srgb,var(--muted)_14%,transparent)_10px)] text-[10px] font-semibold uppercase tracking-wider text-muted" style="top: {{ ($col['break'][0] - $open) * $pm }}px; height: {{ ($col['break'][1] - $col['break'][0]) * $pm }}px">Break</div>@endif
                    @endif

                    @foreach ($col['appointments'] as $a)
                        @php
                            $startMin = $a->start_at->hour * 60 + $a->start_at->minute;
                            $dur = $a->start_at->diffInMinutes($a->end_at);
                            $done = $a->status->value === 'completed';
                            $miss = $a->status->value === 'no_show';
                        @endphp
                        <a href="{{ route('admin.appointments.show', $a) }}" class="group absolute inset-x-1 overflow-hidden rounded-lg border-l-4 px-2 py-1 text-xs shadow-sm transition hover:z-20 hover:shadow-lg {{ $a->status->value === 'pending' ? 'border-dashed' : '' }}"
                           style="top: {{ ($startMin - $open) * $pm }}px; height: {{ $dur * $pm - 2 }}px; border-color: {{ $s->color }}; background: color-mix(in srgb, {{ $s->color }} {{ $done || $miss ? 10 : 20 }}%, var(--surface)); {{ $done ? 'opacity:.7;' : '' }}{{ $miss ? 'opacity:.55;text-decoration:line-through;' : '' }}"
                           title="{{ $a->client->name }} · {{ $a->service->name }} · {{ $a->start_at->format('g:i A') }}–{{ $a->end_at->format('g:i A') }}">
                            <span class="flex items-center gap-1 font-semibold leading-tight">@if ($done)<x-icon name="check" class="size-3 flex-none text-emerald-600" />@endif<span class="truncate">{{ $a->client->name }}</span>@if ($a->client->vip)<span class="text-gold-500">★</span>@endif</span>
                            <span class="block truncate leading-tight text-muted">{{ $a->service->name }}</span>
                            @if ($dur * $pm > 52)<span class="block truncate leading-tight text-muted">{{ $a->start_at->format('g:i') }}–{{ $a->end_at->format('g:i A') }}</span>@endif
                        </a>
                    @endforeach

                    @if ($nowMin && $nowMin >= $open && $nowMin <= $close)<div class="pointer-events-none absolute inset-x-0 z-10 border-t-2 border-rose-500" style="top: {{ ($nowMin - $open) * $pm }}px"><span class="absolute -left-1 -top-[5px] size-2 rounded-full bg-rose-500"></span></div>@endif
                </div>
            </div>
        @endforeach
    </div>
</div>
<p class="text-xs text-muted">Click an appointment to open it. Dashed borders are unconfirmed, faded ones are completed and struck-through ones are no-shows. Hatched areas are breaks or time off.</p>
@endsection
