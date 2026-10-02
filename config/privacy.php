<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Erasure grace period (P12-3)
    |--------------------------------------------------------------------------
    | Days between a user asking to be erased and the sweep actually doing it.
    | The window exists for two reasons: an account taken over by someone else
    | can be recovered by the real owner, and a user who changes their mind has
    | somewhere to change it back.
    |
    | GDPR Art. 12(3) gives a controller one month to act on the request, so
    | anything comfortably under 30 days is safe. 14 is the default.
    */

    'erasure_grace_days' => (int) env('PRIVACY_ERASURE_GRACE_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Erasure blockers (P12-4)
    |--------------------------------------------------------------------------
    | Classes implementing ErasureBlockerInterface. Each one gets a look at the
    | user and answers one question: is there a reason this account cannot be
    | erased yet?
    |
    | This is config rather than a hardcoded list because the answer grows with
    | the project. Today only an open store blocks erasure. When Orders exists,
    | an undelivered order will block it too, and that is a one-line addition
    | here instead of an edit inside the User domain.
    */

    'erasure_blockers' => [
        App\Domain\Seller\Support\OpenStoreErasureBlocker::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Consent versions (P12-6)
    |--------------------------------------------------------------------------
    | A consent record is only evidence if it says WHAT was agreed to. Bump the
    | version here when the wording of a policy changes; the next time the user
    | agrees, a new row is written and the old one is marked superseded.
    |
    | Never edit an old version string — that rewrites history.
    */

    'consent_versions' => [
        'terms' => env('PRIVACY_TERMS_VERSION', '2026-09-01'),
        'privacy_policy' => env('PRIVACY_POLICY_VERSION', '2026-09-01'),
        'marketing' => env('PRIVACY_MARKETING_VERSION', '2026-09-01'),
        'document_processing' => env('PRIVACY_DOCUMENT_PROCESSING_VERSION', '2026-09-01'),
    ],

];
