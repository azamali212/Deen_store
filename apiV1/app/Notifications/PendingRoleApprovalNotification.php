<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingRoleApprovalNotification extends Notification
{
    use Queueable;

    public $pendingRoleRequest;

    public function __construct($pendingRoleRequest)
    {
        $this->pendingRoleRequest = $pendingRoleRequest;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'pending_role_approval',
            'request_id' => $this->pendingRoleRequest->id,
            'role_name' => $this->pendingRoleRequest->name,
            'created_by' => $this->pendingRoleRequest->creator->name ?? 'Unknown', // Fixed null check
            'created_at' => $this->pendingRoleRequest->created_at->format('Y-m-d H:i:s'),
            'message' => 'A new role "'.$this->pendingRoleRequest->name.'" requires your approval.',
            'url' => '/admin/role-requests/'.$this->pendingRoleRequest->id
        ];
    }
}