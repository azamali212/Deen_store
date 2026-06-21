<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events\Concerns;

trait HasAuthContext
{
    public function ipAddress(): ?string
    {
        return property_exists($this, 'ipAddress')
            ? $this->ipAddress
            : null;
    }

    public function userAgent(): ?string
    {
        return property_exists($this, 'userAgent')
            ? $this->userAgent
            : null;
    }

    public function deviceName(): ?string
    {
        return property_exists($this, 'deviceName')
            ? $this->deviceName
            : null;
    }

    public function browser(): ?string
    {
        return property_exists($this, 'browser')
            ? $this->browser
            : null;
    }

    public function operatingSystem(): ?string
    {
        return property_exists($this, 'operatingSystem')
            ? $this->operatingSystem
            : null;
    }

    public function panel(): ?string
    {
        if (! property_exists($this, 'panel')) {
            return null;
        }

        return $this->panel?->value;
    }
}