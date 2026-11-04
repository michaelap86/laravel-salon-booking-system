@extends('layouts.admin')
@section('title', 'Services')
@section('heading', 'Services')
@section('subheading', 'Your menu, prices and who offers what')
@section('joke', 'services')
@section('actions')<a href="{{ route('admin.services.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="size-4" /><span class="hidden sm:inline">New service</span></a>@endsection

@section('content')
@php $jokeKey = ['nails' => 'nails', 'hair-color' => 'color', 'makeup' => 'makeup', 'brows-lashes' => 'lashes']; @endphp
@foreach ($categories as $c)
    <div class="a-card overflow-hidden">
        <div class="flex flex-wrap items-center gap-4 border-b border-line px-6 py-4">
            <span class="theme-{{ $c->theme }} theme-fill grid size-11 place-items-center rounded-2xl text-xl">{{ $c->icon }}</span>
            <div class="min-w-0 flex-1"><h2 class="font-serif text-xl font-semibold">{{ $c->name }}</h2><p class="text-sm text-muted">{{ $c->blurb }}</p></div>
            @if ($c->ladies_pricing)
                <button type="button" class="a-badge b-violet cursor-pointer transition hover:brightness-110" @click="$dispatch('joke', '{{ $jokeKey[$c->slug] ?? 'services' }}')" title="Read the developer's note">💅 prices are made up</button>
            @endif
        </div>
        <div class="overflow-x-auto"><table class="a-table">
            <thead><tr><th>Service</th><th>Duration</th><th class="text-right">Price</th><th>Deposit</th><th>Stylists</th><th class="text-right">Bookings</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach ($c->services as $s)
                    <tr class="{{ $s->active ? '' : 'opacity-50' }}">
                        <td><a href="{{ route('admin.services.edit', $s) }}" class="font-medium hover:text-brand">{{ $s->name }}</a> @if ($s->featured)<span class="text-gold-500" title="Featured">★</span>@endif</td>
                        <td class="whitespace-nowrap text-muted">{{ duration($s->duration) }}</td>
                        <td class="text-right font-semibold tabular-nums">{{ money($s->price) }}</td>
                        <td class="text-muted">{{ $s->deposit_required ? \App\Models\Setting::int('deposit_percent').'%' : 'None' }}</td>
                        <td class="text-muted">{{ $s->staff->count() }}</td>
                        <td class="text-right tabular-nums">{{ number_format($s->appointments_count) }}</td>
                        <td><form method="post" action="{{ route('admin.services.toggle', $s) }}">@csrf<button class="a-badge cursor-pointer {{ $s->active ? 'b-emerald' : 'b-slate' }}" title="Click to {{ $s->active ? 'hide' : 'show' }}">{{ $s->active ? 'Active' : 'Hidden' }}</button></form></td>
                        <td class="text-right"><a href="{{ route('admin.services.edit', $s) }}" class="a-btn a-btn-ghost a-btn-sm"><x-icon name="edit" class="size-3.5" /> Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
    </div>
@endforeach
@endsection
