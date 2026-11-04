<?php

namespace App\Telegram;

use Illuminate\Support\Facades\Cache;
use SergiX44\Nutgram\Nutgram;

/** What the client is in the middle of (a booking draft, or a question we asked them), kept for a few hours. */
final class State
{
    public function __construct(private int $chatId) {}

    public static function for(Nutgram $bot): self
    {
        return new self((int) $bot->chatId());
    }

    public function draft(): array
    {
        return Cache::get($this->key('draft'), []);
    }

    /** Start a fresh draft. */
    public function start(array $data): void
    {
        $this->forget();
        Cache::put($this->key('draft'), $data, now()->addHours(3));
    }

    public function put(array $data): void
    {
        Cache::put($this->key('draft'), [...$this->draft(), ...$data], now()->addHours(3));
    }

    public function drop(string $key): void
    {
        $draft = $this->draft();
        unset($draft[$key]);
        Cache::put($this->key('draft'), $draft, now()->addHours(3));
    }

    /** The next text message from the user answers this question. */
    public function expect(string $kind, array $meta = []): void
    {
        Cache::put($this->key('await'), ['kind' => $kind, ...$meta], now()->addHours(1));
    }

    public function awaiting(): ?array
    {
        return Cache::get($this->key('await'));
    }

    public function stopAwaiting(): void
    {
        Cache::forget($this->key('await'));
    }

    public function forget(): void
    {
        Cache::forget($this->key('draft'));
        $this->stopAwaiting();
    }

    private function key(string $name): string
    {
        return 'tg:'.$this->chatId.':'.$name;
    }
}
