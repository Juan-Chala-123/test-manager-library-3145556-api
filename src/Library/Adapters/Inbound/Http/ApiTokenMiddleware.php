<?php

declare(strict_types=1);

namespace Library\Adapters\Inbound\Http;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiTokenMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $claims = ApiToken::fromRequest($request);
        if ($roles !== [] && !in_array($claims['role'] ?? null, $roles, true)) {
            throw new ApiException('FORBIDDEN_ROLE', 'No tienes permisos para realizar esta operación.', 403);
        }

        $request->attributes->set('auth_claims', $claims);
        return $next($request);
    }
}
