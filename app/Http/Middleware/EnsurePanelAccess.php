<?php

namespace App\Http\Middleware;

use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Auth\Services\PanelAccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePanelAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */

     public function __construct(
        private PanelAccessService $panelAccessService,
    ) {}
    public function handle(Request $request, Closure $next,string $panel): Response
    {
        $user = $request->user();
        if ($user === null) {
            abort(401, 'Unauthenticated.');
        }
        $this->panelAccessService->ensureCanAccess(
            $user,
            AuthPanel::from($panel)
        );
        return $next($request);
    }
}
