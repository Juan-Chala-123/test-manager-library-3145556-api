<?php

declare(strict_types=1);

namespace Library\Application\Services;

use Library\Application\Ports\Outbound\LoanRepository;
use Library\Domain\LoanItem;

/**
 * Servicio para reporte de libros más prestados (data-model.md §3, spec.md §10).
 */
final class MostBorrowedReportService
{
    public function __construct(
        private readonly LoanRepository $loanRepository
    ) {
    }

    /**
     * Genera el reporte de libros más prestados (data-model.md §3, spec.md §10).
     * 
     * @param \DateTimeImmutable $from Fecha de inicio del periodo
     * @param \DateTimeImmutable $to Fecha de fin del periodo
     * @return array Fila por libro con cantidad de préstamos
     */
    public function generateReport(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array {
        // En una implementación real, se haría una consulta agregada
        // que agrupe por book_id, book_title, category_name
        // y cuente las veces prestado
        
        return $this->loanRepository->findMostBorrowedReport($from, $to);
    }
}