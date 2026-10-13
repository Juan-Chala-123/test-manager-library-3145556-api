<?php

declare(strict_types=1);

namespace Library\Application\Ports\Outbound;

use Library\Domain\Loan;
use Library\Domain\LoanId;

/**
 * Puerto para operaciones de préstamos (data-model.md §3, spec.md §3).
 */
interface LoanRepository
{
    /**
     * Crea un nuevo préstamo (data-model.md §3, spec.md §6).
     */
    public function create(Loan $loan): void;
    
    /**
     * Busca un préstamo por ID (data-model.md §3, spec.md §8).
     */
    public function findById(LoanId $id): Loan;
    
    /**
     * Busca préstamos por criterios (data-model.md §3, spec.md §8).
     * 
     * @param \DateTimeImmutable|null $from Fecha de inicio
     * @param \DateTimeImmutable|null $to Fecha de fin
     * @param string|null $status Estado del préstamo (open, overdue, closed)
     * @param string|null $memberId ID del socio (null = sin filtro)
     * @param int $page Página de resultados
     * @param int $size Tamaño de página
     * @return array [items: Loan[], total: int]
     */
    public function search(
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
        ?string $status,
        ?string $memberId,
        int $page,
        int $size
    ): array;
    
    /**
     * Busca líneas de préstamos vencidas (data-model.md §3, spec.md §9).
     * 
     * @param \DateTimeImmutable $now Fecha actual para comparar vencimientos
     * @return array Líneas de préstamos vencidas
     */
    public function findOverdueItems(\DateTimeImmutable $now): array;
    
    /**
     * Genera el reporte de libros más prestados (data-model.md §3, spec.md §10).
     * 
     * @param \DateTimeImmutable $from Fecha de inicio del periodo
     * @param \DateTimeImmutable $to Fecha de fin del periodo
     * @return array Fila por libro con cantidad de préstamos
     */
    public function findMostBorrowedReport(\DateTimeImmutable $from, \DateTimeImmutable $to): array;
}