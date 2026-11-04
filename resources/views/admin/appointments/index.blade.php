@extends('layouts.admin')
@section('title', 'Appointments')
@section('heading', 'Appointments')
@section('subheading', number_format($appointments->total()).' matching'.(array_filter($filters) ? '' : ' · showing recent and upcoming'))
@section('actions')
    @can('manage-business')
        <a href="{{ route('admin.appointments.export', $filters) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="size-4" /><span class="hidden sm:inline">Export CSV</span></a>
        <a href="{{ route('admin.appointments.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="size-4" /><span class="hidden sm:inline">New</span></a>
    @endcan
@endsection

@section('content')
<div class="flex flex-wrap gap-2">
    <a href="{{ route('admin.appointments.index') }}" class="a-btn a-btn-sm {{ empty($filters['status']) ? 'a-btn-primary' : 'a-btn-ghost' }}">All</a>
    @foreach ($statuses as $s)
        <a href="{{ route('admin.appointments.index', [...$filters, 'status' => $s->value]) }}" class="a-btn a-btn-sm {{ ($filters['status'] ?? null) === $s->value ? 'a-btn-primary' : 'a-btn-ghost' }}">{{ $s->label() }} <span class="opacity-60">{{ number_format($counts[$s->value] ?? 0) }}</span></a>
    @endforeach
</div>

<div class="a-card">
    <form method="get" class="flex flex-wrap items-end gap-3 border-b border-line p-4">
        @if (! empty($filters['status']))<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
        <div class="min-w-[200px] flex-1"><label class="a-label">Search</label><input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Client, email or reference" class="a-input"></div>
        @if (! auth()->user()->scopedStaffId())
            <div><label class="a-label">Stylist</label><select name="staff" class="a-input"><option value="">Everyone</option>@foreach ($staff as $s)<option value="{{ $s->id }}" @selected(($filters['staff'] ?? null) == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
        @endif
        <div><label class="a-label">From</label><input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="a-input"></div>
        <div><label class="a-label">To</label><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="a-input"></div>
        <button class="a-btn a-btn-primary">Filter</button>
        @if (array_filter($filters))<a href="{{ route('admin.appointments.index') }}" class="a-btn a-btn-ghost">Reset</a>@endif
    </form>

    <div class="overflow-x-auto">
        <table class="a-table">
            <thead><tr><th>When</th><th>Client</th><th>Service</th><th>Stylist</th><th>Status</th><th class="text-right">Price</th></tr></thead>
            <tbody>
                @forelse ($appointments as $a)
                    <tr class="row-link" data-href="{{ route('admin.appointments.show', $a) }}">
                        <td class="whitespace-nowrap"><b class="block">{{ $a->start_at->format('D, M j') }}</b><span class="text-xs text-muted">{{ $a->start_at->format('g:i A') }}</span></td>
                        <td><span class="flex items-center gap-3"><x-avatar :name="$a->client->name" size="size-8 text-[11px]" /><span class="min-w-0"><span class="block truncate font-medium">{{ $a->client->name }} @if ($a->client->vip)<span class="text-gold-500">★</span>@endif</span><span class="block truncate text-xs text-muted">{{ $a->reference }}</span></span></span></td>
                        <td>{{ $a->service->name }}</td>
                        <td><span class="flex items-center gap-2 whitespace-nowrap"><span class="size-2.5 rounded-full" style="background: {{ $a->staff->color }}"></span>{{ explode(' ', $a->staff->name)[0] }}</span></td>
                        <td><x-status :status="$a->status" /></td>
                        <td class="text-right font-semibold tabular-nums">{{ money($a->price) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-16 text-center text-muted">No appointments match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $appointments->links() }}
</div>
@endsection
