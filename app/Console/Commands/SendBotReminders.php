<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Telegram\Notifier;
use Illuminate\Console\Command;
use SergiX44\Nutgram\Nutgram;

class SendBotReminders extends Command
{
    protected $signature = 'salon:bot-reminders';

    protected $description = 'Send a Telegram reminder to clients whose appointment is within the next 24 hours';

    public function handle(Nutgram $bot): int
    {
        $due = Appointment::query()->upcoming()->whereNull('reminded_at')
            ->where('start_at', '<=', now()->addHours(24))
            ->whereHas('client', fn ($q) => $q->whereNotNull('telegram_id'))
            ->with(['client', 'service', 'staff'])->get();

        foreach ($due as $appointment) {
            try {
                $bot->sendMessage(text: Notifier::reminderText($appointment), chat_id: $appointment->client->telegram_id, parse_mode: 'HTML');
            } catch (\Throwable $e) {
                report($e); // the person may have blocked the bot; still mark it so we do not retry forever
            }
            $appointment->forceFill(['reminded_at' => now()])->save();
        }
        $this->info($due->count().' reminders sent.');

        return self::SUCCESS;
    }
}
