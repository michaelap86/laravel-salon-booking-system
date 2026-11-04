@extends('layouts.admin')
@section('title', $client->name)
@section('heading', $client->name)
@section('subheading', 'Client since '.$client->created_at->format('F Y').' · '.$client->email)
@section('actions')<a href="{{ route('admin.clients.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="size-4" /><span class="hidden sm:inline">Clients</span></a>@endsection

@section('content')
<div class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ([['Visits', $stats['visits']], ['Lifetime spend', money($stats['spent'])], ['Average ticket', money($stats['avg'])], ['Missed / cancelled', $stats['missed']]] as [$l, $v])
                <div class="a-card p-5"><p class="text-sm text-muted">{{ $l }}</p><p class="mt-2 font-serif text-3xl font-semibold">{{ $v }}</p></div>
            @endforeach
        </div>

        <div class="a-card">
            <div class="border-b border-line px-6 py-4"><h2 class="font-serif text-xl font-semibold">Appointment history</h2></div>
            <div class="overflow-x-auto"><table class="a-table">
                <thead><tr><th>Date</th><th>Service</th><th>Stylist</th><th>Status</th><th class="text-right">Price</th></tr></thead>
                <tbody>@forelse ($appointments as $a)
                    <tr class="row-link" data-href="{{ route('admin.appointments.show', $a) }}"><td class="whitespace-nowrap">{{ $a->start_at->format('M j, Y') }}</td><td>{{ $a->service->name }}</td><td><span class="flex items-center gap-2"><span class="size-2.5 rounded-full" style="background: {{ $a->staff->color }}"></span>{{ explode(' ', $a->staff->name)[0] }}</span></td><td><x-status :status="$a->status" /></td><td class="text-right font-semibold tabular-nums">{{ money($a->price) }}</td></tr>
                @empty<tr><td colspan="5" class="py-10 text-center text-muted">No appointments yet.</td></tr>@endforelse</tbody>
            </table></div>
        </div>

        @if ($reviews->isNotEmpty())
            <div class="a-card p-6"><h2 class="font-serif text-xl font-semibold">Reviews by {{ explode(' ', $client->name)[0] }}</h2>
                <div class="mt-4 space-y-4">@foreach ($reviews as $r)<div><div class="flex items-center gap-2"><x-stars :rating="$r->rating" size="size-3.5" /><span class="text-xs text-muted">{{ $r->service->name }}</span></div><p class="mt-1 text-sm">“{{ $r->comment }}”</p></div>@endforeach</div></div>
        @endif
    </div>

    <aside class="space-y-6">
        <div class="a-card p-6">
            <div class="flex items-center gap-4"><x-avatar :name="$client->name" size="size-16 text-xl" /><div><p class="font-serif text-xl font-semibold">{{ $tier['current']['name'] }} member</p><p class="text-sm text-muted">{{ $tier['points'] }} loyalty points</p></div></div>
            @if ($favourite)<p class="mt-4 rounded-xl bg-brand-soft p-3 text-sm text-brand">Favorite service: <b>{{ $favourite }}</b></p>@endif
            @if ($stats['last'])<p class="mt-3 text-sm text-muted">Last visit {{ $stats['last']->diffForHumans() }}</p>@endif
        </div>

        <form method="post" action="{{ route('admin.clients.update', $client) }}" class="a-card space-y-4 p-6">
            @csrf @method('PUT')
            <h2 class="font-serif text-xl font-semibold">Details</h2>
            <div><label class="a-label">Name</label><input name="name" value="{{ old('name', $client->name) }}" class="a-input" @disabled(! $canEdit)></div>
            <div><label class="a-label">Phone</label><input name="phone" value="{{ old('phone', $client->phone) }}" class="a-input" @disabled(! $canEdit)></div>
            <div><label class="a-label">Birthday</label><input type="date" name="birthday" value="{{ old('birthday', $client->birthday?->toDateString()) }}" class="a-input" @disabled(! $canEdit)></div>
            <div><label class="a-label">Notes</label><textarea name="notes" rows="4" class="a-input" placeholder="Color formulas, preferences, allergies…">{{ old('notes', $client->notes) }}</textarea></div>
            <label class="flex items-center gap-3 text-sm"><input type="checkbox" name="vip" value="1" class="size-4 rounded" @checked($client->vip) @disabled(! $canEdit)> Mark as VIP ★</label>
            <button class="a-btn a-btn-primary w-full">Save changes</button>
        </form>
    </aside>
</div>
@endsection
