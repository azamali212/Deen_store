<?php

declare(strict_types=1);

namespace App\Domain\Roles\Services;

use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

final class RoleTemplateMailer
{
    /**
     * Send email using notification_templates table.
     *
     * @param array<string,mixed> $vars
     */
    public function send(string $type, string $toEmail, array $vars = [], string $locale = 'en'): void
    {
        $template = NotificationTemplate::query()
            ->where('type', $type)
            ->where('channel', 'mail')
            ->where('locale', $locale)
            ->where('active', true)
            ->first();

        if (!$template) {
            throw new RuntimeException("NotificationTemplate missing for type={$type}, channel=mail, locale={$locale}");
        }

        $subject = $this->render((string) $template->subject_template, $vars);
        $body    = $this->render((string) $template->body_template, $vars);

        Mail::raw($body, function ($message) use ($toEmail, $subject) {
            $message->to($toEmail)->subject($subject);
        });
    }

    /**
     * Very simple {{var}} replacement.
     *
     * @param array<string,mixed> $vars
     */
    private function render(string $text, array $vars): string
    {
        foreach ($vars as $k => $v) {
            $text = str_replace('{{' . $k . '}}', (string) $v, $text);
        }
        return $text;
    }
}