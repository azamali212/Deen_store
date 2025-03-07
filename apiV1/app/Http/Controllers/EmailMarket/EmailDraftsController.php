<?php

namespace App\Http\Controllers\EmailMarket;

use App\Http\Controllers\Controller;
use App\Repositories\Email\EmailDraftsRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use App\Models\Email;
use Illuminate\Validation\ValidationException;

class EmailDraftsController extends Controller
{
    protected $emailDraftsRepository;

    public function __construct(EmailDraftsRepositoryInterface $emailDraftsRepository)
    {
        $this->emailDraftsRepository = $emailDraftsRepository;
    }

    /**
     * Save a new draft.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'sender_id' => 'required|string',
            'receiver_id' => 'required|string',
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);

        try {
            $draft = $this->emailDraftsRepository->saveDraft(
                $validatedData['sender_id'],
                $validatedData['receiver_id'],
                $validatedData['subject'],
                $validatedData['body']
            );

            return response()->json([
                'message' => 'Draft created successfully',
                'data' => $draft
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get all drafts for a specific user.
     */
    public function index(Request $request, string $userId)
    {
        $filters = $request->all();
        $drafts = $this->emailDraftsRepository->getDraftsForUser($userId, $filters);

        return response()->json([
            'message' => 'Drafts retrieved successfully',
            'data' => $drafts
        ], Response::HTTP_OK);
    }

    /**
     * Get a draft by its ID.
     */
    public function show($userId, $draftId = null)
    {
        if ($draftId) {
            // Fetch a specific draft by its ID
            $draft = $this->emailDraftsRepository->getDraftById($draftId, $userId);

            if (!$draft) {
                return response()->json(['message' => 'Draft not found'], 404);
            }

            return response()->json($draft);
        } else {
            // Fetch all drafts for the user
            $drafts = $this->emailDraftsRepository->getDraftsForUser($userId);

            return response()->json($drafts);
        }
    }

    /**
     * Delete a draft.
     */
    public function destroy(int $draftId, string $userId)
    {
        $deleted = $this->emailDraftsRepository->deleteDraft($draftId, $userId);

        if ($deleted) {
            return response()->json([
                'message' => 'Draft deleted successfully'
            ], Response::HTTP_OK);
        }

        return response()->json([
            'message' => 'Draft not found or cannot be deleted'
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * Restore a draft from the trash.
     */
    public function restore(int $draftId, string $userId)
    {
        $restored = $this->emailDraftsRepository->restoreDraft($draftId, $userId);

        if ($restored) {
            return response()->json([
                'message' => 'Draft restored successfully'
            ], Response::HTTP_OK);
        }

        return response()->json([
            'message' => 'Draft not found or cannot be restored'
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * Permanently delete a draft.
     */
    public function emptyTrash(int $draftId, string $userId)
    {
        $deleted = $this->emailDraftsRepository->emptyTrash($draftId, $userId);

        if ($deleted) {
            return response()->json([
                'message' => 'Draft permanently deleted'
            ], Response::HTTP_OK);
        }

        return response()->json([
            'message' => 'Draft not found or cannot be permanently deleted'
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * Move a draft to the trash.
     */
    public function moveToTrash(int $draftId, string $userId)
    {
        $moved = $this->emailDraftsRepository->moveToTrash($draftId, $userId);

        if ($moved) {
            return response()->json([
                'message' => 'Draft moved to trash'
            ], Response::HTTP_OK);
        }

        return response()->json([
            'message' => 'Draft not found or cannot be moved to trash'
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * Update the draft status.
     */
    public function updateStatus(Request $request)
    {
        $validatedData = $request->validate([
            'draft_ids' => 'required|array',
            'status' => 'required|string',
        ]);

        $updated = $this->emailDraftsRepository->updateDraftStatus($validatedData['draft_ids'], $validatedData['status']);

        if ($updated) {
            return response()->json([
                'message' => 'Draft status updated successfully'
            ], Response::HTTP_OK);
        }

        return response()->json([
            'message' => 'Failed to update draft status'
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Lock a draft for editing.
     */
    public function lock(int $draftId)
    {
        $locked = $this->emailDraftsRepository->lockDraft($draftId);

        if ($locked) {
            return response()->json([
                'message' => 'Draft locked successfully'
            ], Response::HTTP_OK);
        }

        return response()->json([
            'message' => 'Failed to lock draft'
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Track history of changes made to a draft.
     */
    public function trackHistory(int $draftId)
    {
        $history = $this->emailDraftsRepository->trackDraftHistory($draftId);

        return response()->json([
            'message' => 'Draft history retrieved successfully',
            'data' => $history
        ], Response::HTTP_OK);
    }
}
