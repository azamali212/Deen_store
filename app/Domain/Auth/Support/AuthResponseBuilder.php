<?php

declare(strict_types=1);

namespace App\Domain\Auth\Support;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class AuthResponseBuilder
{
    public static function success(
        string $message,
        array $data = [],
        int $status = Response::HTTP_OK
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toDateTimeString(),
        ], $status);
    }

    public static function error(
        string $message,
        int $status = Response::HTTP_BAD_REQUEST,
        array $errors = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => now()->toDateTimeString(),
        ], $status);
    }

    public static function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return self::error(
            message: $message,
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            errors: $errors
        );
    }

    public static function unauthorized(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return self::error(
            message: $message,
            status: Response::HTTP_UNAUTHORIZED
        );
    }

    public static function forbidden(
        string $message = 'Forbidden'
    ): JsonResponse {
        return self::error(
            message: $message,
            status: Response::HTTP_FORBIDDEN
        );
    }

    public static function created(
        string $message,
        array $data = []
    ): JsonResponse {
        return self::success(
            message: $message,
            data: $data,
            status: Response::HTTP_CREATED
        );
    }
}