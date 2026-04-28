<?php

declare(strict_types=1);

final class AgentSqlSafetyGuard
{
    private const FORBIDDEN_KEYWORDS = [
        'INSERT',
        'UPDATE',
        'DELETE',
        'DROP',
        'ALTER',
        'TRUNCATE',
        'CREATE',
        'REPLACE',
        'RENAME',
        'GRANT',
        'REVOKE',
        'LOCK',
        'UNLOCK',
        'CALL',
        'EXEC',
        'EXECUTE',
        'LOAD',
        'MERGE',
        'UPSERT',
    ];

    public function assertSafe(string $sqlTemplate, array $parametersDefinition): void
    {
        $sql = trim($sqlTemplate);

        if ($sql === '') {
            throw new RuntimeException('SQL_SKILL_EMPTY_SQL');
        }

        if (!preg_match('/^SELECT\b/i', $sql)) {
            throw new RuntimeException('SQL_SKILL_ONLY_SELECT_ALLOWED');
        }

        if (preg_match('/(;|--|#|\/\*)/', $sql)) {
            throw new RuntimeException('SQL_SKILL_UNSAFE_SQL_TOKENS');
        }

        if (preg_match('/\bSELECT\s+\*/i', $sql)) {
            throw new RuntimeException('SQL_SKILL_SELECT_STAR_BLOCKED');
        }

        foreach (self::FORBIDDEN_KEYWORDS as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $sql)) {
                throw new RuntimeException('SQL_SKILL_FORBIDDEN_KEYWORD_' . $keyword);
            }
        }

        if (preg_match('/\bINTO\s+(OUTFILE|DUMPFILE)\b/i', $sql) || preg_match('/\bLOAD_FILE\s*\(/i', $sql)) {
            throw new RuntimeException('SQL_SKILL_FILE_ACCESS_BLOCKED');
        }

        if (preg_match('/\b(information_schema|mysql|performance_schema|sys)\./i', $sql)) {
            throw new RuntimeException('SQL_SKILL_SYSTEM_SCHEMA_BLOCKED');
        }

        if (!preg_match('/\bWHERE\b[\s\S]*\b(?:[a-zA-Z_][a-zA-Z0-9_]*\.)?tenant_id\s*=\s*:tenant_id\b/i', $sql)) {
            throw new RuntimeException('SQL_SKILL_TENANT_FILTER_REQUIRED');
        }

        if (str_contains($sql, '?')) {
            throw new RuntimeException('SQL_SKILL_NAMED_PARAMETERS_REQUIRED');
        }

        $placeholders = $this->extractPlaceholders($sql);

        if (!in_array('tenant_id', $placeholders, true)) {
            throw new RuntimeException('SQL_SKILL_TENANT_PARAMETER_REQUIRED');
        }

        $defined = $this->parameterNames($parametersDefinition);

        foreach ($placeholders as $placeholder) {
            if ($placeholder === 'tenant_id') {
                continue;
            }

            if (!in_array($placeholder, $defined, true)) {
                throw new RuntimeException('SQL_SKILL_PARAMETER_NOT_DEFINED_' . strtoupper($placeholder));
            }
        }
    }

    public function extractPlaceholders(string $sqlTemplate): array
    {
        preg_match_all('/:([a-zA-Z_][a-zA-Z0-9_]*)/', $sqlTemplate, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    public function requiredParameterNames(array $parametersDefinition): array
    {
        $required = [];

        foreach ($parametersDefinition as $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $name = $definition['name'] ?? null;

            if (!is_string($name) || trim($name) === '' || $name === 'tenant_id') {
                continue;
            }

            if (($definition['required'] ?? true) === true) {
                $required[] = $name;
            }
        }

        return array_values(array_unique($required));
    }

    private function parameterNames(array $parametersDefinition): array
    {
        $names = [];

        foreach ($parametersDefinition as $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $name = $definition['name'] ?? null;

            if (is_string($name) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }
}
