<?php

declare(strict_types=1);

namespace Library\Domain;

use InvalidArgumentException;

/**
 * Usuario del sistema (data-model.md §3, spec.md §3).
 * 
 * Invariantes:
 * - nombre normalizado
 * - rol en (`admin`, `librarian`)
 */
final class User
{
    public function __construct(
        private readonly UserId $id,
        private readonly string $username,
        private readonly string $passwordHash,
        private readonly string $role
    ) {
        if (empty(trim($username))) {
            throw new InvalidArgumentException('Nombre de usuario no puede estar vacío.');
        }
        
        if ($role !== 'admin' && $role !== 'librarian') {
            throw new InvalidArgumentException('Rol debe ser "admin" o "librarian".');
        }
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function role(): string
    {
        return $this->role;
    }

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }
}
