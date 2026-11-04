<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · Lumière Studio</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='9' fill='%233b1d2e'/><text x='16' y='23' font-size='20' text-anchor='middle' fill='%23e2c896' font-family='Georgia'>L</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/site.js'])
</head>
<body class="bg-cream text-plum-900 antialiased" x-data>
<div class="grid min-h-screen grid-cols-1 lg:grid-cols-[1.05fr_1fr]">
    <aside class="relative hidden overflow-hidden bg-plum-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
        <div class="blob -left-24 -top-24 size-[28rem] bg-rose-500 !opacity-40"></div>
        <div class="blob -bottom-24 -right-24 size-[28rem] bg-gold-500 !opacity-30"></div>
        <div class="blob left-1/3 top-1/3 size-80 bg-plum-500 !opacity-40"></div>
        <a href="{{ route('home') }}" class="relative flex items-baseline gap-2"><span class="font-serif text-3xl font-semibold">Lumière</span><span class="text-[10px] font-semibold uppercase tracking-[0.32em] text-gold-400">Studio</span></a>
        <div class="relative">
            <h1 class="font-serif text-5xl font-medium leading-tight">Beauty, <span class="italic text-gold-300">beautifully</span> booked.</h1>
            <p class="mt-5 max-w-md text-lg text-plum-100/80">A complete salon booking system: online appointments, a client portal and a full back office for the team.</p>
            <ul class="mt-9 space-y-3.5 text-sm text-plum-100/90">
                @foreach (['Live availability across every stylist', 'Deposits, promo codes and free rescheduling', 'Calendar, clients, reports and loyalty rewards', 'Admin, Manager, Stylist and Client roles'] as $f)
                    <li class="flex items-center gap-3"><span class="grid size-6 place-items-center rounded-full bg-white/10 text-gold-300"><x-icon name="check" class="size-3.5" /></span> {{ $f }}</li>
                @endforeach
            </ul>
        </div>
        <div class="relative flex gap-3 text-4xl"><span class="animate-float">💇‍♀️</span><span class="animate-float-slow">💅</span><span class="animate-float">💄</span><span class="animate-float-slow">🦋</span></div>
    </aside>

    <main class="flex min-w-0 items-center justify-center p-6 sm:p-10">
        <div class="w-full min-w-0 max-w-md">
            <a href="{{ route('home') }}" class="mb-8 flex items-baseline gap-2 lg:hidden"><span class="font-serif text-3xl font-semibold">Lumière</span><span class="text-[10px] font-semibold uppercase tracking-[0.32em] text-gold-600">Studio</span></a>
            @yield('content')
        </div>
    </main>
</div>
<x-toasts />
@stack('scripts')
</body>
</html>
