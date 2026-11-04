<?php

namespace App\Services\Payments;

use Illuminate\Support\Str;

/**
 * A pretend card processor that behaves like a payment provider's test mode:
 * no money moves, a few well-known test cards succeed or fail on purpose.
 */
class TestGateway implements PaymentGateway
{
    /** Test cards that always decline, with the reason shown to the user. */
    private const DECLINES = [
        '4000000000000002' => 'Your card was declined.',
        '4000000000009995' => 'Your card has insufficient funds.',
        '4000000000000069' => 'Your card has expired.',
    ];

    public function charge(int $amountCents, string $cardNumber, string $expiry, string $cvc, string $description): array
    {
        $digits = preg_replace('/\D/', '', $cardNumber);

        if (strlen($digits) < 13 || ! $this->luhn($digits)) {
            return ['ok' => false, 'id' => null, 'message' => 'That card number is not valid.'];
        }
        if (! preg_match('/^(0[1-9]|1[0-2])\s*\/\s*(\d{2})$/', trim($expiry), $m) || (int) ('20'.$m[2]) * 12 + (int) $m[1] < now()->year * 12 + now()->month) {
            return ['ok' => false, 'id' => null, 'message' => 'The expiry date is not valid.'];
        }
        if (! preg_match('/^\d{3,4}$/', $cvc)) {
            return ['ok' => false, 'id' => null, 'message' => 'The security code is not valid.'];
        }
        if (isset(self::DECLINES[$digits])) {
            return ['ok' => false, 'id' => null, 'message' => self::DECLINES[$digits]];
        }

        return ['ok' => true, 'id' => 'ch_test_'.Str::lower(Str::random(14)), 'message' => 'Payment received'];
    }

    private function luhn(string $number): bool
    {
        $sum = 0;
        foreach (array_reverse(str_split($number)) as $i => $d) {
            $n = (int) $d;
            if ($i % 2 === 1) {
                $n *= 2;
                $n -= $n > 9 ? 9 : 0;
            }
            $sum += $n;
        }

        return $sum % 10 === 0;
    }
}
