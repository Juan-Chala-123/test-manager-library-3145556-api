<?php

declare(strict_types=1);

namespace Library\Application\Services;

use Library\Application\Ports\Outbound\BookRepository;
use Library\Application\Ports\Outbound\LoanRepository;
use Library\Domain\Book;
use Library\Domain\BookId;
use Library\Domain\Loan;
use Library\Domain\LoanId;
use Library\Domain\LoanItemId;
use Library\Domain\Quantity;

/**
 * Servicio para devolver libros (data-model.md §3, spec.md §7).
 */
final class ReturnItemService
{
    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly LoanRepository $loanRepository
    ) {
    }

    /**
     * Devuelve una línea de préstamo (data-model.md §3, spec.md §7).
     * 
     * @param LoanId $loanId ID del préstamo
     * @param LoanItemId $itemId ID de la línea de préstamo
     * @return void
     * 
     * @throws \InvalidArgumentException si la línea ya fue devuelta
     */
    public function returnItem(LoanId $loanId, LoanItemId $itemId): void
    {
        // Obtener el préstamo
        $loan = $this->loanRepository->findById($loanId);
        
        // Devolver la línea
        $loan->returnItem($itemId);
        
        // Obtener la información de la línea devuelta
        $returnedItem = null;
        foreach ($loan->items() as $item) {
            if ($item->id()->equals($itemId)) {
                $returnedItem = $item;
                break;
            }
        }
        
        if ($returnedItem === null) {
            throw new \InvalidArgumentException('Línea de préstamo no encontrada.');
        }
        
        // Devolver el ejemplar al libro
        $book = $this->bookRepository->findById($returnedItem->bookId());
        $newAvailable = new Quantity($book->availableCopies()->value() + 1);
        $this->bookRepository->updateAvailableCopies($book->id(), $newAvailable);
        
        // Actualizar el préstamo (en realidad, solo se actualiza el campo returned_at de la línea)
        // En un sistema real, esto se haría con una operación de actualización específica
    }
}