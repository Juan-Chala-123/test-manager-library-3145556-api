<?php

declare(strict_types=1);

namespace Library\Application\Services;

use Library\Application\Ports\Outbound\BookRepository;
use Library\Domain\Author;
use Library\Domain\Book;
use Library\Domain\BookId;
use Library\Domain\CategoryId;
use Library\Domain\Isbn;
use Library\Domain\Quantity;
use Library\Domain\Title;

/**
 * Servicio para crear libros (data-model.md §3, spec.md §3).
 */
final class CreateBookService
{
    public function __construct(
        private readonly BookRepository $bookRepository
    ) {
    }

    /**
     * Crea un nuevo libro (data-model.md §3, spec.md §3).
     * 
     * @throws \InvalidArgumentException si el ISBN ya existe
     */
    public function create(
        string $title,
        string $author,
        string $isbn,
        CategoryId $categoryId,
        int $totalCopies
    ): Book {
        $bookId = new BookId($this->generateBookId());
        $isbnObj = new Isbn($isbn);
        $titleObj = new Title($title);
        $authorObj = new Author($author);
        $totalQty = new Quantity($totalCopies);
        $availableQty = new Quantity($totalCopies);
        
        if ($this->bookRepository->isbnExists($isbnObj)) {
            throw new \InvalidArgumentException('ISBN ya existe.');
        }
        
        $book = new Book(
            $bookId,
            $titleObj,
            $authorObj,
            $isbnObj,
            $categoryId,
            $totalQty,
            $availableQty
        );
        
        $this->bookRepository->create($book);
        
        return $book;
    }
    
    private function generateBookId(): string
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