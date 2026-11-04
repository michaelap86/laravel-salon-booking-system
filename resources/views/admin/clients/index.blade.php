@extends('layouts.admin')
@section('title', 'Clients')
@section('heading', 'Clients')
@section('subheading', number_format($clients->total()).' clients')
@section('actions')
    @can('manage-business')
        <a href="{{ route('admin.clients.export', request()->query()) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="size-4" /><span class="hidden sm:inline">Export CSV</span></a>
        <a href="{{ route('admin.clients.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="size-4" /><span class="hidden sm:inline">Add client</span></a>
    @endcan
@endsection

@section('content')
<div class="flex flex-wrap gap-2">
    @foreach ($segments as $key => $label)
        <a href="{{ route('admin.clients.index', array_filter(['segment' => $key === 'all' ? null : $key, 'q' => $q])) }}" class="a-btn a-btn-sm {{ $segment === $key ? 'a-btn-primary' : 'a-btn-ghost' }}">{{ $label }}</a>
    @endforeach
</div>
<div class="a-card">
    <form method="get" class="flex gap-3 border-b border-line p-4">
        <input type="hidden" name="segment" value="{{ $segment }}"><input type="hidden" name="sort" value="{{ $sort }}">
        <div class="relative flex-1"><x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" /><input name="q" value="{{ $q }}" placeholder="Search name, email or phone…" class="a-input !pl-10"></div>
        <button class="a-btn a-btn-primary">Search</button>
    </form>
    @php $col = fn ($key, $label) => '<a class="inline-flex items-center gap-1 hover:text-ink" href="'.e(route('admin.clients.index', array_filter(['segment' => $segment === 'all' ? null : $segment, 'q' => $q, 'sort' => $sort === $key ? '-'.$key : $key]))).'">'.$label.(ltrim($sort, '-') === $key ? (str_starts_with($sort, '-') ? ' ▼' : ' ▲') : '').'</a>'; @endphp
    <div class="overflow-x-auto">
        <table class="a-table">
            <thead><tr><th>{!! $col('name', 'Client') !!}</th><th class="text-right">{!! $col('visits', 'Visits') !!}</th><th class="text-right">{!! $col('spent', 'Lifetime spend') !!}</th><th>{!! $col('last', 'Last visit') !!}</th><th>{!! $col('joined', 'Client since') !!}</th></tr></thead>
            <tbody>
                @forelse ($clients as $c)
                    <tr class="row-link" data-href="{{ route('admin.clients.show', $c) }}">
                        <td><span class="flex items-center gap-3"><x-avatar :name="$c->name" size="size-9 text-xs" /><span class="min-w-0"><span class="block truncate font-medium">{{ $c->name }} @if ($c->vip)<span class="text-gold-500" title="VIP">★</span>@endif</span><span class="block truncate text-xs text-muted">{{ $c->email }}</span></span></span></td>
                        <td class="text-right tabular-nums">{{ $c->visits }}</td>
                        <td class="text-right font-semibold tabular-nums">{{ money($c->spent) }}</td>
                        <td class="whitespace-nowrap text-muted">{{ $c->last_visit ? \Illuminate\Support\Carbon::parse($c->last_visit)->format('M j, Y') : '–' }}</td>
                        <td class="whitespace-nowrap text-muted">{{ $c->created_at->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-16 text-center text-muted">No clients found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $clients->links() }}
</div>
@endsection
