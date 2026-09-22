<?php

declare(strict_types=1);

namespace App\Http\Controllers\Moderation;

use App\Domain\Moderation\Actions\ListModerationFlagsAction;
use App\Domain\Moderation\Actions\ResolveModerationFlagAction;
use App\Domain\Moderation\DTO\ResolveModerationFlagDTO;
use App\Domain\Moderation\Enums\ModerationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Moderation\ResolveModerationFlagRequest;
use App\Models\ModerationFlag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ModerationFlagController extends Controller
{
    // GET /v1/admin/moderation/flags?status=pending — defaults to pending so
    // the admin's review queue is what they see first.
    public function index(Request $request, ListModerationFlagsAction $action): JsonResponse
    {
        $statusParam = $request->query('status', 'pending');
        $status = $statusParam === 'all' ? null : ModerationStatus::tryFrom((string) $statusParam);

        $flags = $action->execute($status, (int) $request->query('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $flags,
        ]);
    }

    // PATCH /v1/admin/moderation/flags/{flag}/resolve
    public function resolve(
        ModerationFlag $flag,
        ResolveModerationFlagRequest $request,
        ResolveModerationFlagAction $action,
    ): JsonResponse {
        $dto = ResolveModerationFlagDTO::fromArray($request->validated());

        $flag = $action->execute($flag, $dto, (int) $request->user()->id);

        return response()->json([
            'success' => true,
            'data' => $flag,
        ]);
    }
}
