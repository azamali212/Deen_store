<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use Illuminate\Http\Request;

final class DeviceFingerprintService
{
    public function generate(
        ?string $ipAddress,
        ?string $userAgent,
        ?string $deviceName,
        string $panel
    ): string {
        return hash(
            'sha256',
            implode('|', [
                $ipAddress ?? '',
                $userAgent ?? '',
                $deviceName ?? '',
                $panel,
            ])
        );
    }

    public function context(Request $request): array
    {
        return [
            'ip_address' => $this->ipAddress($request),
            'user_agent' => $this->userAgent($request),
            'browser' => $this->browser($request),
            'operating_system' => $this->operatingSystem($request),
            'device_name' => $this->deviceName($request),
        ];
    }

    public function ipAddress(Request $request): string
    {
        return (string) $request->ip();
    }

    public function userAgent(Request $request): string
    {
        return (string) $request->userAgent();
    }

    public function browser(Request $request): string
    {
        $agent = strtolower($this->userAgent($request));

        return match (true) {
            str_contains($agent, 'edg') => 'edge',
            str_contains($agent, 'chrome') => 'chrome',
            str_contains($agent, 'firefox') => 'firefox',
            str_contains($agent, 'safari') => 'safari',
            default => 'unknown',
        };
    }

    public function operatingSystem(Request $request): string
    {
        $agent = strtolower($this->userAgent($request));

        return match (true) {
            str_contains($agent, 'windows') => 'windows',
            str_contains($agent, 'mac os') => 'macos',
            str_contains($agent, 'iphone') => 'ios',
            str_contains($agent, 'android') => 'android',
            str_contains($agent, 'linux') => 'linux',
            default => 'unknown',
        };
    }

    public function deviceName(Request $request): string
    {
        $agent = strtolower($this->userAgent($request));

        return match (true) {
            str_contains($agent, 'iphone') => 'iphone',
            str_contains($agent, 'ipad') => 'ipad',
            str_contains($agent, 'android') => 'android-device',
            str_contains($agent, 'mac os') => 'macbook',
            str_contains($agent, 'windows') => 'windows-pc',
            default => 'unknown-device',
        };
    }
}
