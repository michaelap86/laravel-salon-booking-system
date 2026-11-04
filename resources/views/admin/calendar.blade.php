@extends('layouts.admin')
@section('title', 'Calendar')
@section('heading', 'Calendar')
@section('subheading', $config['canMove'] ? 'Drag an upcoming appointment to reschedule it. Click an empty slot to book.' : 'Your appointments for the week')
@section('actions')
    @if ($config['canCreate'])<a href="{{ route('admin.appointments.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="size-4" /><span class="hidden sm:inline">New appointment</span></a>@endif
@endsection

@push('head') @vite('resources/js/calendar.js') @endpush

@section('content')
<div class="inline-flex w-fit rounded-xl bg-surface p-1 ring-1 ring-line text-sm font-semibold">
    <a href="{{ route('admin.calendar') }}" class="rounded-lg bg-brand px-4 py-1.5 text-brand-ink">Week calendar</a>
    <a href="{{ route('admin.calendar.day') }}" class="rounded-lg px-4 py-1.5 text-muted transition hover:text-ink">Team day</a>
</div>
<div class="grid gap-6 xl:grid-cols-[1fr_240px]" x-data="{ open: false, a: {} }" @appt-open.window="a = $event.detail; open = true">
    <div class="a-card order-2 p-4 xl:order-1">
        <div id="calendar" data-config="{{ json_encode($config) }}"></div>
    </div>

    @if ($staff->count() > 1)
        <aside class="a-card order-1 h-fit p-5 xl:order-2">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-muted">Stylists</h2>
            <ul class="mt-3 grid grid-cols-2 gap-2 xl:grid-cols-1">
                @foreach ($staff as $s)
                    <li><label class="flex cursor-pointer items-center gap-3 rounded-xl px-2 py-1.5 text-sm transition hover:bg-surface-2"><input type="checkbox" value="{{ $s->id }}" data-staff-toggle checked class="size-4 rounded" style="accent-color: {{ $s->color }}"><span class="size-3 flex-none rounded-full" style="background: {{ $s->color }}"></span><span class="truncate">{{ $s->name }}</span></label></li>
                @endforeach
            </ul>
            <div class="mt-5 border-t border-line pt-4 text-xs leading-relaxed text-muted"><span class="font-semibold text-ink">Tip:</span> uncheck a stylist to hide their column of appointments. Greyed-out entries are no-shows.</div>
        </aside>
    @endif

    {{-- Appointment drawer --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50" @keydown.escape.window="open = false">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-[2px]" @click="open = false" x-transition.opacity></div>
        <div class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-surface shadow-2xl" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
            <div class="flex items-center justify-between border-b border-line px-6 py-4"><h2 class="font-serif text-xl font-semibold">Appointment</h2><button type="button" class="a-btn a-btn-ghost !px-2" @click="open = false" aria-label="Close"><x-icon name="x" /></button></div>
            <div class="flex-1 space-y-5 overflow-y-auto p-6">
                <div><p class="text-xs font-semibold uppercase tracking-wider text-muted">Client</p><p class="mt-1 text-lg font-semibold" x-text="a.client"></p></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><p class="text-xs font-semibold uppercase tracking-wider text-muted">Service</p><p class="mt-1 font-medium" x-text="a.service"></p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wider text-muted">Stylist</p><p class="mt-1 font-medium" x-text="a.staff"></p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wider text-muted">When</p><p class="mt-1 font-medium" x-text="a.start ? new Date(a.start).toLocaleString('en-US', { weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : ''"></p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wider text-muted">Price</p><p class="mt-1 font-medium" x-text="a.price"></p></div>
                </div>
                <div><p class="text-xs font-semibold uppercase tracking-wider text-muted">Status</p><span class="a-badge mt-1" :class="{ 'b-amber': a.status === 'pending', 'b-sky': a.status === 'confirmed', 'b-emerald': a.status === 'completed', 'b-slate': a.status === 'cancelled', 'b-rose': a.status === 'no_show' }" x-text="a.statusLabel"></span></div>
            </div>
            <div class="border-t border-line p-6"><a :href="a.url" class="a-btn a-btn-primary w-full">Open full details <x-icon name="arrow-right" class="size-4" /></a></div>
        </div>
    </div>
</div>
@endsection
