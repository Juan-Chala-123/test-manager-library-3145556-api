<?php

declare(strict_types=1);

namespace Library\Domain;

use InvalidArgumentException;

/**
 * Libro del catálogo (data-model.md §3, spec.md §3).
 * 
 * Invariantes:
 * - disponibles entre 0 y total
 * - total ≥ 1
 * - título no vacío
 */
final class Book
{
    public function __construct(
        private readonly BookId $id,
        private readonly Title $title,
        private readonly Author $author,
        private readonly Isbn $isbn,
        private readonly CategoryId $categoryId,
        private Quantity $totalCopies,
        private Quantity $availableCopies
    ) {
        if ($totalCopies->value() < 1) {
            throw new InvalidArgumentException('Total de ejemplares debe ser ≥ 1.');
        }
        
        if ($availableCopies->value() < 0 || $availableCopies->value() > $totalCopies->value()) {
            throw new InvalidArgumentException('Ejemplares disponibles debe estar entre 0 y total.');
        }
    }

    public function id(): BookId
    {
        return $this->id;
    }

    public function title(): Title
    {
        return $this->title;
    }

    public function author(): Author
    {
        return $this->author;
    }

    public function isbn(): Isbn
    {
        return $this->isbn;
    }

    public function categoryId(): CategoryId
    {
        return $this->categoryId;
    }

    public function totalCopies(): Quantity
    {
        return $this->totalCopies;
    }

    public function availableCopies(): Quantity
    {
        return $this->availableCopies;
    }

    /**
     * Retira un ejemplar del catálogo (data-model.md §3, spec.md §3).
     * 
     * @throws InvalidArgumentException si no hay ejemplares disponibles
     */
    public function withdraw(): void
    {
        if ($this->availableCopies->value() <= 0) {
            throw new InvalidArgumentException('No quedan ejemplares disponibles.');
        }
        
        $this->availableCopies = new Quantity($this->availableCopies->value() - 1);
    }

    /**
     * Devuelve un ejemplar al catálogo (data-model.md §3, spec.md §3).
     */
    public function restock(): void
    {
        $newValue = $this->availableCopies->value() + 1;
        if ($newValue > $this->totalCopies->value()) {
            throw new InvalidArgumentException('No puede haber más ejemplares disponibles que el total.');
        }
        
        $this->availableCopies = new Quantity($newValue);
    }

    /**
     * Verifica si el libro está dado de baja (data-model.md §3, spec.md §3).
     */
    public function isDeleted(): bool
    {
        // En esta implementación, no se gestiona la baja lógica aquí
        // La baja lógica se manejará en el repositorio o en el dominio superior
        return false;
    }

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }
}
