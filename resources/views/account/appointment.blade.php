@extends('layouts.site')
@section('title', $appointment->service->name)

@section('content')
@include('account._nav', ['heading' => $appointment->service->name])
<section class="bg-cream pb-24 pt-6">
    <div class="mx-auto grid max-w-6xl gap-6 px-6 lg:grid-cols-[1.4fr_1fr]">
        <div class="space-y-6">
            <div class="overflow-hidden rounded-3xl bg-white shadow-sm">
                <div class="theme-{{ $appointment->service->category->theme }} theme-fill flex items-center justify-between px-7 py-5"><span class="text-xs font-bold uppercase tracking-[0.2em] text-plum-900/70">{{ $appointment->service->category->icon }} {{ $appointment->service->category->name }}</span><x-status :status="$appointment->status" /></div>
                <dl class="divide-y divide-plum-900/5 px-7 text-sm">
                    <div class="flex justify-between py-4"><dt class="text-plum-600">When</dt><dd class="font-semibold">{{ $appointment->start_at->format('l, F j, Y') }} · {{ $appointment->start_at->format('g:i A') }}</dd></div>
                    <div class="flex justify-between py-4"><dt class="text-plum-600">With</dt><dd class="font-semibold">{{ $appointment->staff->name }}</dd></div>
                    <div class="flex justify-between py-4"><dt class="text-plum-600">Duration</dt><dd class="font-semibold">{{ duration($appointment->service->duration) }}</dd></div>
                    <div class="flex justify-between py-4"><dt class="text-plum-600">Total</dt><dd class="font-semibold">{{ money($appointment->price) }} @if ($appointment->deposit_paid)<span class="text-xs font-medium text-emerald-600">({{ money($appointment->deposit) }} paid)</span>@endif</dd></div>
                    <div class="flex justify-between py-4"><dt class="text-plum-600">Reference</dt><dd class="font-mono font-bold tracking-widest">{{ $appointment->reference }}</dd></div>
                    @if ($appointment->notes)<div class="flex justify-between gap-6 py-4"><dt class="text-plum-600">Your notes</dt><dd class="text-right">{{ $appointment->notes }}</dd></div>@endif
                </dl>
            </div>

            @if ($appointment->review)
                <div class="rounded-3xl bg-white p-7 shadow-sm"><h2 class="font-serif text-xl font-semibold">Your review</h2><x-stars :rating="$appointment->review->rating" class="mt-2" /><p class="mt-3 text-sm leading-relaxed">“{{ $appointment->review->comment }}”</p>@if ($appointment->review->reply)<p class="mt-4 rounded-2xl bg-plum-50 p-4 text-sm"><b>Reply from the salon:</b> {{ $appointment->review->reply }}</p>@endif</div>
            @endif
        </div>

        <aside class="space-y-6">
            @if ($appointment->isCancellable())
                <div class="rounded-3xl bg-white p-6 shadow-sm" x-data="reschedule(@js(['service' => $appointment->service_id, 'staff' => $appointment->staff_id, 'appointment' => $appointment->id, 'urls' => ['slots' => route('api.booking.slots')]]))">
                    <h2 class="font-serif text-xl font-semibold">Change the time</h2>
                    <form method="post" action="{{ route('account.appointments.reschedule', $appointment) }}" class="mt-4 space-y-4">
                        @csrf
                        <div><label class="field-label">New date</label><input type="date" name="date" x-model="date" @change="load()" min="{{ today()->toDateString() }}" class="field" required></div>
                        <div x-show="date">
                            <label class="field-label">Available times</label>
                            <p x-show="loading" class="text-sm text-plum-600">Loading…</p>
                            <p x-show="!loading && !slots.length" class="rounded-xl bg-plum-50 p-3 text-sm text-plum-700">No times on that day.</p>
                            <div class="grid grid-cols-3 gap-2"><template x-for="s in slots" :key="s.time"><button type="button" @click="time = s.time" class="rounded-xl border px-1 py-2 text-xs font-semibold transition" :class="time === s.time ? 'border-plum-900 bg-plum-900 text-white' : 'border-plum-900/10 hover:bg-plum-50'" x-text="s.label"></button></template></div>
                            <input type="hidden" name="time" :value="time">
                        </div>
                        @error('time')<p class="field-error">{{ $message }}</p>@enderror
                        <button class="btn btn-primary w-full btn-sm" :disabled="!time">Move appointment</button>
                    </form>
                    <form method="post" action="{{ route('account.appointments.cancel', $appointment) }}" data-confirm="Cancel this appointment?" class="mt-5 border-t border-plum-900/5 pt-5">@csrf
                        <button class="btn w-full btn-sm text-rose-600 hover:bg-rose-50">Cancel appointment</button>
                        <p class="mt-2 text-center text-xs text-plum-600">{{ $appointment->cancellableFree() ? 'Free cancellation applies.' : 'Within '.\App\Models\Setting::int('free_cancel_hours').' hours: the deposit is forfeited.' }}</p>
                    </form>
                </div>
            @endif

            @if ($appointment->status->value === 'completed' && ! $appointment->review)
                <div class="rounded-3xl border border-gold-300 bg-gold-100/60 p-6" x-data="{ rating: 0 }">
                    <h2 class="font-serif text-xl font-semibold">Leave a review</h2>
                    <form method="post" action="{{ route('account.appointments.review', $appointment) }}" class="mt-3">@csrf
                        <input type="hidden" name="rating" :value="rating">
                        <div class="flex gap-1">@for ($i = 1; $i <= 5; $i++)<button type="button" @click="rating = {{ $i }}"><svg class="size-8 transition" :class="rating >= {{ $i }} ? 'fill-gold-500 text-gold-500' : 'fill-transparent text-plum-300'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></button>@endfor</div>
                        <textarea name="comment" rows="3" class="field mt-3" required minlength="10" placeholder="How was your visit?"></textarea>
                        <button class="btn btn-gold btn-sm mt-3" :disabled="!rating">Submit</button>
                    </form>
                </div>
            @endif

            <a href="{{ route('book', ['service' => $appointment->service->slug]) }}" class="btn btn-ghost w-full">Book this service again</a>
        </aside>
    </div>
</section>
@endsection
