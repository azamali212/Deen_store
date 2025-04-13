<?php

namespace App\Jobs;

use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};

class LogUserActionJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public $userId,
        public $action,
        public array $details = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(UserRepositoryInterface $repository): void
    {
        $repository->performLog($this->userId, $this->action, $this->details);
    }
}
