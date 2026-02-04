<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Models\PendingRoleRequest;
use App\Notifications\RoleApproval\RoleApprovalNotification;
use App\Notifications\RoleApproval\RoleStatusNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RoleNotificationService
{
    private $channels = ['database', 'mail']; // Default channels

    public function __construct()
    {
        // Load preferred channels from config
        $channels = config('notification.channels', ['database', 'mail']);

        // Filter out unavailable channels
        $this->channels = array_filter($channels, function ($channel) {
            if ($channel === 'slack') {
                return class_exists(\Illuminate\Notifications\Messages\SlackMessage::class);
            }
            if ($channel === 'broadcast') {
                return config('broadcasting.default') !== null;
            }
            return true;
        });
    }

    /**
     * Notify super admins about new role request
     */
    public function notifySuperAdmins(PendingRoleRequest $request, array $channels = null): bool
    {
        try {
            \Log::info('Attempting to notify super admins', [
                'request_id' => $request->id,
                'role_name' => $request->name,
                'creator_id' => $request->created_by
            ]);

            $channels = $channels ?? $this->channels;
            $superAdmins = $this->getSuperAdmins();

            \Log::info('Found super admins', [
                'count' => $superAdmins->count(),
                'admins' => $superAdmins->pluck('email')->toArray()
            ]);

            if ($superAdmins->isEmpty()) {
                \Log::warning('No super admins found to notify about role request', [
                    'request_id' => $request->id,
                    'creator_email' => $request->creator->email ?? 'Unknown'
                ]);
                return false;
            }

            // Log what channels will be used
            \Log::info('Notification channels', [
                'channels' => $channels
            ]);

            $notification = new RoleApprovalNotification($request, $channels);

            // Send notification through all channels
            Notification::send($superAdmins, $notification);

            // Mark as notified
            $request->markAsNotified();

            // Log notification
            \Log::info('Role notification sent successfully', [
                'request_id' => $request->id,
                'type' => 'super_admins',
                'recipients' => $superAdmins->count(),
                'channels' => $channels
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to notify super admins', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Notify creator about status update
     */
    /**
     * Notify creator about status update
     */
    public function notifyCreator(PendingRoleRequest $request, string $status, User $approver = null): bool
    {
        try {
            if (!$request->creator) {
                Log::warning('Creator not found for role request notification', [
                    'request_id' => $request->id
                ]);
                return false;
            }

            $notification = new RoleStatusNotification($request, $status, $approver);

            // Get creator's preferred channels
            $channels = $this->getUserChannels($request->creator);

            // Set the channels on the notification if needed
            // Some notification classes accept channels in constructor
            // OR modify the notification to handle channels differently

            // Just pass the notification object, Laravel handles the rest
            $request->creator->notify($notification);

            // Send additional alerts if needed
            $this->sendAdditionalAlerts($request, $status);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to notify creator', [
                'request_id' => $request->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get super admins with caching
     */
    private function getSuperAdmins()
    {
        $cacheKey = 'super_admins_list';
        $cacheTime = config('notification.cache_time', 300); // 5 minutes

        return Cache::remember($cacheKey, $cacheTime, function () {
            return User::role('Super Admin', 'api')
                ->where('active', true)
                ->where('email_verified_at', '!=', null)
                ->get();
        });
    }

    /**
     * Get user's preferred notification channels
     */
    private function getUserChannels(User $user): array
    {
        // Check if notificationPreferences relationship exists
        if (method_exists($user, 'notificationPreferences')) {
            $preferences = $user->notificationPreferences ?? collect();
        } else {
            $preferences = collect();
        }

        if ($preferences->isEmpty()) {
            return $this->channels;
        }

        return $preferences->where('enabled', true)
            ->pluck('channel')
            ->intersect($this->channels)
            ->toArray();
    }

    /**
     * Send additional alerts (Slack, SMS, etc.)
     */
    private function sendAdditionalAlerts(PendingRoleRequest $request, string $status): void
    {
        $config = config('notification.alerts', []);

        // Slack notification for urgent requests
        if (in_array('slack', $config) && $request->escalated) {
            $this->sendSlackAlert($request, $status);
        }

        // SMS notification for critical roles
        if (in_array('sms', $config) && $this->isCriticalRole($request)) {
            $this->sendSmsAlert($request, $status);
        }
    }

    /**
     * Send Slack alert
     */
    private function sendSlackAlert(PendingRoleRequest $request, string $status): void
    {
        try {
            // Check if SlackMessage class exists
            if (!class_exists(\Illuminate\Notifications\Messages\SlackMessage::class)) {
                Log::warning('Slack notification package not installed');
                return;
            }

            $webhookUrl = config('services.slack.webhook_url');

            if ($webhookUrl) {
                Http::post($webhookUrl, [
                    'text' => "Role Request {$status}: {$request->name}",
                    'attachments' => [[
                        'title' => 'Role Details',
                        'fields' => [
                            ['title' => 'Request ID', 'value' => $request->id, 'short' => true],
                            ['title' => 'Creator', 'value' => $request->creator->email ?? 'Unknown', 'short' => true],
                            ['title' => 'Status', 'value' => ucfirst($status), 'short' => true],
                            ['title' => 'Duration', 'value' => $request->duration, 'short' => true],
                        ]
                    ]]
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send Slack alert', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send SMS alert (placeholder - implement based on your SMS provider)
     */
    private function sendSmsAlert(PendingRoleRequest $request, string $status): void
    {
        try {
            // Get super admins for SMS
            $superAdmins = User::role('Super Admin', 'api')
                ->where('active', true)
                ->whereNotNull('phone') // Only users with phone numbers
                ->get();

            foreach ($superAdmins as $admin) {
                // This is a placeholder - implement based on your SMS provider
                // Example: Twilio, Nexmo, etc.
                Log::info('SMS alert would be sent to:', [
                    'phone' => $admin->phone,
                    'message' => "URGENT: Role '{$request->name}' {$status}. Requires attention."
                ]);

                // Example with a hypothetical SMS service:
                // $smsService = app(SmsService::class);
                // $smsService->send($admin->phone, "URGENT: Role '{$request->name}' {$status}. Requires attention.");
            }
        } catch (\Exception $e) {
            Log::error('Failed to send SMS alert', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Check if role is critical
     */
    private function isCriticalRole(PendingRoleRequest $request): bool
    {
        $criticalPermissions = config('notification.critical_permissions', []);
        $permissions = $request->permission_names ?? [];

        return !empty(array_intersect($criticalPermissions, $permissions));
    }

    /**
     * Log notification for auditing
     */
    private function logNotification(PendingRoleRequest $request, string $type, int $recipientCount): void
    {
        // Make sure notifications channel exists in logging config
        Log::info('Role notification sent', [
            'request_id' => $request->id,
            'type' => $type,
            'recipients' => $recipientCount,
            'timestamp' => now(),
            'channels' => $this->channels
        ]);
    }

    /**
     * Escalate overdue requests
     */
    public function escalateOverdueRequests(int $hours = 48): int
    {
        $overdueRequests = PendingRoleRequest::overdue($hours)->get();
        $escalatedCount = 0;

        foreach ($overdueRequests as $request) {
            if (!$request->escalated) {
                $request->escalate();

                // Use channels that are available
                $channels = ['mail'];
                if (in_array('slack', $this->channels)) {
                    $channels[] = 'slack';
                }

                $this->notifySuperAdmins($request, $channels);
                $escalatedCount++;
            }
        }

        return $escalatedCount;
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats(): array
    {
        return [
            'total_requests' => PendingRoleRequest::count(),
            'pending_notifications' => PendingRoleRequest::needsNotification()->count(),
            'overdue_requests' => PendingRoleRequest::overdue()->count(),
            'escalated_requests' => PendingRoleRequest::where('escalated', true)->count(),
            'avg_response_time' => $this->calculateAverageResponseTime(),
        ];
    }

    /**
     * Calculate average response time for approved requests
     */
    private function calculateAverageResponseTime(): string
    {
        $approvedRequests = PendingRoleRequest::approved()
            ->whereNotNull('approved_at')
            ->get();

        if ($approvedRequests->isEmpty()) {
            return 'N/A';
        }

        $totalSeconds = $approvedRequests->sum(function ($request) {
            return $request->created_at->diffInSeconds($request->approved_at);
        });

        $averageSeconds = $totalSeconds / $approvedRequests->count();

        return now()->diffForHumans(now()->subSeconds($averageSeconds), true);
    }

    /**
     * Resend notifications for a specific request
     */
    public function resendNotifications(PendingRoleRequest $request): bool
    {
        if (!$request->isPending()) {
            Log::warning('Cannot resend notifications for non-pending request', [
                'request_id' => $request->id,
                'status' => $request->status
            ]);
            return false;
        }

        return $this->notifySuperAdmins($request);
    }

    /**
     * Get requests that need notification reminders
     */
    public function getRequestsNeedingReminder(int $hours = 24)
    {
        return PendingRoleRequest::pending()
            ->where(function ($query) use ($hours) {
                $query->whereNull('notified_at')
                    ->orWhere('notified_at', '<', now()->subHours($hours));
            })
            ->where('notification_attempts', '<', 3)
            ->get();
    }
}
