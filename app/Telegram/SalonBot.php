<?php

namespace App\Telegram;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\RateLimiter;
use SergiX44\Nutgram\Nutgram;

/** Connects Telegram updates (commands, button taps, text) to the flows. */
class SalonBot
{
    public function __construct(private BookingFlow $booking, private AccountFlow $account, private MenuFlow $menu) {}

    public function register(Nutgram $bot): void
    {
        // A public demo bot: keep any one person from flooding it
        $bot->middleware(function (Nutgram $bot, $next) {
            if (RateLimiter::attempt('tg:'.$bot->userId(), 90, fn () => true, 60)) {
                $next($bot);
            }
        });

        $bot->onCommand('start', fn (Nutgram $b) => $this->menu->start($b));
        $bot->onCommand('help', fn (Nutgram $b) => $this->menu->help($b));
        $bot->onCommand('book', fn (Nutgram $b) => $this->booking($b, 'cats'));
        $bot->onCommand('bookings', fn (Nutgram $b) => $this->account->upcoming($b));
        $bot->onCommand('rewards', fn (Nutgram $b) => $this->account->rewards($b));
        $bot->onCommand('info', fn (Nutgram $b) => $this->menu->info($b));
        $bot->onCommand('contact', fn (Nutgram $b) => $this->menu->askMessage($b));
        $bot->onCommand('cancel', fn (Nutgram $b) => $this->booking($b, 'x'));
        $bot->onCommand('id', fn (Nutgram $b) => $this->menu->chatId($b));

        $bot->onCallbackQuery(fn (Nutgram $b) => $this->callback($b));
        $bot->onMessage(fn (Nutgram $b) => $this->text($b));

        $bot->onException(function (Nutgram $b, \Throwable $e) {
            if (app()->runningUnitTests()) {
                throw $e;
            }
            report($e);
            try {
                $b->sendMessage('Oops, something went wrong on our side. Please try again.', reply_markup: Kb::menu());
            } catch (\Throwable) {
            }
        });
    }

    public function callback(Nutgram $bot): void
    {
        $bot->answerCallbackQuery();
        [$route, $a, $b] = array_pad(explode(':', (string) $bot->callbackQuery()->data), 3, null);

        try {
            match ($route) {
                'noop' => null,
                'bk' => $this->booking($bot, (string) $a, $b),
                'my' => $this->mine($bot, (string) $a, (int) $b),
                'rv' => $this->account->rate($bot, (int) $a, $b),
                'rw' => $this->account->rewards($bot),
                'info' => $this->menu->info($bot),
                'msg' => $this->menu->askMessage($bot),
                'demo' => $this->account->addSampleHistory($bot),
                default => $this->menu->main($bot),
            };
        } catch (ModelNotFoundException) {
            // Old buttons point at records that the daily demo reset removed
            State::for($bot)->forget();
            Ui::show($bot, '✨ The demo data was just refreshed, so that item is gone. Let us start fresh:', Kb::menu());
        }
    }

    public function text(Nutgram $bot): void
    {
        $text = trim((string) $bot->message()?->text);
        $await = State::for($bot)->awaiting();

        if ($text === '' || ! $await) {
            $this->menu->unknown($bot);

            return;
        }
        match ($await['kind']) {
            'promo' => $this->booking->applyPromo($bot, $text),
            'review' => $this->account->saveReview($bot, $text, $await),
            'message' => $this->menu->saveMessage($bot, $text),
            default => $this->menu->unknown($bot),
        };
    }

    private function booking(Nutgram $bot, string $step, ?string $arg = null): void
    {
        match ($step) {
            'cats' => $this->booking->categories($bot),
            'c' => $this->booking->services($bot, (int) $arg),
            's' => $this->booking->service($bot, (int) $arg),
            't' => $this->booking->stylist($bot, (int) $arg),
            'm' => $this->booking->calendar($bot, $arg),
            'd' => $this->booking->times($bot, (string) $arg),
            'h' => $this->booking->time($bot, (string) $arg),
            'promo' => $this->booking->askPromo($bot),
            'sum' => $this->booking->summary($bot),
            'pay' => $this->booking->checkout($bot),
            'go' => $this->booking->pay($bot, (string) $arg),
            default => $this->booking->cancel($bot),
        };
    }

    private function mine(Nutgram $bot, string $step, int $id): void
    {
        match ($step) {
            'up' => $this->account->upcoming($bot),
            'past' => $this->account->past($bot),
            'a' => $this->account->detail($bot, $id),
            'cx' => $this->account->cancelAsk($bot, $id),
            'cxy' => $this->account->cancelDo($bot, $id),
            'rs' => $this->booking->startReschedule($bot, $this->account->owned($bot, $id)),
            'rb' => $this->booking->rebook($bot, $this->account->owned($bot, $id)),
            'ics' => $this->account->calendarFile($bot, $id),
            'rem' => $this->account->reminderPreview($bot, $id),
            default => $this->account->upcoming($bot),
        };
    }
}
