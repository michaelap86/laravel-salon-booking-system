<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\Staff;
use App\Models\TimeOff;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        $weekStart = today()->startOfWeek();

        return view('admin.staff.index', [
            'staff' => Staff::query()->orderBy('active', 'desc')->orderBy('sort')
                ->withAvg(['reviews as rating' => fn ($q) => $q->where('published', true)], 'rating')
                ->withCount(['appointments as week_count' => fn ($q) => $q->whereIn('status', ['pending', 'confirmed', 'completed'])->where('start_at', '>=', $weekStart)->where('start_at', '<', $weekStart->copy()->addWeek())])
                ->withCount('services')->with('timeOff')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.staff.form', ['staff' => new Staff(['color' => '#7c3aed', 'years' => 3, 'active' => true]), 'services' => $this->services(), 'assigned' => [], 'hours' => collect(), 'timeOff' => collect()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $email = $request->validate(['email' => 'nullable|email|unique:users,email'])['email'] ?? null;
        $user = $email ? User::query()->create(['name' => $data['name'], 'email' => $email, 'password' => Str::random(32), 'role' => Role::Stylist, 'is_demo' => false]) : null;

        $staff = Staff::query()->create([...$data, 'user_id' => $user?->id, 'sort' => (Staff::query()->max('sort') ?? 0) + 1]);
        $staff->services()->sync($request->input('services', []));
        foreach (range(0, 6) as $wd) {
            $works = in_array($wd, [2, 3, 4, 5, 6], true);
            $staff->workingHours()->create(['weekday' => $wd, 'is_working' => $works, 'starts' => $works ? '09:00' : null, 'ends' => $works ? '18:00' : null, 'break_starts' => $works ? '13:00' : null, 'break_ends' => $works ? '14:00' : null]);
        }
        Activity::log('staff', $staff->name, 'Added team member '.$staff->name, route('admin.staff.edit', $staff));

        return redirect()->route('admin.staff.edit', $staff)->with('success', 'Team member added. Set their weekly schedule below.');
    }

    public function edit(Staff $staff): View
    {
        return view('admin.staff.form', [
            'staff' => $staff, 'services' => $this->services(), 'assigned' => $staff->services()->pluck('services.id')->all(),
            'hours' => $staff->workingHours()->get()->keyBy('weekday'), 'timeOff' => $staff->timeOff()->orderBy('starts_on')->get(),
        ]);
    }

    public function update(Request $request, Staff $staff): RedirectResponse
    {
        $staff->update($this->validated($request) + ['active' => $request->boolean('active')]);
        $staff->services()->sync($request->input('services', []));
        Activity::log('staff', $staff->name, 'Updated profile of '.$staff->name, route('admin.staff.edit', $staff));

        return back()->with('success', 'Profile saved.');
    }

    public function schedule(Request $request, Staff $staff): RedirectResponse
    {
        $rows = $request->validate([
            'days' => 'required|array', 'days.*.starts' => 'nullable|date_format:H:i', 'days.*.ends' => 'nullable|date_format:H:i',
            'days.*.break_starts' => 'nullable|date_format:H:i', 'days.*.break_ends' => 'nullable|date_format:H:i',
        ])['days'];

        foreach (range(0, 6) as $wd) {
            $d = $rows[$wd] ?? [];
            $works = ! empty($d['works']) && ! empty($d['starts']) && ! empty($d['ends']) && $d['ends'] > $d['starts'];
            $staff->workingHours()->updateOrCreate(['weekday' => $wd], [
                'is_working' => $works, 'starts' => $works ? $d['starts'] : null, 'ends' => $works ? $d['ends'] : null,
                'break_starts' => $works && ! empty($d['break_starts']) ? $d['break_starts'] : null, 'break_ends' => $works && ! empty($d['break_ends']) ? $d['break_ends'] : null,
            ]);
        }
        Activity::log('staff', $staff->name, 'Updated working hours of '.$staff->name, route('admin.staff.edit', $staff));

        return back()->with('success', 'Weekly schedule saved. Availability updates instantly.');
    }

    public function addTimeOff(Request $request, Staff $staff): RedirectResponse
    {
        $data = $request->validate(['starts_on' => 'required|date', 'ends_on' => 'required|date|after_or_equal:starts_on', 'reason' => 'nullable|string|max:120']);
        $staff->timeOff()->create($data);
        Activity::log('staff', $staff->name, $staff->name.' is off '.$data['starts_on'].' to '.$data['ends_on'], route('admin.staff.edit', $staff));

        return back()->with('success', 'Time off added. Those days are no longer bookable.');
    }

    public function removeTimeOff(Staff $staff, TimeOff $timeOff): RedirectResponse
    {
        abort_unless($timeOff->staff_id === $staff->id, 404);
        $timeOff->delete();

        return back()->with('success', 'Time off removed.');
    }

    private function services()
    {
        return Service::query()->with('category:id,name,icon')->orderBy('service_category_id')->orderBy('sort')->get(['id', 'name', 'service_category_id']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:120', 'title' => 'required|string|max:120', 'bio' => 'required|string|max:800',
            'specialties' => 'required|string|max:200', 'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'], 'years' => 'required|integer|min:0|max:60',
            'instagram' => 'nullable|string|max:60',
        ]);
    }
}
