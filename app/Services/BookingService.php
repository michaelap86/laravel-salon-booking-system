<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Mail\BookingCancelled;
use App\Mail\BookingConfirmed;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\PromoCode;
use App\Models\Service;
use App\Models\User;
use App\Services\Payments\PaymentGateway;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(private Availability $availability, private PaymentGateway $gateway) {}

    /** Price and deposit for a service, with an optional promo code applied. */
    public function quote(Service $service, ?string $promoCode = null): array
    {
        $promo = PromoCode::lookup($promoCode);
        $price = (int) round($service->price * (100 - ($promo?->percent ?? 0)) / 100);

        return [
            'price' => $price,
            'discount' => $service->price - $price,
            'deposit' => $service->depositFor($price),
            'promo' => $promo?->code,
            'percent' => $promo?->percent ?? 0,
        ];
    }

    /**
     * Create a booking from the public form (or the admin "new appointment" form).
     *
     * @param  array{service_id:int, staff_id:?int, date:string, time:string, name:string, email:string, phone:?string, notes:?string, promo:?string, card:?array}  $data
     */
    public function book(array $data, ?User $user = null, string $source = 'web', bool $takeDeposit = true): Appointment
    {
        $service = Service::query()->active()->findOrFail($data['service_id']);
        $start = Carbon::parse($data['date'].' '.$data['time']);
        $staffId = ! empty($data['staff_id']) ? (int) $data['staff_id'] : null;
        $quote = $this->quote($service, $data['promo'] ?? null);

        $appointment = DB::transaction(function () use ($data, $user, $source, $takeDeposit, $service, $start, $staffId, $quote) {
            // Re-check inside the transaction: someone else may have taken the slot while this client was typing
            $free = $this->availability->freeStaff($service, $staffId, $start);
            if (! $free) {
                throw ValidationException::withMessages(['time' => 'Sorry, that time was just taken. Please pick another.']);
            }
            $chosen = $staffId ?: $this->availability->leastBusy($free, $start);

            $paid = false;
            if ($takeDeposit && $quote['deposit'] > 0) {
                $card = $data['card'] ?? [];
                $result = $this->gateway->charge($quote['deposit'], $card['number'] ?? '', $card['expiry'] ?? '', $card['cvc'] ?? '', 'Deposit for '.$service->name);
                if (! $result['ok']) {
                    throw ValidationException::withMessages(['card' => $result['message']]);
                }
                $paid = true;
            }

            $client = $this->client($data, $user);

            return Appointment::query()->create([
                'client_id' => $client->id,
                'staff_id' => $chosen,
                'service_id' => $service->id,
                'start_at' => $start,
                'end_at' => $start->copy()->addMinutes($service->duration),
                'status' => AppointmentStatus::Confirmed,
                'price' => $quote['price'],
                'deposit' => $quote['deposit'],
                'deposit_paid' => $paid,
                'promo_code' => $quote['promo'],
                'source' => $source,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        if ($quote['promo']) {
            PromoCode::query()->where('code', $quote['promo'])->increment('uses');
        }
        $appointment->load(['client', 'service', 'staff']);
        Activity::log('booking', $appointment->reference, $appointment->client->name.' booked '.$service->name, route('admin.appointments.show', $appointment));
        Mail::to($appointment->client->email)->send(new BookingConfirmed($appointment));

        return $appointment;
    }

    public function cancel(Appointment $appointment, string $reason = 'Cancelled by client'): Appointment
    {
        $appointment->update(['status' => AppointmentStatus::Cancelled, 'cancel_reason' => $reason]);
        Activity::log('cancel', $appointment->reference, $appointment->client->name.' cancelled '.$appointment->service->name, route('admin.appointments.show', $appointment));
        Mail::to($appointment->client->email)->send(new BookingCancelled($appointment));

        return $appointment;
    }

    /** Move an appointment to a new time (and optionally another stylist). */
    public function reschedule(Appointment $appointment, Carbon $start, ?int $staffId = null): Appointment
    {
        $service = $appointment->service;
        $target = $staffId ?: $appointment->staff_id;

        return DB::transaction(function () use ($appointment, $start, $target, $service) {
            if (! in_array($target, $this->availability->freeStaff($service, $target, $start, $appointment->id), true)) {
                throw ValidationException::withMessages(['time' => 'That time is not available any more. Please choose another.']);
            }
            $appointment->update(['staff_id' => $target, 'start_at' => $start, 'end_at' => $start->copy()->addMinutes($service->duration), 'status' => AppointmentStatus::Confirmed]);
            Activity::log('reschedule', $appointment->reference, $appointment->client->name.' moved to '.$start->format('M j, g:i A'), route('admin.appointments.show', $appointment));

            return $appointment;
        });
    }

    private function client(array $data, ?User $user): Client
    {
        $client = $user ? Client::query()->where('user_id', $user->id)->first() : null;
        $client ??= Client::query()->where('email', $data['email'])->first();

        if (! $client) {
            return Client::query()->create([
                'user_id' => $user?->id, 'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null, 'source' => 'web',
            ]);
        }
        $client->fill(array_filter(['phone' => $client->phone ?: ($data['phone'] ?? null), 'user_id' => $client->user_id ?: $user?->id]))->save();

        return $client;
    }
}
