<?php

declare(strict_types=1);

namespace App\Domain\Auth\Support;

use Illuminate\Http\Request;
use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Auth\Enums\LoginProvider;

final class LoginContextResolver
{
    public function resolve(
        Request $request, //Collect the request data
        AuthPanel $panel,//Collect the panel data like admin, user, etc
        LoginProvider $provider //Collect the provider data like google, facebook, Password etc
    ): array { //Associative array to return the data
        return [
            'ip_address' => $this->resolveIpAddress($request),
            'user_agent' => $this->resolveUserAgent($request),
            'device_name' => $this->resolveDevice($request),
            'browser' => $this->resolveBrowser($request),
            'operating_system' => $this->resolveOperatingSystem($request),
            'panel' => $panel->value,
            'provider' => $provider->value,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    private function resolveIpAddress(Request $request): string
    {
        return $request->ip() ?? 'unknown';
    }

    private function resolveUserAgent(Request $request): string
    {
        return $request->userAgent() ?? 'unknown';
    }

    private function resolveDevice(Request $request): string
    {
        $agent = strtolower($request->userAgent() ?? '');

        return match (true) {
            str_contains($agent, 'iphone') => 'iPhone',
            str_contains($agent, 'ipad') => 'iPad',
            str_contains($agent, 'android') => 'Android Device',
            str_contains($agent, 'macintosh') => 'Mac',
            str_contains($agent, 'windows') => 'Windows PC',
            default => 'Unknown Device',
        };
    }

    private function resolveBrowser(Request $request): string
    {
        $agent = strtolower($request->userAgent() ?? '');

        return match (true) {
            str_contains($agent, 'chrome') => 'Chrome',
            str_contains($agent, 'firefox') => 'Firefox',
            str_contains($agent, 'safari') => 'Safari',
            str_contains($agent, 'edg') => 'Edge',
            default => 'Unknown Browser',
        };
    }

    private function resolveOperatingSystem(Request $request): string
    {
        $agent = strtolower($request->userAgent() ?? '');

        return match (true) {
            str_contains($agent, 'windows') => 'Windows',
            str_contains($agent, 'mac os') => 'MacOS',
            str_contains($agent, 'android') => 'Android',
            str_contains($agent, 'iphone') => 'iOS',
            str_contains($agent, 'linux') => 'Linux',
            default => 'Unknown OS',
        };
    }
}