<?php

declare(strict_types=1);

final class ConversationStateResolver
{
    public function isAffirmative(string $text): bool
    {
        $text = $this->normalizeText($text);
        return preg_match('/\b(si|claro|ok|quiero|envialo|esta bien)\b/iu', $text) === 1;
    }

    public function isNegative(string $text): bool
    {
        $text = $this->normalizeText($text);
        return preg_match('/\b(no|cancelar|cancela|cancelalo)\b/iu', $text) === 1;
    }

    public function wantsImprove(string $text): bool
    {
        $text = $this->normalizeText($text);
        return preg_match('/\b(mejoralo|mejorar|redactalo mejor)\b/iu', $text) === 1;
    }

    // Returns true when the user wants the text improved AND sent in one step
    // (e.g. "mejoralo y envialo") so the caller can improve + approve atomically.
    public function wantsImproveAndSend(string $text): bool
    {
        $text = $this->normalizeText($text);
        return $this->wantsImprove($text) && $this->isAffirmative($text);
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
        $normalized = $this->normalizeText($text);
        $pattern = '/\b(?:envia|enviale|manda|mandale|dile)\b(?:\s+un)?(?:\s+(?:whatsapp|mensaje))?\s+(?:al|a)\s+(?:numero\s+)?(.+?)\s+(?:diciendo|que\s+diga|diga|da|con\s+el\s+texto|:)\s*(.+)$/iu';

        if (preg_match($pattern, $normalized, $matches) === 1) {
            return [
                'recipient_text' => trim($matches[1]),
                'message_text' => trim($matches[2]),
            ];
        }

        return null;
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($transliterated !== false) {
            $text = $transliterated;
        }
        return str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $text
        );
    }
}
