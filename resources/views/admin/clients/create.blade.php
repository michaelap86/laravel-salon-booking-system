@extends('layouts.admin')
@section('title', 'Add client')
@section('heading', 'Add client')

@section('content')
<form method="post" action="{{ route('admin.clients.store') }}" class="a-card grid max-w-3xl gap-5 p-6 sm:grid-cols-2">
    @csrf
    <div><label class="a-label">Full name</label><input name="name" value="{{ old('name') }}" class="a-input" required>@error('name')<p class="a-error">{{ $message }}</p>@enderror</div>
    <div><label class="a-label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="a-input" required>@error('email')<p class="a-error">{{ $message }}</p>@enderror</div>
    <div><label class="a-label">Phone</label><input name="phone" value="{{ old('phone') }}" class="a-input"></div>
    <div><label class="a-label">Birthday</label><input type="date" name="birthday" value="{{ old('birthday') }}" class="a-input"></div>
    <div class="sm:col-span-2"><label class="a-label">Notes</label><textarea name="notes" rows="3" class="a-input">{{ old('notes') }}</textarea></div>
    <div class="flex justify-end gap-3 sm:col-span-2"><a href="{{ route('admin.clients.index') }}" class="a-btn a-btn-ghost">Cancel</a><button class="a-btn a-btn-primary">Add client</button></div>
</form>
@endsection
