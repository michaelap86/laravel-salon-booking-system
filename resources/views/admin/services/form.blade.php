@extends('layouts.admin')
@section('title', $service->exists ? 'Edit service' : 'New service')
@section('heading', $service->exists ? $service->name : 'New service')
@section('subheading', 'Prices are in US dollars. Changes go live on the website immediately.')

@section('content')
@php $jokeKey = ['nails' => 'nails', 'hair-color' => 'color', 'makeup' => 'makeup', 'brows-lashes' => 'lashes']; @endphp
<form method="post" action="{{ $service->exists ? route('admin.services.update', $service) : route('admin.services.store') }}" class="grid max-w-5xl gap-6 lg:grid-cols-[1.4fr_1fr]"
      x-data="{ onCat(e) { const o = e.target.selectedOptions[0]; if (o.dataset.ladies === '1') $dispatch('joke', { nails: 'nails', 'hair-color': 'color', makeup: 'makeup', 'brows-lashes': 'lashes' }[o.dataset.slug] ?? 'services'); } }">
    @csrf @if ($service->exists) @method('PUT') @endif
    <div class="a-card space-y-5 p-6">
        <div><label class="a-label">Name</label><input name="name" value="{{ old('name', $service->name) }}" class="a-input" required>@error('name')<p class="a-error">{{ $message }}</p>@enderror</div>
        <div><label class="a-label">Category</label>
            <select name="service_category_id" class="a-input" @change="onCat($event)" required>
                @foreach ($categories as $c)<option value="{{ $c->id }}" data-ladies="{{ $c->ladies_pricing ? 1 : 0 }}" data-slug="{{ $c->slug }}" @selected(old('service_category_id', $service->service_category_id) == $c->id)>{{ $c->icon }} {{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div><label class="a-label">Description</label><textarea name="description" rows="3" class="a-input" required>{{ old('description', $service->description) }}</textarea>@error('description')<p class="a-error">{{ $message }}</p>@enderror</div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div><label class="a-label">Price (USD)</label><input type="number" step="0.01" min="0" name="price" value="{{ old('price', $service->price / 100) }}" class="a-input" required @focus="$dispatch('joke', 'services')">@error('price')<p class="a-error">{{ $message }}</p>@enderror</div>
            <div><label class="a-label">Duration (min)</label><input type="number" step="15" min="15" name="duration" value="{{ old('duration', $service->duration) }}" class="a-input" required></div>
            <div><label class="a-label">Cleanup buffer (min)</label><input type="number" step="5" min="0" name="buffer" value="{{ old('buffer', $service->buffer) }}" class="a-input" required></div>
        </div>
        <div class="flex flex-wrap gap-x-8 gap-y-3 text-sm">
            <label class="flex items-center gap-3"><input type="checkbox" name="deposit_required" value="1" class="size-4 rounded" @checked(old('deposit_required', $service->deposit_required))> Require a deposit</label>
            <label class="flex items-center gap-3"><input type="checkbox" name="featured" value="1" class="size-4 rounded" @checked(old('featured', $service->featured))> Featured on the homepage</label>
            <label class="flex items-center gap-3"><input type="checkbox" name="active" value="1" class="size-4 rounded" @checked(old('active', $service->active))> Bookable online</label>
        </div>
    </div>

    <div class="a-card p-6">
        <h2 class="font-serif text-xl font-semibold">Who can perform it</h2>
        <p class="mb-4 text-sm text-muted">Clients will only see these stylists.</p>
        <ul class="space-y-1">
            @foreach ($staff as $s)
                <li><label class="flex cursor-pointer items-center gap-3 rounded-xl px-2 py-2 transition hover:bg-surface-2"><input type="checkbox" name="staff[]" value="{{ $s->id }}" class="size-4 rounded" style="accent-color: {{ $s->color }}" @checked(in_array($s->id, old('staff', $assigned)))><x-avatar :name="$s->name" :color="$s->color" size="size-8 text-[11px]" /><span class="min-w-0"><span class="block truncate text-sm font-medium">{{ $s->name }}</span><span class="block truncate text-xs text-muted">{{ $s->title }}</span></span></label></li>
            @endforeach
        </ul>
    </div>

    <div class="flex justify-end gap-3 lg:col-span-2"><a href="{{ route('admin.services.index') }}" class="a-btn a-btn-ghost">Cancel</a><button class="a-btn a-btn-primary">Save service</button></div>
</form>
@endsection
