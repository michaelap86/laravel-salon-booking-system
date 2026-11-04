<?php

namespace App\Telegram;

use App\Enums\AppointmentStatus;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Review;
use App\Models\Service;
use App\Services\BookingService;
use Illuminate\Support\Carbon;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Internal\InputFile;

/** Everything a returning client does: see and change bookings, rewards and reviews. */
class AccountFlow
{
    public function __construct(private BookingService $bookings) {}

    public function client(Nutgram $bot): ?Client
    {
        return Client::query()->where('telegram_id', $bot->userId())->first();
    }

    /** An appointment of this Telegram user, or a 404 for anyone else's. */
    public function owned(Nutgram $bot, int $id): Appointment
    {
        return Appointment::query()->with(['service', 'staff', 'client', 'review'])
            ->whereHas('client', fn ($q) => $q->where('telegram_id', $bot->userId()))->findOrFail($id);
    }

    public function upcoming(Nutgram $bot): void
    {
        $appointments = $this->client($bot)?->appointments()->upcoming()->with(['service', 'staff'])->orderBy('start_at')->limit(8)->get() ?? collect();
        if ($appointments->isEmpty()) {
            Ui::show($bot, "📋 <b>My bookings</b>\nYou have no upcoming appointments yet.", Kb::rows([
                [['💅 Book an appointment', 'bk:cats']],
                [['🕘 Past visits', 'my:past'], ['« Menu', 'menu']],
            ]));

            return;
        }
        $rows = $appointments->map(fn (Appointment $a) => [[$a->start_at->format('M j · g:i A').' · '.$a->service->name, 'my:a:'.$a->id]])->all();
        $rows[] = [['🕘 Past visits', 'my:past'], ['💅 Book', 'bk:cats']];
        $rows[] = [['« Menu', 'menu']];
        Ui::show($bot, "📋 <b>Your upcoming appointments</b>\nTap one to see details, move or cancel it.", Kb::rows($rows));
    }

    public function past(Nutgram $bot): void
    {
        $appointments = $this->client($bot)?->appointments()->with(['service'])
            ->where(fn ($q) => $q->where('start_at', '<', now())->orWhereIn('status', [AppointmentStatus::Cancelled->value, AppointmentStatus::NoShow->value]))
            ->orderByDesc('start_at')->limit(8)->get() ?? collect();

        if ($appointments->isEmpty()) {
            Ui::show($bot, "🕘 <b>Past visits</b>\nNothing here yet. Want to see how it looks with some history?", Kb::rows([
                [['✨ Add sample past visits (demo)', 'demo']],
                [['« My bookings', 'my:up']],
            ]));

            return;
        }
        $rows = $appointments->map(fn (Appointment $a) => [[$this->statusIcon($a).' '.$a->start_at->format('M j').' · '.$a->service->name, 'my:a:'.$a->id]])->all();
        $rows[] = [['« My bookings', 'my:up']];
        Ui::show($bot, "🕘 <b>Past visits</b>\nTap a visit to rebook it or leave a review.", Kb::rows($rows));
    }

    public function detail(Nutgram $bot, int $id): void
    {
        $a = $this->owned($bot, $id);
        $text = Ui::card($a)."\nStatus: <b>".$a->status->label().'</b>';
        $rows = [];

        if ($a->isCancellable()) {
            $text .= "\n\n".Ui::cancelPolicy($a);
            $rows[] = [['🔄 Reschedule', 'my:rs:'.$a->id], ['✖ Cancel', 'my:cx:'.$a->id]];
            $rows[] = [['📅 Add to calendar', 'my:ics:'.$a->id], ['🔔 Preview reminder', 'my:rem:'.$a->id]];
        }
        if ($a->status === AppointmentStatus::Completed && ! $a->review) {
            $rows[] = [['⭐ Leave a review', 'rv:'.$a->id]];
        }
        if ($a->review) {
            $text .= "\n\nYour review: ".str_repeat('⭐', $a->review->rating);
        }
        $rows[] = [['💅 Book this again', 'my:rb:'.$a->id]];
        $rows[] = [['« Back', $a->start_at->isFuture() ? 'my:up' : 'my:past']];
        Ui::show($bot, $text, Kb::rows($rows));
    }

    public function cancelAsk(Nutgram $bot, int $id): void
    {
        $a = $this->owned($bot, $id);
        abort_unless($a->isCancellable(), 404);
        Ui::show($bot, "Cancel this appointment?\n\n".Ui::card($a)."\n\n".Ui::cancelPolicy($a), Kb::rows([
            [['Yes, cancel it', 'my:cxy:'.$a->id], ['Keep it', 'my:a:'.$a->id]],
        ]));
    }

    public function cancelDo(Nutgram $bot, int $id): void
    {
        $a = $this->owned($bot, $id);
        abort_unless($a->isCancellable(), 404);
        $this->bookings->cancel($a->load(['client', 'service', 'staff']), 'Cancelled by client in Telegram');
        Ui::show($bot, '✅ Your appointment was cancelled.', Kb::rows([
            [['💅 Book again', 'bk:cats'], ['📋 My bookings', 'my:up']],
        ]));
    }

    public function calendarFile(Nutgram $bot, int $id): void
    {
        $a = $this->owned($bot, $id);
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $a->toIcs());
        rewind($stream);
        $bot->sendDocument(document: InputFile::make($stream, 'appointment-'.$a->reference.'.ics'), caption: '📅 Open this file to add the visit to your phone calendar.');
    }

    public function reminderPreview(Nutgram $bot, int $id): void
    {
        $bot->sendMessage(text: Notifier::reminderText($this->owned($bot, $id)), parse_mode: 'HTML');
    }

    public function rewards(Nutgram $bot): void
    {
        $client = $this->client($bot);
        if (! $client) {
            Ui::show($bot, "🎁 <b>Rewards</b>\nEvery $".\App\Models\Setting::int('loyalty_dollars_per_point')." you spend earns a point. Reach Silver at 150 points and Gold at 400.\n\nBook your first visit to start collecting!", Kb::rows([
                [['💅 Book an appointment', 'bk:cats']], [['« Menu', 'menu']],
            ]));

            return;
        }
        $tier = $client->tier();
        $lines = ['🎁 <b>Your rewards</b>', '', '🏅 '.$tier['current']['name'].' member · <b>'.$tier['points'].' points</b>', '🎀 Perk: '.$tier['current']['perk']];
        if ($tier['next']) {
            $span = max(1, $tier['next']['min'] - $tier['current']['min']);
            $filled = (int) round(10 * ($tier['points'] - $tier['current']['min']) / $span);
            $lines[] = '';
            $lines[] = str_repeat('▰', $filled).str_repeat('▱', 10 - $filled);
            $lines[] = ($tier['next']['min'] - $tier['points']).' points to '.$tier['next']['name'];
        }
        $lines[] = '';
        $lines[] = 'Visits: '.$client->appointments()->where('status', AppointmentStatus::Completed->value)->count().' · Spent: '.money($client->lifetimeSpend());
        Ui::show($bot, implode("\n", $lines), Kb::back());
    }

    // ------------------------------------------------------------------ reviews
    public function rate(Nutgram $bot, int $id, ?string $rating): void
    {
        $a = $this->owned($bot, $id);
        abort_unless($a->status === AppointmentStatus::Completed && ! $a->review, 404);

        if ($rating === null) {
            $stars = array_map(fn ($n) => [str_repeat('⭐', $n), 'rv:'.$a->id.':'.$n], range(1, 5));
            Ui::show($bot, '⭐ <b>How was your '.Ui::e($a->service->name)." with {$a->staff->name}?</b>\nTap a rating.", Kb::rows([
                array_slice($stars, 0, 2), array_slice($stars, 2, 3), [['« Back', 'my:a:'.$a->id]],
            ]));

            return;
        }
        State::for($bot)->expect('review', ['appointment_id' => $a->id, 'rating' => (int) $rating]);
        Ui::show($bot, str_repeat('⭐', (int) $rating)."\nNice! Now write a short comment in the chat (at least 10 characters).", Kb::back('my:a:'.$a->id, '« Skip'));
    }

    public function saveReview(Nutgram $bot, string $text, array $await): void
    {
        if (mb_strlen($text) < 10) {
            $bot->sendMessage('A little more, please. At least 10 characters.');

            return;
        }
        $a = $this->owned($bot, (int) $await['appointment_id']);
        State::for($bot)->stopAwaiting();
        if ($a->review) {
            return;
        }
        $rating = (int) $await['rating'];
        Review::query()->create([
            'appointment_id' => $a->id, 'client_id' => $a->client_id, 'staff_id' => $a->staff_id, 'service_id' => $a->service_id,
            'rating' => $rating, 'comment' => mb_substr($text, 0, 1000), 'published' => $rating >= 3,
        ]);
        Activity::log('review', $a->reference, $a->client->name.' left a '.$rating.'-star review', route('admin.reviews.index'));
        $bot->sendMessage('💖 Thank you for your review!', reply_markup: Kb::menu());
    }

    // ------------------------------------------------------------------ demo history
    /** Gives a new visitor a few past visits, so rewards and reviews have something to show. */
    public function addSampleHistory(Nutgram $bot): void
    {
        $tg = $bot->user();
        $client = Client::forTelegram($tg->id, trim($tg->first_name.' '.($tg->last_name ?? '')));
        if ($client->appointments()->where('status', AppointmentStatus::Completed->value)->exists()) {
            $this->past($bot);

            return;
        }

        $services = Service::query()->active()->whereHas('staff')->inRandomOrder()->limit(3)->get();
        foreach ($services as $i => $service) {
            $staff = $service->staff()->where('staff.active', true)->inRandomOrder()->first();
            if (! $staff) {
                continue;
            }
            $start = Carbon::today()->subDays(12 + $i * 21)->setTime(10 + $i * 2, 0);
            Appointment::query()->create([
                'client_id' => $client->id, 'staff_id' => $staff->id, 'service_id' => $service->id,
                'start_at' => $start, 'end_at' => $start->copy()->addMinutes($service->duration), 'status' => AppointmentStatus::Completed,
                'price' => $service->price, 'deposit' => $service->depositFor($service->price), 'deposit_paid' => true, 'source' => 'telegram',
            ]);
        }
        $this->past($bot);
    }

    private function statusIcon(Appointment $a): string
    {
        return match ($a->status) {
            AppointmentStatus::Completed => '✅',
            AppointmentStatus::Cancelled => '✖️',
            AppointmentStatus::NoShow => '🚫',
            default => '⏳',
        };
    }
}
