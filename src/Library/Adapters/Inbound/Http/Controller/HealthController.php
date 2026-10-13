<?php

declare(strict_types=1);

namespace Library\Adapters\Inbound\Http\Controller;

use Illuminate\Http\JsonResponse;

/**
 * Comprobación de vida del servicio (api-contract.md §3.1).
 * Público: no exige token.
 */
final class HealthController
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
