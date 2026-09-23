<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Domain\User\Actions\ExportUserDataAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DataExportController extends Controller
{
    // GDPR-style "download my data" — the authenticated user's own account,
    // profile, addresses and preferences as a downloadable JSON file.
    public function export(
        Request $request,
        ExportUserDataAction $action,
    ): StreamedResponse {

        $data = $action->execute($request->user());

        $filename = sprintf(
            'zimal-data-export-%d-%s.json',
            $request->user()->id,
            now()->format('Y-m-d'),
        );

        return response()->streamDownload(
            function () use ($data): void {
                echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
            $filename,
            ['Content-Type' => 'application/json'],
        );
    }
}
