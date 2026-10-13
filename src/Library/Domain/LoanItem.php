<?php

declare(strict_types=1);

namespace Library\Domain;

use InvalidArgumentException;

/**
 * Línea de préstamo (data-model.md §3, spec.md §3).
 */
final class LoanItem
{
    public function __construct(
        private readonly LoanItemId $id,
        private readonly LoanId $loanId,
        private readonly BookId $bookId,
        private readonly Title $title,
        private readonly Isbn $isbn,
        private readonly CategoryId $categoryId,
        private readonly \DateTimeImmutable $dueAt,
        private ?\DateTimeImmutable $returnedAt = null
    ) {
    }

    public function id(): LoanItemId
    {
        return $this->id;
    }

    public function loanId(): LoanId
    {
        return $this->loanId;
    }

    public function bookId(): BookId
    {
        return $this->bookId;
    }

    public function title(): Title
    {
        return $this->title;
    }

    public function isbn(): Isbn
    {
        return $this->isbn;
    }

    public function categoryId(): CategoryId
    {
        return $this->categoryId;
    }

    public function dueAt(): \DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function returnedAt(): ?\DateTimeImmutable
    {
        return $this->returnedAt;
    }

    /**
     * Marca la línea como devuelta (data-model.md §3, spec.md §3).
     */
    public function returnItem(): void
    {
        if ($this->returnedAt !== null) {
            throw new InvalidArgumentException('La línea ya fue devuelta.');
        }
        
        $this->returnedAt = new \DateTimeImmutable();
    }

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }
}
