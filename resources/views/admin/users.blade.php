@extends('layouts.admin')
@section('title', 'Users & roles')
@section('heading', 'Users & roles')
@section('subheading', 'Control who can see and change what')

@section('content')
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    @foreach ($roles as $r)
        <a href="{{ route('admin.users.index', ['role' => $role === $r->value ? null : $r->value]) }}" class="a-card p-5 transition hover:-translate-y-0.5 {{ $role === $r->value ? 'ring-2 ring-brand' : '' }}">
            <span class="a-badge b-{{ $r->color() }}">{{ $r->label() }}</span><p class="mt-3 font-serif text-3xl font-semibold">{{ number_format($counts[$r->value] ?? 0) }}</p><p class="mt-1 text-xs leading-relaxed text-muted">{{ $r->blurb() }}</p>
        </a>
    @endforeach
</div>

<div class="a-card">
    <div class="overflow-x-auto"><table class="a-table">
        <thead><tr><th>User</th><th>Role</th><th>Account</th><th>Status</th><th></th></tr></thead>
        <tbody>
            @foreach ($users as $u)
                <tr class="{{ $u->active ? '' : 'opacity-50' }}">
                    <td><span class="flex items-center gap-3"><x-avatar :name="$u->name" :color="$u->staff?->color" size="size-9 text-xs" /><span class="min-w-0"><span class="block truncate font-medium">{{ $u->name }} @if ($u->id === auth()->id())<span class="text-xs text-muted">(you)</span>@endif</span><span class="block truncate text-xs text-muted">{{ $u->email }}</span></span></span></td>
                    <td>
                        @if ($u->id === auth()->id() || $u->is_demo)
                            <span class="a-badge b-{{ $u->role->color() }}">{{ $u->role->label() }}</span>
                        @else
                            <form method="post" action="{{ route('admin.users.role', $u) }}">@csrf<select name="role" class="a-input !w-auto !py-1.5" onchange="this.form.requestSubmit()" aria-label="Role for {{ $u->name }}">@foreach ($roles as $r)<option value="{{ $r->value }}" @selected($u->role === $r)>{{ $r->label() }}</option>@endforeach</select></form>
                        @endif
                    </td>
                    <td>@if ($u->is_demo)<span class="a-badge b-violet">Demo · locked</span>@else<span class="text-sm text-muted">Registered {{ $u->created_at->format('M j') }}</span>@endif</td>
                    <td><span class="a-badge {{ $u->active ? 'b-emerald' : 'b-slate' }}">{{ $u->active ? 'Active' : 'Disabled' }}</span></td>
                    <td class="text-right">@if ($u->id !== auth()->id() && ! $u->is_demo)<form method="post" action="{{ route('admin.users.active', $u) }}" data-confirm="{{ $u->active ? 'Disable' : 'Re-enable' }} {{ $u->name }}?">@csrf<button class="a-btn a-btn-ghost a-btn-sm">{{ $u->active ? 'Disable' : 'Enable' }}</button></form>@endif</td>
                </tr>
            @endforeach
        </tbody>
    </table></div>
    {{ $users->links() }}
</div>
@endsection
