<?php

namespace App\Telegram;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\PromoCode;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\Staff;
use App\Services\Availability;
use App\Services\BookingService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use SergiX44\Nutgram\Nutgram;

/**
 * Booking by tapping buttons: category, service, stylist, day, time, deposit.
 * It uses the same Availability and BookingService as the website, so the rules are identical.
 */
class BookingFlow
{
    /** Test cards of the demo payment gateway. */
    private const CARD_OK = '4242424242424242';

    private const CARD_DECLINED = '4000000000000002';

    private const MAX_UPCOMING = 5;

    public function __construct(private Availability $availability, private BookingService $bookings, private AccountFlow $account) {}

    public function categories(Nutgram $bot): void
    {
        $rows = ServiceCategory::query()->whereHas('services', fn ($q) => $q->active())->orderBy('sort')->get()
            ->map(fn ($c) => [[Ui::icon($c->name).' '.$c->name, 'bk:c:'.$c->id]])->all();
        $rows[] = [['« Menu', 'menu']];

        Ui::show($bot, "💅 <b>What would you like to book?</b>\nPick a category.", Kb::rows($rows));
    }

    public function services(Nutgram $bot, int $categoryId): void
    {
        $category = ServiceCategory::query()->findOrFail($categoryId);
        $rows = $category->services()->active()->get()
            ->map(fn (Service $s) => [[$s->name.' · '.money($s->price).' · '.duration($s->duration), 'bk:s:'.$s->id]])->all();
        $rows[] = [['« Categories', 'bk:cats']];

        $note = $category->ladies_pricing ? "\n<i>Demo prices, made up by the developer.</i>" : '';
        Ui::show($bot, Ui::icon($category->name).' <b>'.Ui::e($category->name).'</b>'.($category->blurb ? "\n".Ui::e($category->blurb) : '').$note, Kb::rows($rows));
    }

    public function service(Nutgram $bot, int $serviceId): void
    {
        $service = Service::query()->active()->findOrFail($serviceId);
        State::for($bot)->start(['service_id' => $service->id]);

        $rows = [[['✨ Anyone available', 'bk:t:0']]];
        foreach ($this->availability->eligibleStaff($service) as $staff) {
            $rows[] = [['👩‍🎨 '.$staff->name.' · '.$staff->title, 'bk:t:'.$staff->id]];
        }
        $rows[] = [['« Back', 'bk:c:'.$service->service_category_id]];

        $text = '<b>'.Ui::e($service->name).'</b>'."\n".Ui::e($service->description)."\n\n"
            .'💵 '.money($service->price).' · ⏱ '.duration($service->duration)
            .($service->deposit_required ? ' · deposit '.Setting::int('deposit_percent').'%' : '')
            ."\n\nWho should look after you?";
        Ui::show($bot, $text, Kb::rows($rows));
    }

    public function stylist(Nutgram $bot, int $staffId): void
    {
        State::for($bot)->put(['staff_id' => $staffId ?: null]);
        $this->calendar($bot);
    }

    /** A month grid where only days with openings can be tapped. */
    public function calendar(Nutgram $bot, ?string $month = null): void
    {
        $draft = State::for($bot)->draft();
        [$service, $staffId] = $this->target($draft);

        $first = $month ? Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth() : today()->startOfMonth();
        if ($first->lt(today()->startOfMonth())) {
            $first = today()->startOfMonth();
        }
        $open = $this->availability->openDates($service, $staffId, $first->copy()->max(today()), $first->copy()->endOfMonth()->startOfDay());

        $hasPrev = $first->gt(today()->startOfMonth());
        $hasNext = $first->copy()->addMonth()->lte(today()->addDays(Setting::int('booking_window_days')));
        $rows = [[
            [$hasPrev ? '◀' : '·', $hasPrev ? 'bk:m:'.$first->copy()->subMonth()->format('Y-m') : 'noop'],
            [$first->format('F Y'), 'noop'],
            [$hasNext ? '▶' : '·', $hasNext ? 'bk:m:'.$first->copy()->addMonth()->format('Y-m') : 'noop'],
        ]];
        $rows[] = array_map(fn ($l) => [$l, 'noop'], ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']);

        $cells = array_fill(0, $first->dayOfWeek, ["\u{2800}", 'noop']);
        for ($day = $first->copy(); $day->month === $first->month; $day->addDay()) {
            $cells[] = isset($open[$day->toDateString()]) ? [(string) $day->day, 'bk:d:'.$day->toDateString()] : ['·', 'noop'];
        }
        foreach (array_chunk($cells, 7) as $week) {
            $rows[] = array_pad($week, 7, ["\u{2800}", 'noop']);
        }
        $rows[] = [['✖ Cancel', 'bk:x']];

        $with = $staffId ? ' with '.Staff::query()->find($staffId)?->name : '';
        $text = (isset($draft['reschedule_id']) ? '🔄 <b>Pick a new day</b>' : '🗓 <b>Pick a day</b>')."\n".Ui::e($service->name.$with)
            ."\nDays with a dot have no openings.".($open ? '' : "\n\n<i>Nothing free in ".$first->format('F').'. Try the next month.</i>');
        Ui::show($bot, $text, Kb::rows($rows));
    }

    public function times(Nutgram $bot, string $date): void
    {
        $state = State::for($bot);
        $state->put(['date' => $date]);
        [$service, $staffId] = $this->target($state->draft());

        $slots = $this->availability->slots($service, $staffId, Carbon::parse($date));
        if (! $slots) {
            Ui::show($bot, '😕 No times left on '.Carbon::parse($date)->format('l, M j').'.', Kb::back('bk:m:'.substr($date, 0, 7), '« Pick another day'));

            return;
        }
        $buttons = array_map(fn ($s) => [$s['label'], 'bk:h:'.str_replace(':', '', $s['time'])], $slots);
        $rows = array_chunk($buttons, 3);
        $rows[] = [['« Other day', 'bk:m:'.substr($date, 0, 7)], ['✖ Cancel', 'bk:x']];

        Ui::show($bot, '⏰ <b>'.Carbon::parse($date)->format('l, M j')."</b>\nWhat time suits you?", Kb::rows($rows));
    }

    public function time(Nutgram $bot, string $hhmm): void
    {
        $state = State::for($bot);
        $state->put(['time' => substr($hhmm, 0, 2).':'.substr($hhmm, 2, 2)]);

        $draft = $state->draft();
        if (isset($draft['reschedule_id'])) {
            $this->reschedule($bot, $draft);

            return;
        }
        $this->summary($bot);
    }

    public function summary(Nutgram $bot, ?string $notice = null): void
    {
        $draft = State::for($bot)->draft();
        [$service, $staffId] = $this->target($draft);
        $quote = $this->bookings->quote($service, $draft['promo'] ?? null);
        $start = Carbon::parse($draft['date'].' '.$draft['time']);

        $lines = [
            '🧾 <b>Review your booking</b>',
            '',
            '<b>'.Ui::e($service->name).'</b> · '.duration($service->duration),
            '🗓 '.Ui::when($start),
            '👩‍🎨 '.($staffId ? Ui::e(Staff::query()->find($staffId)?->name) : 'First available stylist'),
            '',
            'Price: '.money($service->price, true),
        ];
        if ($quote['promo']) {
            $lines[] = '🏷 '.$quote['promo'].' (-'.$quote['percent'].'%): -'.money($quote['discount'], true);
            $lines[] = 'Total: <b>'.money($quote['price'], true).'</b>';
        }
        $lines[] = $quote['deposit'] > 0 ? 'Deposit due now: <b>'.money($quote['deposit'], true).'</b>' : 'No deposit needed.';
        if ($notice) {
            $lines = [$notice, '', ...$lines];
        }

        Ui::show($bot, implode("\n", $lines), Kb::rows([
            [[$quote['deposit'] > 0 ? '💳 Pay '.money($quote['deposit'], true).' deposit' : '✅ Confirm booking', 'bk:pay']],
            [['🏷 Promo code', 'bk:promo'], ['⏰ Change time', 'bk:d:'.$draft['date']]],
            [['✖ Cancel', 'bk:x']],
        ]));
    }

    public function askPromo(Nutgram $bot): void
    {
        State::for($bot)->expect('promo');
        Ui::show($bot, "🏷 <b>Promo code</b>\nType your code in the chat. Try <code>WELCOME10</code>, <code>GLOW15</code> or <code>FRIENDS</code>.", Kb::back('bk:sum', '« Back'));
    }

    public function applyPromo(Nutgram $bot, string $text): void
    {
        $state = State::for($bot);
        $promo = PromoCode::lookup($text);
        if (! $promo) {
            $bot->sendMessage('That code is not valid. Check the spelling, or go back.', reply_markup: Kb::back('bk:sum', '« Back'));

            return;
        }
        $state->stopAwaiting();
        $state->put(['promo' => $promo->code]);
        $this->summary($bot, '✅ Code <b>'.$promo->code.'</b> applied.');
    }

    /** The demo checkout: pick a test card. No money moves. */
    public function checkout(Nutgram $bot): void
    {
        $draft = State::for($bot)->draft();
        [$service] = $this->target($draft);
        $quote = $this->bookings->quote($service, $draft['promo'] ?? null);

        if ($quote['deposit'] === 0) {
            $this->book($bot, self::CARD_OK);

            return;
        }
        Ui::show($bot, '💳 <b>Pay the deposit: '.money($quote['deposit'], true)."</b>\n\n"
            ."<i>This is a demo. The payment gateway is in test mode, so no real money moves and no card details are asked for.</i>\n\nChoose a test card:",
            Kb::rows([
                [['✅ Visa •••• 4242 (succeeds)', 'bk:go:ok']],
                [['🚫 Visa •••• 0002 (declined)', 'bk:go:bad']],
                [['« Back', 'bk:sum']],
            ]));
    }

    public function pay(Nutgram $bot, string $card): void
    {
        $this->book($bot, $card === 'bad' ? self::CARD_DECLINED : self::CARD_OK);
    }

    public function cancel(Nutgram $bot): void
    {
        State::for($bot)->forget();
        Ui::show($bot, 'No problem, nothing was booked. What next?', Kb::menu());
    }

    /** Start a new booking for the service and stylist of an earlier appointment. */
    public function rebook(Nutgram $bot, Appointment $previous): void
    {
        State::for($bot)->start(['service_id' => $previous->service_id, 'staff_id' => $previous->staff_id]);
        $this->calendar($bot);
    }

    /** Move an existing appointment: the draft carries its id, and the same calendar is reused. */
    public function startReschedule(Nutgram $bot, Appointment $appointment): void
    {
        State::for($bot)->start(['reschedule_id' => $appointment->id, 'service_id' => $appointment->service_id, 'staff_id' => $appointment->staff_id]);
        $this->calendar($bot);
    }

    // ------------------------------------------------------------------ internals
    private function book(Nutgram $bot, string $card): void
    {
        $state = State::for($bot);
        $draft = $state->draft();
        [$service] = $this->target($draft);
        $tg = $bot->user();
        $client = Client::forTelegram($tg->id, trim($tg->first_name.' '.($tg->last_name ?? '')));

        if ($client->appointments()->upcoming()->where('source', 'telegram')->count() >= self::MAX_UPCOMING) {
            Ui::show($bot, '🙈 This demo allows '.self::MAX_UPCOMING.' upcoming bookings per person. Cancel one under My bookings to make room.', Kb::back('my:up', '📋 My bookings'));

            return;
        }

        try {
            $appointment = $this->bookings->book([
                'service_id' => $service->id, 'staff_id' => $draft['staff_id'] ?? null, 'date' => $draft['date'], 'time' => $draft['time'],
                'name' => $client->name, 'email' => $client->email, 'phone' => null, 'notes' => 'Booked in Telegram', 'promo' => $draft['promo'] ?? null,
                'card' => ['number' => $card, 'expiry' => now()->addYears(2)->format('m/y'), 'cvc' => '123'],
            ], null, 'telegram', true);
        } catch (ValidationException $e) {
            $this->bookingFailed($bot, $e, $draft);

            return;
        }

        $state->forget();
        Notifier::newBooking($appointment);
        Ui::show($bot, "🎉 <b>You're booked!</b>\n\n".Ui::card($appointment)."\n\n".Ui::cancelPolicy($appointment)
            ."\n\nWe'll send you a reminder here before your visit.", Kb::rows([
                [['📅 Add to calendar', 'my:ics:'.$appointment->id], ['📋 My bookings', 'my:up']],
                [['💅 Book another', 'bk:cats'], ['« Menu', 'menu']],
            ]));
    }

    private function bookingFailed(Nutgram $bot, ValidationException $e, array $draft): void
    {
        $errors = $e->errors();
        if (isset($errors['card'])) {
            Ui::show($bot, "❌ <b>Payment failed</b>\n".Ui::e($errors['card'][0])."\n\nNothing was booked. Try another card.", Kb::rows([
                [['💳 Try again', 'bk:pay']], [['✖ Cancel', 'bk:x']],
            ]));

            return;
        }
        Ui::show($bot, '😕 '.Ui::e($errors['time'][0] ?? 'That time is no longer available.'), Kb::back('bk:d:'.($draft['date'] ?? today()->toDateString()), '⏰ Pick another time'));
    }

    private function reschedule(Nutgram $bot, array $draft): void
    {
        $appointment = $this->account->owned($bot, (int) $draft['reschedule_id']);
        try {
            $this->bookings->reschedule($appointment->load(['client', 'service', 'staff']), Carbon::parse($draft['date'].' '.$draft['time']));
        } catch (ValidationException $e) {
            Ui::show($bot, '😕 '.Ui::e($e->errors()['time'][0] ?? 'That time is not available.'), Kb::back('bk:d:'.$draft['date'], '⏰ Pick another time'));

            return;
        }
        State::for($bot)->forget();
        Ui::show($bot, "✅ <b>Moved!</b>\n\n".Ui::card($appointment->refresh()), Kb::rows([
            [['📅 Add to calendar', 'my:ics:'.$appointment->id], ['📋 My bookings', 'my:up']],
        ]));
    }

    /** @return array{0: Service, 1: ?int} */
    private function target(array $draft): array
    {
        $service = Service::query()->active()->findOrFail($draft['service_id'] ?? 0);

        return [$service, $draft['staff_id'] ?? null];
    }
}
