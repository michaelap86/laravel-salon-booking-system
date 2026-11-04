<?php

use Carbon\CarbonInterface;

if (! function_exists('money')) {
    /** Format an amount in cents as dollars. */
    function money(int|float|null $cents, bool $decimals = false): string
    {
        $dollars = ($cents ?? 0) / 100;

        return '$'.number_format($dollars, $decimals || fmod($dollars, 1) !== 0.0 ? 2 : 0);
    }
}

if (! function_exists('duration')) {
    function duration(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return trim(($h ? $h.' h ' : '').($m ? $m.' min' : ''));
    }
}

if (! function_exists('short_time')) {
    function short_time(CarbonInterface|string $t): string
    {
        return \Illuminate\Support\Carbon::parse($t)->format('g:i A');
    }
}

if (! function_exists('initials')) {
    function initials(string $name): string
    {
        return collect(explode(' ', trim($name)))->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    }
}
