<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('salon.name').' · '.config('salon.tagline'))@hasSection('title') · {{ config('salon.name') }}@endif</title>
    <meta name="description" content="@yield('description', 'Book hair, nails, skin and makeup appointments online at Lumière Studio. A demo salon booking system built with Laravel.')">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='9' fill='%233b1d2e'/><text x='16' y='23' font-size='20' text-anchor='middle' fill='%23e2c896' font-family='Georgia'>L</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/site.js'])
    @stack('head')
</head>
<body class="bg-cream text-plum-900 antialiased" x-data>
    <header x-data="siteNav" class="fixed inset-x-0 top-0 z-50 transition duration-300" :class="scrolled || open ? 'bg-white/85 shadow-sm shadow-plum-900/5 backdrop-blur-xl' : ''">
        <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-6">
            <a href="{{ route('home') }}" class="flex items-baseline gap-2" aria-label="{{ config('salon.name') }}">
                <span class="font-serif text-2xl font-semibold tracking-tight text-plum-900">Lumière</span>
                <span class="text-[10px] font-semibold uppercase tracking-[0.32em] text-gold-600">Studio</span>
            </a>

            <nav class="hidden items-center gap-8 text-sm font-medium text-plum-800 lg:flex">
                <a href="{{ route('services.index') }}" class="transition hover:text-plum-500 {{ request()->routeIs('services.*') ? 'text-plum-500' : '' }}">Services</a>
                <a href="{{ route('team.index') }}" class="transition hover:text-plum-500 {{ request()->routeIs('team.*') ? 'text-plum-500' : '' }}">Our team</a>
                <a href="{{ route('home') }}#gallery" class="transition hover:text-plum-500">Gallery</a>
                <a href="{{ route('home') }}#reviews" class="transition hover:text-plum-500">Reviews</a>
                <a href="{{ route('contact') }}" class="transition hover:text-plum-500 {{ request()->routeIs('contact') ? 'text-plum-500' : '' }}">Contact</a>
            </nav>

            <div class="hidden items-center gap-3 lg:flex">
                @auth
                    <a href="{{ auth()->user()->homeRoute() }}" class="flex items-center gap-2 rounded-full border border-plum-900/10 bg-white/70 py-1.5 pl-1.5 pr-4 text-sm font-medium text-plum-800 transition hover:bg-white">
                        <x-avatar :name="auth()->user()->name" size="size-7 text-[10px]" /> {{ explode(' ', auth()->user()->name)[0] }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-plum-800 transition hover:text-plum-500">Log in</a>
                @endauth
                <a href="{{ route('book') }}" class="btn btn-primary btn-sm">Book now</a>
            </div>

            <button type="button" class="grid size-11 place-items-center rounded-full text-plum-900 lg:hidden" @click="open = !open" aria-label="Menu">
                <x-icon name="menu" x-show="!open" /><x-icon name="x" x-show="open" x-cloak />
            </button>
        </div>

        <div x-show="open" x-cloak x-collapse class="border-t border-plum-900/5 bg-white/95 backdrop-blur-xl lg:hidden">
            <nav class="mx-auto flex max-w-7xl flex-col gap-1 px-6 py-4 text-base font-medium text-plum-800">
                <a class="rounded-xl px-3 py-3 hover:bg-plum-50" href="{{ route('services.index') }}">Services</a>
                <a class="rounded-xl px-3 py-3 hover:bg-plum-50" href="{{ route('team.index') }}">Our team</a>
                <a class="rounded-xl px-3 py-3 hover:bg-plum-50" href="{{ route('home') }}#gallery">Gallery</a>
                <a class="rounded-xl px-3 py-3 hover:bg-plum-50" href="{{ route('home') }}#reviews">Reviews</a>
                <a class="rounded-xl px-3 py-3 hover:bg-plum-50" href="{{ route('contact') }}">Contact</a>
                <a class="rounded-xl px-3 py-3 hover:bg-plum-50" href="{{ auth()->check() ? auth()->user()->homeRoute() : route('login') }}">{{ auth()->check() ? 'My account' : 'Log in' }}</a>
                <a href="{{ route('book') }}" class="btn btn-primary mt-2">Book an appointment</a>
            </nav>
        </div>
    </header>

    <main>@yield('content')</main>

    @unless (View::hasSection('no_footer'))
        <footer class="relative overflow-hidden bg-plum-950 text-plum-100">
            <div class="blob -right-24 -top-24 size-96 bg-plum-700"></div>
            <div class="relative mx-auto grid max-w-7xl gap-12 px-6 py-16 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <div class="flex items-baseline gap-2"><span class="font-serif text-3xl font-semibold text-white">Lumière</span><span class="text-[10px] font-semibold uppercase tracking-[0.32em] text-gold-400">Studio</span></div>
                    <p class="mt-4 max-w-xs text-sm leading-relaxed text-plum-200/80">{{ config('salon.tagline') }} A neighborhood salon where every visit feels a little bit special.</p>
                    <p class="mt-5 flex items-center gap-2 text-sm text-gold-300"><x-icon name="instagram" class="size-4" /> {{ config('salon.instagram') }}</p>
                </div>
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-[0.2em] text-gold-400">Explore</h3>
                    <ul class="mt-5 space-y-3 text-sm text-plum-200/85">
                        <li><a class="hover:text-white" href="{{ route('services.index') }}">All services</a></li>
                        <li><a class="hover:text-white" href="{{ route('team.index') }}">Meet the team</a></li>
                        <li><a class="hover:text-white" href="{{ route('book') }}">Book online</a></li>
                        <li><a class="hover:text-white" href="{{ route('contact') }}">Contact us</a></li>
                        <li><a class="hover:text-white" href="{{ route('login') }}">Client login</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-[0.2em] text-gold-400">Opening hours</h3>
                    <ul class="mt-5 space-y-2 text-sm text-plum-200/85">
                        @foreach ($footerHours as $h)
                            <li class="flex justify-between gap-4"><span>{{ \App\Models\BusinessHour::dayName($h->weekday) }}</span><span class="{{ $h->is_open ? '' : 'text-plum-300/60' }}">{{ $h->is_open ? short_time($h->opens).' – '.short_time($h->closes) : 'Closed' }}</span></li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-[0.2em] text-gold-400">Visit us</h3>
                    <ul class="mt-5 space-y-3 text-sm text-plum-200/85">
                        <li class="flex gap-3"><x-icon name="pin" class="mt-0.5 size-4 flex-none text-gold-400" /> {{ config('salon.address') }}</li>
                        <li class="flex gap-3"><x-icon name="phone" class="mt-0.5 size-4 flex-none text-gold-400" /> {{ config('salon.phone') }}</li>
                        <li class="flex gap-3"><x-icon name="mail" class="mt-0.5 size-4 flex-none text-gold-400" /> {{ config('salon.email') }}</li>
                    </ul>
                </div>
            </div>
            <div class="relative border-t border-white/10">
                <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-6 py-6 text-xs text-plum-300/70 sm:flex-row">
                    <span>© {{ date('Y') }} Lumière Studio. A fictional salon, built as a demo.</span>
                    <span>All services, prices and clients are made-up demo data that resets daily.</span>
                </div>
            </div>
        </footer>
    @endunless

    <x-toasts />
    @stack('scripts')
</body>
</html>
