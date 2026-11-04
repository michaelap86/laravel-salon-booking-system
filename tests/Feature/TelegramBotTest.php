<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Message;
use App\Models\Review;
use App\Models\Service;
use App\Services\Availability;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Telegram\Types\User\User;
use SergiX44\Nutgram\Testing\FakeNutgram;
use Tests\TestCase;

class TelegramBotTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private FakeNutgram $bot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bot = app(Nutgram::class);
        $this->as(4242, 'Ana');
    }

    /** Every message in a test comes from the same person in the same private chat. */
    private function as(int $id, string $name): void
    {
        $this->bot->setCommonUser(User::make(id: $id, is_bot: false, first_name: $name));
        $this->bot->setCommonChat(Chat::make(id: $id, type: 'private'));
    }

    /** The last call the bot made to Telegram: method name, text and button data. */
    private function last(): array
    {
        $history = $this->bot->getRequestHistory();
        $request = end($history)['request'];
        $data = FakeNutgram::getActualData($request);
        $markup = $data['reply_markup'] ?? null;
        $markup = is_string($markup) ? json_decode($markup, true) : ($markup ?? []);
        $buttons = collect($markup['inline_keyboard'] ?? [])->flatten(1);

        return [
            'method' => basename($request->getUri()->getPath()),
            'text' => $data['text'] ?? ($data['caption'] ?? ''),
            'buttons' => $buttons->pluck('callback_data')->filter()->values()->all(),
            'labels' => $buttons->pluck('text')->all(),
        ];
    }

    private function tap(string $data): array
    {
        $this->bot->hearCallbackQueryData($data)->reply();

        return $this->last();
    }

    private function say(string $text): array
    {
        $this->bot->hearText($text)->reply();

        return $this->last();
    }

    /** Appointments created through the bot by the test user (the demo data also contains some from Telegram). */
    private function mine(): Builder
    {
        return Appointment::query()->whereHas('client', fn ($q) => $q->where('telegram_id', 4242));
    }

    /** Walks the whole booking dialog up to the summary screen. */
    private function chooseSlot(string $slug = 'gel-manicure', int $staff = 0): array
    {
        $service = Service::query()->where('slug', $slug)->firstOrFail();
        $day = array_key_first(app(Availability::class)->openDates($service, $staff ?: null, today(), today()->addDays(30)));
        $slot = app(Availability::class)->slots($service, $staff ?: null, Carbon::parse($day))[0];

        $this->tap('bk:cats');
        $this->tap('bk:c:'.$service->service_category_id);
        $this->tap('bk:s:'.$service->id);
        $this->tap('bk:t:'.$staff);
        $this->tap('bk:d:'.$day);

        return [$service, $day, $slot, $this->tap('bk:h:'.str_replace(':', '', $slot['time']))];
    }

    private function book(string $slug = 'gel-manicure'): Appointment
    {
        $this->chooseSlot($slug);
        $this->tap('bk:pay');
        $this->tap('bk:go:ok');

        return $this->mine()->latest('id')->firstOrFail();
    }

    public function test_start_shows_the_menu(): void
    {
        $reply = $this->say('/start');

        $this->assertStringContainsString('Welcome to Lumière Studio', $reply['text']);
        $this->assertContains('bk:cats', $reply['buttons']);
        $this->assertContains('my:up', $reply['buttons']);
    }

    public function test_full_booking_with_the_demo_deposit(): void
    {
        [$service, , , $summary] = $this->chooseSlot();

        $this->assertStringContainsString('Review your booking', $summary['text']);
        $this->assertStringContainsString('Deposit due now', $summary['text']);

        $checkout = $this->tap('bk:pay');
        $this->assertStringContainsString('test mode', $checkout['text']);
        $this->assertSame(0, $this->mine()->count());

        $done = $this->tap('bk:go:ok');
        $this->assertStringContainsString("You're booked", $done['text']);

        $appointment = $this->mine()->firstOrFail();
        $this->assertSame($service->id, $appointment->service_id);
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
        $this->assertTrue($appointment->deposit_paid);
        $this->assertNotNull($appointment->client->telegram_id);
        $this->assertSame('telegram', $appointment->client->source);
    }

    public function test_a_declined_card_books_nothing(): void
    {
        $this->chooseSlot();
        $this->tap('bk:pay');
        $failed = $this->tap('bk:go:bad');

        $this->assertStringContainsString('Payment failed', $failed['text']);
        $this->assertSame(0, $this->mine()->count());
    }

    public function test_promo_code_changes_the_price(): void
    {
        $this->chooseSlot();
        $ask = $this->tap('bk:promo');
        $this->assertStringContainsString('Promo code', $ask['text']);

        $bad = $this->say('NOPE');
        $this->assertStringContainsString('not valid', $bad['text']);

        $ok = $this->say('welcome10');
        $this->assertStringContainsString('WELCOME10', $ok['text']);
        $this->assertStringContainsString('Total', $ok['text']);

        $this->tap('bk:pay');
        $this->tap('bk:go:ok');
        $this->assertSame('WELCOME10', $this->mine()->firstOrFail()->promo_code);
    }

    public function test_the_same_slot_cannot_be_taken_twice(): void
    {
        $first = $this->book();
        $start = $first->start_at;

        // Same stylist, same time, picked from an old screen
        $this->tap('bk:cats');
        $this->tap('bk:c:'.$first->service->service_category_id);
        $this->tap('bk:s:'.$first->service_id);
        $this->tap('bk:t:'.$first->staff_id);
        $this->tap('bk:d:'.$start->toDateString());
        $times = $this->last();
        $this->assertNotContains('bk:h:'.$start->format('Hi'), $times['buttons']);
    }

    public function test_my_bookings_and_cancel(): void
    {
        $appointment = $this->book();

        $list = $this->tap('my:up');
        $this->assertContains('my:a:'.$appointment->id, $list['buttons']);

        $detail = $this->tap('my:a:'.$appointment->id);
        $this->assertContains('my:cx:'.$appointment->id, $detail['buttons']);

        $ask = $this->tap('my:cx:'.$appointment->id);
        $this->assertStringContainsString('Cancel this appointment', $ask['text']);

        $this->tap('my:cxy:'.$appointment->id);
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->refresh()->status);
    }

    public function test_nobody_can_open_or_cancel_someone_elses_booking(): void
    {
        $appointment = $this->book();

        $this->as(777000, 'Intruder');
        $reply = $this->tap('my:cxy:'.$appointment->id);

        $this->assertStringContainsString('refreshed', $reply['text']);
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->refresh()->status);
    }

    public function test_reschedule_moves_the_appointment(): void
    {
        $appointment = $this->book();
        $old = $appointment->start_at->copy();

        $this->tap('my:rs:'.$appointment->id);
        $slots = collect();
        foreach (array_keys(app(Availability::class)->openDates($appointment->service, $appointment->staff_id, today(), today()->addDays(30))) as $day) {
            $slots = collect(app(Availability::class)->slots($appointment->service, $appointment->staff_id, Carbon::parse($day), $appointment->id))
                ->filter(fn ($s) => $day.' '.$s['time'] !== $old->format('Y-m-d H:i'))->values();
            if ($slots->isNotEmpty()) {
                break;
            }
        }
        $this->tap('bk:d:'.$day);
        $done = $this->tap('bk:h:'.str_replace(':', '', $slots->first()['time']));

        $this->assertStringContainsString('Moved', $done['text']);
        $this->assertSame($day.' '.$slots->first()['time'], $appointment->refresh()->start_at->format('Y-m-d H:i'));
    }

    public function test_sample_history_unlocks_rewards_and_reviews(): void
    {
        $empty = $this->tap('rw');
        $this->assertStringContainsString('first visit', $empty['text']);

        $past = $this->tap('demo');
        $this->assertStringContainsString('Past visits', $past['text']);
        $client = Client::query()->whereNotNull('telegram_id')->firstOrFail();
        $this->assertSame(3, $client->appointments()->where('status', 'completed')->count());

        $rewards = $this->tap('rw');
        $this->assertStringContainsString('points', $rewards['text']);

        $visit = $client->appointments()->first();
        $this->tap('rv:'.$visit->id);
        $this->tap('rv:'.$visit->id.':5');
        $short = $this->say('nice');
        $this->assertStringContainsString('At least 10', $short['text']);
        $this->say('Loved it, the colour is perfect!');

        $review = Review::query()->where('appointment_id', $visit->id)->firstOrFail();
        $this->assertSame(5, $review->rating);
        $this->assertTrue($review->published);
    }

    public function test_message_to_the_team_lands_in_the_inbox(): void
    {
        $this->tap('msg');
        $this->say('Do you do bridal hair trials?');

        $this->assertSame('Do you do bridal hair trials?', Message::query()->latest('id')->firstOrFail()->body);
    }

    public function test_reminders_are_sent_once(): void
    {
        $appointment = $this->book();
        $appointment->update(['start_at' => now()->addHours(5), 'end_at' => now()->addHours(6)]);

        $this->artisan('salon:bot-reminders')->expectsOutput('1 reminders sent.')->assertSuccessful();
        $this->assertNotNull($appointment->refresh()->reminded_at);
        $this->assertStringContainsString('See you soon', $this->last()['text']);

        $this->artisan('salon:bot-reminders')->expectsOutput('0 reminders sent.')->assertSuccessful();
    }

    public function test_calendar_file_is_sent(): void
    {
        $appointment = $this->book();
        $file = $this->tap('my:ics:'.$appointment->id);

        $this->assertSame('sendDocument', $file['method']);
    }

    public function test_the_webhook_route_is_outside_the_session_and_csrf_stack(): void
    {
        $route = app('router')->getRoutes()->getByName('telegram.webhook');

        $this->assertNotNull($route);
        $this->assertSame(['POST'], $route->methods());
        $this->assertNotContains('web', $route->gatherMiddleware());
    }
}
