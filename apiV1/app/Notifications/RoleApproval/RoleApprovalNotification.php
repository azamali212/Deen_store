<?php

namespace App\Notifications\RoleApproval;

use App\Models\PendingRoleRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class RoleApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $request;
    public $channels;

    public function __construct(PendingRoleRequest $request, array $channels = null)
    {
        $this->request = $request;
        $this->channels = $channels ?? ['database', 'mail'];
    }

    public function via($notifiable): array
    {
        // Filter out unavailable channels
        return array_filter($this->channels, function ($channel) {
            if ($channel === 'broadcast' && !config('broadcasting.default')) {
                return false;
            }
            return true;
        });
    }

    public function toMail($notifiable): MailMessage
    {
        $url = url('/admin/role-requests/' . $this->request->id);
        
        return (new MailMessage)
            ->subject('🔔 New Role Request Requires Approval')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A new role creation request requires your approval.')
            ->line(new HtmlString('<strong>Role Name:</strong> ' . $this->request->name))
            ->line(new HtmlString('<strong>Requested By:</strong> ' . $this->request->creator->email))
            ->line(new HtmlString('<strong>Submitted:</strong> ' . $this->request->created_at->diffForHumans()))
            ->action('Review Request', $url)
            ->line('Please review this request at your earliest convenience.')
            ->salutation('Best Regards,<br>'. config('app.name'));
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'role_approval_request',
            'request_id' => $this->request->id,
            'role_name' => $this->request->name,
            'creator_email' => $this->request->creator->email ?? 'Unknown',
            'created_at' => $this->request->created_at->toDateTimeString(),
            'message' => 'New role request requires approval',
            'action_url' => '/admin/role-requests/' . $this->request->id,
            'priority' => 'high',
            'icon' => 'shield-check',
        ];
    }

    public function toSlack($notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->from(config('app.name') . ' Bot')
            ->to('#' . config('services.slack.channel'))
            ->content('New role request requires approval')
            ->attachment(function ($attachment) {
                $attachment->title('Role Request Details', url('/admin/role-requests/' . $this->request->id))
                    ->fields([
                        'Role Name' => $this->request->name,
                        'Requested By' => $this->request->creator->email ?? 'Unknown',
                        'Submitted' => $this->request->created_at->diffForHumans(),
                        'Permissions' => count($this->request->permission_names ?? []) . ' permissions',
                    ])
                    ->color('#3498db')
                    ->markdown(['text']);
            });
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'role_approval_request',
            'request_id' => $this->request->id,
            'role_name' => $this->request->name,
            'creator' => $this->request->creator->email ?? 'Unknown',
            'created_at' => $this->request->created_at->toDateTimeString(),
            'message' => 'New role request requires your approval',
            'url' => '/admin/role-requests/' . $this->request->id,
        ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'request_id' => $this->request->id,
            'role_name' => $this->request->name,
            'message' => 'New role request requires approval',
        ];
    }
}