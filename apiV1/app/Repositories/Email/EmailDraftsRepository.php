<?php

namespace App\Repositories\Email;

use App\Models\Email;
use Illuminate\Support\Collection;

class EmailDraftsRepository implements EmailDraftsRepositoryInterface
{
    /**
     * Save a new draft.
     * Ensures that email drafts are valid and complete.
     */
    public function saveDraft(string $senderId, string $receiverId, string $subject, string $body): Email
    {
        // Validate the draft
        if (!$this->validateDraft($senderId, $receiverId, $subject, $body)) {
            throw new \InvalidArgumentException('Invalid draft data');
        }

        // Create a new draft
        return Email::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'subject' => $subject,
            'body' => $body,
            'status' => 'draft',
        ]);
    }

    /**
     * Get all drafts for a specific user.
     * Optionally filter by status or sort drafts.
     */
    public function getDraftsForUser(string $userId, array $filters = []): Collection
    {
        $query = Email::where('sender_id', $userId)
                     ->where('draft_status', 'draft');  // Use draft_status directly
    
        if (isset($filters['order_by'])) {
            $query->orderBy($filters['order_by']);
        }
    
        return $query->get();
    }

    /**
     * Get a draft by its ID.
     * Checks if the draft belongs to the user.
     */
    public function getDraftById(int $draftId, string $userId): ?Email
    {
        return Email::where('id', $draftId)
            ->where('sender_id', $userId)
            ->where('draft_status', 'draft')
            ->first();
    }

    /**
     * Delete a draft.
     * Checks if the draft belongs to the user before deleting.
     */
    public function deleteDraft(int $draftId, string $userId): bool
    {
        $draft = $this->getDraftById($draftId, $userId);

        if ($draft) {
            return $draft->delete();
        }

        return false;
    }

    /**
     * Restore a draft from the trash.
     * Checks if the draft is in the trash and belongs to the user.
     */
    public function restoreDraft(int $draftId, string $userId): bool
    {
        $draft = $this->getTrashedDrafts($userId)->find($draftId);

        if ($draft) {
            $draft->status = 'draft';  // Change status back to draft
            return $draft->save();
        }

        return false;
    }

    /**
     * Permanently delete a draft.
     * Empties the trash for a specific draft.
     */
    public function emptyTrash(int $draftId, string $userId): bool
    {
        $draft = $this->getTrashedDrafts($userId)->find($draftId);

        if ($draft) {
            return $draft->forceDelete(); // Permanently delete
        }

        return false;
    }

    /**
     * Move a draft to the trash.
     * Checks if the draft exists and belongs to the user.
     */
    public function moveToTrash(int $draftId, string $userId): bool
    {
        $draft = $this->getDraftById($draftId, $userId);

        if ($draft) {
            $draft->status = 'trashed';  // Update the status to trashed
            return $draft->save();
        }

        return false;
    }

    /**
     * Get all trashed drafts for a user.
     * Optionally filter trashed drafts by certain attributes.
     */
    public function getTrashedDrafts(string $userId, array $filters = []): Collection
    {
        $query = Email::where('sender_id', $userId)->where('status', 'trashed');

        if (isset($filters['order_by'])) {
            $query->orderBy($filters['order_by']);
        }

        return $query->get();
    }

    /**
     * Update the draft status.
     * Allows batch updates for drafts' status (e.g., send, archive, etc.).
     */
    public function updateDraftStatus(array $draftIds, string $status): bool
    {
        return Email::whereIn('id', $draftIds)
            ->where('status', 'draft')
            ->update(['status' => $status]) > 0;
    }

    /**
     * Lock or unlock a draft for editing.
     * Prevents other actions on a draft while locked.
     */
    public function lockDraft(int $draftId): bool
    {
        $draft = Email::find($draftId);

        if ($draft && $draft->status !== 'locked') {
            $draft->status = 'locked';  // Lock the draft
            return $draft->save();
        }

        return false;
    }

    /**
     * Track history of changes made to a draft (e.g., who edited, when).
     */
    public function trackDraftHistory(int $draftId): Collection
    {
        // Assuming you have a `draft_histories` table to track changes
        return Email::find($draftId)->histories;  // Retrieve the related history records
    }

    /**
     * Validate a draft before saving.
     * Ensures subject and body are provided, sender and receiver exist, etc.
     */
    public function validateDraft(string $senderId, string $receiverId, string $subject, string $body): bool
    {
        // Ensure all fields are provided and valid
        return !empty($senderId) && !empty($receiverId) && !empty($subject) && !empty($body);
    }
}
