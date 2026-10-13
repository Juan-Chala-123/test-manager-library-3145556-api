<?php

declare(strict_types=1);

namespace Library\Adapters\Outbound\Persistence;

use Library\Application\Ports\Outbound\UserRepository;
use Library\Domain\User;
use Library\Domain\UserId;
use PDO;

/**
 * Adaptador de persistencia para usuarios (data-model.md §3, spec.md §1, §2).
 */
final class UserRepositoryAdapter implements UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByUsername(string $username): User
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, password_hash, role FROM library.user WHERE username = ?'
        );
        $stmt->execute([$username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            throw new \InvalidArgumentException('Usuario no encontrado.');
        }
        
        return new User(
            new UserId($row['id']),
            $row['username'],
            $row['password_hash'],
            $row['role']
        );
    }

    public function create(User $user): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO library.user (id, username, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        
        $stmt->execute([
            $user->id()->value(),
            $user->username(),
            $user->passwordHash(),
            $user->role()
        ]);
    }
}