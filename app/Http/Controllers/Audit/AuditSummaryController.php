<?php

declare(strict_types=1);

namespace App\Http\Controllers\Audit;

use App\Domain\Audit\Actions\SummarizeUserAuditActivityAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuditSummaryController extends Controller
{
    // Plain-English AI summary of a user's audit trail, for an admin.
    // {user} route-model-binds — only super_admin|admin can reach this
    // route (routes/audit/admin.php), same gate as routes/user/admin.php.
    public function forUser(
        User $user,
        Request $request,
        SummarizeUserAuditActivityAction $action,
    ): JsonResponse {

        $days = (int) $request->query('days', 30);
        $days = max(1, min($days, 90));

        $summary = $action->execute($user, $days);

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'days' => $days,
                'summary' => $summary,
            ],
        ]);
    }
}
