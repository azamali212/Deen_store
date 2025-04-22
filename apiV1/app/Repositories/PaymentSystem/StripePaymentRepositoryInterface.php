<?php

namespace App\Repositories\PaymentSystem;

use App\Models\User;
use Laravel\Cashier\Subscription;

interface StripePaymentRepositoryInterface
{
    // 🔹 Customer Management
    public function createCustomer(User $user): void;
    public function getCustomerId(User $user): ?string;

    // 🔹 Payment Method Management
    public function createOrAttachPaymentMethod(User $user, $paymentMethodData = null, $paymentMethodId = null): string;
    public function updatePaymentMethod(User $user, string $paymentMethod): void;
    public function getPaymentMethod(User $user): ?array;
    public function detachPaymentMethods(User $user, bool $preserveDefault = true): void;

    public function chargeCustomer(User $user, int $amountInCents, string $currency = 'usd', string $paymentMethodId = null): array;
}