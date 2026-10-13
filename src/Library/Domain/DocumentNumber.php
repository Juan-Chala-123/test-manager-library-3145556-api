<?php

declare(strict_types=1);

namespace Library\Domain;

/**
 * Documento de socio (data-model.md §3, spec.md §3).
 */
final class DocumentNumber implements ValueObject
{
    public function __construct(private readonly string $value)
    {
        // No validaciones específicas en data-model.md, pero es único en la base.
        // El formato real (número, tipo) se deja a la implementación.
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
