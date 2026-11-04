@extends('layouts.site')
@section('title', 'Contact')

@section('content')
<x-page-header eyebrow="Get in touch" title="We would love to hear from you" subtitle="Questions about a treatment, a bridal party or a lost earring? Drop us a line." />

<section class="bg-cream pb-24">
    <div class="mx-auto grid max-w-6xl gap-8 px-6 lg:grid-cols-[1.2fr_1fr]">
        <form method="post" action="{{ route('contact.send') }}" class="rounded-3xl border border-plum-900/5 bg-white p-8 shadow-xl shadow-plum-900/5">
            @csrf
            <h2 class="font-serif text-2xl font-semibold">Send a message</h2>
            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div><label class="field-label">Your name</label><input name="name" value="{{ old('name', auth()->user()?->name) }}" class="field" required>@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label class="field-label">Email</label><input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" class="field" required>@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><label class="field-label">Subject</label><input name="subject" value="{{ old('subject') }}" class="field" required>@error('subject')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><label class="field-label">Message</label><textarea name="body" rows="5" class="field" required>{{ old('body') }}</textarea>@error('body')<p class="field-error">{{ $message }}</p>@enderror</div>
            </div>
            <button class="btn btn-primary mt-6">Send message <x-icon name="arrow-right" class="size-4" /></button>
        </form>

        <div class="space-y-5">
            <div class="rounded-3xl bg-plum-900 p-8 text-white">
                <h2 class="font-serif text-2xl font-semibold">Visit the studio</h2>
                <ul class="mt-5 space-y-4 text-sm text-plum-100/90">
                    <li class="flex gap-3"><x-icon name="pin" class="mt-0.5 size-5 flex-none text-gold-400" /> {{ config('salon.address') }}</li>
                    <li class="flex gap-3"><x-icon name="phone" class="mt-0.5 size-5 flex-none text-gold-400" /> {{ config('salon.phone') }}</li>
                    <li class="flex gap-3"><x-icon name="mail" class="mt-0.5 size-5 flex-none text-gold-400" /> {{ config('salon.email') }}</li>
                </ul>
            </div>
            <div class="rounded-3xl border border-plum-900/5 bg-white p-8">
                <h2 class="font-serif text-2xl font-semibold">Opening hours</h2>
                <ul class="mt-4 divide-y divide-plum-900/5 text-sm">
                    @foreach ($footerHours as $h)
                        <li class="flex justify-between py-2.5"><span class="font-medium">{{ \App\Models\BusinessHour::dayName($h->weekday) }}</span><span class="{{ $h->is_open ? 'text-plum-700' : 'text-plum-400' }}">{{ $h->is_open ? short_time($h->opens).' – '.short_time($h->closes) : 'Closed' }}</span></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>
@endsection
