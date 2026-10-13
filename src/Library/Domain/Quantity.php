<?php

declare(strict_types=1);

namespace Library\Domain;

use InvalidArgumentException;

/**
 * Cantidad positiva (data-model.md §3, spec.md §3).
 */
final class Quantity implements ValueObject
{
    public function __construct(private readonly int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('Cantidad debe ser positiva.');
        }
    }

    public function value(): int
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
        return (string)$this->value;
    }
}
