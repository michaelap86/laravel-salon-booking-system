@extends('layouts.site')
@section('title', 'Book an appointment')
@section('description', 'Choose a service, pick your stylist and book your appointment online in under a minute.')

@section('content')
<section class="relative min-h-screen overflow-hidden bg-cream pb-24 pt-28" x-data="booking(@js($cfg))">
    <div class="blob -left-32 top-10 size-96 bg-rose-200"></div>
    <div class="blob -right-32 bottom-0 size-96 bg-gold-100"></div>

    <div class="relative mx-auto max-w-7xl px-6" x-ref="top">
        <div class="mx-auto max-w-2xl pt-4 text-center">
            <h1 class="h-display text-4xl sm:text-5xl">Book your <span class="italic text-plum-500">appointment</span></h1>
            <p class="mt-3 text-plum-800/70">Real-time availability. Instant confirmation. No account needed.</p>
        </div>

        {{-- Stepper --}}
        <ol class="mx-auto mt-10 flex max-w-3xl items-center justify-between">
            <template x-for="(label, i) in steps" :key="i">
                <li class="flex flex-1 items-center last:flex-none">
                    <button type="button" class="group flex flex-col items-center gap-2" @click="goTo(i + 1)" :disabled="i + 1 >= step">
                        <span class="grid size-10 place-items-center rounded-full text-sm font-bold transition duration-300"
                              :class="i + 1 < step ? 'bg-emerald-500 text-white' : i + 1 === step ? 'bg-plum-900 text-white shadow-lg shadow-plum-900/30 scale-110' : 'bg-white text-plum-400 ring-1 ring-plum-900/10'">
                            <svg x-show="i + 1 < step" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <span x-show="i + 1 >= step" x-text="i + 1"></span>
                        </span>
                        <span class="hidden text-[11px] font-semibold uppercase tracking-wider sm:block" :class="i + 1 === step ? 'text-plum-900' : 'text-plum-500'" x-text="label"></span>
                    </button>
                    <span x-show="i < steps.length - 1" class="mx-2 mb-6 h-0.5 flex-1 rounded sm:mb-7" :class="i + 1 < step ? 'bg-emerald-400' : 'bg-plum-900/10'"></span>
                </li>
            </template>
        </ol>

        <div class="mt-12 grid gap-8 lg:grid-cols-[1fr_380px]">
            <div class="min-w-0">
                {{-- STEP 1: service --}}
                <div x-show="step === 1" x-transition:enter="transition duration-400" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0">
                    <h2 class="font-serif text-2xl font-semibold">What would you like to book?</h2>
                    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="thin-scroll flex flex-1 gap-2 overflow-x-auto pb-1">
                            <button type="button" @click="cat = 'all'" class="flex-none rounded-full border px-4 py-2 text-sm font-semibold transition" :class="cat === 'all' ? 'border-plum-900 bg-plum-900 text-white' : 'border-plum-900/10 bg-white hover:border-plum-300'">All</button>
                            <template x-for="c in cfg.categories" :key="c.slug"><button type="button" @click="cat = c.slug" class="flex-none rounded-full border px-4 py-2 text-sm font-semibold transition" :class="cat === c.slug ? 'border-plum-900 bg-plum-900 text-white' : 'border-plum-900/10 bg-white hover:border-plum-300'" x-text="c.icon + ' ' + c.name"></button></template>
                        </div>
                        <input type="search" x-model="query" placeholder="Search…" class="field sm:!w-56 !rounded-full">
                    </div>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <template x-for="s in filteredServices" :key="s.id">
                            <button type="button" @click="pickService(s)" class="group flex items-start gap-4 rounded-2xl border bg-white p-4 text-left transition hover:-translate-y-0.5 hover:border-plum-300 hover:shadow-lg hover:shadow-plum-900/5" :class="serviceId === s.id ? 'border-plum-600 ring-2 ring-plum-600/20' : 'border-plum-900/5'">
                                <span class="grid size-12 flex-none place-items-center rounded-xl text-2xl theme-fill" :class="'theme-' + s.theme" x-text="s.icon"></span>
                                <span class="min-w-0 flex-1"><b class="block font-serif text-lg leading-tight" x-text="s.name"></b><span class="mt-1 block text-xs text-plum-600" x-text="s.catName"></span><span class="mt-1 line-clamp-1 block text-xs text-plum-800/60" x-text="s.description"></span></span>
                                <span class="flex-none text-right"><b class="block font-serif text-xl" x-text="money(s.price)"></b><span class="text-xs text-plum-600" x-text="s.duration + ' min'"></span></span>
                            </button>
                        </template>
                    </div>
                    <p x-show="!filteredServices.length" class="py-12 text-center text-plum-600">No services match your search.</p>
                </div>

                {{-- STEP 2: stylist --}}
                <div x-show="step === 2" x-cloak x-transition:enter="transition duration-400" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0">
                    <h2 class="font-serif text-2xl font-semibold">Who would you like to see?</h2>
                    <p class="mt-1 text-sm text-plum-700/70">Pick a favorite, or choose “Anyone available” for the most time options.</p>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <button type="button" @click="pickStaff('any')" class="flex items-center gap-4 rounded-2xl border bg-white p-4 text-left transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-plum-900/5" :class="staffId === 'any' ? 'border-plum-600 ring-2 ring-plum-600/20' : 'border-plum-900/5'">
                            <span class="grid size-14 flex-none place-items-center rounded-full bg-gradient-to-br from-gold-300 to-plum-400 text-2xl text-white">✨</span>
                            <span><b class="block font-serif text-lg">Anyone available</b><span class="text-xs text-plum-600">Best chance of your preferred time</span></span>
                        </button>
                        <template x-for="s in eligibleStaff" :key="s.id">
                            <button type="button" @click="pickStaff(s.id)" class="flex items-center gap-4 rounded-2xl border bg-white p-4 text-left transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-plum-900/5" :class="staffId === s.id ? 'border-plum-600 ring-2 ring-plum-600/20' : 'border-plum-900/5'">
                                <span class="avatar size-14 flex-none text-lg" :style="`background-image: linear-gradient(135deg, ${s.color}, color-mix(in srgb, ${s.color} 55%, #3b1d2e))`" x-text="s.initials"></span>
                                <span class="min-w-0"><b class="block font-serif text-lg leading-tight" x-text="s.name"></b><span class="block truncate text-xs text-plum-600" x-text="s.title"></span><span class="mt-1 flex items-center gap-1 text-xs text-plum-700" x-show="s.rating"><svg class="size-3.5 fill-gold-500 text-gold-500" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><b x-text="s.rating.toFixed(1)"></b></span></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- STEP 3: date & time --}}
                <div x-show="step === 3" x-cloak x-transition:enter="transition duration-400" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0">
                    <h2 class="font-serif text-2xl font-semibold">Choose a day and time</h2>
                    <div class="mt-5 grid gap-6 md:grid-cols-[1.1fr_1fr]">
                        <div class="rounded-3xl border border-plum-900/5 bg-white p-5">
                            <div class="flex items-center justify-between">
                                <button type="button" class="grid size-9 place-items-center rounded-full border border-plum-900/10 transition hover:bg-plum-50 disabled:opacity-30" @click="shiftMonth(-1)" :disabled="!canPrev" aria-label="Previous month"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="m15 18-6-6 6-6"/></svg></button>
                                <b class="font-serif text-lg" x-text="monthLabel"></b>
                                <button type="button" class="grid size-9 place-items-center rounded-full border border-plum-900/10 transition hover:bg-plum-50" @click="shiftMonth(1)" aria-label="Next month"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="m9 18 6-6-6-6"/></svg></button>
                            </div>
                            <div class="mt-4 grid grid-cols-7 gap-1 text-center text-[11px] font-semibold uppercase tracking-wider text-plum-500"><template x-for="d in ['S','M','T','W','T','F','S']"><span x-text="d"></span></template></div>
                            <div class="relative mt-2 grid grid-cols-7 gap-1" :class="loadingDates && 'opacity-50'">
                                <template x-for="(d, i) in days" :key="i">
                                    <div>
                                        <button type="button" x-show="d" @click="pickDate(d.iso)" :disabled="!d || d.past || !d.count"
                                                class="relative grid aspect-square w-full place-items-center rounded-xl text-sm font-semibold transition"
                                                :class="!d ? '' : date === d.iso ? 'bg-plum-900 text-white shadow-lg shadow-plum-900/30' : d.count ? 'bg-plum-50 text-plum-900 hover:bg-plum-100 hover:scale-105' : 'text-plum-300'">
                                            <span x-text="d?.n"></span>
                                            <span x-show="d && d.count && date !== d.iso" class="absolute bottom-1 size-1 rounded-full bg-emerald-500"></span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            <p class="mt-4 flex items-center gap-2 text-xs text-plum-600"><span class="size-2 rounded-full bg-emerald-500"></span> Days with open times</p>
                        </div>

                        <div class="rounded-3xl border border-plum-900/5 bg-white p-5">
                            <p class="font-serif text-lg font-semibold" x-text="date ? dateLabel : 'Select a day'"></p>
                            <div x-show="loadingSlots" class="mt-4 grid grid-cols-3 gap-2"><template x-for="n in 9"><span class="h-10 animate-pulse rounded-xl bg-plum-50"></span></template></div>
                            <div x-show="!loadingSlots && date && !slots.length" class="mt-6 rounded-2xl bg-plum-50 p-5 text-center text-sm text-plum-700">No times left on this day. Try another date.</div>
                            <div x-show="!loadingSlots" class="mt-3 space-y-5">
                                <template x-for="[name, list] in groupedSlots" :key="name">
                                    <div>
                                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-widest text-plum-500" x-text="name"></p>
                                        <div class="grid grid-cols-3 gap-2">
                                            <template x-for="s in list" :key="s.time"><button type="button" @click="pickTime(s)" class="rounded-xl border px-2 py-2.5 text-sm font-semibold transition" :class="time === s.time ? 'border-plum-900 bg-plum-900 text-white shadow-lg shadow-plum-900/25' : 'border-plum-900/10 hover:border-plum-400 hover:bg-plum-50'" x-text="s.label"></button></template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    <p x-show="errors.time" class="mt-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700" x-text="errors.time"></p>
                </div>

                {{-- STEP 4: details --}}
                <div x-show="step === 4" x-cloak x-transition:enter="transition duration-400" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0">
                    <h2 class="font-serif text-2xl font-semibold">Your details</h2>
                    @guest<p class="mt-1 text-sm text-plum-700/70">Already a client? <a href="{{ route('login') }}" class="font-semibold text-plum-600 underline">Log in</a> to fill this in automatically.</p>@endguest
                    <div class="mt-5 grid gap-5 rounded-3xl border border-plum-900/5 bg-white p-6 sm:grid-cols-2">
                        <div><label class="field-label">Full name</label><input x-model="form.name" class="field" autocomplete="name"><p class="field-error" x-text="errors.name"></p></div>
                        <div><label class="field-label">Email</label><input type="email" x-model="form.email" class="field" autocomplete="email"><p class="field-error" x-text="errors.email"></p></div>
                        <div class="sm:col-span-2"><label class="field-label">Phone</label><input type="tel" x-model="form.phone" class="field" autocomplete="tel"><p class="field-error" x-text="errors.phone"></p></div>
                        <div class="sm:col-span-2"><label class="field-label">Anything we should know? <span class="font-normal normal-case text-plum-500">(optional)</span></label><textarea x-model="form.notes" rows="3" class="field" placeholder="Allergies, reference photos, special occasion…"></textarea></div>
                    </div>
                </div>

                {{-- STEP 5: confirm --}}
                <div x-show="step === 5" x-cloak x-transition:enter="transition duration-400" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0">
                    <h2 class="font-serif text-2xl font-semibold">Confirm your booking</h2>

                    <div class="mt-5 rounded-3xl border border-plum-900/5 bg-white p-6">
                        <label class="field-label">Promo code</label>
                        <div class="flex gap-2"><input x-model="promo" @keydown.enter.prevent="applyPromo()" class="field uppercase" placeholder="Try WELCOME10"><button type="button" class="btn btn-ghost" @click="applyPromo()">Apply</button></div>
                        <p x-show="promoError" class="field-error" x-text="promoError"></p>
                        <p x-show="promoInfo" class="mt-2 flex items-center gap-2 text-sm text-emerald-700"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span x-text="promoInfo?.description"></span></p>
                    </div>

                    <div class="mt-5 rounded-3xl border border-plum-900/5 bg-white p-6" x-show="quote.deposit > 0">
                        <div class="flex items-center justify-between"><h3 class="font-serif text-lg font-semibold">Pay your deposit</h3><span class="flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700"><svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Test mode: no real charge</span></div>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2"><label class="field-label">Card number</label><input x-model="card.number" @input="formatCard()" inputmode="numeric" class="field font-mono tracking-wider" autocomplete="off"></div>
                            <div><label class="field-label">Expiry</label><input x-model="card.expiry" @input="formatExpiry()" placeholder="MM/YY" class="field font-mono" autocomplete="off"></div>
                            <div><label class="field-label">CVC</label><input x-model="card.cvc" inputmode="numeric" maxlength="4" class="field font-mono" autocomplete="off"></div>
                        </div>
                        <p class="mt-3 text-xs text-plum-600">Demo card is pre-filled. Try <code class="rounded bg-plum-50 px-1.5">4000 0000 0000 0002</code> to see a declined payment.</p>
                    </div>

                    <label class="mt-5 flex cursor-pointer items-start gap-3 rounded-2xl bg-white p-4 text-sm">
                        <input type="checkbox" x-model="form.terms" class="mt-0.5 size-5 rounded border-plum-300 text-plum-700 focus:ring-plum-500">
                        <span>I understand I can cancel free of charge up to <b x-text="cfg.freeCancelHours + ' hours'"></b> before my appointment. Later cancellations forfeit the deposit.</span>
                    </label>
                    <p x-show="error" class="mt-4 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700" x-text="error"></p>
                </div>

                {{-- Navigation --}}
                <div class="mt-8 flex items-center justify-between gap-3">
                    <button type="button" class="btn btn-ghost" x-show="step > 1" @click="back()"><x-icon name="arrow-left" class="size-4" /> Back</button>
                    <span x-show="step === 1"></span>
                    <button type="button" class="btn btn-primary px-8" x-show="step === 3 || step === 4" @click="next()" :disabled="(step === 3 && !time)">Continue <x-icon name="arrow-right" class="size-4" /></button>
                    <button type="button" class="btn btn-gold px-8 py-4" x-show="step === 5" @click="submit()" :disabled="submitting">
                        <svg x-show="submitting" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M21 12a9 9 0 1 1-6.2-8.55" stroke-linecap="round"/></svg>
                        <span x-text="submitting ? 'Booking…' : (quote.deposit > 0 ? 'Pay ' + money(quote.deposit) + ' & confirm' : 'Confirm booking')"></span>
                    </button>
                </div>
            </div>

            {{-- Summary --}}
            <aside class="lg:sticky lg:top-28 lg:self-start">
                <div class="overflow-hidden rounded-3xl border border-plum-900/5 bg-white shadow-xl shadow-plum-900/5">
                    <div class="bg-gradient-to-br from-plum-900 to-plum-700 px-6 py-5 text-white"><p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-gold-300">Your booking</p><p class="mt-1 font-serif text-xl" x-text="service ? service.name : 'Nothing selected yet'"></p></div>
                    <dl class="space-y-4 px-6 py-5 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-plum-600">Duration</dt><dd class="font-medium" x-text="service ? service.duration + ' min' : '—'"></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-plum-600">Stylist</dt><dd class="text-right font-medium" x-text="step >= 3 ? (assignedStaff ? assignedStaff.name : 'Anyone available') : '—'"></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-plum-600">When</dt><dd class="text-right font-medium" x-text="date && time ? dateLabel + ' at ' + timeLabel : '—'"></dd></div>
                        <div class="border-t border-plum-900/5 pt-4" x-show="service">
                            <div class="flex justify-between"><dt class="text-plum-600">Price</dt><dd class="font-medium" x-text="money(service?.price)"></dd></div>
                            <div class="mt-2 flex justify-between text-emerald-700" x-show="quote.discount"><dt x-text="'Promo ' + (promoInfo?.code ?? '')"></dt><dd x-text="'−' + money(quote.discount)"></dd></div>
                            <div class="mt-3 flex justify-between border-t border-dashed border-plum-900/10 pt-3 font-serif text-lg"><dt>Total</dt><dd class="font-semibold" x-text="money(quote.price)"></dd></div>
                            <div class="mt-2 flex justify-between text-xs text-plum-600" x-show="quote.deposit"><dt>Deposit due today</dt><dd x-text="money(quote.deposit)"></dd></div>
                            <div class="mt-1 flex justify-between text-xs text-plum-600" x-show="quote.deposit"><dt>Balance at the salon</dt><dd x-text="money(quote.price - quote.deposit)"></dd></div>
                        </div>
                    </dl>
                    <p class="border-t border-plum-900/5 bg-plum-50/60 px-6 py-3 text-xs text-plum-600">A fictional salon. Nothing you book here is real.</p>
                </div>
            </aside>
        </div>
    </div>
</section>
@endsection
