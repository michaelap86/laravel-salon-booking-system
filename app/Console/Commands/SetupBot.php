<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Command\BotCommand;

class SetupBot extends Command
{
    protected $signature = 'salon:bot-setup {--url= : Public HTTPS address of the webhook}';

    protected $description = 'Register the bot menu, description and webhook with Telegram';

    public function handle(Nutgram $bot): int
    {
        $bot->setMyCommands([
            BotCommand::make('start', 'Open the main menu'),
            BotCommand::make('book', 'Book an appointment'),
            BotCommand::make('bookings', 'My upcoming and past visits'),
            BotCommand::make('rewards', 'My points and tier'),
            BotCommand::make('info', 'Address and opening hours'),
            BotCommand::make('contact', 'Message the team'),
            BotCommand::make('cancel', 'Stop what I am doing'),
        ]);
        $bot->setMyShortDescription('Book hair, nails, lashes and skin appointments. A portfolio demo with fictional data.');
        $bot->setMyDescription("Book appointments at Lumière Studio right in Telegram: choose a service and a stylist, pick a time and pay the deposit.\n\nThis is a portfolio demo. The salon, the prices and the payments are fictional, and the data resets every day.");

        if ($url = $this->option('url')) {
            $bot->setWebhook($url, allowed_updates: ['message', 'callback_query'], drop_pending_updates: true, secret_token: md5(config('app.key')));
            $this->info('Webhook set to '.$url);
        }
        $this->info('Bot configured.');

        return self::SUCCESS;
    }
}
