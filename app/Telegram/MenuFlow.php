<?php

namespace App\Telegram;

use App\Models\Activity;
use App\Models\BusinessHour;
use App\Models\Client;
use App\Models\Message;
use SergiX44\Nutgram\Nutgram;

/** The welcome screen, salon info and the "message us" form. */
class MenuFlow
{
    public function start(Nutgram $bot): void
    {
        State::for($bot)->forget();
        $name = Ui::e($bot->user()?->first_name ?? 'there');
        $bot->sendMessage(
            text: "✨ <b>Welcome to ".config('salon.name').", {$name}!</b>\n\nBook hair, nails, lashes and skin treatments right here: pick a service and a stylist, choose a time and pay the deposit.\n\n"
                .'<i>This is a portfolio demo. The salon, the prices and the payments are all fictional, and the data resets every day.</i>',
            parse_mode: 'HTML',
            reply_markup: Kb::menu(),
        );
    }

    public function main(Nutgram $bot): void
    {
        State::for($bot)->forget();
        Ui::show($bot, '🌸 <b>'.config('salon.name').'</b>'."\nWhat would you like to do?", Kb::menu());
    }

    public function help(Nutgram $bot): void
    {
        $bot->sendMessage(
            text: "🌸 <b>How this works</b>\n\n/book  book an appointment\n/bookings  your upcoming and past visits\n/rewards  your points and tier\n/info  address and opening hours\n/contact  message the team\n/cancel  stop what you are doing\n\nEverything also works with the buttons.",
            parse_mode: 'HTML',
            reply_markup: Kb::menu(),
        );
    }

    public function info(Nutgram $bot): void
    {
        $hours = BusinessHour::query()->orderByRaw('(weekday + 6) % 7')->get()
            ->map(fn (BusinessHour $h) => substr(BusinessHour::dayName($h->weekday), 0, 3).'  '.($h->is_open ? short_time($h->opens).' - '.short_time($h->closes) : 'Closed'))->implode("\n");

        Ui::show($bot, '📍 <b>'.config('salon.name')."</b>\n".config('salon.address')."\n📞 ".config('salon.phone')."\n📸 ".config('salon.instagram')
            ."\n\n🕘 <b>Opening hours</b>\n<pre>".Ui::e($hours).'</pre>', Kb::rows([
                [['🌐 Open the website', rtrim(config('app.url'), '/')]],
                [['« Menu', 'menu']],
            ]));
    }

    public function askMessage(Nutgram $bot): void
    {
        State::for($bot)->expect('message');
        Ui::show($bot, "💬 <b>Message the team</b>\nWrite your message in the chat and we'll see it in the salon's inbox.", Kb::back('menu', '« Cancel'));
    }

    public function saveMessage(Nutgram $bot, string $text): void
    {
        State::for($bot)->stopAwaiting();
        $tg = $bot->user();
        $client = Client::query()->where('telegram_id', $tg->id)->first();
        $name = $client?->name ?? trim($tg->first_name.' '.($tg->last_name ?? ''));

        Message::query()->create([
            'name' => $name, 'email' => $client?->email ?? 'tg'.$tg->id.'@telegram.lumiere.demo',
            'subject' => 'Message from Telegram', 'body' => mb_substr($text, 0, 2000),
        ]);
        Activity::log('message', 'Message from Telegram', $name.' sent a message', route('admin.messages.index'));
        $bot->sendMessage('💌 Thank you! Your message is in our inbox and we will get back to you soon.', reply_markup: Kb::menu());
    }

    public function unknown(Nutgram $bot): void
    {
        $bot->sendMessage('I did not catch that. Tap a button to continue:', reply_markup: Kb::menu());
    }

    public function chatId(Nutgram $bot): void
    {
        $bot->sendMessage('Your chat id is <code>'.$bot->chatId().'</code>', parse_mode: 'HTML');
    }
}
