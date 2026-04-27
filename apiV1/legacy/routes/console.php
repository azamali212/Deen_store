<?php

use App\Jobs\CartAbandonmentReminderJob;
use App\Models\Cart;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('cart:remind', function () {
    // Fetch all carts older than 7 days
    $carts = Cart::where('updated_at', '<', now()->subWeek())->get();
    
    foreach ($carts as $cart) {
        // Dispatch the job for each cart
        CartAbandonmentReminderJob::dispatch($cart);
    }

    $this->info('Cart abandonment reminders have been sent!');
})->daily();