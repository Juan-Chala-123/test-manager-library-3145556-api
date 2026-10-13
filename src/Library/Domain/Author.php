<?php

declare(strict_types=1);

namespace Library\Domain;

/**
 * Autor de libro (data-model.md §3).
 */
final class Author implements ValueObject
{
    public function __construct(private readonly string $value)
    {
        // No hay validaciones específicas en data-model.md
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
