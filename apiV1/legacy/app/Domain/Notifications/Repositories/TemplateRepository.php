<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Repositories;

use App\Domain\Notifications\Enums\NotificationChannel;
use App\Models\NotificationTemplate;

final class TemplateRepository
{
    public function resolve(string $type, NotificationChannel $channel, string $locale = 'en'): ?NotificationTemplate
    {
        $query = NotificationTemplate::query()
            ->where('type', $type)
            ->where('channel', $channel->value)
            ->where('active', true);

        return $query->where('locale', $locale)->first()
            ?? $query->where('locale', 'en')->first();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function upsert(array $attributes): NotificationTemplate
    {
        $payload = [
            'subject_template' => $attributes['subject_template'] ?? null,
            'body_template' => $attributes['body_template'],
            'active' => $attributes['active'] ?? true,
            'version' => $attributes['version'] ?? 1,
        ];

        return NotificationTemplate::query()->updateOrCreate(
            [
                'type' => $attributes['type'],
                'channel' => $attributes['channel'],
                'locale' => $attributes['locale'] ?? 'en',
            ],
            $payload,
        );
    }
}