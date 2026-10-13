<?php

declare(strict_types=1);

namespace Library\Domain;

use InvalidArgumentException;

/**
 * Rango de fechas (data-model.md §3, spec.md §3).
 */
final class DateRange implements ValueObject
{
    public function __construct(
        private readonly \DateTimeImmutable $start,
        private readonly \DateTimeImmutable $end
    ) {
        if ($start > $end) {
            throw new InvalidArgumentException('Fecha de inicio debe ser menor o igual a la de fin.');
        }
    }

    public function start(): \DateTimeImmutable
    {
        return $this->start;
    }

    public function end(): \DateTimeImmutable
    {
        return $this->end;
    }

    public function contains(\DateTimeImmutable $date): bool
    {
        return $date >= $this->start && $date <= $this->end;
    }

    public function equals(ValueObject $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }
        return $this->start == $other->start && $this->end == $other->end;
    }
    
    public function __toString(): string
    {
        return sprintf('%s to %s', $this->start->format('Y-m-d'), $this->end->format('Y-m-d'));
    }
}
