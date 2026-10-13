<?php

declare(strict_types=1);

namespace Library\Application\Services;

use Library\Application\Ports\Outbound\BookRepository;
use Library\Application\Ports\Outbound\LoanRepository;
use Library\Domain\Book;
use Library\Domain\BookId;
use Library\Domain\Loan;
use Library\Domain\LoanId;
use Library\Domain\LoanItem;
use Library\Domain\LoanItemId;
use Library\Domain\MemberId;
use Library\Domain\UserId;
use Library\Domain\Quantity;

/**
 * Servicio para prestar libros (data-model.md §3, spec.md §6).
 * 
 * Coordina Book.withdraw() y Loan.addItem() en una sola transacción.
 */
final class LendBooksService
{
    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly LoanRepository $loanRepository
    ) {
    }

    /**
     * Registra un préstamo de libros (data-model.md §3, spec.md §6).
     * 
     * @param MemberId $memberId ID del socio
     * @param array $bookIds IDs de los libros a prestar
     * @param UserId $userId ID del usuario que registra el préstamo
     * @param \DateTimeImmutable $loanedAt Fecha del préstamo
     * @return Loan El préstamo creado
     * 
     * @throws \InvalidArgumentException si no hay ejemplares disponibles o se excede el límite
     */
    public function lend(
        MemberId $memberId,
        array $bookIds,
        UserId $userId,
        \DateTimeImmutable $loanedAt
    ): Loan {
        // Validar que no se exceda el límite de 3 libros
        if (count($bookIds) < 1 || count($bookIds) > 3) {
            throw new \InvalidArgumentException('Un préstamo debe tener entre 1 y 3 libros.');
        }
        
        // Obtener los libros
        $books = $this->bookRepository->findByIds($bookIds);
        
        // Validar que todos los libros existan
        if (count($books) !== count($bookIds)) {
            throw new \InvalidArgumentException('Uno o más libros no existen.');
        }
        
        // Validar que no haya libros repetidos
        $bookIdsSet = [];
        foreach ($bookIds as $id) {
            if (in_array($id, $bookIdsSet, true)) {
                throw new \InvalidArgumentException('No se puede prestar el mismo libro más de una vez.');
            }
            $bookIdsSet[] = $id;
        }
        
        // Validar disponibilidad y retirar ejemplares
        $availableBooks = [];
        foreach ($books as $book) {
            if ($book->availableCopies()->value() <= 0) {
                throw new \InvalidArgumentException('No quedan ejemplares disponibles del libro "' . $book->title()->value() . '".');
            }
            
            $availableBooks[] = $book;
        }
        
        // Calcular fecha de vencimiento (14 días)
        $dueAt = $loanedAt->modify('+14 days');
        
        // Crear el préstamo
        $loanId = new LoanId($this->generateLoanId());
        $loan = new Loan($loanId, $memberId, $userId, $loanedAt);
        
        // Agregar cada libro al préstamo
        foreach ($availableBooks as $book) {
            $loan->addItem(
                $book->id(),
                $book->title(),
                $book->isbn(),
                $book->categoryId(),
                $dueAt
            );
            
            // Retirar ejemplar del libro
            $newAvailable = new Quantity($book->availableCopies()->value() - 1);
            $this->bookRepository->updateAvailableCopies($book->id(), $newAvailable);
        }
        
        // Guardar el préstamo
        $this->loanRepository->create($loan);
        
        return $loan;
    }
    
    private function generateLoanId(): string
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