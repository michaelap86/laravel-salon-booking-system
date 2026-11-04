@extends('layouts.admin')
@section('title', 'Appointment '.$appointment->reference)
@section('heading', $appointment->service->name)
@section('subheading', $appointment->start_at->format('l, F j, Y').' · '.$appointment->start_at->format('g:i A').' · '.$appointment->reference)
@section('actions')<a href="{{ route('admin.appointments.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="size-4" /><span class="hidden sm:inline">Back</span></a>@endsection

@section('content')
@php $a = $appointment; $cur = $a->status->value; @endphp
<div class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
    <div class="space-y-6">
        <div class="a-card overflow-hidden">
            <div class="theme-{{ $a->service->category->theme }} theme-fill flex items-center justify-between px-6 py-5">
                <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-black/60">{{ $a->service->category->icon }} {{ $a->service->category->name }}</p><p class="mt-1 font-serif text-2xl font-semibold text-black/80">{{ $a->service->name }}</p></div>
                <x-status :status="$a->status" class="!bg-white/80" />
            </div>
            <dl class="grid gap-x-8 gap-y-5 p-6 sm:grid-cols-2 text-sm">
                <div><dt class="a-label">Date and time</dt><dd class="font-semibold">{{ $a->start_at->format('D, M j, Y') }}<br>{{ $a->start_at->format('g:i A') }} – {{ $a->end_at->format('g:i A') }} ({{ duration($a->service->duration) }})</dd></div>
                <div><dt class="a-label">Stylist</dt><dd class="flex items-center gap-2 font-semibold"><x-avatar :name="$a->staff->name" :color="$a->staff->color" size="size-8 text-[11px]" /> {{ $a->staff->name }}</dd></div>
                <div><dt class="a-label">Price</dt><dd class="font-semibold">{{ money($a->price) }} @if ($a->promo_code)<span class="a-badge b-violet ml-1">{{ $a->promo_code }}</span>@endif</dd></div>
                <div><dt class="a-label">Deposit</dt><dd class="font-semibold">{{ $a->deposit ? money($a->deposit) : 'None' }} @if ($a->deposit)<span class="a-badge {{ $a->deposit_paid ? 'b-emerald' : 'b-amber' }} ml-1">{{ $a->deposit_paid ? 'Paid' : 'Unpaid' }}</span>@endif</dd></div>
                <div><dt class="a-label">Booked via</dt><dd class="font-semibold capitalize">{{ $a->source }} · {{ $a->created_at->format('M j, g:i A') }}</dd></div>
                @if ($a->cancel_reason)<div><dt class="a-label">Cancellation reason</dt><dd>{{ $a->cancel_reason }}</dd></div>@endif
            </dl>
        </div>

        <div class="a-card p-6">
            <h2 class="font-serif text-xl font-semibold">Update status</h2>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach (\App\Enums\AppointmentStatus::cases() as $s)
                    <form method="post" action="{{ route('admin.appointments.status', $a) }}" @if ($s->value === 'cancelled') data-confirm="Cancel this appointment and email the client?" @endif>@csrf<input type="hidden" name="status" value="{{ $s->value }}">
                        <button class="a-btn a-btn-sm {{ $cur === $s->value ? 'a-btn-primary' : 'a-btn-ghost' }}" @disabled($cur === $s->value)>{{ $s->label() }}</button>
                    </form>
                @endforeach
            </div>
            <div class="mt-5 flex flex-wrap gap-2 border-t border-line pt-5">
                <form method="post" action="{{ route('admin.appointments.remind', $a) }}">@csrf<button class="a-btn a-btn-soft a-btn-sm"><x-icon name="mail" class="size-4" /> Send reminder email</button></form>
            </div>
        </div>

        @if ($canEdit && $a->isCancellable())
            <div class="a-card p-6" x-data="reschedule(@js(['service' => $a->service_id, 'staff' => $a->staff_id, 'appointment' => $a->id, 'urls' => ['slots' => route('api.booking.slots')]]))">
                <h2 class="font-serif text-xl font-semibold">Reschedule</h2>
                <form method="post" action="{{ route('admin.appointments.reschedule', $a) }}" class="mt-4 grid items-end gap-4 sm:grid-cols-[1fr_1fr_auto]">@csrf
                    <div><label class="a-label">New date</label><input type="date" name="date" x-model="date" @change="load()" min="{{ today()->toDateString() }}" class="a-input" required></div>
                    <div><label class="a-label">Time</label><select name="time" x-model="time" class="a-input" required :disabled="!slots.length"><option value="" x-text="loading ? 'Loading…' : (slots.length ? 'Select…' : 'Pick a date')"></option><template x-for="s in slots" :key="s.time"><option :value="s.time" x-text="s.label"></option></template></select></div>
                    <button class="a-btn a-btn-primary" :disabled="!time">Move</button>
                </form>
                @error('time')<p class="a-error">{{ $message }}</p>@enderror
            </div>
        @endif

        <div class="a-card p-6">
            <h2 class="font-serif text-xl font-semibold">Notes</h2>
            <form method="post" action="{{ route('admin.appointments.notes', $a) }}" class="mt-3">@csrf<textarea name="notes" rows="3" class="a-input" placeholder="Allergies, preferences, color formulas…">{{ $a->notes }}</textarea><button class="a-btn a-btn-ghost a-btn-sm mt-3">Save notes</button></form>
        </div>
    </div>

    <aside class="space-y-6">
        <div class="a-card p-6">
            <div class="flex items-center gap-4"><x-avatar :name="$a->client->name" size="size-14 text-lg" /><div class="min-w-0"><a href="{{ route('admin.clients.show', $a->client) }}" class="block truncate font-serif text-xl font-semibold hover:text-brand">{{ $a->client->name }} @if ($a->client->vip)<span class="text-gold-500">★</span>@endif</a><p class="truncate text-sm text-muted">{{ $a->client->email }}</p></div></div>
            <dl class="mt-5 grid grid-cols-3 gap-3 text-center">
                <div class="rounded-xl bg-surface-2 p-3"><dd class="font-serif text-xl font-semibold">{{ $clientVisits }}</dd><dt class="text-[11px] uppercase tracking-wider text-muted">Visits</dt></div>
                <div class="rounded-xl bg-surface-2 p-3"><dd class="font-serif text-xl font-semibold">{{ money($clientSpend) }}</dd><dt class="text-[11px] uppercase tracking-wider text-muted">Spent</dt></div>
                <div class="rounded-xl bg-surface-2 p-3"><dd class="font-serif text-xl font-semibold">{{ $a->client->tier()['current']['name'] }}</dd><dt class="text-[11px] uppercase tracking-wider text-muted">Tier</dt></div>
            </dl>
            <p class="mt-4 flex items-center gap-2 text-sm text-muted"><x-icon name="phone" class="size-4" /> {{ $a->client->phone ?: 'No phone on file' }}</p>
            @if ($a->client->notes)<p class="mt-3 rounded-xl bg-brand-soft p-3 text-sm text-brand">{{ $a->client->notes }}</p>@endif
        </div>

        @if ($a->review)
            <div class="a-card p-6"><h2 class="font-serif text-xl font-semibold">Review</h2><x-stars :rating="$a->review->rating" class="mt-2" /><p class="mt-2 text-sm">“{{ $a->review->comment }}”</p></div>
        @endif

        <div class="a-card p-6">
            <h2 class="font-serif text-xl font-semibold">History</h2>
            <ol class="mt-4 space-y-4 border-l border-line pl-5">
                @forelse ($history as $h)
                    <li class="relative"><span class="absolute -left-[26px] top-1.5 size-2.5 rounded-full bg-brand ring-4 ring-surface"></span><p class="text-sm font-medium">{{ $h->details }}</p><p class="text-xs text-muted">{{ $h->user?->name ?? 'Client' }} · {{ $h->created_at->diffForHumans() }}</p></li>
                @empty
                    <li class="text-sm text-muted">No recorded changes yet.</li>
                @endforelse
                <li class="relative"><span class="absolute -left-[26px] top-1.5 size-2.5 rounded-full bg-muted ring-4 ring-surface"></span><p class="text-sm font-medium">Booked ({{ $a->source }})</p><p class="text-xs text-muted">{{ $a->created_at->format('M j, Y g:i A') }}</p></li>
            </ol>
        </div>
    </aside>
</div>
@endsection
