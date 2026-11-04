@php
    $user = auth()->user();
    $isManager = $user->hasRole('admin', 'manager');
    $isAdmin = $user->hasRole('admin');
    $nav = [
        'Overview' => [
            ['admin.dashboard', 'dashboard', 'Dashboard', true],
            ['admin.calendar', 'calendar', 'Calendar', true],
            ['admin.appointments.index', 'clipboard', 'Appointments', true],
        ],
        'Business' => [
            ['admin.clients.index', 'users', 'Clients', true],
            ['admin.services.index', 'scissors', 'Services', $isManager],
            ['admin.staff.index', 'sparkles', 'Team', $isManager],
            ['admin.reviews.index', 'star', 'Reviews', $isManager],
            ['admin.messages.index', 'inbox', 'Messages', $isManager],
        ],
        'Insights' => [
            ['admin.reports', 'chart', 'Reports', $isManager],
        ],
        'System' => [
            ['admin.outbox', 'mail', 'Email outbox', $isManager],
            ['admin.activity', 'file', 'Activity log', $isAdmin],
            ['admin.users.index', 'shield', 'Users & roles', $isAdmin],
            ['admin.settings', 'settings', 'Settings', $isAdmin],
        ],
    ];
@endphp
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') · Lumière Studio</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='9' fill='%237a3558'/><text x='16' y='23' font-size='20' text-anchor='middle' fill='white' font-family='Georgia'>L</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
    <script>try { var t = localStorage.getItem('salon-theme'); if (t) document.documentElement.dataset.theme = t; } catch (e) {}</script>
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
    @stack('head')
</head>
<body class="bg-bg text-ink antialiased" x-data="adminShell" @keydown.escape.window="sidebar = false" data-joke="@yield('joke')">
<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <div x-show="sidebar" x-cloak class="fixed inset-0 z-30 bg-black/40 lg:hidden" @click="sidebar = false" x-transition.opacity></div>
    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-line bg-surface transition-transform duration-200 lg:translate-x-0" :class="sidebar && '!translate-x-0'">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-6 py-5">
            <span class="grid size-9 place-items-center rounded-xl bg-brand font-serif text-lg font-semibold text-brand-ink">L</span>
            <span><span class="block font-serif text-lg font-semibold leading-none">Lumière</span><span class="text-[10px] font-semibold uppercase tracking-[0.28em] text-muted">Admin</span></span>
        </a>
        <nav class="flex-1 space-y-5 overflow-y-auto px-3 pb-4">
            @foreach ($nav as $group => $items)
                @php $visible = collect($items)->filter(fn ($i) => $i[3]); @endphp
                @if ($visible->isNotEmpty())
                    <div>
                        <p class="px-3 pb-1.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-muted/70">{{ $group }}</p>
                        <div class="space-y-0.5">
                            @foreach ($visible as [$route, $icon, $label])
                                @php
                                    $section = Str::beforeLast($route, '.');
                                    $isActive = $route === 'admin.dashboard' ? request()->routeIs($route) : request()->routeIs($section === 'admin' ? $route.'*' : $section.'.*');
                                @endphp
                                <a href="{{ route($route) }}" class="nav-link {{ $isActive ? 'active' : '' }}">
                                    <x-icon :name="$icon" class="size-[18px]" /> {{ $label }}
                                    @if ($route === 'admin.messages.index' && ($unreadMessages ?? 0) > 0)<span class="ml-auto rounded-full bg-brand px-2 text-[11px] font-bold text-brand-ink">{{ $unreadMessages }}</span>@endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>
        <div class="space-y-3 border-t border-line p-4">
            <div class="flex items-center gap-3">
                <x-avatar :name="$user->name" />
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold">{{ $user->name }}</p>
                    <span class="a-badge b-{{ $user->role->color() }}">{{ $user->role->label() }}</span>
                </div>
            </div>
            <a href="{{ route('home') }}" target="_blank" class="a-btn a-btn-ghost a-btn-sm w-full"><x-icon name="external" class="size-3.5" /> View website</a>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
        {{-- Top bar --}}
        <header class="sticky top-0 z-20 flex items-center gap-4 border-b border-line bg-bg/85 px-5 py-4 backdrop-blur-xl lg:px-8">
            <button type="button" class="a-btn a-btn-ghost !px-2.5 lg:hidden" @click="sidebar = true" aria-label="Open menu"><x-icon name="menu" /></button>
            <div class="min-w-0 flex-1">
                <h1 class="truncate font-serif text-2xl font-semibold leading-tight">@yield('heading', 'Dashboard')</h1>
                @hasSection('subheading')<p class="truncate text-sm text-muted">@yield('subheading')</p>@endif
            </div>
            <div class="flex items-center gap-2">
                @yield('actions')
                <button type="button" class="a-btn a-btn-ghost !px-2.5" @click="$store.theme.toggle()" aria-label="Toggle dark mode">
                    <x-icon name="moon" x-show="!$store.theme.dark" /><x-icon name="sun" x-show="$store.theme.dark" x-cloak />
                </button>

                @if ($isManager)
                    <div class="relative" @click.outside="notif = false">
                        <button type="button" class="a-btn a-btn-ghost relative !px-2.5" @click="notif = !notif; if (notif) api('{{ route('admin.notifications.seen') }}', { method: 'POST' }).then(() => $refs.badge?.remove())" aria-label="Notifications">
                            <x-icon name="bell" />
                            @if (($bellUnread ?? 0) > 0)<span x-ref="badge" class="absolute -right-1 -top-1 grid min-w-[18px] place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">{{ $bellUnread }}</span>@endif
                        </button>
                        <div x-show="notif" x-cloak x-transition class="absolute right-0 top-full mt-2 w-[min(92vw,380px)] overflow-hidden rounded-2xl border border-line bg-surface shadow-2xl shadow-black/15">
                            <div class="flex items-center justify-between border-b border-line px-4 py-3"><b class="text-sm">Recent activity</b><a href="{{ $isAdmin ? route('admin.activity') : route('admin.appointments.index') }}" class="text-xs font-semibold text-brand">View all</a></div>
                            <ul class="max-h-96 divide-y divide-line overflow-y-auto">
                                @forelse ($bellItems ?? [] as $a)
                                    <li><a href="{{ $a->url ?: '#' }}" class="flex gap-3 px-4 py-3 text-sm transition hover:bg-surface-2">
                                        <span class="mt-0.5 grid size-8 flex-none place-items-center rounded-full bg-brand-soft text-brand"><x-icon name="{{ ['booking' => 'calendar', 'cancel' => 'x', 'review' => 'star', 'reschedule' => 'refresh', 'message' => 'mail'][$a->action] ?? 'info' }}" class="size-4" /></span>
                                        <span class="min-w-0"><span class="block truncate font-medium">{{ $a->details }}</span><span class="text-xs text-muted">{{ \App\Models\Activity::labels()[$a->action] ?? ucfirst($a->action) }} · {{ $a->created_at->diffForHumans() }}</span></span>
                                    </a></li>
                                @empty
                                    <li class="px-4 py-8 text-center text-sm text-muted">Nothing new.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                @endif

                <div class="relative" @click.outside="userMenu = false">
                    <button type="button" class="block rounded-full" @click="userMenu = !userMenu" aria-label="Account"><x-avatar :name="$user->name" /></button>
                    <div x-show="userMenu" x-cloak x-transition class="absolute right-0 top-full mt-2 w-56 overflow-hidden rounded-2xl border border-line bg-surface p-1.5 shadow-2xl shadow-black/15">
                        <div class="px-3 py-2"><p class="truncate text-sm font-semibold">{{ $user->name }}</p><p class="truncate text-xs text-muted">{{ $user->email }}</p></div>
                        <a href="{{ route('home') }}" class="nav-link"><x-icon name="globe" class="size-4" /> View website</a>
                        <form method="post" action="{{ route('logout') }}">@csrf<button class="nav-link w-full"><x-icon name="logout" class="size-4" /> Sign out</button></form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 space-y-6 p-5 lg:p-8">
            @yield('content')
        </main>
    </div>
</div>
<x-joke-modal />
<x-toasts />
@stack('scripts')
</body>
</html>
