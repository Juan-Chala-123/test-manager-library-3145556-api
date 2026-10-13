<?php

declare(strict_types=1);

namespace Library\Domain;

use InvalidArgumentException;
use RuntimeException;

/**
 * Préstamo de libros (data-model.md §3, spec.md §3).
 * 
 * Invariantes:
 * - 1 a 3 líneas
 * - sin libro repetido
 * - vencimiento a 14 días
 * - inmutable salvo devolución
 */
final class Loan
{
    /** @var LoanItem[] */
    private array $items = [];

    public function __construct(
        private readonly LoanId $id,
        private readonly MemberId $memberId,
        private readonly UserId $userId,
        private readonly \DateTimeImmutable $loanedAt
    ) {
    }

    public function id(): LoanId
    {
        return $this->id;
    }

    public function memberId(): MemberId
    {
        return $this->memberId;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function loanedAt(): \DateTimeImmutable
    {
        return $this->loanedAt;
    }

    /**
     * Agrega una línea al préstamo (data-model.md §3, spec.md §3).
     * 
     * @throws InvalidArgumentException si ya hay 3 líneas o el libro ya está en el préstamo
     */
    public function addItem(BookId $bookId, Title $title, Isbn $isbn, CategoryId $categoryId, \DateTimeImmutable $dueAt): void
    {
        if (count($this->items) >= 3) {
            throw new InvalidArgumentException('Un préstamo no puede tener más de 3 líneas.');
        }

        foreach ($this->items as $item) {
            if ($item->bookId()->equals($bookId)) {
                throw new InvalidArgumentException('El mismo libro no se puede prestar más de una vez en un préstamo.');
            }
        }

        $item = new LoanItem(
            new LoanItemId($this->generateLoanItemId()),
            $this->id,
            $bookId,
            $title,
            $isbn,
            $categoryId,
            $dueAt
        );
        
        $this->items[] = $item;
    }

    /**
     * Devuelve una línea del préstamo (data-model.md §3, spec.md §3).
     * 
     * @throws InvalidArgumentException si la línea ya fue devuelta
     */
    public function returnItem(LoanItemId $itemId): void
    {
        foreach ($this->items as $item) {
            if ($item->id()->equals($itemId)) {
                $item->returnItem();
                return;
            }
        }
        
        throw new InvalidArgumentException('Línea de préstamo no encontrada.');
    }

    /**
     * Calcula el estado del préstamo (data-model.md §3, spec.md §3).
     */
    public function status(\DateTimeImmutable $now): string
    {
        $hasOpenItems = false;
        $hasOverdueItems = false;
        
        foreach ($this->items as $item) {
            if ($item->returnedAt() === null) {
                $hasOpenItems = true;
                if ($item->dueAt() < $now) {
                    $hasOverdueItems = true;
                }
            }
        }
        
        if ($hasOverdueItems) {
            return 'overdue';
        }
        
        if ($hasOpenItems) {
            return 'open';
        }
        
        return 'closed';
    }

    /**
     * Obtiene las líneas del préstamo.
     * 
     * @return LoanItem[]
     */
    public function items(): array
    {
        return $this->items;
    }

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }

    private function generateLoanItemId(): string
    {
        // Generador simple de UUID (esto debería ser mejorado)
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }
}
