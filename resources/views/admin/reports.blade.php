@extends('layouts.admin')
@section('title', 'Reports')
@section('heading', 'Reports')
@section('subheading', $ranges[$days].' · completed visits')
@section('joke', 'reports')
@section('actions')
    <div class="hidden rounded-xl bg-surface p-1 ring-1 ring-line sm:flex">@foreach ($ranges as $d => $l)<a href="?range={{ $d }}" class="rounded-lg px-3 py-1.5 text-sm font-semibold transition {{ $days === $d ? 'bg-brand text-brand-ink' : 'text-muted hover:text-ink' }}">{{ $d === 30 ? '30d' : ($d === 90 ? '90d' : ($d === 180 ? '6m' : '12m')) }}</a>@endforeach</div>
    <a href="{{ route('admin.reports.export', ['range' => $days]) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="size-4" /><span class="hidden sm:inline">Export CSV</span></a>
@endsection

@push('head') @vite('resources/js/charts.js') @endpush

@section('content')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
    @foreach ($kpis as [$label, $value, $icon, $hint])
        <div class="a-card p-5"><div class="flex items-center justify-between"><span class="text-sm font-medium text-muted">{{ $label }}</span><span class="grid size-9 place-items-center rounded-xl bg-brand-soft text-brand"><x-icon :name="$icon" class="size-[18px]" /></span></div><p class="mt-3 font-serif text-3xl font-semibold leading-none">{{ $value }}</p>@if ($hint)<p class="mt-2 text-xs text-muted">{{ $hint }}</p>@endif</div>
    @endforeach
</div>

<div class="a-card p-6"><h2 class="font-serif text-xl font-semibold">Revenue trend</h2><p class="mb-2 text-sm text-muted">{{ $days <= 30 ? 'Daily' : 'Weekly' }} completed revenue</p><div data-chart="{{ json_encode($charts['trend']) }}"></div></div>

<div class="grid gap-6 xl:grid-cols-2">
    <div class="a-card p-6"><h2 class="font-serif text-xl font-semibold">Top services by revenue</h2><p class="mb-2 text-sm text-muted">The treatments that pay the bills</p><div data-chart="{{ json_encode($charts['services']) }}"></div></div>
    <div class="a-card p-6"><h2 class="font-serif text-xl font-semibold">Busiest hours</h2><p class="mb-2 text-sm text-muted">Appointments by weekday and start hour. Darker means busier.</p><div data-chart="{{ json_encode($charts['heat']) }}"></div></div>
</div>

<div class="a-card">
    <div class="border-b border-line px-6 py-4"><h2 class="font-serif text-xl font-semibold">Stylist performance</h2></div>
    <div class="overflow-x-auto"><table class="a-table">
        <thead><tr><th>Stylist</th><th class="text-right">Visits</th><th class="text-right">Revenue</th><th style="min-width:180px">Utilization</th><th class="text-right">No-show</th><th class="text-right">Rating</th></tr></thead>
        <tbody>@foreach ($staff as $s)
            <tr><td><span class="flex items-center gap-3"><x-avatar :name="$s['name']" :color="$s['color']" size="size-9 text-xs" /><span><b class="block">{{ $s['name'] }}</b><span class="text-xs text-muted">{{ $s['title'] }}</span></span></span></td>
                <td class="text-right tabular-nums">{{ number_format($s['visits']) }}</td><td class="text-right font-semibold tabular-nums">{{ money($s['revenue']) }}</td>
                <td><span class="flex items-center gap-3"><span class="h-2 flex-1 overflow-hidden rounded-full bg-surface-2"><span class="block h-full rounded-full" style="width: {{ $s['utilization'] }}%; background: {{ $s['color'] }}"></span></span><span class="w-10 text-right text-xs tabular-nums text-muted">{{ $s['utilization'] }}%</span></span></td>
                <td class="text-right tabular-nums {{ $s['noshow'] > 6 ? 'text-rose-500' : '' }}">{{ $s['noshow'] }}%</td><td class="text-right tabular-nums">{{ $s['rating'] ?? '–' }}</td></tr>
        @endforeach</tbody>
    </table></div>
</div>

<div class="grid gap-6 xl:grid-cols-[1.6fr_1fr]">
    <div class="a-card">
        <div class="border-b border-line px-6 py-4"><h2 class="font-serif text-xl font-semibold">Service performance</h2></div>
        <div class="max-h-[520px] overflow-auto"><table class="a-table">
            <thead class="sticky top-0 bg-surface"><tr><th>Service</th><th class="text-right">Visits</th><th class="text-right">Revenue</th><th class="text-right">Avg price</th><th style="min-width:120px">Share</th></tr></thead>
            <tbody>@foreach ($services as $s)
                <tr><td><b class="block">{{ $s['name'] }}</b><span class="text-xs text-muted">{{ $s['category'] }}</span></td><td class="text-right tabular-nums">{{ number_format($s['count']) }}</td><td class="text-right font-semibold tabular-nums">{{ money($s['revenue']) }}</td><td class="text-right tabular-nums text-muted">{{ money($s['avg']) }}</td>
                    <td><span class="flex items-center gap-2"><span class="h-1.5 flex-1 overflow-hidden rounded-full bg-surface-2"><span class="block h-full rounded-full bg-brand" style="width: {{ min(100, $s['share'] * 4) }}%"></span></span><span class="w-10 text-right text-xs tabular-nums text-muted">{{ $s['share'] }}%</span></span></td></tr>
            @endforeach</tbody>
        </table></div>
    </div>
    <div class="space-y-6">
        <div class="a-card p-6"><h2 class="font-serif text-xl font-semibold">Where bookings come from</h2><div data-chart="{{ json_encode($charts['sources']) }}"></div></div>
        <div class="a-card">
            <div class="border-b border-line px-6 py-4"><h2 class="font-serif text-xl font-semibold">Top clients</h2></div>
            <ul class="divide-y divide-line">@foreach ($topClients as $c)@if ($c['client'])<li><a href="{{ route('admin.clients.show', $c['client']) }}" class="flex items-center gap-3 px-6 py-3 transition hover:bg-surface-2"><x-avatar :name="$c['client']->name" size="size-9 text-xs" /><span class="min-w-0 flex-1"><span class="block truncate text-sm font-medium">{{ $c['client']->name }}</span><span class="text-xs text-muted">{{ $c['visits'] }} visits</span></span><b class="tabular-nums">{{ money($c['spent']) }}</b></a></li>@endif @endforeach</ul>
        </div>
    </div>
</div>
@endsection
