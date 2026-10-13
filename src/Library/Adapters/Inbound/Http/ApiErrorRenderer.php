<?php

declare(strict_types=1);

namespace Library\Adapters\Inbound\Http;

use Illuminate\Validation\ValidationException;
use Throwable;

final class ApiErrorRenderer
{
    public static function render(Throwable $error): ?\Illuminate\Http\JsonResponse
    {
        if ($error instanceof ApiException) {
            return response()->json(['code' => $error->errorCode, 'message' => $error->getMessage(), 'details' => $error->details], $error->status);
        }
        if ($error instanceof ValidationException) {
            return response()->json(['code' => 'VALIDATION_ERROR', 'message' => 'La solicitud contiene campos inválidos.', 'details' => collect($error->errors())->flatMap(fn ($issues, $field) => collect($issues)->map(fn ($issue) => ['field' => $field, 'issue' => $issue]))->values()], 422);
        }
        return null;
    }
}
