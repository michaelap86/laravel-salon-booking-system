<?php

namespace App\Telegram;

use App\Models\Appointment;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

final class Ui
{
    public static function e(?string $text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES);
    }

    /** Edit the message the button was on, or send a new one when the user typed something. */
    public static function show(Nutgram $bot, string $text, ?InlineKeyboardMarkup $keyboard = null): void
    {
        if ($bot->callbackQuery()?->message) {
            try {
                $bot->editMessageText(text: $text, parse_mode: ParseMode::HTML, reply_markup: $keyboard);

                return;
            } catch (\Throwable $e) {
                if (str_contains($e->getMessage(), 'not modified')) {
                    return;
                }
            }
        }
        $bot->sendMessage(text: $text, parse_mode: ParseMode::HTML, reply_markup: $keyboard);
    }

    public static function icon(string $categoryName): string
    {
        $name = strtolower($categoryName);

        return match (true) {
            str_contains($name, 'nail') => '💅',
            str_contains($name, 'colo') => '🎨',
            str_contains($name, 'lash'), str_contains($name, 'brow') => '👁️',
            str_contains($name, 'makeup'), str_contains($name, 'make-up') => '💄',
            str_contains($name, 'skin'), str_contains($name, 'facial') => '🧖‍♀️',
            str_contains($name, 'wax') => '🌸',
            str_contains($name, 'hair'), str_contains($name, 'cut'), str_contains($name, 'style') => '💇‍♀️',
            default => '✨',
        };
    }

    public static function when(Carbon $date): string
    {
        return $date->format('D, M j').' · '.$date->format('g:i A');
    }

    /** The details of one appointment, as shown in confirmations and reminders. */
    public static function card(Appointment $a): string
    {
        $a->loadMissing(['service', 'staff']);
        $lines = [
            '<b>'.self::e($a->service->name).'</b>',
            '🗓 '.self::when($a->start_at),
            '👩‍🎨 '.self::e($a->staff->name).' · '.duration($a->service->duration),
            '💵 '.money($a->price, true).($a->deposit > 0 ? ' · deposit '.money($a->deposit, true).($a->deposit_paid ? ' paid' : '') : ''),
            '🔖 Ref <code>'.$a->reference.'</code>',
        ];

        return implode("\n", $lines);
    }

    public static function cancelPolicy(Appointment $a): string
    {
        $hours = Setting::int('free_cancel_hours');

        return $a->cancellableFree()
            ? 'Free cancellation is available until '.$a->start_at->copy()->subHours($hours)->format('M j, g:i A').'.'
            : 'This visit is within '.$hours.' hours, so cancelling forfeits the deposit.';
    }
}
