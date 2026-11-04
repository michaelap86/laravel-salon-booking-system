<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $segment = $request->query('segment', 'all');

        return view('admin.clients.index', [
            'clients' => $this->query($request)->paginate(20)->withQueryString(),
            'segment' => $segment,
            'q' => $request->query('q'),
            'sort' => $request->query('sort', '-visits'),
            'segments' => ['all' => 'All clients', 'vip' => 'VIP', 'new' => 'New (30 days)', 'regular' => 'Regulars (5+ visits)', 'lapsed' => 'Lapsed (60+ days)'],
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()->hasRole('admin', 'manager'), 403);

        return view('admin.clients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin', 'manager'), 403);
        $data = $request->validate(['name' => 'required|string|max:120', 'email' => 'required|email|max:160', 'phone' => 'nullable|string|max:30', 'birthday' => 'nullable|date|before:today', 'notes' => 'nullable|string|max:2000']);
        $client = Client::query()->create([...$data, 'source' => 'admin']);
        Activity::log('client', $client->name, 'Added client '.$client->name, route('admin.clients.show', $client));

        return redirect()->route('admin.clients.show', $client)->with('success', 'Client added.');
    }

    public function show(Request $request, Client $client): View
    {
        $staffId = $request->user()->scopedStaffId();
        abort_if($staffId && ! $client->appointments()->where('staff_id', $staffId)->exists(), 404);

        $appointments = $client->appointments()->with(['service:id,name,service_category_id', 'staff:id,name,color'])
            ->when($staffId, fn ($q) => $q->where('staff_id', $staffId))->orderByDesc('start_at')->get();
        $done = $appointments->where('status', \App\Enums\AppointmentStatus::Completed);

        return view('admin.clients.show', [
            'client' => $client,
            'appointments' => $appointments->take(15),
            'stats' => [
                'visits' => $done->count(),
                'spent' => $done->sum('price'),
                'avg' => $done->count() ? intdiv($done->sum('price'), $done->count()) : 0,
                'last' => $done->first()?->start_at,
                'missed' => $appointments->whereIn('status', [\App\Enums\AppointmentStatus::NoShow, \App\Enums\AppointmentStatus::Cancelled])->count(),
            ],
            'favourite' => $done->groupBy('service.name')->map->count()->sortDesc()->keys()->first(),
            'tier' => $client->tier(),
            'reviews' => $client->reviews()->with('service:id,name')->latest()->limit(3)->get(),
            'canEdit' => $request->user()->hasRole('admin', 'manager'),
        ]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:120', 'phone' => 'nullable|string|max:30', 'birthday' => 'nullable|date|before:today', 'notes' => 'nullable|string|max:2000', 'vip' => 'nullable|boolean']);
        $client->update([...$data, 'vip' => $request->boolean('vip')]);
        Activity::log('client', $client->name, 'Updated client '.$client->name, route('admin.clients.show', $client));

        return back()->with('success', 'Client saved.');
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->hasRole('admin', 'manager'), 403);
        Activity::log('export', 'Clients', 'Exported clients (CSV)');
        $query = $this->query($request);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Name', 'Email', 'Phone', 'Visits', 'Lifetime spend (USD)', 'Last visit', 'VIP', 'Marketing opt-in']);
            $query->lazy()->each(fn ($c) => fputcsv($out, [$c->name, $c->email, $c->phone, $c->visits, number_format(($c->spent ?? 0) / 100, 2, '.', ''), $c->last_visit, $c->vip ? 'yes' : 'no', $c->marketing_opt_in ? 'yes' : 'no']));
            fclose($out);
        }, 'clients-'.today()->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }

    private function query(Request $request)
    {
        $staffId = $request->user()->scopedStaffId();
        $segment = $request->query('segment', 'all');
        $sort = $request->query('sort', '-visits');
        $col = ['name' => 'name', 'visits' => 'visits', 'spent' => 'spent', 'last' => 'last_visit', 'joined' => 'created_at'][ltrim($sort, '-')] ?? 'visits';

        return Client::query()
            ->withCount(['appointments as visits' => fn ($q) => $q->where('status', 'completed')])
            ->withSum(['appointments as spent' => fn ($q) => $q->where('status', 'completed')], 'price')
            ->withMax(['appointments as last_visit' => fn ($q) => $q->where('status', 'completed')], 'start_at')
            ->when($staffId, fn ($q) => $q->whereHas('appointments', fn ($a) => $a->where('staff_id', $staffId)))
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")))
            ->when($segment === 'vip', fn ($q) => $q->where('vip', true))
            ->when($segment === 'new', fn ($q) => $q->where('created_at', '>=', today()->subDays(30)))
            ->when($segment === 'regular', fn ($q) => $q->has('appointments', '>=', 5, 'and', fn ($a) => $a->where('status', 'completed')))
            // Lapsed: has visited before, but not in the last 60 days
            ->when($segment === 'lapsed', fn ($q) => $q->whereHas('appointments', fn ($a) => $a->where('status', 'completed'))
                ->whereDoesntHave('appointments', fn ($a) => $a->where('status', 'completed')->where('start_at', '>=', today()->subDays(60))))
            ->orderBy($col, str_starts_with($sort, '-') ? 'desc' : 'asc')->orderBy('id');
    }
}
