<?php

namespace App\Http\Controllers\EmailMarket;

use App\Events\EmailStatusUpdated;
use App\Repositories\Email\EmailRepositoryInterface;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class EmailController extends Controller
{
    protected $emailRepository;

    public function __construct(EmailRepositoryInterface $emailRepository)
    {
        $this->emailRepository = $emailRepository;
        //$this->middleware('auth');
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
    public function getEmailsForUser(string $userId, string $status = 'sent'): JsonResponse
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
    public function moveToTrash($id)
    {
        return response()->json([
            'success' => $this->emailRepository->moveToTrash($id),
            'message' => 'Email moved to Trash',
        ]);
    }

    public function restoreEmail($id)
    {
        return response()->json([
            'success' => $this->emailRepository->restoreEmail($id),
            'message' => 'Email restored successfully',
        ]);
    }

    public function emptyTrash($id)
    {
        return response()->json([
            'success' => $this->emailRepository->emptyTrash($id),
            'message' => 'Email permanently deleted',
        ]);
    }

    public function getTrashedEmails($userId)
    {
        $trashedEmails = $this->emailRepository->getTrashedEmails($userId);

        // If no trashed emails are found, return a message
        if ($trashedEmails->isEmpty()) {
            return response()->json(['message' => 'No trashed emails found.'], 404);
        }

        return response()->json([
            'emails' => $trashedEmails
        ], 200);
    }
}
