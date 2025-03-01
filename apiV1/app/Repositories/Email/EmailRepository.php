<?php

namespace App\Repositories\Email;

use App\Events\EmailStatusUpdated;
use App\Models\Email;
use App\Models\Email_Status;
use Illuminate\Database\Eloquent\Collection;

class EmailRepository implements EmailRepositoryInterface
{

    /**
     * Send a new email.
     *
     * @param int $senderId
     * @param int $receiverId
     * @param string $subject
     * @param string $body
     * @return Email
     */

     public function sendEmail(string $senderId, string $receiverId, string $subject, string $body): Email
     {
         // Create the email
         $email = Email::create([
             'sender_id' => $senderId,
             'receiver_id' => $receiverId,
             'subject' => $subject,
             'body' => $body,
         ]);
     
         if (!$email) {
             throw new \Exception("Failed to create email.");
         }
     
         // Create the status for the email
         Email_Status::create([
             'email_id' => $email->id,
             'status' => 'sent',
             'read_status' => 'unread',
             'archive_status' => 'unarchived',
         ]);
     
         return $email;
     }

    /**
     * Get all emails for a user based on status (sent or received).
     *
     * @param int $userId
     * @param string $status
     * @return Collection
     */
    public function getEmailsForUser(int $userId, string $status = 'sent'): Collection
    {
        // Query based on whether the user is the sender or receiver
        if ($status === 'sent') {
            return Email::where('sender_id', $userId)->get();
        } elseif ($status === 'received') {
            return Email::where('receiver_id', $userId)->get();
        }

        // Default to sent emails
        return Email::where('sender_id', $userId)->get();
    }

    /**
     * Get a single email by its ID.
     *
     * @param int $emailId
     * @return Email|null
     */

    public function getEmailById(int $emailId): ?Email
    {
        // Find the email by its ID
        return Email::find($emailId);
    }

    /**
     * Mark an email as read.
     *
     * @param int $emailId
     * @return bool
     */
    public function markAsRead(int $emailId): bool
    {
        $emailStatus = Email_Status::where('email_id', $emailId)->first();
        
        if ($emailStatus) {
            $email = $emailStatus->email; // Ensure the email relation exists
            if (!$email) {
                \Log::error("Email record not found for Email_Status ID: {$emailStatus->id}");
                return false;
            }
        
            // Update read status
            $emailStatus->update(['read_status' => 'read']);
            return true;
        }
        
        \Log::error("Email_Status not found for emailId: {$emailId}");
        return false;
    }

    /**
     * Mark an email as unread.
     *
     * @param int $emailId
     * @return bool
     */

    public function markAsUnread(int $emailId): bool
    {
        $emailStatus = Email_Status::where('email_id', $emailId)->first();

        if ($emailStatus) {
            $emailStatus->update(['read_status' => 'unread']);
            return true;
        }

        return false;
    }

    /**
     * Archive an email.
     *
     * @param int $emailId
     * @return bool
     */
    public function archiveEmail(int $emailId): bool
    {
        $emailStatus = Email_Status::where('email_id', $emailId)->first();

        if ($emailStatus) {
            $emailStatus->update(['archive_status' => 'archived']);
            return true;
        }

        return false;
    }

    /**
     * Unarchive an email.
     *
     * @param int $emailId
     * @return bool
     */
    public function unarchiveEmail(int $emailId): bool
    {
        $emailStatus = Email_Status::where('email_id', $emailId)->first();

        if ($emailStatus) {
            $emailStatus->update(['archive_status' => 'unarchived']);
            return true;
        }

        return false;
    }

    /**
     * Delete an email and its status.
     *
     * @param int $emailId
     * @return bool
     */
    public function deleteEmail(int $emailId): bool
    {
        // Delete the email status first
        $emailStatus = Email_Status::where('email_id', $emailId)->first();
        if ($emailStatus) {
            $emailStatus->delete();
        }

        // Now delete the email
        $email = Email::find($emailId);
        if ($email) {
            $email->delete();
            return true;
        }

        return false;
    }

    /**
     * Get all email statuses for a user (both sent and received emails).
     *
     * @param int $userId
     * @return Collection
     */
    public function getEmailStatusesForUser(int $userId): Collection
    {
        return Email_Status::whereHas('email', function ($query) use ($userId) {
            $query->where('sender_id', $userId)
                ->orWhere('receiver_id', $userId);
        })->get();
    }
}
