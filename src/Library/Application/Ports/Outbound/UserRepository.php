<?php

declare(strict_types=1);

namespace Library\Application\Ports\Outbound;

use Library\Domain\User;

/**
 * Puerto para autenticación de usuarios (spec.md §1, api-contract.md §3.1).
 */
interface UserRepository
{
    /**
     * Busca un usuario por nombre de usuario (data-model.md §3, spec.md §1).
     * 
     * @throws \InvalidArgumentException si el usuario no existe
     */
    public function findByUsername(string $username): User;
    
    /**
     * Crea un nuevo usuario (data-model.md §3, spec.md §2).
     */
    public function create(User $user): void;
}