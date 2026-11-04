<?php

namespace App\Services\Payments;

/**
 * Anything that can take a card deposit. The demo uses TestGateway;
 * a real deployment would add a StripeGateway implementing the same contract.
 */
interface PaymentGateway
{
    /**
     * @return array{ok: bool, id: ?string, message: string}
     */
    public function charge(int $amountCents, string $cardNumber, string $expiry, string $cvc, string $description): array;
}
