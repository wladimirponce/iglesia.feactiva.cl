<?php

declare(strict_types=1);

function env(string $key, mixed $default = null): mixed
{
    static $loaded = false;

    if (!$loaded) {
        $loaded = true;
        $envPath = dirname(__DIR__, 2) . '/.env';

        if (is_readable($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines ?: [] as $line) {
                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                if (
                    (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))
                ) {
                    $value = substr($value, 1, -1);
                }

                if ($name !== '' && getenv($name) === false) {
                    putenv($name . '=' . $value);
                    $_ENV[$name] = $value;
                }
            }
        }
    }

    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    return match (strtolower($value)) {
        'true' => true,
        'false' => false,
        'null' => null,
        default => $value,
    };
}
