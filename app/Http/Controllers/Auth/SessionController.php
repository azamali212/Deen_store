<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Services\SessionService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\SessionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class SessionController extends Controller
{
    public function __construct(
        private readonly SessionService $sessionService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return SessionResource::collection(
            $this->sessionService->activeSessions(
                $request->user()
            )
        );
    }
}