<?php

declare(strict_types=1);

namespace Library\Application\Ports\Outbound;

use Library\Domain\User;

/**
 * Puerto para generación de tokens JWT (plan.md D-11).
 */
interface TokenIssuer
{
    /**
     * Genera un token JWT para un usuario (data-model.md §3, spec.md §1).
     */
    public function issue(User $user): string;
}