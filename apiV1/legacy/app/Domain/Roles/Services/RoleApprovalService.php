<?php

declare(strict_types=1);

namespace App\Domain\Roles\Services;

use App\Domain\Notifications\NotificationTypes;
use App\Models\PendingRoleRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RoleApprovalService
{
    public function __construct(
        private readonly RoleTemplateMailer $mailer,
    ) {}

    /**
     * Admin -> create pending request + email Super Admins
     *
     * @param array{name:string, slug?:string|null, permission_names?:array<int,string>, description?:string|null} $data
     */
    public function requestRole(array $data, string $createdByUserId): PendingRoleRequest
    {
        $creator = User::query()->findOrFail($createdByUserId);

        // duplicate pending for same user + same role name
        $duplicate = PendingRoleRequest::query()
            ->where('name', $data['name'])
            ->where('created_by', $creator->id)
            ->where('status', 'pending')
            ->exists();

        if ($duplicate) {
            throw new RuntimeException('You already have a pending request for this role name.');
        }

        // role already exists
        if (Role::query()->where('name', $data['name'])->exists()) {
            throw new RuntimeException('A role with this name already exists.');
        }

        $req = PendingRoleRequest::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? null,
            'permission_names' => array_values($data['permission_names'] ?? []),
            'description' => $data['description'] ?? null,
            'created_by' => $creator->id,
            'status' => 'pending',
        ]);

        // Email Super Admins (mail only; no central DB inserts)
        $superAdmins = User::role('Super Admin')->get(['email']);

        foreach ($superAdmins as $sa) {
            if (!$sa->email) continue;

            $this->mailer->send(
                type: NotificationTypes::ROLE_REQUESTED,
                toEmail: (string) $sa->email,
                vars: [
                    'role_name' => $req->name,
                    'requested_by_email' => $creator->email,
                    'request_id' => $req->id,
                ],
                locale: 'en'
            );
        }

        return $req;
    }

    /**
     * Super Admin -> approve -> create actual role -> email requester
     */
    public function approve(string $requestId, string $approvedByUserId): Role
    {
        $approver = User::query()->findOrFail($approvedByUserId);

        if (!$approver->hasRole('Super Admin')) {
            throw new RuntimeException('Only Super Admin can approve role requests.');
        }

        return DB::transaction(function () use ($requestId, $approver): Role {
            $req = PendingRoleRequest::query()->lockForUpdate()->findOrFail($requestId);

            if ($req->status !== 'pending') {
                throw new RuntimeException('Request already processed.');
            }

            if (Role::query()->where('name', $req->name)->exists()) {
                throw new RuntimeException('A role with this name already exists.');
            }

            $role = Role::create([
                'name' => $req->name,
                'slug' => $req->slug,
            ]);

            $permNames = $req->permission_names ?? [];
            if (is_array($permNames) && count($permNames) > 0) {
                $permissions = Permission::query()->whereIn('name', $permNames)->get();
                if ($permissions->count() !== count($permNames)) {
                    throw new RuntimeException('Some permissions do not exist.');
                }
                $role->syncPermissions($permissions);
            }

            $req->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            $creator = User::query()->findOrFail($req->created_by);

            if ($creator->email) {
                $this->mailer->send(
                    type: NotificationTypes::ROLE_APPROVED,
                    toEmail: (string) $creator->email,
                    vars: [
                        'role_name' => $req->name,
                        'reviewed_by_email' => $approver->email,
                        'request_id' => $req->id,
                    ],
                    locale: 'en'
                );
            }

            return $role->load('permissions');
        });
    }

    /**
     * Super Admin -> reject -> email requester
     */
    public function reject(string $requestId, string $reviewedByUserId, ?string $reason = null): PendingRoleRequest
    {
        $reviewer = User::query()->findOrFail($reviewedByUserId);

        if (!$reviewer->hasRole('Super Admin')) {
            throw new RuntimeException('Only Super Admin can reject role requests.');
        }

        return DB::transaction(function () use ($requestId, $reviewer, $reason): PendingRoleRequest {
            $req = PendingRoleRequest::query()->lockForUpdate()->findOrFail($requestId);

            if ($req->status !== 'pending') {
                throw new RuntimeException('Request already processed.');
            }

            $req->update([
                'status' => 'rejected',
                'approved_by' => $reviewer->id, // your schema uses approved_by for reviewer id
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $creator = User::query()->findOrFail($req->created_by);

            if ($creator->email) {
                $this->mailer->send(
                    type: NotificationTypes::ROLE_REJECTED,
                    toEmail: (string) $creator->email,
                    vars: [
                        'role_name' => $req->name,
                        'reviewed_by_email' => $reviewer->email,
                        'rejection_reason' => $reason ?? 'Not provided',
                        'request_id' => $req->id,
                    ],
                    locale: 'en'
                );
            }

            return $req;
        });
    }
}