<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingRoleStatusNotification extends Notification
{
    use Queueable;

    public $pendingRoleRequest;
    public $status;
    public $approver;

    public function __construct($pendingRoleRequest, $status, $approver = null)
    {
        $this->pendingRoleRequest = $pendingRoleRequest;
        $this->status = $status;
        $this->approver = $approver;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        if ($this->status === 'pending') {
            return [
                'type' => 'role_submitted',
                'request_id' => $this->pendingRoleRequest->id,
                'role_name' => $this->pendingRoleRequest->name,
                'message' => 'Your role "'.$this->pendingRoleRequest->name.'" has been submitted for approval.',
                'url' => '/admin/my-role-requests'
            ];
        } elseif ($this->status === 'approved') {
            return [
                'type' => 'pending_role_approved',
                'request_id' => $this->pendingRoleRequest->id,
                'role_name' => $this->pendingRoleRequest->name,
                'approved_by' => $this->approver?->name ?? 'System',
                'approved_at' => $this->pendingRoleRequest->approved_at ? $this->pendingRoleRequest->approved_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                'message' => 'Your role request "'.$this->pendingRoleRequest->name.'" has been approved!',
                'url' => '/admin/roles'
            ];
        } else {
            return [
                'type' => 'pending_role_rejected',
                'request_id' => $this->pendingRoleRequest->id,
                'role_name' => $this->pendingRoleRequest->name,
                'rejection_reason' => $this->pendingRoleRequest->rejection_reason,
                'rejected_by' => $this->approver?->name ?? 'System',
                'rejected_at' => $this->pendingRoleRequest->rejected_at ? $this->pendingRoleRequest->rejected_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                'message' => 'Your role request "'.$this->pendingRoleRequest->name.'" has been rejected.',
                'url' => '/admin/my-role-requests'
            ];
        }
    }
}