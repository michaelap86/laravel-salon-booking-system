<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Closure;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Staff;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Works out when a client can book a service.
 *
 * A start time is bookable for a stylist when it falls inside the salon's opening hours,
 * the stylist's own shift, outside their break and time off, far enough in the future,
 * and does not overlap another appointment (including the cleanup buffer after it).
 */
class Availability
{
    private ?Collection $hours = null;

    private ?array $closures = null;

    /** Active stylists who offer the service, with their schedules loaded. */
    public function eligibleStaff(Service $service): Collection
    {
        return $service->staff()->where('staff.active', true)->with(['workingHours', 'timeOff'])->orderBy('staff.sort')->get();
    }

    /**
     * Bookable start times on one day.
     *
     * @return array<int, array{time: string, label: string, staff: int[]}>
     */
    public function slots(Service $service, ?int $staffId, Carbon $date, ?int $ignoreAppointmentId = null): array
    {
        $ctx = $this->context($service, $staffId, $date, $date);

        return $this->daySlots($ctx, $service, $date, $ignoreAppointmentId);
    }

    /**
     * Number of bookable start times for each day in a range (days without any are omitted).
     *
     * @return array<string, int>
     */
    public function openDates(Service $service, ?int $staffId, Carbon $from, Carbon $to): array
    {
        $ctx = $this->context($service, $staffId, $from, $to);
        $out = [];
        for ($d = $from->copy()->startOfDay(); $d->lte($to); $d->addDay()) {
            $count = count($this->daySlots($ctx, $service, $d, null));
            if ($count > 0) {
                $out[$d->toDateString()] = $count;
            }
        }

        return $out;
    }

    /** Ids of stylists who can take this exact start time. */
    public function freeStaff(Service $service, ?int $staffId, Carbon $start, ?int $ignoreAppointmentId = null): array
    {
        foreach ($this->slots($service, $staffId, $start->copy()->startOfDay(), $ignoreAppointmentId) as $slot) {
            if ($slot['time'] === $start->format('H:i')) {
                return $slot['staff'];
            }
        }

        return [];
    }

    /** When several stylists are free, give the job to whoever has the lightest day. */
    public function leastBusy(array $staffIds, Carbon $start): int
    {
        $counts = Appointment::query()->blocking()->whereIn('staff_id', $staffIds)
            ->whereDate('start_at', $start->toDateString())->selectRaw('staff_id, count(*) c')->groupBy('staff_id')->pluck('c', 'staff_id');

        return collect($staffIds)->sortBy(fn ($id) => $counts[$id] ?? 0)->first();
    }

    /** Any overlapping appointment for this stylist (used by the admin calendar). */
    public function conflict(int $staffId, Carbon $start, Carbon $end, ?int $ignoreAppointmentId = null): ?Appointment
    {
        return Appointment::query()->blocking()->where('staff_id', $staffId)
            ->when($ignoreAppointmentId, fn ($q) => $q->where('id', '!=', $ignoreAppointmentId))
            ->where('start_at', '<', $end)->where('end_at', '>', $start)->first();
    }

    public function isOpen(Carbon $date): bool
    {
        $bh = $this->businessHours()->get($date->dayOfWeek);

        return $bh && $bh->is_open && ! isset($this->closures()[$date->toDateString()]);
    }

    // ------------------------------------------------------------------ internals
    private function context(Service $service, ?int $staffId, Carbon $from, Carbon $to): array
    {
        $staff = $this->eligibleStaff($service);
        if ($staffId) {
            $staff = $staff->where('id', $staffId)->values();
        }
        $appointments = Appointment::query()->blocking()->with('service:id,buffer')
            ->whereIn('staff_id', $staff->pluck('id'))
            ->where('start_at', '>=', $from->copy()->startOfDay())->where('start_at', '<', $to->copy()->addDay()->startOfDay())
            ->get()->groupBy(fn (Appointment $a) => $a->staff_id.'|'.$a->start_at->toDateString());

        return ['staff' => $staff, 'appointments' => $appointments];
    }

    private function daySlots(array $ctx, Service $service, Carbon $date, ?int $ignoreId): array
    {
        $bh = $this->businessHours()->get($date->dayOfWeek);
        $key = $date->toDateString();
        if (! $bh || ! $bh->is_open || isset($this->closures()[$key])) {
            return [];
        }
        if ($date->gt(today()->addDays(Setting::int('booking_window_days')))) {
            return [];
        }

        $open = $this->minutes($bh->opens);
        $close = $this->minutes($bh->closes);
        $step = max(5, Setting::int('slot_interval'));
        $earliest = now()->addHours(Setting::int('min_notice_hours'));
        $found = [];

        foreach ($ctx['staff'] as $staff) {
            /** @var Staff $staff */
            $wh = $staff->workingHours->firstWhere('weekday', $date->dayOfWeek);
            if (! $wh || ! $wh->is_working || $staff->isOffOn($date)) {
                continue;
            }
            $from = (int) (ceil(max($open, $this->minutes($wh->starts)) / $step) * $step);
            $to = min($close, $this->minutes($wh->ends));

            $busy = [];
            if ($wh->break_starts && $wh->break_ends) {
                $busy[] = [$this->minutes($wh->break_starts), $this->minutes($wh->break_ends)];
            }
            foreach ($ctx['appointments'][$staff->id.'|'.$key] ?? [] as $a) {
                if ($a->id !== $ignoreId) {
                    $busy[] = [$this->minutes($a->start_at->format('H:i')), $this->minutes($a->end_at->format('H:i')) + (int) $a->service->buffer];
                }
            }

            for ($t = $from; $t + $service->duration <= $to; $t += $step) {
                if ($date->copy()->startOfDay()->addMinutes($t)->lt($earliest)) {
                    continue;
                }
                foreach ($busy as [$bs, $be]) {
                    if ($t < $be && $t + $service->duration > $bs) {
                        continue 2;
                    }
                }
                $found[$t][] = $staff->id;
            }
        }

        ksort($found);

        return collect($found)->map(fn ($ids, $t) => [
            'time' => sprintf('%02d:%02d', intdiv($t, 60), $t % 60),
            'label' => Carbon::createFromTime(intdiv($t, 60), $t % 60)->format('g:i A'),
            'staff' => $ids,
        ])->values()->all();
    }

    private function minutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($time, 0, 5)));

        return $h * 60 + $m;
    }

    private function businessHours(): Collection
    {
        return $this->hours ??= BusinessHour::query()->get()->keyBy('weekday');
    }

    private function closures(): array
    {
        return $this->closures ??= Closure::query()->get()->mapWithKeys(fn ($c) => [$c->date->toDateString() => $c->reason])->all();
    }
}
