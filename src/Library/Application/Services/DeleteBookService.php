<?php

declare(strict_types=1);

namespace Library\Application\Services;

use Library\Application\Ports\Outbound\BookRepository;
use Library\Domain\BookId;

/**
 * Servicio para dar de baja libros (data-model.md §3, spec.md §3).
 */
final class DeleteBookService
{
    public function __construct(
        private readonly BookRepository $bookRepository
    ) {
    }

    /**
     * Da de baja un libro (data-model.md §3, spec.md §3).
     * 
     * @throws \InvalidArgumentException si el libro tiene préstamos abiertos
     */
    public function delete(BookId $id): void
    {
        $this->bookRepository->delete($id);
    }
}