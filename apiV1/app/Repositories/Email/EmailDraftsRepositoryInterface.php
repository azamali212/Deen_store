<?php

namespace App\Repositories\Email;

use Illuminate\Support\Collection;
use App\Models\Email;

interface EmailDraftsRepositoryInterface
{
    /**
     * Save a new draft.
     * Ensures that email drafts are valid and complete.
     */
    public function saveDraft(string $senderId, string $receiverId, string $subject, string $body): Email;

    /**
     * Get all drafts for a specific user.
     * Optionally filter by status or sort drafts.
     */
    public function getDraftsForUser(string $userId, array $filters = []): Collection;

    /**
     * Get a draft by its ID.
     * Checks if the draft belongs to the user.
     */
    public function getDraftById(int $draftId, string $userId): ?Email;

    /**
     * Delete a draft.
     * Checks if the draft belongs to the user before deleting.
     */
    public function deleteDraft(int $draftId, string $userId): bool;

    /**
     * Restore a draft from the trash.
     * Checks if the draft is in the trash and belongs to the user.
     */
    public function restoreDraft(int $draftId, string $userId): bool;

    /**
     * Permanently delete a draft.
     * Empties the trash for a specific draft.
     */
    public function emptyTrash(int $draftId, string $userId): bool;

    /**
     * Move a draft to the trash.
     * Checks if the draft exists and belongs to the user.
     */
    public function moveToTrash(int $draftId, string $userId): bool;

    /**
     * Get all trashed drafts for a user.
     * Optionally filter trashed drafts by certain attributes.
     */
    public function getTrashedDrafts(string $userId, array $filters = []): Collection;

    /**
     * Update the draft status.
     * Allows batch updates for drafts' status (e.g., send, archive, etc.).
     */
    public function updateDraftStatus(array $draftIds, string $status): bool;

    /**
     * Lock or unlock a draft for editing.
     * Prevents other actions on a draft while locked.
     */
    public function lockDraft(int $draftId): bool;

    /**
     * Track history of changes made to a draft (e.g., who edited, when).
     */
    public function trackDraftHistory(int $draftId): Collection;

    /**
     * Validate a draft before saving.
     * Ensures subject and body are provided, sender and receiver exist, etc.
     */
    public function validateDraft(string $senderId, string $receiverId, string $subject, string $body): bool;
}