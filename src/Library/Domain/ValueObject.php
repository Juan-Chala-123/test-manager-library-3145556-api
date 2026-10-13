<?php

declare(strict_types=1);

namespace Library\Domain;

/**
 * Interface para objetos de valor (data-model.md §3).
 */
interface ValueObject
{
    public function equals(ValueObject $other): bool;
}