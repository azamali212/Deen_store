<?php

namespace App\Http\Controllers\StripPayment;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\PaymentSystem\StripePaymentRepositoryInterface;
use Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripPaymentController extends Controller
{
    protected StripePaymentRepositoryInterface $stripePaymentRepository;
    public function __construct(StripePaymentRepositoryInterface $stripePaymentRepository)
    {
        $this->stripePaymentRepository = $stripePaymentRepository;
    }

    public function createCustomer()
    {
        try {
            $user = Auth::user();

            if (!$user instanceof User) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access. Please login to continue.'
                ], 401);
            }

            // Check if customer already exists in Stripe
            if ($user->stripe_id) {
                return response()->json([
                    'status' => 'info',
                    'message' => 'Stripe customer already exists.',
                    'stripe_id' => $user->stripe_id
                ], 200);
            }

            // Create customer
            $this->stripePaymentRepository->createCustomer($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Stripe customer created successfully.',
                'stripe_id' => $user->stripe_id
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Stripe customer.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCustomerId()
    {
        $user = Auth::user();

        if (!$user instanceof User) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $customerId = $this->stripePaymentRepository->getCustomerId($user);

        if (!$customerId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stripe customer ID not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Stripe customer ID retrieved successfully.',
            'stripe_customer_id' => $customerId,
        ]);
    }
    public function createPaymentMethod(Request $request): JsonResponse
    {
        $request->validate([
            'payment_method_id' => 'nullable|string',
            'card_token' => 'nullable|string',
        ]);
    
        try {
            $user = Auth::user();
    
            if (!$user instanceof User) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized.'
                ], 401);
            }
    
            $paymentMethodId = $request->input('payment_method_id');
            $paymentMethodData = $request->only(['card_token']);
    
            $paymentMethod = $this->stripePaymentRepository->createOrAttachPaymentMethod($user, $paymentMethodData, $paymentMethodId);
    
            return response()->json([
                'status' => 'success',
                'message' => 'Payment method added successfully.',
                'payment_method_id' => $paymentMethod,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updatePaymentMethod(Request $request)
    {
        $user = Auth::user();

        if (!$user instanceof User) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'payment_method' => 'required|string',
        ]);

        try {
            $this->stripePaymentRepository->updatePaymentMethod($user, $request->payment_method);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment method updated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update payment method.',
                'error' => $e->getMessage(),
                'data' => $user->only('default_payment_method'),
            ], 500);
        }
    }

    public function getPaymentMethod(Request $request)
    {
        $user = $request->user();

        $paymentMethod = $this->stripePaymentRepository->getPaymentMethod($user);

        if (!$paymentMethod) {
            return response()->json([
                'status' => 'error',
                'message' => 'No default payment method found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Payment method retrieved successfully.',
            'data' => $paymentMethod,
        ]);
    }

    public function detachAllPaymentMethods(User $user): JsonResponse
    {
        try {
            $this->stripePaymentRepository->detachPaymentMethods($user, preserveDefault: true);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment methods detached successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage() ?? 'Something went wrong.',
            ], 500);
        }
    }

    public function chargeCustomer(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string',
            'payment_method_id' => 'nullable|string',
        ]);

        $user = Auth::user();

        try {
            $charge = $this->stripePaymentRepository->chargeCustomer(
                $user,
                (int) ($request->amount * 100), // convert to cents
                $request->currency ?? 'usd',
                $request->payment_method_id
            );

            return response()->json([
                'message' => 'Payment successful.',
                'charge' => $charge,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Payment failed.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
    
}
