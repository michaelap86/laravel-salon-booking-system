@extends('layouts.site')
@section('title', 'My profile')

@section('content')
@include('account._nav', ['heading' => 'My profile'])
<section class="bg-cream pb-24 pt-6">
    <div class="mx-auto max-w-3xl px-6">
        @if (auth()->user()->is_demo)
            <div class="mb-5 flex gap-3 rounded-2xl border border-gold-300 bg-gold-100/60 p-4 text-sm text-plum-800"><x-icon name="lock" class="mt-0.5 size-5 flex-none text-gold-600" /> This is the shared demo account, so its details are read-only. Create your own free account to edit a profile.</div>
        @endif
        <form method="post" action="{{ route('account.profile.update') }}" class="grid gap-5 rounded-3xl bg-white p-8 shadow-sm sm:grid-cols-2">
            @csrf
            @php $ro = auth()->user()->is_demo ? 'disabled' : ''; @endphp
            <div class="sm:col-span-2 flex items-center gap-4"><x-avatar :name="$client->name" size="size-16 text-xl" /><div><p class="font-serif text-2xl font-semibold">{{ $client->name }}</p><p class="text-sm text-plum-600">{{ $client->email }}</p></div></div>
            <div><label class="field-label">Full name</label><input name="name" value="{{ old('name', $client->name) }}" class="field" {{ $ro }} required></div>
            <div><label class="field-label">Phone</label><input name="phone" value="{{ old('phone', $client->phone) }}" class="field" {{ $ro }}></div>
            <div><label class="field-label">Birthday</label><input type="date" name="birthday" value="{{ old('birthday', $client->birthday?->toDateString()) }}" class="field" {{ $ro }}></div>
            <label class="flex items-center gap-3 self-end rounded-xl bg-plum-50 px-4 py-3 text-sm"><input type="checkbox" name="marketing_opt_in" value="1" class="size-5 rounded border-plum-300 text-plum-700" @checked($client->marketing_opt_in) {{ $ro }}> Send me offers and reminders</label>
            @unless (auth()->user()->is_demo)<div class="sm:col-span-2"><button class="btn btn-primary">Save changes</button></div>@endunless
        </form>
    </div>
</section>
@endsection
