<?php

declare(strict_types=1);

namespace Library\Domain;

use InvalidArgumentException;

/**
 * Valor de campo de texto (data-model.md §3).
 */
interface ValueObject
{
    public function equals(ValueObject $other): bool;
}

/**
 * ISBN de 13 dígitos (data-model.md §3, spec.md §3).
 */
final class Isbn implements ValueObject
{
    public function __construct(private readonly string $value)
    {
        if (!preg_match('/^\d{13}$/', $value)) {
            throw new InvalidArgumentException('ISBN debe tener 13 dígitos.');
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
