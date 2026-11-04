@extends('layouts.admin')
@section('title', 'New appointment')
@section('heading', 'New appointment')
@section('subheading', 'Book a client by phone or at the front desk')

@section('content')
<form method="post" action="{{ route('admin.appointments.store') }}" class="grid max-w-5xl gap-6 lg:grid-cols-2" x-data="apptForm(@js($config))">
    @csrf
    <div class="a-card space-y-5 p-6" x-data="{ mode: '{{ old('client_id') || ! old('name') ? 'existing' : 'new' }}' }">
        <h2 class="font-serif text-xl font-semibold">Client</h2>
        <div class="inline-flex rounded-xl bg-surface-2 p-1 text-sm font-semibold">
            <button type="button" class="rounded-lg px-4 py-1.5 transition" :class="mode === 'existing' ? 'bg-surface shadow text-brand' : 'text-muted'" @click="mode = 'existing'">Existing client</button>
            <button type="button" class="rounded-lg px-4 py-1.5 transition" :class="mode === 'new' ? 'bg-surface shadow text-brand' : 'text-muted'" @click="mode = 'new'">New client</button>
        </div>
        <div x-show="mode === 'existing'">
            <label class="a-label">Choose client</label>
            <select name="client_id" class="a-input" :disabled="mode !== 'existing'"><option value="">Select…</option>@foreach ($clients as $c)<option value="{{ $c->id }}" @selected(old('client_id') == $c->id)>{{ $c->name }} · {{ $c->email }}</option>@endforeach</select>
            @error('client_id')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <div x-show="mode === 'new'" x-cloak class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2"><label class="a-label">Name</label><input name="name" value="{{ old('name') }}" class="a-input" :disabled="mode !== 'new'">@error('name')<p class="a-error">{{ $message }}</p>@enderror</div>
            <div><label class="a-label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="a-input" :disabled="mode !== 'new'">@error('email')<p class="a-error">{{ $message }}</p>@enderror</div>
            <div><label class="a-label">Phone</label><input name="phone" value="{{ old('phone') }}" class="a-input" :disabled="mode !== 'new'"></div>
        </div>
        <div><label class="a-label">Notes</label><textarea name="notes" rows="3" class="a-input" placeholder="Allergies, preferences…">{{ old('notes') }}</textarea></div>
        <label class="flex items-center gap-3 text-sm"><input type="checkbox" name="deposit_paid" value="1" class="size-4 rounded"> Deposit already received</label>
    </div>

    <div class="a-card space-y-5 p-6">
        <h2 class="font-serif text-xl font-semibold">Service and time</h2>
        <div><label class="a-label">Service</label>
            <select name="service_id" class="a-input" x-model="serviceId" @change="pick()" required>
                <option value="">Select a service…</option>
                @foreach ($services->groupBy('category.name') as $cat => $list)<optgroup label="{{ $cat }}">@foreach ($list as $s)<option value="{{ $s->id }}">{{ $s->name }} · {{ duration($s->duration) }} · {{ money($s->price) }}</option>@endforeach</optgroup>@endforeach
            </select>
            @error('service_id')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <div><label class="a-label">Stylist</label>
            <select name="staff_id" class="a-input" x-model="staffId" @change="load()" :disabled="!service">
                <option value="">Anyone available</option>
                <template x-for="s in staffOptions" :key="s.id"><option :value="s.id" x-text="s.name" :selected="String(s.id) === String(staffId)"></option></template>
            </select>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="a-label">Date</label><input type="date" name="date" x-model="date" @change="load()" class="a-input" required></div>
            <div><label class="a-label">Time</label>
                <select name="time" x-model="time" class="a-input" required :disabled="!slots.length">
                    <option value="" x-text="loading ? 'Loading…' : (slots.length ? 'Select a time' : 'No times available')"></option>
                    <template x-for="s in slots" :key="s.time"><option :value="s.time" x-text="s.label" :selected="s.time === time"></option></template>
                </select>
            </div>
        </div>
        @error('time')<p class="a-error">{{ $message }}</p>@enderror
        <div class="flex justify-end gap-3 border-t border-line pt-5"><a href="{{ route('admin.appointments.index') }}" class="a-btn a-btn-ghost">Cancel</a><button class="a-btn a-btn-primary">Book appointment</button></div>
    </div>
</form>
@endsection
