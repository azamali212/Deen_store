<?php

namespace App\Notifications\RoleApproval;

use App\Models\PendingRoleRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class RoleStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $request;
    public $status;
    public $approver;

    public function __construct(PendingRoleRequest $request, string $status, User $approver = null)
    {
        $this->request = $request;
        $this->status = $status;
        $this->approver = $approver;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $subject = $this->status === 'approved' 
            ? '✅ Role Request Approved'
            : '❌ Role Request Rejected';

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->name . '!');

        if ($this->status === 'approved') {
            $message->line('Your role request has been approved!')
                ->line(new HtmlString('<strong>Role Name:</strong> ' . $this->request->name))
                ->line(new HtmlString('<strong>Approved By:</strong> ' . $this->approver->email))
                ->line(new HtmlString('<strong>Approved At:</strong> ' . $this->request->approved_at->format('Y-m-d H:i:s')))
                ->line('The role is now active and can be assigned to users.')
                ->action('View Role', url('/roles/' . $this->request->slug));
        } else {
            $message->line('Your role request has been rejected.')
                ->line(new HtmlString('<strong>Role Name:</strong> ' . $this->request->name))
                ->line(new HtmlString('<strong>Rejected By:</strong> ' . $this->approver->email))
                ->line(new HtmlString('<strong>Rejected At:</strong> ' . $this->request->rejected_at->format('Y-m-d H:i:s')))
                ->line(new HtmlString('<strong>Reason:</strong> ' . $this->request->rejection_reason))
                ->line('Please review the rejection reason and submit a new request if needed.');
        }

        return $message->salutation('Best Regards,<br>'. config('app.name'));
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'role_request_' . $this->status,
            'request_id' => $this->request->id,
            'role_name' => $this->request->name,
            'status' => $this->status,
            'approver_email' => $this->approver->email ?? null,
            'reason' => $this->request->rejection_reason,
            'action_url' => '/my-role-requests',
            'icon' => $this->status === 'approved' ? 'check-circle' : 'x-circle',
            'color' => $this->status === 'approved' ? 'success' : 'danger',
        ];
    }

    public function toArray($notifiable): array
    {
        return [
            'request_id' => $this->request->id,
            'role_name' => $this->request->name,
            'status' => $this->status,
            'message' => $this->status === 'approved' 
                ? 'Your role request has been approved'
                : 'Your role request has been rejected',
        ];
    }
}