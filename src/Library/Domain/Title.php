<?php

declare(strict_types=1);

namespace Library\Domain;

/**
 * Título de libro (data-model.md §3).
 */
final class Title implements ValueObject
{
    public function __construct(private readonly string $value)
    {
        if (empty(trim($value))) {
            throw new InvalidArgumentException('Título no puede estar vacío.');
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
