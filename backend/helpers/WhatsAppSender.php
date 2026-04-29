<?php

declare(strict_types=1);

final class WhatsAppSender
{
    public static function send(string $to, string $text): bool
    {
        $accessToken   = $_ENV['WHATSAPP_INTEGRATION_KEY'] ?? '';
        $phoneNumberId = $_ENV['WHATSAPP_PHONE_NUMBER_ID'] ?? '';

        if ($accessToken === '' || $phoneNumberId === '') {
            error_log('WhatsAppSender: missing WHATSAPP_INTEGRATION_KEY or WHATSAPP_PHONE_NUMBER_ID');
            return false;
        }

        $url     = "https://graph.facebook.com/v20.0/{$phoneNumberId}/messages";
        $payload = json_encode([
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'text',
            'text'              => ['body' => $text],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ],
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response   = curl_exec($ch);
        $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '' || $httpStatus >= 400) {
            error_log('WhatsAppSender: send failed — status=' . $httpStatus . ' curl=' . $curlError . ' body=' . (string) $response);
            return false;
        }

        return true;
    }
}
