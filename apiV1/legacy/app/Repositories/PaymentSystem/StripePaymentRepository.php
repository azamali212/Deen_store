<?php

namespace App\Repositories\PaymentSystem;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Subscription;
use Stripe\Stripe;
use Stripe\Exception\ApiErrorException;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Log;
use Stripe\PaymentMethod;

class StripePaymentRepository implements StripePaymentRepositoryInterface
{

    public function __construct()
    {
        Stripe::setApiKey(config('cashier.secret'));
    }
    public function createCustomer(User $user): void
    {
        // Implementation for creating a customer in Stripe
        if (!$user->stripe_id) {
            $user->createAsStripeCustomer();
        }
    }

    public function getCustomerId(User $user): ?string
    {
        // Implementation for retrieving the customer ID from Stripe
        return $user->stripe_id ?? null;
    }

    public function createOrAttachPaymentMethod(User $user, $paymentMethodData = null, $paymentMethodId = null): string
    {
        try {
            // Ensure Stripe customer exists
            if (!$user->hasStripeId()) {
                $user->createAsStripeCustomer();
            }
    
            Stripe::setApiKey(env('STRIPE_SECRET'));
    
            if ($paymentMethodId) {
                // Attach existing Stripe payment method
                $paymentMethodObj = \Stripe\PaymentMethod::retrieve($paymentMethodId);
    
                if ($paymentMethodObj->customer && $paymentMethodObj->customer !== $user->stripe_id) {
                    throw new \Exception('Payment method already attached to another customer.');
                }
    
                // Attach to current user
                $paymentMethodObj->attach(['customer' => $user->stripe_id]);
                $user->updateDefaultPaymentMethod($paymentMethodObj->id);
    
                return $paymentMethodObj->id;
            } elseif ($paymentMethodData && isset($paymentMethodData['card_token'])) {
                // Create from token (like tok_visa)
                $paymentMethod = \Stripe\PaymentMethod::create([
                    'type' => 'card',
                    'card' => [
                        'token' => $paymentMethodData['card_token'],
                    ],
                ]);
    
                $paymentMethod->attach(['customer' => $user->stripe_id]);
                $user->updateDefaultPaymentMethod($paymentMethod->id);
    
                return $paymentMethod->id;
            } else {
                throw new \Exception('Invalid request: provide either a valid paymentMethodId or card_token.');
            }
        } catch (ApiErrorException $e) {
            \Log::error('Stripe API error: ' . $e->getMessage());
            throw new \Exception('Stripe API error: ' . $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('Payment method error: ' . $e->getMessage());
            throw new \Exception('Error: ' . $e->getMessage());
        }
    }
    public function updatePaymentMethod(User $user, string $paymentMethod): void
    {
        // Step 1: Create/Attach payment method
        $paymentMethodId = $this->createPaymentMethod($user, $paymentMethod);

        // Step 2: Set it as the default payment method
        $user->updateDefaultPaymentMethod($paymentMethodId);

        // Step 3: Detach all other payment methods
        $existingMethods = $user->paymentMethods();
        foreach ($existingMethods as $method) {
            if ($method->id !== $paymentMethodId) {
                $method->delete();
            }
        }

        // Step 4: Store the default locally
        $user->default_payment_method = $paymentMethodId;
        $user->save();
    }
    public function getPaymentMethod(User $user): ?array
    {
        try {
            // Check if the user has a Stripe ID
            if (!$user->hasStripeId()) {
                return null;
            }

            // Get the default payment method from Stripe
            $stripeCustomer = $user->asStripeCustomer();
            $defaultPaymentMethodId = $stripeCustomer->invoice_settings->default_payment_method ?? $user->default_payment_method;

            if (!$defaultPaymentMethodId) {
                return null;
            }

            // Retrieve the actual payment method details
            $paymentMethod = \Stripe\PaymentMethod::retrieve($defaultPaymentMethodId);

            // Return formatted payment method info
            return [
                'brand' => $paymentMethod->card->brand,
                'last4' => $paymentMethod->card->last4,
                'exp_month' => $paymentMethod->card->exp_month,
                'exp_year' => $paymentMethod->card->exp_year,
                'id' => $paymentMethod->id,
                'type' => $paymentMethod->type,
            ];
        } catch (\Exception $e) {
            // Log error for debugging
            \Log::error('Error fetching payment method: ' . $e->getMessage());

            // In production, just return null or a graceful error fallback
            return null;
        }
    }
    public function detachPaymentMethods(User $user, bool $preserveDefault = true): void
    {
        try {
            // Get all payment methods
            $paymentMethods = $user->paymentMethods();

            foreach ($paymentMethods as $method) {
                $isDefault = $user->defaultPaymentMethod()?->id === $method->id;

                // Skip default if preserving
                if ($preserveDefault && $isDefault) {
                    continue;
                }

                // Detach from Stripe
                $method->delete();
            }

            // Optional: Reset local DB column if no methods left
            if (!$user->fresh()->hasPaymentMethod()) {
                $user->update(['default_payment_method' => null]);
            }
        } catch (ApiErrorException $e) {
            // Log and rethrow for controller to catch
            \Log::error('Stripe detach error: ' . $e->getMessage());
            throw new \Exception('Unable to detach payment methods at this time.');
        }
    }

    public function chargeCustomer(User $user, int $amountInCents, string $currency = 'usd', string $paymentMethodId = null): array
    {
        try {
            if (!$user->hasStripeId()) {
                $user->createAsStripeCustomer();
            }

            $paymentMethodId = $paymentMethodId ?? $user->defaultPaymentMethod()?->id;

            if (!$paymentMethodId) {
                throw new \Exception('No valid payment method found.');
            }

            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => $currency,
                'customer' => $user->stripe_id,
                'payment_method' => $paymentMethodId,
                'off_session' => true,
                'confirm' => true,
            ]);

            // Save to database
            Payment::create([
                'user_id' => $user->id,
                'order_id' => 0, // if no real order, just pass dummy or optional later
                'payment_method' => 'stripe',
                'transaction_id' => $paymentIntent->id,
                'amount' => $amountInCents / 100, // convert to dollars
                'currency' => $currency,
                'status' => $paymentIntent->status === 'succeeded' ? 'paid' : 'pending',
                'payment_response' => json_encode($paymentIntent->toArray()),
                'paid_at' => $paymentIntent->status === 'succeeded' ? Carbon::now() : null,
            ]);

            return [
                'id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
            ];
        } catch (IncompletePayment $e) {
            throw new \Exception('Payment incomplete. Action required.');
        } catch (ApiErrorException $e) {
            Log::error('Stripe charge error: ' . $e->getMessage());
            throw new \Exception('Unable to complete payment.');
        }
    }
}
