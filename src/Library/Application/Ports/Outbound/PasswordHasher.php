<?php

declare(strict_types=1);

namespace Library\Application\Ports\Outbound;

use Library\Domain\User;

/**
 * Puerto para hash de contraseñas (plan.md D-09).
 */
interface PasswordHasher
{
    /**
     * Genera el hash de una contraseña (data-model.md §3, spec.md §1).
     */
    public function hash(string $password): string;
    
    /**
     * Verifica si una contraseña coincide con un hash (data-model.md §3, spec.md §1).
     */
    public function verify(string $password, string $hash): bool;
}