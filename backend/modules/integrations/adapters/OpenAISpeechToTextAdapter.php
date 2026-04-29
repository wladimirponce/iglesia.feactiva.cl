<?php

declare(strict_types=1);

final class OpenAISpeechToTextAdapter implements SpeechToTextInterface
{
    private const TRANSCRIPTIONS_URL = 'https://api.openai.com/v1/audio/transcriptions';

    public function canTranscribe(): bool
    {
        return trim($this->apiKey()) !== '' && function_exists('curl_init') && class_exists('CURLFile');
    }

    /** @param array<string, mixed> $metadata */
    public function transcribe(string $mediaUrl, array $metadata = []): array
    {
        if (!$this->canTranscribe()) {
            return [
                'success' => false,
                'error' => 'OPENAI_STT_NOT_CONFIGURED',
                'transcription_text' => '',
                'simulated' => false,
            ];
        }

        $mediaUrl = trim($mediaUrl);
        $mediaId = trim((string) ($metadata['media_id'] ?? ''));
        $resolvedFromId = false;
        if ($mediaUrl === '' && $mediaId !== '') {
            $resolved = $this->resolveMediaUrl($mediaId);
            if (($resolved['success'] ?? false) !== true) {
                return [
                    'success' => false,
                    'error' => $resolved['error'] ?? 'MEDIA_URL_RESOLVE_FAILED',
                    'transcription_text' => '',
                    'simulated' => false,
                ];
            }
            $mediaUrl = (string) $resolved['media_url'];
            $resolvedFromId = true;
        }

        if ($mediaUrl === '') {
            return [
                'success' => false,
                'error' => 'MEDIA_URL_MISSING',
                'transcription_text' => '',
                'simulated' => false,
            ];
        }

        $download = $this->downloadMedia($mediaUrl);
        if (($download['success'] ?? false) !== true && !$resolvedFromId && $mediaId !== '') {
            $resolved = $this->resolveMediaUrl($mediaId);
            if (($resolved['success'] ?? false) === true) {
                $mediaUrl = (string) $resolved['media_url'];
                $download = $this->downloadMedia($mediaUrl);
                $resolvedFromId = true;
            }
        }
        if (($download['success'] ?? false) !== true) {
            return [
                'success' => false,
                'error' => $download['error'] ?? 'MEDIA_DOWNLOAD_FAILED',
                'transcription_text' => '',
                'simulated' => false,
            ];
        }

        $filePath = (string) $download['file_path'];
        try {
            $result = $this->transcribeFile($filePath, (string) ($download['mime_type'] ?? 'audio/ogg'));
            $result['media_resolved_from_id'] = $resolvedFromId;
            return $result;
        } finally {
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }
    }

    /** @return array<string, mixed> */
    private function resolveMediaUrl(string $mediaId): array
    {
        $waToken = trim((string) env('WA_ACCESS_TOKEN', ''));
        if ($waToken === '') {
            return ['success' => false, 'error' => 'WA_ACCESS_TOKEN_MISSING'];
        }

        $version = trim((string) env('WA_GRAPH_VERSION', 'v22.0')) ?: 'v22.0';
        $ch = curl_init('https://graph.facebook.com/' . $version . '/' . rawurlencode($mediaId));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $waToken,
                'Accept: application/json',
            ],
        ]);

        $raw = curl_exec($ch);
        $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $httpStatus < 200 || $httpStatus >= 300) {
            return [
                'success' => false,
                'error' => 'MEDIA_URL_RESOLVE_FAILED',
                'http_status' => $httpStatus,
                'curl_error' => $curlError !== '' ? $curlError : null,
            ];
        }

        $decoded = json_decode((string) $raw, true);
        $mediaUrl = is_array($decoded) ? trim((string) ($decoded['url'] ?? '')) : '';
        if ($mediaUrl === '') {
            return ['success' => false, 'error' => 'MEDIA_URL_NOT_FOUND', 'http_status' => $httpStatus];
        }

        return ['success' => true, 'media_url' => $mediaUrl, 'http_status' => $httpStatus];
    }

    /** @return array<string, mixed> */
    private function downloadMedia(string $mediaUrl): array
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'feactiva_wa_audio_');
        if ($tmpFile === false) {
            return ['success' => false, 'error' => 'TEMP_FILE_FAILED'];
        }

        $fp = fopen($tmpFile, 'wb');
        if ($fp === false) {
            @unlink($tmpFile);
            return ['success' => false, 'error' => 'TEMP_FILE_OPEN_FAILED'];
        }

        $headers = ['Accept: audio/*,application/octet-stream'];
        $waToken = trim((string) env('WA_ACCESS_TOKEN', ''));
        if ($waToken !== '') {
            $headers[] = 'Authorization: Bearer ' . $waToken;
        }

        $ch = curl_init($mediaUrl);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $ok = curl_exec($ch);
        $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $curlError = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($ok === false || $httpStatus < 200 || $httpStatus >= 300 || filesize($tmpFile) === 0) {
            @unlink($tmpFile);
            return [
                'success' => false,
                'error' => 'MEDIA_DOWNLOAD_FAILED',
                'http_status' => $httpStatus,
                'curl_error' => $curlError !== '' ? $curlError : null,
            ];
        }

        return [
            'success' => true,
            'file_path' => $tmpFile,
            'mime_type' => $contentType !== '' ? $contentType : 'audio/ogg',
        ];
    }

    /** @return array<string, mixed> */
    private function transcribeFile(string $filePath, string $mimeType): array
    {
        $model = trim((string) env('OPENAI_TRANSCRIBE_MODEL', 'gpt-4o-mini-transcribe')) ?: 'gpt-4o-mini-transcribe';
        $postFields = [
            'model' => $model,
            'file' => new CURLFile($filePath, $mimeType, 'whatsapp-audio.ogg'),
            'response_format' => 'json',
            'language' => trim((string) env('OPENAI_TRANSCRIBE_LANGUAGE', 'es')) ?: 'es',
        ];

        $prompt = trim((string) env('OPENAI_TRANSCRIBE_PROMPT', 'Transcribe el audio de WhatsApp en espanol chileno. Devuelve solo el texto hablado.'));
        if ($prompt !== '') {
            $postFields['prompt'] = $prompt;
        }

        $ch = curl_init(self::TRANSCRIPTIONS_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey(),
            ],
        ]);

        $raw = curl_exec($ch);
        $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $httpStatus < 200 || $httpStatus >= 300) {
            return [
                'success' => false,
                'error' => 'OPENAI_TRANSCRIPTION_FAILED',
                'http_status' => $httpStatus,
                'curl_error' => $curlError !== '' ? $curlError : null,
            ];
        }

        $decoded = json_decode((string) $raw, true);
        $text = is_array($decoded) ? trim((string) ($decoded['text'] ?? '')) : '';

        return [
            'success' => $text !== '',
            'simulated' => false,
            'provider' => 'openai',
            'model' => $model,
            'transcription_text' => $text,
            'http_status' => $httpStatus,
        ];
    }

    private function apiKey(): string
    {
        return (string) env('OPENAI_API_KEY', '');
    }
}
