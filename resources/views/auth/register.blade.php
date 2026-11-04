@extends('layouts.auth')
@section('title', 'Create account')

@section('content')
<h2 class="font-serif text-4xl font-semibold">Join Lumière</h2>
<p class="mt-2 text-plum-700/75">Manage your bookings, earn rewards and rebook in one tap.</p>
<form method="post" action="{{ route('register') }}" class="mt-8 space-y-5">
    @csrf
    <div><label class="field-label">Full name</label><input name="name" value="{{ old('name') }}" class="field" required autofocus>@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
    <div><label class="field-label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="field" required>@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
    <div><label class="field-label">Phone <span class="font-normal normal-case text-plum-500">(optional)</span></label><input name="phone" value="{{ old('phone') }}" class="field"></div>
    <div class="grid gap-5 sm:grid-cols-2">
        <div><label class="field-label">Password</label><input type="password" name="password" class="field" required>@error('password')<p class="field-error">{{ $message }}</p>@enderror</div>
        <div><label class="field-label">Confirm</label><input type="password" name="password_confirmation" class="field" required></div>
    </div>
    <button class="btn btn-primary w-full py-3.5">Create account</button>
</form>
<p class="mt-6 rounded-2xl bg-plum-50 p-4 text-xs leading-relaxed text-plum-700">This is a demo. Accounts created here are removed automatically every day, so please don't use real personal details.</p>
<p class="mt-6 text-center text-sm text-plum-700/80">Already have an account? <a href="{{ route('login') }}" class="font-semibold text-plum-700 underline">Sign in</a></p>
@endsection
