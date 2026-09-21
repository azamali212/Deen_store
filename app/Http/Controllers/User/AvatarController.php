<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Domain\User\Actions\DeleteAvatarAction;
use App\Domain\User\Actions\UploadAvatarAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UploadAvatarRequest;
use App\Http\Resources\User\UserProfileResource;
use Illuminate\Http\Request;

final class AvatarController extends Controller
{
    // Upload (or replace) the authenticated user's avatar
    public function store(
        UploadAvatarRequest $request,
        UploadAvatarAction $action,
    ): UserProfileResource {

        return new UserProfileResource(
            $action->execute(
                $request->user()->id,
                $request->file('avatar'),
            ),
        );
    }

    // Remove the authenticated user's avatar
    public function destroy(
        Request $request,
        DeleteAvatarAction $action,
    ): UserProfileResource {

        return new UserProfileResource(
            $action->execute(
                $request->user()->id,
            ),
        );
    }
}
