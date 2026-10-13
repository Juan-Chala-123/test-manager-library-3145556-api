<?php

declare(strict_types=1);

namespace Library\Domain;

use InvalidArgumentException;

/**
 * Identificador de usuario (data-model.md §3).
 */
final class UserId implements ValueObject
{
    public function __construct(private readonly string $value)
    {
        // Validación básica de UUID
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            throw new InvalidArgumentException('Formato de UUID inválido.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(ValueObject $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }
        return $this->value === $other->value;
    }
    
    public function __toString(): string
    {
        return $this->value;
    }
}
