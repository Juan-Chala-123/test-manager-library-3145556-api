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
 * Servicio para buscar libros (data-model.md §3, spec.md §4).
 */
final class SearchBooksService
{
    public function __construct(
        private readonly BookRepository $bookRepository
    ) {
    }

    /**
     * Busca libros por criterios (data-model.md §3, spec.md §4).
     * 
     * @param array|null $categoryIds Categorías a filtrar (null = todas)
     * @param string|null $searchText Texto a buscar en título o autor (null = sin filtro)
     * @param int $page Página de resultados
     * @param int $size Tamaño de página
     * @return array [items: Book[], total: int]
     */
    public function search(
        ?array $categoryIds,
        ?string $searchText,
        int $page,
        int $size
    ): array {
        return $this->bookRepository->search($categoryIds, $searchText, $page, $size);
    }
}