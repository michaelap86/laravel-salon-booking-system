<?php

namespace App\Listeners;

use App\Models\SentEmail;
use Illuminate\Mail\Events\MessageSent;

/** Keeps a copy of every outgoing email so the Outbox page can show it. */
class LogSentEmail
{
    public function handle(MessageSent $event): void
    {
        $message = $event->message;

        SentEmail::query()->create([
            'to' => collect($message->getTo())->map(fn ($a) => $a->getAddress())->implode(', '),
            'subject' => (string) $message->getSubject(),
            'html' => (string) ($message->getHtmlBody() ?? $message->getTextBody()),
            'created_at' => now(),
        ]);
    }
}
