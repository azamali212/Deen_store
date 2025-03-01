<?php

namespace App\Http\Controllers\EmailMarket;

use App\Events\EmailStatusUpdated;
use App\Repositories\Email\EmailRepositoryInterface;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class EmailController extends Controller
{
    protected $emailRepository;

    public function __construct(EmailRepositoryInterface $emailRepository)
    {
        $this->emailRepository = $emailRepository;
    }

    // Send an email
    public function sendEmail(Request $request)
    {
        $request->validate([
            'sender_id' => 'required|exists:users,id',
            'receiver_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $email = $this->emailRepository->sendEmail(
            $request->sender_id,
            $request->receiver_id,
            $request->subject,
            $request->body
        );
        event(new EmailStatusUpdated($email->id)); // Fire event with $email

        return response()->json(['email' => $email], 201);
    }

    // Get emails for a specific user
    public function getUserEmails($userId, $status = 'sent')
    {
        $emails = $this->emailRepository->getEmailsForUser($userId, $status);
        return response()->json(['emails' => $emails], 200);
    }

    // Mark an email as read
    public function markAsRead($emailId)
    {
        $this->emailRepository->markAsRead($emailId);
        return response()->json(['message' => 'Email marked as read'], 200);
    }

    // Mark an email as unread
    public function markAsUnread($emailId)
    {
        $this->emailRepository->markAsUnread($emailId);
        return response()->json(['message' => 'Email marked as unread'], 200);
    }

    // Archive an email
    public function archiveEmail($emailId)
    {
        $this->emailRepository->archiveEmail($emailId);
        return response()->json(['message' => 'Email archived'], 200);
    }

    // Unarchive an email
    public function unarchiveEmail($emailId)
    {
        $this->emailRepository->unarchiveEmail($emailId);
        return response()->json(['message' => 'Email unarchived'], 200);
    }

    // Delete an email
    public function deleteEmail($emailId)
    {
        $this->emailRepository->deleteEmail($emailId);
        return response()->json(['message' => 'Email deleted'], 200);
    }
}
