<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | KYC document retention (P9-3)
    |--------------------------------------------------------------------------
    | How long identity documents are kept on disk. These are DAYS, and they
    | are config rather than literals in the code precisely so that changing
    | them is one .env value and not a code change.
    |
    | kyc_retention_days — how long a CLOSED store's documents are kept
    | after it closed. 1825 days (5 years) is the figure commonly used for
    | AML/KYC record keeping.
    |
    | THIS IS NOT LEGAL ADVICE and has not been checked against Pakistani
    | law. Confirm the number with a lawyer before launch.
    |
    | superseded_retention_days — how long a REJECTED renewal, or an
    | approved one that a newer approved renewal has replaced, is kept.
    | It proves nothing current; 90 days is long enough to settle a dispute
    | about the decision.
    |
    | The ROW always survives (P9-2). Only the file is deleted.
    */

    'kyc_retention_days' => (int) env('SELLER_KYC_RETENTION_DAYS', 1825),

    'superseded_retention_days' => (int) env('SELLER_SUPERSEDED_RETENTION_DAYS', 90),

];
