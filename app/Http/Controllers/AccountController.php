<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Review;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(Request $request): View
    {
        $client = $this->client($request);
        $appointments = $client->appointments()->with(['service.category', 'staff', 'review']);

        return view('account.dashboard', [
            'client' => $client,
            'tier' => $client->tier(),
            'upcoming' => (clone $appointments)->upcoming()->orderBy('start_at')->limit(4)->get(),
            'recent' => (clone $appointments)->where('status', AppointmentStatus::Completed->value)->latest('start_at')->limit(5)->get(),
            'toReview' => (clone $appointments)->where('status', AppointmentStatus::Completed->value)->doesntHave('review')->where('start_at', '>=', now()->subDays(30))->latest('start_at')->first(),
            'visits' => $client->appointments()->where('status', AppointmentStatus::Completed->value)->count(),
            'spent' => $client->lifetimeSpend(),
        ]);
    }

    public function appointments(Request $request): View
    {
        $client = $this->client($request);
        $tab = $request->query('tab', 'upcoming');
        $query = $client->appointments()->with(['service.category', 'staff', 'review']);
        $tab === 'past' ? $query->where('start_at', '<', now())->orderByDesc('start_at') : $query->where('start_at', '>=', now())->orderBy('start_at');

        return view('account.appointments', ['tab' => $tab, 'appointments' => $query->paginate(10)->withQueryString()]);
    }

    public function show(Request $request, Appointment $appointment): View
    {
        $this->authorizeOwner($request, $appointment);
        $appointment->load(['service.category', 'staff', 'review', 'client']);

        return view('account.appointment', ['appointment' => $appointment]);
    }

    public function cancel(Request $request, Appointment $appointment, BookingService $bookings): RedirectResponse
    {
        $this->authorizeOwner($request, $appointment);
        abort_unless($appointment->isCancellable(), 403);
        $bookings->cancel($appointment->load(['client', 'service', 'staff']));

        return redirect()->route('account.appointments')->with('success', 'Your appointment was cancelled.');
    }

    public function reschedule(Request $request, Appointment $appointment, BookingService $bookings): RedirectResponse
    {
        $this->authorizeOwner($request, $appointment);
        abort_unless($appointment->isCancellable(), 403);
        $data = $request->validate(['date' => 'required|date_format:Y-m-d|after_or_equal:today', 'time' => ['required', 'regex:/^\d{2}:\d{2}$/']]);

        $bookings->reschedule($appointment->load(['client', 'service', 'staff']), Carbon::parse($data['date'].' '.$data['time']));

        return redirect()->route('account.appointments.show', $appointment)->with('success', 'Done! Your appointment has been moved.');
    }

    public function review(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorizeOwner($request, $appointment);
        abort_unless($appointment->status === AppointmentStatus::Completed && ! $appointment->review, 403);
        $data = $request->validate(['rating' => 'required|integer|between:1,5', 'comment' => 'required|string|min:10|max:1000']);

        Review::query()->create([
            'appointment_id' => $appointment->id, 'client_id' => $appointment->client_id, 'staff_id' => $appointment->staff_id,
            'service_id' => $appointment->service_id, 'rating' => $data['rating'], 'comment' => $data['comment'], 'published' => $data['rating'] >= 3,
        ]);
        Activity::log('review', $appointment->reference, $appointment->client->name.' left a '.$data['rating'].'-star review', route('admin.reviews.index'));

        return back()->with('success', 'Thank you for your review!');
    }

    public function profile(Request $request): View
    {
        return view('account.profile', ['client' => $this->client($request)]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->is_demo, 403, 'The shared demo account cannot be edited.');
        $data = $request->validate([
            'name' => 'required|string|max:120', 'phone' => 'nullable|string|max:30',
            'birthday' => 'nullable|date|before:today', 'marketing_opt_in' => 'nullable|boolean',
        ]);
        $user->update(['name' => $data['name'], 'phone' => $data['phone'] ?? null]);
        $this->client($request)->update(['name' => $data['name'], 'phone' => $data['phone'] ?? null, 'birthday' => $data['birthday'] ?? null, 'marketing_opt_in' => $request->boolean('marketing_opt_in')]);

        return back()->with('success', 'Profile saved.');
    }

    private function client(Request $request): Client
    {
        $user = $request->user();

        return $user->client ?? Client::query()->create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'phone' => $user->phone, 'source' => 'web']);
    }

    private function authorizeOwner(Request $request, Appointment $appointment): void
    {
        abort_unless($appointment->client->user_id === $request->user()->id, 404);
    }
}
