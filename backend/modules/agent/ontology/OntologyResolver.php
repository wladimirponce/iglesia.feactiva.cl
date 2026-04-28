<?php

declare(strict_types=1);

final class OntologyResolver
{
    public function __construct(
        private readonly OntologyRegistry $registry
    ) {
    }

    public function resolve(string $inputText): array
    {
        $text = $this->normalize($inputText);
        $actionName = $this->detectAction($text);

        if ($actionName === null) {
            return [
                'object' => null,
                'action' => null,
                'tool' => null,
                'permission' => null,
                'resolved_entities' => new stdClass(),
            ];
        }

        $action = $this->registry->action($actionName);

        if (!$action instanceof OntologyAction) {
            return [
                'object' => null,
                'action' => null,
                'tool' => null,
                'permission' => null,
                'resolved_entities' => new stdClass(),
            ];
        }

        return [
            'object' => $action->objectName,
            'action' => $action->name,
            'tool' => $action->toolName,
            'permission' => $action->permissionCode,
            'resolved_entities' => $this->resolveEntities($text, $action->name),
        ];
    }

    private function detectAction(string $text): ?string
    {
        if (preg_match('/\b(crea|crear|registra|registrar)\s+(una\s+)?persona\b/u', $text) === 1) {
            return 'crear_persona';
        }

        if (preg_match('/\b(busca|buscar)\s+(una\s+)?persona\b/u', $text) === 1) {
            return 'buscar_persona';
        }

        if (preg_match('/\b(registra|registrar|crea|crear)\s+(un\s+|una\s+)?diezmo\b/u', $text) === 1) {
            return 'registrar_diezmo';
        }

        if (preg_match('/\b(oracion|oración|solicitud de oracion|solicitud de oración|peticion|petición)\b/u', $text) === 1) {
            return 'crear_oracion';
        }

        if (preg_match('/\b(recu[eé]rdame|recordatorio|agenda)\b/u', $text) === 1) {
            return 'crear_recordatorio';
        }

        return null;
    }

    private function resolveEntities(string $text, string $actionName): array
    {
        $entities = [];

        if (preg_match('/\b([0-9]{1,3}(?:[.][0-9]{3})+|[0-9]+)(?:,\d+)?\b/u', $text, $matches) === 1) {
            $entities['monto'] = (float) str_replace('.', '', $matches[1]);
        }

        if ($actionName === 'registrar_diezmo') {
            $persona = null;
            if (preg_match('/\bdiezmo\s+de\s+(.+?)\s+de\s+[0-9]/iu', $text, $matches) === 1) {
                $persona = trim($matches[1]);
            }
            $persona ??= $this->extractNameAfter($text, 'de');
            if ($persona !== null) {
                $entities['persona'] = $this->titleName($persona);
            }
        }

        if ($actionName === 'buscar_persona') {
            $persona = preg_replace('/^.*?\bpersona\s+/u', '', $text);
            if (is_string($persona) && trim($persona) !== '') {
                $entities['persona'] = $this->titleName(trim($persona));
            }
        }

        if ($actionName === 'crear_persona') {
            $name = preg_replace('/\b(crea|crear|registra|registrar)\s+(una\s+)?persona\s+/u', '', $text);
            if (is_string($name) && trim($name) !== '') {
                $entities['persona'] = $this->titleName(trim(preg_replace('/,\s*(telefono|teléfono|email|correo)\b.*$/u', '', $name) ?? $name));
            }
        }

        if ($actionName === 'crear_oracion') {
            $persona = $this->extractNameAfter($text, 'para');
            if ($persona !== null) {
                $entities['persona'] = $this->titleName($persona);
            }
            if (str_contains($text, ':')) {
                $entities['detalle'] = trim(explode(':', $text, 2)[1]);
            }
        }

        if ($actionName === 'crear_recordatorio') {
            $entities['texto'] = trim($text);
        }

        return $entities;
    }

    private function extractNameAfter(string $text, string $keyword): ?string
    {
        if (preg_match('/\b' . preg_quote($keyword, '/') . '\s+([a-záéíóúñ]+(?:\s+[a-záéíóúñ]+)?)/iu', $text, $matches) !== 1) {
            return null;
        }

        return trim($matches[1]);
    }

    private function normalize(string $value): string
    {
        return trim(mb_strtolower(preg_replace('/\s+/', ' ', $value) ?? $value));
    }

    private function titleName(string $value): string
    {
        return mb_convert_case(trim($value), MB_CASE_TITLE, 'UTF-8');
    }
}
