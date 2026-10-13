<?php

declare(strict_types=1);

namespace Library\Application\Services;

use Library\Application\Ports\Outbound\LoanRepository;
use Library\Domain\LoanItem;

/**
 * Servicio para obtener préstamos vencidos (data-model.md §3, spec.md §9).
 */
final class OverdueService
{
    public function __construct(
        private readonly LoanRepository $loanRepository
    ) {
    }

    /**
     * Obtiene las líneas de préstamos vencidas (data-model.md §3, spec.md §9).
     * 
     * @param \DateTimeImmutable $now Fecha actual para comparar vencimientos
     * @return LoanItem[] Líneas de préstamos vencidas
     */
    public function getOverdueItems(\DateTimeImmutable $now): array
    {
        // Obtener todas las líneas vencidas
        // En una implementación real, se haría una consulta específica en la base de datos
        // usando el índice de vencidos (data-model.md §4)
        return $this->loanRepository->findOverdueItems($now);
    }
}