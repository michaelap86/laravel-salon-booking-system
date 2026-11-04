<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Mail\BookingReminder;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.appointments.index', [
            'appointments' => $this->filtered($request)->paginate(20)->withQueryString(),
            'staff' => Staff::query()->active()->get(['id', 'name']),
            'filters' => $request->only(['q', 'status', 'staff', 'from', 'to']),
            'statuses' => AppointmentStatus::cases(),
            'counts' => $this->scoped($request)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->hasRole('admin', 'manager'), 403);
        $services = Service::query()->active()->with('category:id,name,slug')->orderBy('sort')->get();
        $staff = Staff::query()->active()->with('services:id')->get();

        return view('admin.appointments.create', [
            'clients' => Client::query()->orderBy('name')->get(['id', 'name', 'email']),
            'services' => $services,
            'staff' => $staff,
            'config' => [
                'service' => $request->query('service'), 'preStaff' => $request->query('staff'), 'date' => $request->query('date', today()->toDateString()), 'time' => $request->query('time'),
                'ladies' => ServiceCategory::query()->where('ladies_pricing', true)->pluck('slug'),
                'services' => $services->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'cat' => $s->category->slug]),
                'staff' => $staff->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'services' => $s->services->pluck('id')]),
                'urls' => ['slots' => route('api.booking.slots')],
            ],
        ]);
    }

    public function store(Request $request, BookingService $bookings): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin', 'manager'), 403);
        $data = $request->validate([
            'client_id' => 'nullable|integer|exists:clients,id',
            'name' => 'required_without:client_id|nullable|string|max:120',
            'email' => 'required_without:client_id|nullable|email|max:160',
            'phone' => 'nullable|string|max:30',
            'service_id' => 'required|integer|exists:services,id',
            'staff_id' => 'nullable|integer|exists:staff,id',
            'date' => 'required|date_format:Y-m-d',
            'time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'notes' => 'nullable|string|max:500',
            'deposit_paid' => 'nullable|boolean',
        ]);
        if (! empty($data['client_id'])) {
            $client = Client::query()->findOrFail($data['client_id']);
            $data = [...$data, 'name' => $client->name, 'email' => $client->email, 'phone' => $client->phone];
        }

        $appointment = $bookings->book($data, null, 'admin', false);
        if ($request->boolean('deposit_paid')) {
            $appointment->update(['deposit_paid' => true]);
        }

        return redirect()->route('admin.appointments.show', $appointment)->with('success', 'Appointment booked. A confirmation email was sent to the client.');
    }

    public function show(Request $request, Appointment $appointment): View
    {
        $this->authorizeView($request, $appointment);
        $appointment->load(['client', 'service.category', 'staff', 'review']);

        return view('admin.appointments.show', [
            'appointment' => $appointment,
            'history' => Activity::query()->with('user:id,name')->where('subject', $appointment->reference)->latest('created_at')->get(),
            'clientVisits' => $appointment->client->appointments()->where('status', 'completed')->count(),
            'clientSpend' => $appointment->client->lifetimeSpend(),
            'canEdit' => $request->user()->hasRole('admin', 'manager'),
        ]);
    }

    public function status(Request $request, Appointment $appointment, BookingService $bookings): RedirectResponse
    {
        $this->authorizeView($request, $appointment);
        $data = $request->validate(['status' => ['required', Rule::enum(AppointmentStatus::class)]]);
        $new = AppointmentStatus::from($data['status']);

        if ($new === AppointmentStatus::Cancelled) {
            $bookings->cancel($appointment->load(['client', 'service', 'staff']), 'Cancelled by the salon');
        } else {
            $old = $appointment->status->label();
            $appointment->update(['status' => $new]);
            Activity::log('status', $appointment->reference, $appointment->client->name.': '.$old.' → '.$new->label(), route('admin.appointments.show', $appointment));
        }

        return back()->with('success', 'Marked as '.$new->label().'.');
    }

    public function reschedule(Request $request, Appointment $appointment, BookingService $bookings): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin', 'manager'), 403);
        $data = $request->validate(['date' => 'required|date_format:Y-m-d', 'time' => ['required', 'regex:/^\d{2}:\d{2}$/']]);
        $bookings->reschedule($appointment->load(['client', 'service', 'staff']), Carbon::parse($data['date'].' '.$data['time']));

        return back()->with('success', 'Appointment moved.');
    }

    public function remind(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorizeView($request, $appointment);
        Mail::to($appointment->client->email)->send(new BookingReminder($appointment->load(['client', 'service', 'staff'])));
        Activity::log('status', $appointment->reference, 'Reminder sent to '.$appointment->client->name, route('admin.appointments.show', $appointment));

        return back()->with('success', 'Reminder sent. You can read it in the email outbox.');
    }

    public function notes(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorizeView($request, $appointment);
        $appointment->update($request->validate(['notes' => 'nullable|string|max:500']));

        return back()->with('success', 'Notes saved.');
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->hasRole('admin', 'manager'), 403);
        Activity::log('export', 'Appointments', 'Exported appointments (CSV)');
        $query = $this->filtered($request);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Reference', 'Date', 'Time', 'Client', 'Email', 'Service', 'Stylist', 'Status', 'Price (USD)', 'Deposit paid', 'Source']);
            $query->lazy()->each(fn ($a) => fputcsv($out, [
                $a->reference, $a->start_at->format('Y-m-d'), $a->start_at->format('H:i'), $a->client->name, $a->client->email, $a->service->name,
                $a->staff->name, $a->status->label(), number_format($a->price / 100, 2, '.', ''), $a->deposit_paid ? 'yes' : 'no', $a->source,
            ]));
            fclose($out);
        }, 'appointments-'.today()->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }

    private function scoped(Request $request)
    {
        return Appointment::query()->when($request->user()->scopedStaffId(), fn ($q, $id) => $q->where('staff_id', $id));
    }

    private function filtered(Request $request)
    {
        $f = $request->only(['q', 'status', 'staff', 'from', 'to']);

        return $this->scoped($request)->with(['client:id,name,email,vip', 'service:id,name', 'staff:id,name,color'])
            ->when($f['q'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('reference', 'like', '%'.strtoupper($s).'%')->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%"))))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($f['staff'] ?? null, fn ($q, $s) => $q->where('staff_id', $s))
            ->when($f['from'] ?? null, fn ($q, $d) => $q->where('start_at', '>=', Carbon::parse($d)->startOfDay()))
            ->when($f['to'] ?? null, fn ($q, $d) => $q->where('start_at', '<=', Carbon::parse($d)->endOfDay()))
            ->when(! array_filter($f), fn ($q) => $q->where('start_at', '>=', today()->subDays(3)))
            ->orderBy('start_at', ($f['to'] ?? null) || ($f['from'] ?? null) || ($f['status'] ?? null) === 'completed' ? 'desc' : 'asc');
    }

    private function authorizeView(Request $request, Appointment $appointment): void
    {
        $staffId = $request->user()->scopedStaffId();
        abort_if($staffId && $appointment->staff_id !== $staffId, 404);
    }
}
