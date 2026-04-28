<?php

declare(strict_types=1);

final class ConversationStateResolver
{
    public function isAffirmative(string $text): bool
    {
        return preg_match('/\b(si|s[ií]|claro|ok|quiero|envialo|env[ií]alo|esta bien|est[aá] bien)\b/iu', $text) === 1;
    }

    public function isNegative(string $text): bool
    {
        return preg_match('/\b(no|cancelar|cancela|cancelalo|canc[eé]lalo)\b/iu', $text) === 1;
    }

    public function wantsImprove(string $text): bool
    {
        return preg_match('/\b(mejoralo|mej[oó]ralo|mejorar|redactalo mejor|red[aá]ctalo mejor)\b/iu', $text) === 1;
    }

    public function extractNameEmail(string $text): ?array
    {
        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', $text, $emailMatch) !== 1) {
            return null;
        }
        $email = trim($emailMatch[0]);
        $name = trim(str_replace($email, '', $text));
        $name = trim(preg_replace('/[,;]+/', ' ', $name) ?? $name);
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        return ['name' => $name, 'email' => $email];
    }

    public function detectOutboundDraft(string $text): ?array
    {
        if (preg_match('/\b(?:enviale|env[ií]ale|manda|dile)\b.*?\b(?:whatsapp|mensaje)?\s*(?:al|a)\s+(.+?)\s+(?:diciendo|que diga|:)\s*(.+)$/iu', $text, $matches) === 1) {
            return [
                'recipient_text' => trim($matches[1]),
                'message_text' => trim($matches[2]),
            ];
        }
        return null;
    }
}
