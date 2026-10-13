<?php

declare(strict_types=1);

namespace Library\Application\Services;

use Library\Application\Ports\Outbound\PasswordHasher;
use Library\Application\Ports\Outbound\UserRepository;
use Library\Domain\User;
use Library\Domain\UserId;

/**
 * Servicio para crear bibliotecarios (spec.md §2).
 */
final class CreateLibrarianService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PasswordHasher $passwordHasher
    ) {
    }

    /**
     * Crea un nuevo bibliotecario (data-model.md §3, spec.md §2).
     * 
     * @throws \InvalidArgumentException si el nombre de usuario ya existe
     */
    public function createLibrarian(string $username, string $password, string $role = 'librarian'): User
    {
        if ($role !== 'librarian') {
            throw new \InvalidArgumentException('Solo se permiten roles "librarian".');
        }
        
        $userId = new UserId($this->generateUserId());
        $passwordHash = $this->passwordHasher->hash($password);
        $user = new User($userId, $username, $passwordHash, $role);
        
        $this->userRepository->create($user);
        
        return $user;
    }
    
    private function generateUserId(): string
    {
        // Generador simple de UUID (esto debería ser mejorado)
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }
}