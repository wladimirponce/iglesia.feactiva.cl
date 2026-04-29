<?php

declare(strict_types=1);

final class GoogleOAuthValidator
{
    /** @return array<int, array{field:string,message:string}> */
    public function validateAuthUrl(array $query): array
    {
        $service = (string) ($query['service'] ?? '');
        if (!in_array($service, ['calendar', 'gmail', 'both'], true)) {
            return [['field' => 'service', 'message' => 'Service debe ser calendar, gmail o both.']];
        }

        return [];
    }
}
