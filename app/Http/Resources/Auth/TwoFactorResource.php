<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TwoFactorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(
        Request $request,
    ): array {

        return [
            'success' => true,

            'message' => 'Two-factor authentication initialized.',

            'data' => [

                'secret' => $this['secret'],

                'qr_code_uri' => $this['qr_code_uri'],

                'provider' => $this['provider'],

            ],
        ];
    }
}
