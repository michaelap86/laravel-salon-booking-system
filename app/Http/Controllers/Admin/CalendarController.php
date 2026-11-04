<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Closure;
use App\Models\Staff;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $staffId = $user->scopedStaffId();

        return view('admin.calendar', [
            'staff' => Staff::query()->active()->when($staffId, fn ($q) => $q->where('id', $staffId))->get(['id', 'name', 'color']),
            'config' => [
                'canMove' => $user->hasRole('admin', 'manager'),
                'canCreate' => $user->hasRole('admin', 'manager'),
                'urls' => [
                    'events' => route('admin.calendar.events'),
                    'move' => route('admin.calendar.move', '__ID__'),
                    'create' => route('admin.appointments.create'),
                ],
            ],
        ]);
    }

    /** One column per stylist for a single day, like a paper appointment book. */
    public function day(Request $request): View
    {
        $user = $request->user();
        $staffId = $user->scopedStaffId();
        $date = Carbon::parse($request->query('date', today()->toDateString()))->startOfDay();

        $hours = BusinessHour::query()->where('weekday', $date->dayOfWeek)->first();
        $closure = Closure::query()->whereDate('date', $date)->first();
        $min = fn (?string $t) => $t ? (int) substr($t, 0, 2) * 60 + (int) substr($t, 3, 2) : null;
        $open = $hours?->is_open ? $min($hours->opens) : 9 * 60;
        $close = $hours?->is_open ? $min($hours->closes) : 18 * 60;

        $staff = Staff::query()->active()->with(['workingHours', 'timeOff'])->when($staffId, fn ($q) => $q->where('id', $staffId))->get();
        $appointments = Appointment::query()->with(['client:id,name,vip', 'service:id,name'])->whereDate('start_at', $date)
            ->where('status', '!=', 'cancelled')->when($staffId, fn ($q) => $q->where('staff_id', $staffId))->orderBy('start_at')->get()->groupBy('staff_id');

        $columns = $staff->map(function (Staff $s) use ($date, $min, $appointments) {
            $wh = $s->workingHours->firstWhere('weekday', $date->dayOfWeek);
            $off = $s->isOffOn($date) ? 'Time off' : (! $wh?->is_working ? 'Not working' : null);

            return [
                'staff' => $s, 'off' => $off,
                'from' => $wh?->is_working ? $min($wh->starts) : null, 'to' => $wh?->is_working ? $min($wh->ends) : null,
                'break' => $wh?->break_starts ? [$min($wh->break_starts), $min($wh->break_ends)] : null,
                'appointments' => $appointments[$s->id] ?? collect(),
            ];
        });

        return view('admin.calendar-day', [
            'date' => $date, 'columns' => $columns, 'open' => $open, 'close' => $close, 'perMinute' => 1.25,
            'closedReason' => $closure ? $closure->reason : (($hours && ! $hours->is_open) ? 'The salon is closed on '.$date->format('l').'s' : null),
            'canCreate' => $user->hasRole('admin', 'manager'),
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        $staffId = $request->user()->scopedStaffId();
        // FullCalendar sends local wall-clock times; keep just "Y-m-d\TH:i:s"
        $start = Carbon::parse(substr($request->query('start', today()->toDateString()), 0, 19));
        $end = Carbon::parse(substr($request->query('end', today()->addWeek()->toDateString()), 0, 19));

        $events = Appointment::query()->with(['client:id,name', 'service:id,name', 'staff:id,name,color'])
            ->when($staffId, fn ($q) => $q->where('staff_id', $staffId))
            ->where('start_at', '>=', $start)->where('start_at', '<', $end)->where('status', '!=', 'cancelled')
            ->get()->map->toCalendarEvent();

        return response()->json($events);
    }

    public function move(Request $request, Appointment $appointment, BookingService $bookings): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin', 'manager'), 403);
        abort_unless($appointment->isCancellable(), 422, 'Only upcoming appointments can be moved.');
        $data = $request->validate(['start' => 'required|date_format:Y-m-d H:i']);

        $bookings->reschedule($appointment->load(['client', 'service', 'staff']), Carbon::parse($data['start']));

        return response()->json(['ok' => true]);
    }
}
