<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notification;

use App\Domain\Notifications\Repositories\TemplateRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\UpsertTemplateRequest;
use Illuminate\Http\JsonResponse;

final class NotificationTemplateController extends Controller
{
    public function upsert(UpsertTemplateRequest $request, TemplateRepository $templateRepository): JsonResponse
    {
        $template = $templateRepository->upsert($request->validated());

        return response()->json([
            'id' => $template->id,
            'type' => $template->type,
            'channel' => $template->channel,
            'locale' => $template->locale,
            'version' => $template->version,
            'active' => $template->active,
        ]);
    }
}