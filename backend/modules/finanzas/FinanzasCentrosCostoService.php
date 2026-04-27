<?php

declare(strict_types=1);

final class FinanzasCentrosCostoService
{
    public function __construct(
        private readonly FinanzasCentrosCostoRepository $repository
    ) {
    }

    public function list(int $tenantId): array
    {
        return $this->repository->list($tenantId);
    }
}
