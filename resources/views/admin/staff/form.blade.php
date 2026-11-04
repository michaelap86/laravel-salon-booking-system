@extends('layouts.admin')
@section('title', $staff->exists ? $staff->name : 'Add team member')
@section('heading', $staff->exists ? $staff->name : 'Add team member')
@section('subheading', $staff->exists ? $staff->title : 'Create a profile, then set their weekly schedule')
@section('actions')<a href="{{ route('admin.staff.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="size-4" /><span class="hidden sm:inline">Team</span></a>@endsection

@section('content')
<div class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
    <div class="space-y-6">
        <form method="post" action="{{ $staff->exists ? route('admin.staff.update', $staff) : route('admin.staff.store') }}" class="a-card space-y-5 p-6">
            @csrf @if ($staff->exists) @method('PUT') @endif
            <h2 class="font-serif text-xl font-semibold">Profile</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="a-label">Name</label><input name="name" value="{{ old('name', $staff->name) }}" class="a-input" required>@error('name')<p class="a-error">{{ $message }}</p>@enderror</div>
                <div><label class="a-label">Job title</label><input name="title" value="{{ old('title', $staff->title) }}" class="a-input" required>@error('title')<p class="a-error">{{ $message }}</p>@enderror</div>
                <div><label class="a-label">Specialties</label><input name="specialties" value="{{ old('specialties', $staff->specialties) }}" class="a-input" placeholder="Balayage, curls, cuts" required></div>
                <div class="grid grid-cols-[1fr_auto] gap-3"><div><label class="a-label">Years of experience</label><input type="number" min="0" name="years" value="{{ old('years', $staff->years) }}" class="a-input" required></div><div><label class="a-label">Color</label><input type="color" name="color" value="{{ old('color', $staff->color) }}" class="h-[42px] w-14 cursor-pointer rounded-xl border border-line bg-surface p-1"></div></div>
                <div><label class="a-label">Instagram</label><input name="instagram" value="{{ old('instagram', $staff->instagram) }}" class="a-input"></div>
                @unless ($staff->exists)<div><label class="a-label">Login email <span class="font-normal normal-case">(optional)</span></label><input type="email" name="email" value="{{ old('email') }}" class="a-input">@error('email')<p class="a-error">{{ $message }}</p>@enderror</div>@endunless
            </div>
            <div><label class="a-label">Bio</label><textarea name="bio" rows="4" class="a-input" required>{{ old('bio', $staff->bio) }}</textarea></div>
            <div>
                <label class="a-label">Services offered</label>
                <div class="grid gap-x-6 gap-y-1 sm:grid-cols-2">@foreach ($services as $s)<label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-1.5 text-sm transition hover:bg-surface-2"><input type="checkbox" name="services[]" value="{{ $s->id }}" class="size-4 rounded" @checked(in_array($s->id, old('services', $assigned)))> <span class="truncate">{{ $s->category->icon }} {{ $s->name }}</span></label>@endforeach</div>
            </div>
            @if ($staff->exists)<label class="flex items-center gap-3 text-sm"><input type="checkbox" name="active" value="1" class="size-4 rounded" @checked(old('active', $staff->active))> Accepting bookings</label>@endif
            <div class="flex justify-end"><button class="a-btn a-btn-primary">{{ $staff->exists ? 'Save profile' : 'Create team member' }}</button></div>
        </form>
    </div>

    @if ($staff->exists)
        <div class="space-y-6">
            <form method="post" action="{{ route('admin.staff.schedule', $staff) }}" class="a-card p-6">
                @csrf @method('PUT')
                <h2 class="font-serif text-xl font-semibold">Weekly schedule</h2>
                <p class="mb-4 text-sm text-muted">Availability on the website updates as soon as you save.</p>
                <div class="space-y-3">
                    @foreach ([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 0 => 'Sunday'] as $wd => $name)
                        @php $h = $hours[$wd] ?? null; @endphp
                        <div class="rounded-xl border border-line p-3" x-data="{ on: {{ $h?->is_working ? 'true' : 'false' }} }">
                            <label class="flex cursor-pointer items-center justify-between text-sm font-semibold"><span>{{ $name }}</span><input type="checkbox" name="days[{{ $wd }}][works]" value="1" x-model="on" class="size-4 rounded"></label>
                            <div x-show="on" x-cloak class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                <div><span class="text-muted">Starts</span><input type="time" name="days[{{ $wd }}][starts]" value="{{ substr($h?->starts ?? '09:00', 0, 5) }}" class="a-input !py-1.5"></div>
                                <div><span class="text-muted">Ends</span><input type="time" name="days[{{ $wd }}][ends]" value="{{ substr($h?->ends ?? '18:00', 0, 5) }}" class="a-input !py-1.5"></div>
                                <div><span class="text-muted">Break from</span><input type="time" name="days[{{ $wd }}][break_starts]" value="{{ substr($h?->break_starts ?? '', 0, 5) }}" class="a-input !py-1.5"></div>
                                <div><span class="text-muted">Break to</span><input type="time" name="days[{{ $wd }}][break_ends]" value="{{ substr($h?->break_ends ?? '', 0, 5) }}" class="a-input !py-1.5"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button class="a-btn a-btn-primary mt-5 w-full">Save schedule</button>
            </form>

            <div class="a-card p-6">
                <h2 class="font-serif text-xl font-semibold">Time off</h2>
                <ul class="mt-3 divide-y divide-line">
                    @forelse ($timeOff as $t)
                        <li class="flex items-center justify-between gap-3 py-2.5 text-sm"><span><b>{{ $t->starts_on->format('M j') }}{{ $t->ends_on->ne($t->starts_on) ? ' – '.$t->ends_on->format('M j') : '' }}</b><span class="block text-xs text-muted">{{ $t->reason ?: 'Time off' }}</span></span>
                            <form method="post" action="{{ route('admin.staff.time-off.remove', [$staff, $t]) }}" data-confirm="Remove this time off?">@csrf @method('DELETE')<button class="a-btn a-btn-ghost a-btn-sm" aria-label="Remove"><x-icon name="trash" class="size-3.5" /></button></form></li>
                    @empty
                        <li class="py-3 text-sm text-muted">No time off planned.</li>
                    @endforelse
                </ul>
                <form method="post" action="{{ route('admin.staff.time-off', $staff) }}" class="mt-4 grid grid-cols-2 gap-3 border-t border-line pt-4">@csrf
                    <div><label class="a-label">From</label><input type="date" name="starts_on" class="a-input" required></div>
                    <div><label class="a-label">To</label><input type="date" name="ends_on" class="a-input" required></div>
                    <div class="col-span-2"><label class="a-label">Reason</label><input name="reason" class="a-input" placeholder="Vacation, training…"></div>
                    <button class="a-btn a-btn-soft col-span-2">Add time off</button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
