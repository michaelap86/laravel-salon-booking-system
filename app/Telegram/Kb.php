<?php

namespace App\Telegram;

use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/** Builds inline keyboards from plain arrays: each button is [label, callback data or URL]. */
final class Kb
{
    /** @param  array<int, array<int, array{0: string, 1: string}>>  $rows */
    public static function rows(array $rows): InlineKeyboardMarkup
    {
        $keyboard = InlineKeyboardMarkup::make();
        foreach ($rows as $row) {
            $keyboard->addRow(...array_map(
                fn (array $b) => str_starts_with($b[1], 'http')
                    ? InlineKeyboardButton::make($b[0], url: $b[1])
                    : InlineKeyboardButton::make($b[0], callback_data: $b[1]),
                $row,
            ));
        }

        return $keyboard;
    }

    public static function menu(): InlineKeyboardMarkup
    {
        return self::rows([
            [['💅 Book an appointment', 'bk:cats']],
            [['📋 My bookings', 'my:up'], ['🎁 Rewards', 'rw']],
            [['📍 Salon info', 'info'], ['💬 Message us', 'msg']],
        ]);
    }

    public static function back(string $data = 'menu', string $label = '« Menu'): InlineKeyboardMarkup
    {
        return self::rows([[[$label, $data]]]);
    }
}
