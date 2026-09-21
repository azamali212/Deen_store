<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Domain\User\Actions\AddAddressAction;
use App\Domain\User\Actions\DeleteAddressAction;
use App\Domain\User\Actions\ListAddressesAction;
use App\Domain\User\Actions\SetDefaultAddressAction;
use App\Domain\User\Actions\UpdateAddressAction;
use App\Domain\User\DTO\UserAddressDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreAddressRequest;
use App\Http\Requests\User\UpdateAddressRequest;
use App\Http\Resources\User\UserAddressCollection;
use App\Http\Resources\User\UserAddressResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AddressController extends Controller
{
    // List all saved addresses for the authenticated user
    public function index(
        Request $request,
        ListAddressesAction $action,
    ): UserAddressCollection {

        return new UserAddressCollection(
            $action->execute(
                $request->user()->id,
            ),
        );
    }

    // Add a new address (enforces the max-10-per-user business rule inside AddressService)
    public function store(
        StoreAddressRequest $request,
        AddAddressAction $action,
    ): UserAddressResource {

        $dto = UserAddressDTO::fromArray(
            $request->validated(),
        );

        return new UserAddressResource(
            $action->execute(
                $request->user()->id,
                $dto,
            ),
        );
    }

    // Update an existing address
    public function update(
        UpdateAddressRequest $request,
        int $address,
        UpdateAddressAction $action,
    ): UserAddressResource {

        $dto = UserAddressDTO::fromArray(
            $request->validated(),
        );

        return new UserAddressResource(
            $action->execute(
                $request->user()->id,
                $address,
                $dto,
            ),
        );
    }

    // Delete an address (AddressService auto-promotes the next one to default if this was the default)
    public function destroy(
        Request $request,
        int $address,
        DeleteAddressAction $action,
    ): JsonResponse {

        $action->execute(
            $request->user()->id,
            $address,
        );

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully.',
        ]);
    }

    // Mark an address as the default one
    public function setDefault(
        Request $request,
        int $address,
        SetDefaultAddressAction $action,
    ): UserAddressResource {

        return new UserAddressResource(
            $action->execute(
                $request->user()->id,
                $address,
            ),
        );
    }
}
