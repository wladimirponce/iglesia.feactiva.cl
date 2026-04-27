<?php

declare(strict_types=1);

final class FinanzasCuentasService
{
    public function __construct(
        private readonly FinanzasCuentasRepository $repository
    ) {
    }

    public function list(int $tenantId): array
    {
        return $this->repository->list($tenantId);
    }
}
