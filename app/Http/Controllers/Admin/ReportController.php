<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Client;
use App\Models\Closure;
use App\Models\Review;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const RANGES = [30 => 'Last 30 days', 90 => 'Last 90 days', 180 => 'Last 6 months', 365 => 'Last 12 months'];

    public function index(Request $request): View
    {
        $days = (int) $request->query('range', 90);
        $days = array_key_exists($days, self::RANGES) ? $days : 90;
        $data = $this->build($days);

        $revenueByWeek = $data['done']->groupBy(fn ($a) => $days <= 30 ? $a->start_at->format('M j') : $a->start_at->copy()->startOfWeek()->format('M j'));
        $labels = $revenueByWeek->keys();

        $heat = $data['active']->groupBy(fn ($a) => $a->start_at->dayOfWeek)->map(fn ($g) => $g->groupBy(fn ($a) => $a->start_at->hour)->map->count());
        $hours = range(9, 19);
        $heatSeries = collect([1, 2, 3, 4, 5, 6, 0])->map(fn ($d) => [
            'name' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][$d],
            'data' => collect($hours)->map(fn ($h) => ['x' => date('ga', mktime($h)), 'y' => $heat[$d][$h] ?? 0])->values(),
        ])->reverse()->values();

        $sources = $data['active']->groupBy('source')->map->count()->sortDesc();
        $top = $data['services']->take(8);

        return view('admin.reports', [
            'days' => $days, 'ranges' => self::RANGES, 'kpis' => $data['kpis'], 'services' => $data['services'], 'staff' => $data['staff'], 'topClients' => $data['topClients'],
            'charts' => [
                'trend' => ['type' => 'area', 'height' => 300, 'money' => true, 'categories' => $labels, 'series' => [['name' => 'Revenue', 'data' => $revenueByWeek->map(fn ($g) => round($g->sum('price') / 100))->values()]]],
                'services' => ['type' => 'bar', 'height' => 340, 'money' => true, 'horizontal' => true, 'categories' => $top->pluck('name'), 'series' => [['name' => 'Revenue', 'data' => $top->pluck('revenue')->map(fn ($v) => round($v / 100))]]],
                'heat' => ['type' => 'heatmap', 'height' => 330, 'categories' => [], 'series' => $heatSeries],
                'sources' => ['type' => 'donut', 'height' => 280, 'categories' => $sources->keys()->map(fn ($s) => ucfirst($s)), 'series' => $sources->values(), 'totalLabel' => 'Bookings'],
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $days = (int) $request->query('range', 90);
        $data = $this->build(array_key_exists($days, self::RANGES) ? $days : 90);
        Activity::log('export', 'Service report', 'Exported service performance (CSV)');

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Service', 'Category', 'Completed visits', 'Revenue (USD)', 'Average price (USD)', 'Share of revenue %']);
            foreach ($data['services'] as $s) {
                fputcsv($out, [$s['name'], $s['category'], $s['count'], number_format($s['revenue'] / 100, 2, '.', ''), number_format($s['avg'] / 100, 2, '.', ''), $s['share']]);
            }
            fclose($out);
        }, 'service-report-'.today()->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }

    private function build(int $days): array
    {
        $from = today()->subDays($days - 1);
        $appointments = Appointment::query()->where('start_at', '>=', $from)->where('start_at', '<', now())
            ->get(['id', 'client_id', 'staff_id', 'service_id', 'start_at', 'status', 'price', 'source']);
        $done = $appointments->where('status', AppointmentStatus::Completed);
        $active = $appointments->whereIn('status', [AppointmentStatus::Completed, AppointmentStatus::Confirmed]);
        $svc = Service::query()->with('category:id,name')->get()->keyBy('id');
        $revenue = $done->sum('price');

        $perClient = $done->groupBy('client_id');
        $kpis = [
            ['Revenue', money($revenue), 'wallet', null],
            ['Completed visits', number_format($done->count()), 'check-circle', null],
            ['Average ticket', money($done->count() ? intdiv($revenue, $done->count()) : 0), 'tag', null],
            ['New clients', number_format(Client::query()->where('created_at', '>=', $from)->count()), 'users', null],
            ['Repeat rate', $perClient->count() ? round($perClient->filter(fn ($g) => $g->count() >= 2)->count() / $perClient->count() * 100).'%' : '–', 'refresh', 'clients with 2+ visits'],
            ['No-show rate', $appointments->count() ? round($appointments->where('status', AppointmentStatus::NoShow)->count() / $appointments->count() * 100, 1).'%' : '–', 'alert', null],
        ];

        $services = $done->groupBy('service_id')->map(function (Collection $g, $id) use ($svc, $revenue) {
            $sum = $g->sum('price');

            return ['name' => $svc[$id]->name, 'category' => $svc[$id]->category->name, 'count' => $g->count(), 'revenue' => $sum, 'avg' => intdiv($sum, max(1, $g->count())), 'share' => $revenue ? round($sum / $revenue * 100, 1) : 0];
        })->sortByDesc('revenue')->values();

        return [
            'done' => $done->sortBy('start_at'), 'active' => $active, 'kpis' => $kpis, 'services' => $services,
            'staff' => $this->staffStats($appointments, $svc, $from),
            'topClients' => $perClient->map(fn ($g, $id) => ['id' => $id, 'visits' => $g->count(), 'spent' => $g->sum('price')])->sortByDesc('spent')->take(8)->values()
                ->map(function ($c) { $c['client'] = Client::query()->find($c['id'], ['id', 'name']); return $c; }),
        ];
    }

    private function staffStats(Collection $appointments, Collection $svc, $from): Collection
    {
        $hours = BusinessHour::query()->get()->keyBy('weekday');
        $closed = Closure::query()->where('date', '>=', $from)->pluck('date')->map->toDateString()->flip();
        $ratings = Review::query()->where('created_at', '>=', $from)->selectRaw('staff_id, avg(rating) r, count(*) c')->groupBy('staff_id')->get()->keyBy('staff_id');

        return Staff::query()->active()->with(['workingHours', 'timeOff'])->get()->map(function (Staff $s) use ($appointments, $svc, $hours, $closed, $ratings, $from) {
            $mine = $appointments->where('staff_id', $s->id);
            $done = $mine->where('status', AppointmentStatus::Completed);
            $scheduled = 0;
            for ($d = $from->copy(); $d->lte(today()); $d->addDay()) {
                $wh = $s->workingHours->firstWhere('weekday', $d->dayOfWeek);
                $bh = $hours->get($d->dayOfWeek);
                if (! $wh?->is_working || ! $bh?->is_open || isset($closed[$d->toDateString()]) || $s->isOffOn($d)) {
                    continue;
                }
                $scheduled += max(0, $this->min($wh->ends) - $this->min($wh->starts) - ($wh->break_starts ? $this->min($wh->break_ends) - $this->min($wh->break_starts) : 0));
            }
            $booked = $done->sum(fn ($a) => $svc[$a->service_id]->duration);

            return [
                'id' => $s->id, 'name' => $s->name, 'color' => $s->color, 'title' => $s->title, 'visits' => $done->count(), 'revenue' => $done->sum('price'),
                'utilization' => $scheduled ? min(100, round($booked / $scheduled * 100)) : 0,
                'noshow' => $mine->count() ? round($mine->where('status', AppointmentStatus::NoShow)->count() / $mine->count() * 100, 1) : 0,
                'rating' => isset($ratings[$s->id]) ? round($ratings[$s->id]->r, 2) : null,
            ];
        })->sortByDesc('revenue')->values();
    }

    private function min(string $t): int
    {
        return (int) substr($t, 0, 2) * 60 + (int) substr($t, 3, 2);
    }
}
