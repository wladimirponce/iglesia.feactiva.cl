<?php

declare(strict_types=1);

final class EntityResolver
{
    public function __construct(
        private readonly PersonEntityResolver $personResolver = new PersonEntityResolver(),
        private readonly FinancialAccountEntityResolver $accountResolver = new FinancialAccountEntityResolver(),
        private readonly FinancialCategoryEntityResolver $categoryResolver = new FinancialCategoryEntityResolver(),
        private readonly FamilyEntityResolver $familyResolver = new FamilyEntityResolver(),
        private readonly DiscipleshipRouteEntityResolver $routeResolver = new DiscipleshipRouteEntityResolver()
    ) {
    }

    /** @return array{fields: array<string, mixed>, results: array<string, EntityResolutionResult>} */
    public function resolveToolInput(int $tenantId, ?string $toolName, array $fields): array
    {
        $results = [];

        if (isset($fields['persona_nombre']) && $this->emptyId($fields['persona_id'] ?? null)) {
            $result = $this->personResolver->resolve($tenantId, (string) $fields['persona_nombre']);
            $results['persona_id'] = $result;
            if ($result->resolved) {
                $fields['persona_id'] = $result->id;
                $fields['persona_display_name'] = $result->displayName;
            }
        }

        if (isset($fields['cuenta_nombre']) && $this->emptyId($fields['cuenta_id'] ?? null)) {
            $result = $this->accountResolver->resolve($tenantId, (string) $fields['cuenta_nombre']);
            $results['cuenta_id'] = $result;
            if ($result->resolved) {
                $fields['cuenta_id'] = $result->id;
                $fields['cuenta_display_name'] = $result->displayName;
            }
        }

        if ($this->emptyId($fields['categoria_id'] ?? null)) {
            $categoryName = $fields['categoria_nombre'] ?? $fields['subtipo'] ?? null;
            if (is_string($categoryName) && trim($categoryName) !== '') {
                $tipo = $toolName === 'finanzas_create_expense' ? 'egreso' : 'ingreso';
                $result = $this->categoryResolver->resolve($tenantId, $tipo, $categoryName);
                $results['categoria_id'] = $result;
                if ($result->resolved) {
                    $fields['categoria_id'] = $result->id;
                    $fields['categoria_display_name'] = $result->displayName;
                }
            }
        }

        if (isset($fields['familia_nombre']) && $this->emptyId($fields['familia_id'] ?? null)) {
            $result = $this->familyResolver->resolve($tenantId, (string) $fields['familia_nombre']);
            $results['familia_id'] = $result;
            if ($result->resolved) {
                $fields['familia_id'] = $result->id;
                $fields['familia_display_name'] = $result->displayName;
            }
        }

        if (isset($fields['ruta_nombre']) && $this->emptyId($fields['ruta_id'] ?? null)) {
            $result = $this->routeResolver->resolve($tenantId, (string) $fields['ruta_nombre']);
            $results['ruta_id'] = $result;
            if ($result->resolved) {
                $fields['ruta_id'] = $result->id;
                $fields['ruta_display_name'] = $result->displayName;
            }
        }

        return ['fields' => $fields, 'results' => $results];
    }

    /** @param array<string, EntityResolutionResult> $results */
    public function ambiguousResults(array $results): array
    {
        return array_filter($results, static fn (EntityResolutionResult $result): bool => $result->ambiguous);
    }

    /** @param array<string, EntityResolutionResult> $results */
    public function notFoundResults(array $results): array
    {
        return array_filter($results, static fn (EntityResolutionResult $result): bool => !$result->resolved && !$result->ambiguous);
    }

    private function emptyId(mixed $value): bool
    {
        return $value === null || $value === '' || $value === 0 || $value === '0';
    }
}
