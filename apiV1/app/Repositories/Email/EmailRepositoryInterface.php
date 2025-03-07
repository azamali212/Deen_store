<?php

namespace App\Repositories\Email;

use App\Models\Email;
use Illuminate\Database\Eloquent\Collection;

interface EmailRepositoryInterface
{
    public function sendEmail(string $senderId, string $receiverId, string $subject, string $body): Email;

    public function getEmailsForUser(string $userId, string $status = 'sent'): Collection;

    public function getEmailById(int $emailId): ?Email;

    public function markAsRead(int $emailId): bool;

    public function markAsUnread(int $emailId): bool;

    public function archiveEmail(int $emailId): bool;

    public function unarchiveEmail(int $emailId): bool;

    public function deleteEmail(int $emailId): bool;

    public function getEmailStatusesForUser(int $userId): Collection;

    public function moveToTrash(int $emailId): bool;

    public function restoreEmail(int $emailId): bool;

    public function emptyTrash(int $emailId): bool;
    
    public function getTrashedEmails(string $userId): Collection;
}
