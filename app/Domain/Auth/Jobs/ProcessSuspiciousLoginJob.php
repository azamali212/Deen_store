<?php

declare(strict_types=1);

namespace App\Domain\Auth\Jobs;

use App\Domain\Auth\Events\Data\SuspiciousLoginEventData;
use App\Domain\Auth\Notifications\SuspiciousLoginNotification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ProcessSuspiciousLoginJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly SuspiciousLoginEventData $data,
    ) {}

    public function handle(): void
    {
        Log::warning(
            'Suspicious login detected.',
            $this->data->toArray()
        );

        $user = User::query()->find(
            $this->data->userId
        );

        if (! $user) {
            return;
        }

        $user->notify(
            new SuspiciousLoginNotification(
                $this->data
            )
        );
    }
}