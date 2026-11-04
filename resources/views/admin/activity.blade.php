@extends('layouts.admin')
@section('title', 'Activity log')
@section('heading', 'Activity log')
@section('subheading', 'Who did what, and when')

@section('content')
<div class="a-card">
    <form method="get" class="flex flex-wrap items-end gap-3 border-b border-line p-4">
        <div class="min-w-[200px] flex-1"><label class="a-label">Search</label><input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Details, reference or IP…" class="a-input"></div>
        <div><label class="a-label">Action</label><select name="action" class="a-input"><option value="">All actions</option>@foreach ($actions as $k => $l)<option value="{{ $k }}" @selected(($filters['action'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div><label class="a-label">User</label><select name="user" class="a-input"><option value="">Everyone</option>@foreach ($people as $p)<option value="{{ $p->id }}" @selected(($filters['user'] ?? '') == $p->id)>{{ $p->name }}</option>@endforeach</select></div>
        <button class="a-btn a-btn-primary">Filter</button>@if (array_filter($filters))<a href="{{ route('admin.activity') }}" class="a-btn a-btn-ghost">Reset</a>@endif
    </form>
    <div class="overflow-x-auto"><table class="a-table">
        <thead><tr><th>When</th><th>User</th><th>Action</th><th>Details</th><th>IP address</th></tr></thead>
        <tbody>@forelse ($items as $i)
            <tr><td class="whitespace-nowrap"><b class="block">{{ $i->created_at->format('M j, g:i A') }}</b><span class="text-xs text-muted">{{ $i->created_at->diffForHumans() }}</span></td>
                <td>@if ($i->user)<span class="flex items-center gap-2"><x-avatar :name="$i->user->name" size="size-7 text-[10px]" /> {{ $i->user->name }}</span>@else<span class="text-muted">Client / visitor</span>@endif</td>
                <td><span class="a-badge b-violet">{{ $actions[$i->action] ?? $i->action }}</span></td>
                <td>@if ($i->url)<a href="{{ $i->url }}" class="hover:text-brand">{{ $i->details }}</a>@else{{ $i->details }}@endif</td>
                <td class="font-mono text-xs text-muted">{{ $i->ip ?: '–' }}</td></tr>
        @empty<tr><td colspan="5" class="py-16 text-center text-muted">No activity matches.</td></tr>@endforelse</tbody>
    </table></div>
    {{ $items->links() }}
</div>
@endsection
