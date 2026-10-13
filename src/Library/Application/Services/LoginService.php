<?php

declare(strict_types=1);

namespace Library\Application\Services;

use Library\Application\Ports\Outbound\PasswordHasher;
use Library\Application\Ports\Outbound\TokenIssuer;
use Library\Application\Ports\Outbound\UserRepository;
use Library\Domain\User;

/**
 * Servicio para iniciar sesión (spec.md §1).
 */
final class LoginService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PasswordHasher $passwordHasher,
        private readonly TokenIssuer $tokenIssuer
    ) {
    }

    /**
     * Inicia sesión de un usuario (data-model.md §3, spec.md §1).
     * 
     * @throws \InvalidArgumentException si las credenciales son inválidas
     */
    public function login(string $username, string $password): string
    {
        $user = $this->userRepository->findByUsername($username);
        
        if (!$this->passwordHasher->verify($password, $user->passwordHash())) {
            throw new \InvalidArgumentException('Credenciales inválidas.');
        }
        
        return $this->tokenIssuer->issue($user);
    }
}