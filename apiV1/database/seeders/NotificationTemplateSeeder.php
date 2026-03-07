<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Notifications\Enums\NotificationChannel;
use App\Domain\Notifications\NotificationTypes;
use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

final class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'type' => NotificationTypes::LOGIN_OTP_SENT,
                'channel' => NotificationChannel::MAIL->value,
                'locale' => 'en',
                'subject_template' => 'Your login verification code',
                'body_template' => 'Use OTP {{otp_code}}. It expires at {{expires_at}}.',
            ],
            [
                'type' => 'login.alert',
                'channel' => 'mail',
                'locale' => 'en',
                'subject_template' => 'New Login Detected',
                'body_template' => "
             Hello {{user_name}},

              We detected a new login to your account.

                IP Address: {{ip}}
                Device: {{device}}
                Time: {{login_time}}

If this was not you, please secure your account immediately.
"
            ],

            [
                'type' => NotificationTypes::LOGIN_OTP_SENT,
                'channel' => NotificationChannel::BROADCAST->value,
                'locale' => 'en',
                'subject_template' => 'OTP sent',
                'body_template' => 'OTP {{otp_code}} expires at {{expires_at}}.',
            ],

            [
                'type' => 'role.requested',
                'channel' => 'mail',
                'locale' => 'en',
                'subject_template' => 'Role approval required: {{role_name}}',
                'body_template' => "A new role has been requested.\n\nRole: {{role_name}}\nRequested by: {{requested_by_email}}\n\nPlease approve/reject in admin panel.",
            ],

            [
                'type' => 'role.approved',
                'channel' => 'mail',
                'locale' => 'en',
                'subject_template' => 'Role approved: {{role_name}}',
                'body_template' => "Your role request has been approved.\n\nRole: {{role_name}}\nApproved by: {{reviewed_by_email}}",
            ],
            [
                'type' => 'role.rejected',
                'channel' => 'mail',
                'locale' => 'en',
                'subject_template' => 'Role rejected: {{role_name}}',
                'body_template' => "Your role request has been rejected.\n\nRole: {{role_name}}\nRejected by: {{reviewed_by_email}}\nReason: {{rejection_reason}}",
            ]
        ];

        foreach ($templates as $t) {
            NotificationTemplate::query()->updateOrCreate(
                [
                    'type' => $t['type'],
                    'channel' => $t['channel'],
                    'locale' => $t['locale'],
                ],
                [
                    'subject_template' => $t['subject_template'],
                    'body_template' => $t['body_template'],
                    'active' => true,
                    'version' => 1,
                ]
            );
        }
    }
}
