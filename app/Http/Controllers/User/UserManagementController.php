<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Domain\User\Actions\ActivateUserAction;
use App\Domain\User\Actions\DeleteUserAction;
use App\Domain\User\Actions\GetUserAction;
use App\Domain\User\Actions\RestoreUserAction;
use App\Domain\User\Actions\SearchUsersAction;
use App\Domain\User\Actions\SuspendUserAction;
use App\Domain\User\Actions\UpdateUserAction;
use App\Domain\User\DTO\UserFilterDTO;
use App\Domain\User\DTO\UpdateUserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\SearchUsersRequest;
use App\Http\Requests\User\SuspendUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\User\UserCollection;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\JsonResponse;

/**
 * Admin-only user management (search/view/edit/suspend/ban/delete/restore
 * ANY user). Route-level middleware enforces 'role:super_admin|admin' —
 * the same authorization AuthPolicy::manageUsers() already encodes for the
 * Auth domain's admin endpoints, kept consistent rather than introducing a
 * second, separate User-domain policy for the same rule.
 */
final class UserManagementController extends Controller
{
    // Search / list users with filters (admin panel table)
    public function index(
        SearchUsersRequest $request,
        SearchUsersAction $action,
    ): UserCollection {

        $dto = UserFilterDTO::fromArray(
            $request->validated(),
        );

        return new UserCollection(
            $action->execute($dto),
        );
    }

    // View a single user
    public function show(
        int $user,
        GetUserAction $action,
    ): UserResource {

        return new UserResource(
            $action->execute($user),
        );
    }

    // Update a user's core account fields (name/email/phone)
    public function update(
        UpdateUserRequest $request,
        int $user,
        UpdateUserAction $action,
    ): UserResource {

        $dto = UpdateUserDTO::fromArray(
            $request->validated(),
        );

        return new UserResource(
            $action->execute($user, $dto),
        );
    }

    // Soft-delete a user's account
    public function destroy(
        int $user,
        DeleteUserAction $action,
    ): JsonResponse {

        $action->execute($user);

        return response()->json([
            'success' => true,
            'message' => 'User account deleted successfully.',
        ]);
    }

    // Restore a soft-deleted user's account
    public function restore(
        int $user,
        RestoreUserAction $action,
    ): UserResource {

        return new UserResource(
            $action->execute($user),
        );
    }

    // Suspend a user's account (with an optional reason)
    public function suspend(
        SuspendUserRequest $request,
        int $user,
        SuspendUserAction $action,
    ): UserResource {

        return new UserResource(
            $action->execute(
                $user,
                $request->validated('reason'),
            ),
        );
    }

    // Reactivate a suspended/inactive user's account
    public function activate(
        int $user,
        ActivateUserAction $action,
    ): UserResource {

        return new UserResource(
            $action->execute($user),
        );
    }
}
