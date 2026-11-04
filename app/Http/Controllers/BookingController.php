<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\Staff;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function create(Request $request): View
    {
        $user = $request->user();
        $client = $user?->client;
        $services = Service::query()->active()->with('category')->orderBy('sort')->get();
        $ratings = Staff::query()->active()->withAvg(['reviews as rating' => fn ($q) => $q->where('published', true)], 'rating')->with('services:id')->get();

        $cfg = [
            'preselect' => ($slug = $request->query('service')) ? $services->firstWhere('slug', $slug)?->id : null,
            'depositPercent' => Setting::int('deposit_percent'),
            'freeCancelHours' => Setting::int('free_cancel_hours'),
            'ladies' => ServiceCategory::query()->where('ladies_pricing', true)->pluck('slug'),
            'categories' => ServiceCategory::query()->orderBy('sort')->get(['slug', 'name', 'icon', 'theme']),
            'services' => $services->map(fn ($s) => [
                'id' => $s->id, 'name' => $s->name, 'description' => $s->description, 'duration' => $s->duration, 'price' => $s->price,
                'deposit' => $s->deposit_required, 'cat' => $s->category->slug, 'icon' => $s->category->icon, 'theme' => $s->category->theme, 'catName' => $s->category->name,
            ]),
            'staff' => $ratings->map(fn ($s) => [
                'id' => $s->id, 'name' => $s->name, 'title' => $s->title, 'color' => $s->color, 'initials' => $s->initials,
                'rating' => round((float) $s->rating, 1), 'services' => $s->services->pluck('id'),
            ]),
            'user' => $user ? ['name' => $user->name, 'email' => $user->email, 'phone' => $client?->phone ?? $user->phone] : null,
            'urls' => [
                'dates' => route('api.booking.dates'), 'slots' => route('api.booking.slots'),
                'promo' => route('api.booking.promo'), 'book' => route('book.store'),
            ],
        ];

        return view('site.book', ['cfg' => $cfg]);
    }

    public function store(Request $request, BookingService $bookings): JsonResponse
    {
        $data = $request->validate([
            'service_id' => 'required|integer|exists:services,id',
            'staff_id' => 'nullable|integer|exists:staff,id',
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160',
            'phone' => 'required|string|max:30',
            'notes' => 'nullable|string|max:500',
            'promo' => 'nullable|string|max:40',
            'card' => 'nullable|array',
            'card.number' => 'nullable|string|max:25',
            'card.expiry' => 'nullable|string|max:9',
            'card.cvc' => 'nullable|string|max:4',
        ]);

        $appointment = $bookings->book($data, $request->user());

        return response()->json(['ok' => true, 'url' => route('booking.show', $appointment->reference).'?new=1']);
    }

    public function show(Request $request, string $reference): View
    {
        $appointment = $this->find($reference);

        return view('site.booking', [
            'appointment' => $appointment,
            'isNew' => $request->boolean('new'),
            'canManage' => $appointment->isCancellable(),
        ]);
    }

    public function cancel(string $reference, BookingService $bookings): RedirectResponse
    {
        $appointment = $this->find($reference);
        abort_unless($appointment->isCancellable(), 403);
        $bookings->cancel($appointment);

        return redirect()->route('booking.show', $reference)->with('success', 'Your appointment has been cancelled.');
    }

    public function ics(string $reference): Response
    {
        $a = $this->find($reference);
        $fmt = fn ($d) => $d->copy()->utc()->format('Ymd\THis\Z');
        $body = implode("\r\n", [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Lumiere Studio//Booking//EN', 'CALSCALE:GREGORIAN', 'BEGIN:VEVENT',
            'UID:'.$a->reference.'@lumiere-studio.example', 'DTSTAMP:'.$fmt(now()), 'DTSTART:'.$fmt($a->start_at), 'DTEND:'.$fmt($a->end_at),
            'SUMMARY:'.$a->service->name.' at Lumière Studio', 'DESCRIPTION:With '.$a->staff->name.'. Booking ref '.$a->reference.'.',
            'LOCATION:'.str_replace(',', '\,', config('salon.address')), 'END:VEVENT', 'END:VCALENDAR',
        ]);

        return response($body, 200, ['Content-Type' => 'text/calendar; charset=utf-8', 'Content-Disposition' => 'attachment; filename="appointment-'.$a->reference.'.ics"']);
    }

    private function find(string $reference): Appointment
    {
        return Appointment::query()->with(['client', 'service.category', 'staff'])->where('reference', strtoupper($reference))->firstOrFail();
    }
}
