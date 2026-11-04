<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Services\Availability;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SalonTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $email): User
    {
        return User::query()->where('email', $email.'@lumiere.demo')->firstOrFail();
    }

    /** First bookable day and time for a service. */
    private function firstSlot(string $slug): array
    {
        $service = Service::query()->where('slug', $slug)->firstOrFail();
        $dates = app(Availability::class)->openDates($service, null, today(), today()->addDays(30));
        $day = array_key_first($dates);
        $slot = app(Availability::class)->slots($service, null, Carbon::parse($day))[0];

        return [$service, $day, $slot];
    }

    private function bookingPayload(Service $service, string $day, string $time, array $override = []): array
    {
        return [...[
            'service_id' => $service->id, 'staff_id' => null, 'date' => $day, 'time' => $time, 'name' => 'Test Visitor',
            'email' => 'visitor@example.com', 'phone' => '555 0100', 'notes' => null, 'promo' => null,
            'card' => ['number' => '4242 4242 4242 4242', 'expiry' => '12/40', 'cvc' => '123'],
        ], ...$override];
    }

    public function test_public_pages_render(): void
    {
        foreach (['/', '/services', '/services/balayage', '/team', '/team/1', '/contact', '/book', '/login', '/register'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_guests_cannot_reach_admin_or_account(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/account')->assertRedirect('/login');
    }

    public function test_demo_accounts_sign_in_and_land_in_the_right_place(): void
    {
        $this->post('/login', ['email' => 'admin@lumiere.demo', 'password' => 'demo1234'])->assertRedirect('/admin');
        auth()->logout();
        $this->post('/login', ['email' => 'client@lumiere.demo', 'password' => 'demo1234'])->assertRedirect('/account');
        auth()->logout();
        $this->post('/login', ['email' => 'admin@lumiere.demo', 'password' => 'wrong'])->assertSessionHasErrors('email');
    }

    public function test_online_booking_with_deposit_and_promo(): void
    {
        [$service, $day, $slot] = $this->firstSlot('gel-manicure');

        $response = $this->postJson('/book', $this->bookingPayload($service, $day, $slot['time'], ['promo' => 'WELCOME10']))->assertOk();

        $appointment = Appointment::query()->where('reference', basename(parse_url($response->json('url'), PHP_URL_PATH)))->firstOrFail();
        $this->assertSame((int) round($service->price * 0.9), $appointment->price);
        $this->assertTrue($appointment->deposit_paid);
        $this->assertSame('confirmed', $appointment->status->value);
        $this->get(route('booking.show', $appointment->reference))->assertOk()->assertSee($appointment->reference);
        $this->assertDatabaseHas('sent_emails', ['to' => 'visitor@example.com']);
    }

    public function test_declined_card_does_not_create_a_booking(): void
    {
        [$service, $day, $slot] = $this->firstSlot('gel-manicure');
        $before = Appointment::query()->count();

        $this->postJson('/book', $this->bookingPayload($service, $day, $slot['time'], ['card' => ['number' => '4000 0000 0000 0002', 'expiry' => '12/40', 'cvc' => '123']]))
            ->assertStatus(422)->assertJsonValidationErrors('card');

        $this->assertSame($before, Appointment::query()->count());
    }

    public function test_the_same_slot_cannot_be_booked_twice_by_the_same_stylist(): void
    {
        [$service, $day, $slot] = $this->firstSlot('gel-manicure');
        $staffId = $slot['staff'][0];

        $this->postJson('/book', $this->bookingPayload($service, $day, $slot['time'], ['staff_id' => $staffId]))->assertOk();
        $this->postJson('/book', $this->bookingPayload($service, $day, $slot['time'], ['staff_id' => $staffId, 'email' => 'other@example.com']))
            ->assertStatus(422)->assertJsonValidationErrors('time');
    }

    public function test_availability_respects_closed_days_and_time_off(): void
    {
        $service = Service::query()->where('slug', 'gel-manicure')->firstOrFail();
        $availability = app(Availability::class);

        $this->assertSame([], $availability->slots($service, null, Carbon::parse('next monday')), 'Monday is closed');
        $this->assertFalse($availability->isOpen(today()->addDays(19)), 'team retreat closure');

        $chloe = \App\Models\Staff::query()->where('name', 'Chloe Dubois')->firstOrFail();
        $this->assertSame([], $availability->slots($service, $chloe->id, today()->addDays(7)), 'Chloe is on vacation');
    }

    public function test_stylist_only_sees_their_own_appointments_and_no_admin_pages(): void
    {
        $stylist = $this->user('stylist');
        $this->actingAs($stylist);

        $response = $this->get('/admin/appointments?from='.today()->subDays(30)->toDateString())->assertOk();
        foreach ($response->viewData('appointments') as $a) {
            $this->assertSame($stylist->staff->id, $a->staff_id);
        }
        $other = Appointment::query()->where('staff_id', '!=', $stylist->staff->id)->firstOrFail();
        $this->get('/admin/appointments/'.$other->id)->assertNotFound();
        $this->get('/admin/services')->assertForbidden();
        $this->get('/admin/reports')->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
    }

    public function test_manager_has_business_pages_but_not_users_or_settings(): void
    {
        $this->actingAs($this->user('manager'));

        foreach (['/admin', '/admin/calendar', '/admin/calendar/day', '/admin/clients', '/admin/services', '/admin/team', '/admin/reports', '/admin/reviews'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/settings')->assertForbidden();
        $this->get('/admin/activity')->assertForbidden();
    }

    public function test_admin_can_open_every_admin_page(): void
    {
        $this->actingAs($this->user('admin'));
        $appointment = Appointment::query()->firstOrFail();

        foreach (['/admin', '/admin/calendar', '/admin/calendar/day', '/admin/appointments', '/admin/appointments/create', '/admin/appointments/'.$appointment->id,
            '/admin/clients', '/admin/clients/'.$appointment->client_id, '/admin/services', '/admin/services/create', '/admin/team', '/admin/reviews',
            '/admin/messages', '/admin/outbox', '/admin/reports', '/admin/settings', '/admin/activity', '/admin/users'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_customer_cannot_use_the_admin_area_and_sees_only_their_own_appointments(): void
    {
        $emma = $this->user('client');
        $this->actingAs($emma);

        $this->get('/admin')->assertForbidden();
        $this->get('/account')->assertOk();
        $mine = Appointment::query()->whereHas('client', fn ($c) => $c->where('user_id', $emma->id))->firstOrFail();
        $this->get('/account/appointments/'.$mine->id)->assertOk();
        $others = Appointment::query()->whereHas('client', fn ($c) => $c->whereNull('user_id'))->firstOrFail();
        $this->get('/account/appointments/'.$others->id)->assertNotFound();
    }

    public function test_manager_can_reschedule_but_not_into_a_taken_slot(): void
    {
        $this->actingAs($this->user('manager'));
        [$service, $day, $slot] = $this->firstSlot('gel-manicure');
        $staffId = $slot['staff'][0];
        $booked = app(BookingService::class)->book($this->bookingPayload($service, $day, $slot['time'], ['staff_id' => $staffId]));

        $next = app(Availability::class)->slots($service, $staffId, Carbon::parse($day))[3] ?? null;
        $this->assertNotNull($next);
        $this->postJson("/admin/calendar/{$booked->id}/move", ['start' => $day.' '.$next['time']])->assertOk();
        $this->assertSame($next['time'], $booked->fresh()->start_at->format('H:i'));

        $second = app(BookingService::class)->book($this->bookingPayload($service, $day, $slot['time'], ['staff_id' => $staffId, 'email' => 'x@example.com']));
        $this->postJson("/admin/calendar/{$second->id}/move", ['start' => $day.' '.$next['time']])->assertStatus(422);
    }

    public function test_booking_service_rejects_a_taken_slot(): void
    {
        [$service, $day, $slot] = $this->firstSlot('gel-manicure');
        $payload = $this->bookingPayload($service, $day, $slot['time'], ['staff_id' => $slot['staff'][0]]);
        app(BookingService::class)->book($payload);

        $this->expectException(ValidationException::class);
        app(BookingService::class)->book($payload);
    }

    public function test_client_can_cancel_and_only_admin_can_change_roles(): void
    {
        $emma = $this->user('client');
        $upcoming = Appointment::query()->upcoming()->whereHas('client', fn ($c) => $c->where('user_id', $emma->id))->firstOrFail();
        $this->actingAs($emma)->post("/account/appointments/{$upcoming->id}/cancel")->assertRedirect();
        $this->assertSame('cancelled', $upcoming->fresh()->status->value);

        $visitor = User::factory()->create(['role' => 'customer', 'is_demo' => false]);
        $this->actingAs($this->user('manager'))->post("/admin/users/{$visitor->id}/role", ['role' => 'admin'])->assertForbidden();
        $this->actingAs($this->user('admin'))->post("/admin/users/{$visitor->id}/role", ['role' => 'manager'])->assertRedirect();
        $this->assertSame('manager', $visitor->fresh()->role->value);
        $this->actingAs($this->user('admin'))->post('/admin/users/'.$this->user('manager')->id.'/role', ['role' => 'viewer'])->assertSessionHasErrors();
    }

    public function test_demo_data_refresh_is_idempotent_and_keeps_demo_accounts(): void
    {
        $adminId = $this->user('admin')->id;
        $visitor = User::factory()->create(['role' => 'customer', 'is_demo' => false]);

        $this->seed(\Database\Seeders\DemoSeeder::class);

        $this->assertSame($adminId, $this->user('admin')->id, 'demo accounts survive so visitors stay signed in');
        $this->assertNull(User::query()->find($visitor->id), 'accounts created by visitors are removed');
        $this->assertSame(today()->toDateString(), \App\Models\Setting::get('demo_seeded_on'));
    }
}
