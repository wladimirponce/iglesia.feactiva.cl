<?php

declare(strict_types=1);

final class DateTimeResolver
{
    public function resolve(string $input, string $timezone = 'America/Santiago'): array
    {
        $original = trim($input);
        $text = $this->normalize($original);
        $tz = new DateTimeZone($timezone);
        $now = new DateTimeImmutable('now', $tz);

        if (preg_match('/\b(20[0-9]{2}-[0-9]{2}-[0-9]{2})(?:\s+([0-2]?[0-9]):([0-5][0-9]))?\b/u', $text, $matches) === 1) {
            $time = isset($matches[2]) ? sprintf('%02d:%02d:00', (int) $matches[2], (int) $matches[3]) : '09:00:00';
            return $this->ok($matches[1] . ' ' . $time, $original);
        }

        $date = null;
        if (preg_match('/\bpasado manana\b/u', $text) === 1) {
            $date = $now->modify('+2 days');
        } elseif (preg_match('/\bmanana\b/u', $text) === 1) {
            $date = $now->modify('+1 day');
        } elseif (preg_match('/\bhoy\b/u', $text) === 1) {
            $date = $now;
        } elseif (preg_match('/\bproximo\s+(lunes|martes|miercoles|jueves|viernes|sabado|domingo)\b/u', $text, $matches) === 1) {
            $date = $this->nextWeekday($now, $matches[1], true);
        } elseif (preg_match('/\bel\s+(lunes|martes|miercoles|jueves|viernes|sabado|domingo)\b/u', $text, $matches) === 1) {
            $date = $this->nextWeekday($now, $matches[1], false);
        } elseif (preg_match('/\b(lunes|martes|miercoles|jueves|viernes|sabado|domingo)\b/u', $text, $matches) === 1) {
            $date = $this->nextWeekday($now, $matches[1], false);
        } elseif (preg_match('/\ben\s+([0-9]+)\s+horas?\b/u', $text, $matches) === 1) {
            $dateTime = $now->modify('+' . (int) $matches[1] . ' hours');
            return $this->ok($dateTime->format('Y-m-d H:i:s'), $original);
        }

        if (!$date instanceof DateTimeImmutable) {
            return ['resolved' => false, 'missing' => 'fecha_hora', 'original' => $original];
        }

        $time = '09:00:00';
        if (preg_match('/\ba\s+las\s+([0-2]?[0-9])(?::([0-5][0-9]))?\b/u', $text, $matches) === 1) {
            $time = sprintf('%02d:%02d:00', (int) $matches[1], isset($matches[2]) ? (int) $matches[2] : 0);
        }

        return $this->ok($date->format('Y-m-d') . ' ' . $time, $original);
    }

    private function ok(string $datetime, string $original): array
    {
        return ['resolved' => true, 'datetime' => $datetime, 'original' => $original];
    }

    private function nextWeekday(DateTimeImmutable $now, string $weekday, bool $forceNextWeek): DateTimeImmutable
    {
        $map = [
            'lunes' => 1,
            'martes' => 2,
            'miercoles' => 3,
            'jueves' => 4,
            'viernes' => 5,
            'sabado' => 6,
            'domingo' => 7,
        ];
        $target = $map[$weekday] ?? 1;
        $current = (int) $now->format('N');
        $days = ($target - $current + 7) % 7;
        if ($days === 0 || $forceNextWeek) {
            $days += 7;
        }
        return $now->modify('+' . $days . ' days');
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        return preg_replace('/\s+/', ' ', $normalized === false ? $value : $normalized) ?? $value;
    }
}
