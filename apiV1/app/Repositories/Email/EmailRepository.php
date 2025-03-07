<?php

namespace App\Repositories\Email;

use App\Events\EmailDeleted;
use App\Events\EmailStatusUpdated;
use App\Models\Email;
use App\Models\Email_Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;


class EmailRepository implements EmailRepositoryInterface
{
    /**
     * Send a new email.
     *
     * @param string $senderId
     * @param string $receiverId
     * @param string $subject
     * @param string $body
     * @return Email
     */
    public function sendEmail(string $senderId, string $receiverId, string $subject, string $body): Email
    {
        // Fetch sender and receiver emails
        $sender = User::findOrFail($senderId);
        $receiver = User::findOrFail($receiverId);



        // Create the email record
        $email = Email::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'from_email' => $sender->email,  // Store sender's email
            'to_email' => $receiver->email,  // Store receiver's email
            'subject' => $subject,
            'body' => $body,
        ]);

        \Log::debug('Sender email: ' . $sender->email);
        \Log::debug('Receiver email: ' . $receiver->email);
        if (!$email) {
            throw new \Exception("Failed to create email.");
        }

        // Create the email status record
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
     * @param string $userId
     * @param string $status
     * @return Collection
     */
    public function getEmailsForUser(string $userId, string $status = 'sent'): Collection
    {
        if ($status === 'sent') {
            return Email::where('sender_id', $userId)->get();
        } elseif ($status === 'received') {
            return Email::where('receiver_id', $userId)->get();
        }

        return Email::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->get();
    }

    /**
     * Get a single email by its ID.
     *
     * @param int $emailId
     * @return Email|null
     */
    public function getEmailById(int $emailId): ?Email
    {
        return Email::find($emailId);
    }

    /**
     * Find emails by sender or receiver email.
     *
     * @param string $email
     * @return Collection
     */
    public function findEmailsByEmail(string $email): Collection
    {
        return Email::where('from_email', $email)
            ->orWhere('to_email', $email)
            ->get();
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
            $emailStatus->update(['read_status' => 'read']);
            return true;
        }

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
    public function moveToTrash(int $emailId): bool
    {
        $email = Email::find($emailId);

        if ($email) {
            $email->delete();
            event(new EmailDeleted($email)); // Fire event
            return true;
        }

        return false;
    }

    public function restoreEmail(int $emailId): bool
    {
        $email = Email::onlyTrashed()->find($emailId);

        if ($email) {
            $email->restore();
            return true;
        }

        return false;
    }
    public function emptyTrash(int $emailId): bool
    {
        $email = Email::onlyTrashed()->find($emailId);

        if ($email) {
            $email->forceDelete();
            return true;
        }

        return false;
    }

    public function getTrashedEmails(string $userId): Collection
    {
        \Log::debug('Fetching trashed emails for user: ' . $userId); // Log user ID
    
        $trashedEmails = Email::onlyTrashed()
            ->where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->get();
    
        \Log::debug('Found trashed emails: ' . $trashedEmails->count()); // Log count of trashed emails
    
        return $trashedEmails;
    }

    public function deleteEmail(int $emailId): bool
    {
        $emailStatus = Email_Status::where('email_id', $emailId)->first();
        if ($emailStatus) {
            $emailStatus->delete();
        }

        $email = Email::find($emailId);
        if ($email) {
            $email->delete();
            return true;
        }

        return false;
    }

    /**
     * Get all email statuses for a user.
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
