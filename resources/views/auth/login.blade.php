@extends('layouts.auth')
@section('title', 'Sign in')

@section('content')
<div x-data="{ email: @js(old('email', $accounts[0]['email'])), password: @js($password), loading: false }">
    <h2 class="font-serif text-4xl font-semibold">Welcome back</h2>
    <p class="mt-2 text-plum-700/75">The demo account is pre-filled. Just press <b>Sign in</b>, or jump in as another role below.</p>

    <form method="post" action="{{ route('login') }}" class="mt-8 space-y-5" x-ref="form" @submit="loading = true">
        @csrf
        <div>
            <label class="field-label" for="email">Email</label>
            <input id="email" name="email" type="email" x-model="email" class="field" autocomplete="username" required autofocus>
            @error('email')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="field-label" for="password">Password</label>
            <input id="password" name="password" type="password" x-model="password" class="field" autocomplete="current-password" required>
        </div>
        <label class="flex items-center gap-2 text-sm text-plum-700"><input type="checkbox" name="remember" class="size-4 rounded border-plum-300 text-plum-700"> Keep me signed in</label>
        <button class="btn btn-primary w-full py-3.5" :disabled="loading">
            <svg x-show="loading" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M21 12a9 9 0 1 1-6.2-8.55" stroke-linecap="round"/></svg>
            Sign in
        </button>
    </form>

    <div class="mt-8 rounded-3xl border border-dashed border-plum-300 bg-white p-5">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1"><x-icon name="info" class="size-4 text-plum-500" /><b class="text-sm">Demo accounts</b><span class="ml-auto text-xs text-plum-600">password: <code class="rounded bg-plum-50 px-1.5 py-0.5 font-mono">{{ $password }}</code></span></div>
        <div class="mt-4 grid grid-cols-1 gap-2">
            @foreach ($accounts as $a)
                <button type="button" @click="email = @js($a['email']); password = @js($password); loading = true; $nextTick(() => $refs.form.requestSubmit())" class="group flex min-w-0 items-center gap-3 rounded-2xl border border-plum-900/5 bg-cream px-4 py-3 text-left transition hover:border-plum-300 hover:bg-plum-50">
                    <span class="a-badge {{ ['Admin' => 'b-violet', 'Manager' => 'b-sky', 'Stylist' => 'b-rose', 'Client' => 'b-slate'][$a['role']] }} !bg-transparent ring-1 ring-current/30">{{ $a['role'] }}</span>
                    <span class="min-w-0 flex-1"><span class="block truncate text-xs font-semibold text-plum-900">{{ $a['email'] }}</span><span class="block truncate text-xs text-plum-600">{{ $a['hint'] }}</span></span>
                    <x-icon name="arrow-right" class="size-4 text-plum-400 transition group-hover:translate-x-0.5 group-hover:text-plum-700" />
                </button>
            @endforeach
        </div>
    </div>

    <p class="mt-8 text-center text-sm text-plum-700/80">New here? <a href="{{ route('register') }}" class="font-semibold text-plum-700 underline">Create a free account</a> · <a href="{{ route('home') }}" class="font-semibold text-plum-700 underline">Back to website</a></p>
</div>
@endsection
