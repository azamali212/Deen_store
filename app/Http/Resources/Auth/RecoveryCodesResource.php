<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RecoveryCodesResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(
        Request $request,
    ): array {

        return [
            'success' => true,

            'message' => 'Recovery codes generated successfully.',

            'data' => [

                'recovery_codes' => $this['recovery_codes'],

            ],
        ];
    }
}
