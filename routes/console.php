<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Phase 8a — nightly KYC expiry sweep. Runs before office hours so the
// admin queue already has the day's renewals waiting when reviewers
// arrive. withoutOverlapping: a long run must never stack on the next.
Schedule::command('seller:check-document-expiry')
    ->dailyAt('03:00')
    ->withoutOverlapping();

// Phase 9a — KYC retention. Runs after the expiry sweep so a document that
// was renewed this morning is never purged by the same night's run.
Schedule::command('seller:purge-expired-kyc-documents')
    ->dailyAt('03:30')
    ->withoutOverlapping();

// A5 / Phase 12 — the erasure sweep. Deliberately AFTER the KYC purge: on a
// night when a closed store's documents finally age out, they are gone before
// the owner's account is anonymised, so the two never race.
Schedule::command('users:process-erasure-requests')
    ->dailyAt('04:00')
    ->withoutOverlapping();
