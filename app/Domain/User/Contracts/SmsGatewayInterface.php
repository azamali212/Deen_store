<?php

declare(strict_types=1);

namespace App\Domain\User\Contracts;

interface SmsGatewayInterface
{
    /**
     * Send a plain text SMS message to the given phone number.
     *
     * Implementations decide HOW the message is delivered (Twilio, log file,
     * another provider) — callers only depend on this contract, never on a
     * concrete gateway.
     */
    public function send(string $toPhoneNumber, string $message): void;
}
