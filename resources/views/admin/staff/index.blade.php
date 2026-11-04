@extends('layouts.admin')
@section('title', 'Team')
@section('heading', 'Team')
@section('subheading', $staff->where('active', true)->count().' active stylists')
@section('actions')<a href="{{ route('admin.staff.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="size-4" /><span class="hidden sm:inline">Add team member</span></a>@endsection

@section('content')
<div class="grid gap-5 sm:grid-cols-2 2xl:grid-cols-3">
    @foreach ($staff as $s)
        @php $off = $s->timeOff->first(fn ($t) => $t->ends_on >= today()); @endphp
        <div class="a-card overflow-hidden {{ $s->active ? '' : 'opacity-60' }}">
            <div class="h-2" style="background: {{ $s->color }}"></div>
            <div class="p-6">
                <div class="flex items-center gap-4"><x-avatar :name="$s->name" :color="$s->color" size="size-16 text-xl" /><div class="min-w-0"><h2 class="truncate font-serif text-xl font-semibold">{{ $s->name }}</h2><p class="truncate text-sm text-muted">{{ $s->title }}</p></div></div>
                <dl class="mt-5 grid grid-cols-3 gap-3 text-center">
                    <div class="rounded-xl bg-surface-2 p-2.5"><dd class="font-serif text-lg font-semibold">{{ $s->week_count }}</dd><dt class="text-[10px] uppercase tracking-wider text-muted">This week</dt></div>
                    <div class="rounded-xl bg-surface-2 p-2.5"><dd class="font-serif text-lg font-semibold">{{ $s->rating ? number_format($s->rating, 1) : '–' }}</dd><dt class="text-[10px] uppercase tracking-wider text-muted">Rating</dt></div>
                    <div class="rounded-xl bg-surface-2 p-2.5"><dd class="font-serif text-lg font-semibold">{{ $s->services_count }}</dd><dt class="text-[10px] uppercase tracking-wider text-muted">Services</dt></div>
                </dl>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    @if (! $s->active)<span class="a-badge b-slate">Inactive</span>@elseif ($off)<span class="a-badge b-amber">Off {{ $off->starts_on->format('M j') }} – {{ $off->ends_on->format('M j') }}</span>@else<span class="a-badge b-emerald">Taking bookings</span>@endif
                    <a href="{{ route('admin.staff.edit', $s) }}" class="a-btn a-btn-ghost a-btn-sm ml-auto"><x-icon name="edit" class="size-3.5" /> Edit</a>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
