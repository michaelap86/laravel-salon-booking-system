@extends('layouts.admin')
@section('title', 'Dashboard')
@section('heading', 'Good '.(now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening')).', '.explode(' ', auth()->user()->name)[0])
@section('subheading', now()->format('l, F j').' · '.(auth()->user()->scopedStaffId() ? 'Your schedule and clients' : 'Here is how the salon is doing'))
@section('joke', 'dashboard')
@section('actions')
    @if ($isManager)<a href="{{ route('admin.appointments.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="size-4" /><span class="hidden sm:inline">New appointment</span></a>@endif
@endsection

@push('head') @vite('resources/js/charts.js') @endpush

@section('content')
{{-- KPIs --}}
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
    @foreach ($kpis as [$label, $value, $unit, $icon, $extra, $change])
        <div class="a-card p-5">
            <div class="flex items-center justify-between"><span class="text-sm font-medium text-muted">{{ $label }}</span><span class="grid size-9 place-items-center rounded-xl bg-brand-soft text-brand"><x-icon :name="$icon" class="size-[18px]" /></span></div>
            <p class="mt-3 font-serif text-3xl font-semibold leading-none">{{ $value }}</p>
            <p class="mt-2 flex flex-wrap items-center gap-x-2 text-xs text-muted">
                <span>{{ $unit }}</span>
                @if ($change !== null)<span class="a-badge {{ $change >= 0 ? 'b-emerald' : 'b-rose' }} !py-0">{{ $change >= 0 ? '▲' : '▼' }} {{ abs($change) }}%</span>@endif
            </p>
            @if ($extra)<p class="mt-0.5 text-xs text-muted">{{ $extra }}</p>@endif
        </div>
    @endforeach
</div>

<div class="grid gap-6 xl:grid-cols-3">
    <div class="a-card p-6 xl:col-span-2">
        <div class="mb-2 flex items-center justify-between"><div><h2 class="font-serif text-xl font-semibold">Revenue</h2><p class="text-sm text-muted">Completed visits, last 30 days</p></div></div>
        <div data-chart="{{ json_encode($charts['revenueChart']) }}"></div>
    </div>

    <div class="a-card flex flex-col">
        <div class="flex items-center justify-between border-b border-line px-6 py-4"><div><h2 class="font-serif text-xl font-semibold">Today</h2><p class="text-sm text-muted">{{ $todays->count() }} appointments</p></div><a href="{{ route('admin.calendar') }}" class="text-sm font-semibold text-brand">Calendar</a></div>
        <ul class="max-h-[420px] flex-1 divide-y divide-line overflow-y-auto">
            @forelse ($todays as $a)
                <li><a href="{{ route('admin.appointments.show', $a) }}" class="flex items-center gap-3 px-6 py-3 transition hover:bg-surface-2 {{ $a->status->value === 'completed' ? 'opacity-60' : '' }}">
                    <span class="w-14 flex-none text-sm font-semibold tabular-nums">{{ $a->start_at->format('g:i') }}<span class="ml-0.5 text-[10px] text-muted">{{ $a->start_at->format('A') }}</span></span>
                    <span class="h-9 w-1 flex-none rounded-full" style="background: {{ $a->staff->color }}"></span>
                    <span class="min-w-0 flex-1"><span class="block truncate text-sm font-medium">{{ $a->client->name }} @if ($a->client->vip)<span class="text-gold-500">★</span>@endif</span><span class="block truncate text-xs text-muted">{{ $a->service->name }} · {{ explode(' ', $a->staff->name)[0] }}</span></span>
                    <x-status :status="$a->status" />
                </a></li>
            @empty
                <li class="px-6 py-14 text-center text-sm text-muted">A quiet day. ☕</li>
            @endforelse
        </ul>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
    <div class="a-card p-6"><h2 class="font-serif text-xl font-semibold">Revenue by category</h2><p class="mb-2 text-sm text-muted">Last 30 days</p><div data-chart="{{ json_encode($charts['categoryChart']) }}"></div></div>
    <div class="a-card p-6"><h2 class="font-serif text-xl font-semibold">Busiest days</h2><p class="mb-2 text-sm text-muted">Appointments per weekday, last 90 days</p><div data-chart="{{ json_encode($charts['weekdayChart']) }}"></div></div>
    @if ($charts['staffChart'])
        <div class="a-card p-6"><h2 class="font-serif text-xl font-semibold">Revenue by stylist</h2><p class="mb-2 text-sm text-muted">Last 30 days</p><div data-chart="{{ json_encode($charts['staffChart']) }}"></div></div>
    @endif
</div>

<div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
    <div class="a-card">
        <div class="border-b border-line px-6 py-4"><h2 class="font-serif text-xl font-semibold">Awaiting confirmation</h2></div>
        <ul class="divide-y divide-line">
            @forelse ($pending as $a)
                <li class="flex items-center gap-3 px-6 py-3"><span class="min-w-0 flex-1"><a href="{{ route('admin.appointments.show', $a) }}" class="block truncate text-sm font-medium hover:text-brand">{{ $a->client->name }}</a><span class="text-xs text-muted">{{ $a->service->name }} · {{ $a->start_at->format('D, M j g:i A') }}</span></span>
                    <form method="post" action="{{ route('admin.appointments.status', $a) }}">@csrf<input type="hidden" name="status" value="confirmed"><button class="a-btn a-btn-soft a-btn-sm">Confirm</button></form></li>
            @empty
                <li class="px-6 py-10 text-center text-sm text-muted">Everything is confirmed. ✨</li>
            @endforelse
        </ul>
    </div>

    <div class="a-card">
        <div class="flex items-center justify-between border-b border-line px-6 py-4"><h2 class="font-serif text-xl font-semibold">Latest reviews</h2>@if ($isManager)<a href="{{ route('admin.reviews.index') }}" class="text-sm font-semibold text-brand">All</a>@endif</div>
        <ul class="divide-y divide-line">
            @forelse ($reviews as $r)
                <li class="px-6 py-3"><div class="flex items-center justify-between"><span class="text-sm font-medium">{{ $r->client->name }}</span><x-stars :rating="$r->rating" size="size-3.5" /></div><p class="mt-1 line-clamp-2 text-xs text-muted">{{ $r->comment }}</p></li>
            @empty
                <li class="px-6 py-10 text-center text-sm text-muted">No reviews yet.</li>
            @endforelse
        </ul>
    </div>

    @if ($isManager)
        <div class="a-card">
            <div class="border-b border-line px-6 py-4"><h2 class="font-serif text-xl font-semibold">Upcoming birthdays 🎂</h2></div>
            <ul class="divide-y divide-line">
                @forelse ($birthdays as $c)
                    <li><a href="{{ route('admin.clients.show', $c) }}" class="flex items-center gap-3 px-6 py-3 transition hover:bg-surface-2"><x-avatar :name="$c->name" size="size-9 text-xs" /><span class="min-w-0 flex-1"><span class="block truncate text-sm font-medium">{{ $c->name }}</span><span class="text-xs text-muted">{{ $c->next_birthday->isToday() ? 'Today!' : $c->next_birthday->format('l, M j') }}</span></span></a></li>
                @empty
                    <li class="px-6 py-10 text-center text-sm text-muted">No birthdays in the next two weeks.</li>
                @endforelse
            </ul>
        </div>
    @endif
</div>
@endsection
