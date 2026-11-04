<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * The demo data is generated relative to "today". On the first request of each day it is
 * rebuilt, so recent dates always have bookings and anything a visitor changed is reset.
 */
class DemoRefresh
{
    private const LOCK_TTL = 300; // seconds before a leftover lock is considered stale

    public function handle(Request $request, Closure $next): Response
    {
        if (config('salon.demo_auto_refresh') && ! app()->runningUnitTests() && $this->stale()) {
            $this->refresh();
        }

        return $next($request);
    }

    private function stale(): bool
    {
        if (! Schema::hasTable('settings')) {
            return false;
        }

        return Setting::query()->where('key', 'demo_seeded_on')->value('value') !== today()->toDateString();
    }

    private function refresh(): void
    {
        $lock = storage_path('framework/demo-refresh.lock');
        if (is_file($lock) && time() - filemtime($lock) > self::LOCK_TTL) {
            @unlink($lock);
        }
        $fd = @fopen($lock, 'x'); // fails if another request is already refreshing
        if (! $fd) {
            return;
        }
        fclose($fd);

        try {
            Artisan::call('db:seed', ['--force' => true]);
        } finally {
            @unlink($lock);
        }
    }
}
