<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $staffId = $user->scopedStaffId();
        $isManager = $user->hasRole('admin', 'manager');
        $base = fn () => Appointment::query()->when($staffId, fn ($q) => $q->where('staff_id', $staffId));
        $done = AppointmentStatus::Completed->value;
        $today = today();

        $todays = $base()->with(['client:id,name,vip,phone', 'service:id,name,duration', 'staff:id,name,color'])
            ->whereDate('start_at', $today)->whereNotIn('status', ['cancelled'])->orderBy('start_at')->get();

        $sum = fn (Carbon $from, Carbon $to) => (int) $base()->where('status', $done)->where('start_at', '>=', $from)->where('start_at', '<', $to)->sum('price');
        $monthStart = $today->copy()->startOfMonth();
        $elapsed = $today->diffInDays($monthStart) + 1;
        $revenue = $sum($monthStart, $today->copy()->addDay());
        $lastRevenue = $sum($monthStart->copy()->subMonthNoOverflow(), $monthStart->copy()->subMonthNoOverflow()->addDays($elapsed));

        $weekAhead = $base()->whereIn('status', ['pending', 'confirmed'])->where('start_at', '>=', now())->where('start_at', '<', $today->copy()->addDays(8));
        $clientScope = fn () => Client::query()->when($staffId, fn ($q) => $q->whereHas('appointments', fn ($a) => $a->where('staff_id', $staffId)));
        $newClients = $clientScope()->where('created_at', '>=', $monthStart)->count();
        $lastNewClients = $clientScope()->where('created_at', '>=', $monthStart->copy()->subMonthNoOverflow())->where('created_at', '<', $monthStart)->count();

        $last30 = $base()->where('start_at', '>=', $today->copy()->subDays(30))->where('start_at', '<', now())->get(['status', 'price', 'start_at', 'staff_id', 'service_id']);
        $lost = $last30->whereIn('status', [AppointmentStatus::NoShow, AppointmentStatus::Cancelled])->count();
        $rate = $last30->count() ? round($lost / $last30->count() * 100, 1) : 0;

        $reviewScope = fn () => Review::query()->when($staffId, fn ($q) => $q->where('staff_id', $staffId));
        $rating = round((float) $reviewScope()->where('created_at', '>=', $today->copy()->subDays(90))->avg('rating'), 2);

        $kpis = [
            ['Today', $todays->count(), 'appointments', 'calendar', $todays->where('status', AppointmentStatus::Completed)->count().' completed so far', null],
            ['Revenue this month', money($revenue), 'completed visits', 'wallet', null, $lastRevenue ? round(($revenue - $lastRevenue) / $lastRevenue * 100, 1) : null],
            ['Booked, next 7 days', (clone $weekAhead)->count(), 'upcoming visits', 'clock', money((clone $weekAhead)->sum('price')).' expected', null],
            ['New clients', $newClients, 'this month', 'users', null, $lastNewClients ? round(($newClients - $lastNewClients) / $lastNewClients * 100, 1) : null],
            ['Average rating', $rating ?: '–', 'last 90 days', 'star', null, null],
            ['Missed or cancelled', $rate.'%', 'last 30 days', 'alert', $lost.' appointments', null],
        ];

        // Charts
        $daily = $last30->where('status', AppointmentStatus::Completed)->groupBy(fn ($a) => $a->start_at->format('M j'))->map(fn ($g) => $g->sum('price') / 100);
        $labels = collect(range(29, 0))->map(fn ($d) => $today->copy()->subDays($d)->format('M j'));
        $revenueChart = ['type' => 'area', 'height' => 290, 'money' => true, 'categories' => $labels, 'series' => [['name' => 'Revenue', 'data' => $labels->map(fn ($l) => round($daily[$l] ?? 0, 2))]]];

        $services = $last30->where('status', AppointmentStatus::Completed)->groupBy('service_id');
        $serviceNames = \App\Models\Service::query()->with('category:id,name')->get(['id', 'name', 'service_category_id'])->keyBy('id');
        $byCategory = $services->flatMap(fn ($g, $id) => $g->map(fn ($a) => ['cat' => $serviceNames[$id]->category->name ?? '?', 'price' => $a->price]))
            ->groupBy('cat')->map(fn ($g) => round($g->sum('price') / 100))->sortDesc();
        $categoryChart = ['type' => 'donut', 'height' => 280, 'money' => true, 'categories' => $byCategory->keys(), 'series' => $byCategory->values(), 'totalLabel' => '30-day revenue'];

        $weekdays = $base()->where('start_at', '>=', $today->copy()->subDays(90))->where('start_at', '<', now())->whereIn('status', ['completed', 'confirmed'])->get(['start_at'])->groupBy(fn ($a) => $a->start_at->dayOfWeek)->map->count();
        $weekdayChart = ['type' => 'bar', 'height' => 260, 'categories' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'], 'series' => [['name' => 'Appointments', 'data' => collect(range(0, 6))->map(fn ($d) => $weekdays[$d] ?? 0)]]];

        $staffChart = null;
        if ($isManager) {
            $staff = \App\Models\Staff::query()->active()->get(['id', 'name', 'color']);
            $by = $last30->where('status', AppointmentStatus::Completed)->groupBy('staff_id')->map(fn ($g) => round($g->sum('price') / 100));
            $staffChart = ['type' => 'bar', 'height' => 300, 'money' => true, 'horizontal' => true, 'distributed' => true, 'colors' => $staff->pluck('color'), 'categories' => $staff->pluck('name'), 'series' => [['name' => 'Revenue', 'data' => $staff->map(fn ($s) => $by[$s->id] ?? 0)]]];
        }

        return view('admin.dashboard', [
            'kpis' => $kpis,
            'todays' => $todays,
            'pending' => $base()->with(['client:id,name', 'service:id,name', 'staff:id,name'])->where('status', 'pending')->where('start_at', '>=', now())->orderBy('start_at')->limit(6)->get(),
            'reviews' => $reviewScope()->with(['client:id,name', 'service:id,name'])->latest()->limit(4)->get(),
            'birthdays' => $isManager ? $this->birthdays() : collect(),
            'charts' => compact('revenueChart', 'categoryChart', 'weekdayChart', 'staffChart'),
            'isManager' => $isManager,
        ]);
    }

    public function seen(Request $request): JsonResponse
    {
        $request->user()->update(['notifications_seen_at' => now()]);

        return response()->json(['ok' => true]);
    }

    private function birthdays()
    {
        $today = today();

        return Client::query()->whereNotNull('birthday')->get(['id', 'name', 'birthday', 'vip'])
            ->map(function ($c) use ($today) {
                $next = $c->birthday->copy()->year($today->year);
                if ($next->lt($today)) {
                    $next->addYear();
                }
                $c->next_birthday = $next;

                return $c;
            })->filter(fn ($c) => $c->next_birthday->lte($today->copy()->addDays(14)))->sortBy('next_birthday')->take(5)->values();
    }
}
