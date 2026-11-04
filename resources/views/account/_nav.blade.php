<section class="relative overflow-hidden bg-cream pb-6 pt-32">
    <div class="blob -left-24 -top-24 size-96 bg-rose-200"></div>
    <div class="relative mx-auto max-w-6xl px-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gold-600">My account</p>
                <h1 class="h-display mt-1 text-4xl sm:text-5xl">{{ $heading }}</h1>
            </div>
            <a href="{{ route('book') }}" class="btn btn-primary">Book an appointment</a>
        </div>
        <nav class="thin-scroll mt-8 flex gap-2 overflow-x-auto border-b border-plum-900/10 pb-px text-sm font-semibold">
            @foreach ([['account.dashboard', 'Overview'], ['account.appointments', 'Appointments'], ['account.profile', 'Profile']] as [$r, $l])
                <a href="{{ route($r) }}" class="-mb-px border-b-2 px-4 py-3 transition {{ request()->routeIs($r.'*') ? 'border-plum-700 text-plum-900' : 'border-transparent text-plum-600 hover:text-plum-900' }}">{{ $l }}</a>
            @endforeach
            <form method="post" action="{{ route('logout') }}" class="ml-auto">@csrf<button class="px-4 py-3 text-plum-500 transition hover:text-rose-600">Sign out</button></form>
        </nav>
    </div>
</section>
