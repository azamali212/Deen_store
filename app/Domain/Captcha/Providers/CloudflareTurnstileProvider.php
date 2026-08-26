<?php

declare(strict_types=1);

namespace App\Domain\Captcha\Providers;

use App\Domain\Captcha\Contracts\CaptchaProviderInterface;
use App\Domain\Captcha\DTO\VerifyCaptchaDTO;
use Illuminate\Support\Facades\Http;

final readonly class CloudflareTurnstileProvider implements CaptchaProviderInterface
{
    public function verify(
        VerifyCaptchaDTO $dto,
    ): bool {

        $response = Http::asForm()
            ->post(
                config('captcha.turnstile.verify_url'),
                [
                    'secret' => config('captcha.turnstile.secret_key'),

                    'response' => $dto->token,

                    'remoteip' => $dto->ipAddress,
                ],
            );

        if (! $response->successful()) {
            return false;
        }

        return (bool) $response->json(
            'success',
        );
    }
}
