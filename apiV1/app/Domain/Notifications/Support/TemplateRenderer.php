<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Support;

final class TemplateRenderer
{
    /**
     * @param array<string, mixed> $payload
     */
    public function render(string $template, array $payload): string
    {
        return (string) preg_replace_callback('/{{\s*([\w\.]+)\s*}}/', function (array $matches) use ($payload): string {
            $value = $this->extract($payload, $matches[1]);
            return is_scalar($value) ? (string) $value : '';
        }, $template);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extract(array $payload, string $path): mixed
    {
        $segments = explode('.', $path);
        $value = $payload;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}