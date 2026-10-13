<?php

declare(strict_types=1);

namespace Library\Application\Services;

use Library\Application\Ports\Outbound\BookRepository;
use Library\Domain\Book;
use Library\Domain\BookId;
use Library\Domain\CategoryId;
use Library\Domain\Isbn;
use Library\Domain\Quantity;
use Library\Domain\Title;

/**
 * Servicio para actualizar libros (data-model.md §3, spec.md §3).
 */
final class UpdateBookService
{
    public function __construct(
        private readonly BookRepository $bookRepository
    ) {
    }

    /**
     * Actualiza un libro (data-model.md §3, spec.md §3).
     * 
     * @throws \InvalidArgumentException si el ISBN ya existe en otro libro
     */
    public function update(
        BookId $id,
        string $title,
        string $author,
        CategoryId $categoryId,
        int $totalCopies
    ): Book {
        $book = $this->bookRepository->findById($id);
        $isbn = $book->isbn(); // Mantener ISBN original
        
        $titleObj = new Title($title);
        $authorObj = new Author($author);
        $categoryIdObj = new CategoryId($categoryId->value()); // Validar categoría
        $totalQty = new Quantity($totalCopies);
        
        // Validar que el ISBN no esté duplicado (excepto el mismo libro)
        if ($this->bookRepository->isbnExists($isbn) && !$this->bookRepository->findById($id)->isbn()->equals($isbn)) {
            throw new \InvalidArgumentException('ISBN ya existe.');
        }
        
        // Actualizar el libro con los nuevos datos
        $updatedBook = new Book(
            $id,
            $titleObj,
            $authorObj,
            $isbn,
            $categoryIdObj,
            $totalQty,
            $book->availableCopies() // Mantener ejemplares disponibles actuales
        );
        
        $this->bookRepository->update($updatedBook);
        
        return $updatedBook;
    }
}