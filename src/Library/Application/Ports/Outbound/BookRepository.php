<?php

declare(strict_types=1);

namespace Library\Application\Ports\Outbound;

use Library\Domain\Book;
use Library\Domain\BookId;
use Library\Domain\CategoryId;
use Library\Domain\Isbn;
use Library\Domain\Quantity;

/**
 * Puerto para operaciones de libros (data-model.md §3, spec.md §3).
 */
interface BookRepository
{
    /**
     * Crea un nuevo libro (data-model.md §3, spec.md §3).
     */
    public function create(Book $book): void;
    
    /**
     * Busca un libro por ID (data-model.md §3, spec.md §4).
     */
    public function findById(BookId $id): Book;
    
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
    ): array;
    
    /**
     * Actualiza un libro (data-model.md §3, spec.md §3).
     */
    public function update(Book $book): void;
    
    /**
     * Da de baja un libro (data-model.md §3, spec.md §3).
     * 
     * @throws \InvalidArgumentException si el libro tiene préstamos abiertos
     */
    public function delete(BookId $id): void;
    
    /**
     * Verifica si un ISBN ya existe (data-model.md §3, spec.md §3).
     */
    public function isbnExists(Isbn $isbn): bool;
    
    /**
     * Obtiene libros por IDs (data-model.md §3, spec.md §6).
     * 
     * @return Book[]
     */
    public function findByIds(array $ids): array;
    
    /**
     * Actualiza los ejemplares disponibles de un libro (data-model.md §3, spec.md §3).
     * 
     * @throws \InvalidArgumentException si no hay suficientes ejemplares
     */
    public function updateAvailableCopies(BookId $id, Quantity $newAvailable): void;
}