<?php

namespace App\Telegram;

use App\Models\Appointment;
use SergiX44\Nutgram\Nutgram;

/** Messages the bot sends on its own: new bookings to the salon owner, reminders to clients. */
final class Notifier
{
    /** Tell the salon owner, if an admin chat is configured (send /id to the bot to find yours). */
    public static function newBooking(Appointment $appointment): void
    {
        $chat = config('salon.telegram.admin_chat');
        if (! $chat) {
            return;
        }
        $appointment->loadMissing(['client', 'service', 'staff']);

        try {
            app(Nutgram::class)->sendMessage(
                text: "🔔 <b>New booking</b>\n".Ui::e($appointment->client->name).' booked '.Ui::e($appointment->service->name)."\n"
                    .Ui::when($appointment->start_at).' with '.Ui::e($appointment->staff->name),
                chat_id: $chat,
                parse_mode: 'HTML',
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function reminderText(Appointment $appointment): string
    {
        return "🔔 <b>See you soon!</b>\nA reminder about your visit:\n\n".Ui::card($appointment)."\n\n".Ui::cancelPolicy($appointment);
    }
}
