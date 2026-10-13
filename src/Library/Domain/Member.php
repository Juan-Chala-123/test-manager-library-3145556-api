<?php

declare(strict_types=1);

namespace Library\Domain;

use InvalidArgumentException;

/**
 * Socio (data-model.md §3, spec.md §3).
 * 
 * Invariantes:
 * - documento obligatorio y único
 */
final class Member
{
    public function __construct(
        private readonly MemberId $id,
        private readonly DocumentNumber $documentNumber,
        private readonly string $fullName
    ) {
        if (empty(trim($fullName))) {
            throw new InvalidArgumentException('Nombre completo no puede estar vacío.');
        }
    }

    public function id(): MemberId
    {
        return $this->id;
    }

    public function documentNumber(): DocumentNumber
    {
        return $this->documentNumber;
    }

    public function fullName(): string
    {
        return $this->fullName;
    }

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }
}
