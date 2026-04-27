<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function generate_poster_with_gemini(string $prompt): array
{
    $apiKey = env_value('GEMINI_API_KEY', '');
    $model = env_value('GEMINI_MODEL', 'gemini-2.0-flash-preview-image-generation');

    if ($apiKey === '') {
        return ['ok' => false, 'error' => 'Missing GEMINI_API_KEY in .env'];
    }

    $systemPrompt = env_value(
        'GEMINI_SYSTEM_PROMPT',
        'Create a 1:1 square Indian marketing poster as PNG. Keep it readable for mobile users.'
    );
    $finalPrompt = $systemPrompt . "\n" . $prompt;

    $endpoint = sprintf(
        'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
        rawurlencode($model),
        rawurlencode($apiKey)
    );

    $payload = [
        'contents' => [[
            'parts' => [[
                'text' => $finalPrompt,
            ]],
        ]],
        'generationConfig' => [
            'responseModalities' => ['TEXT', 'IMAGE'],
        ],
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 40,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $error !== '') {
        return ['ok' => false, 'error' => 'Gemini request failed: ' . $error];
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Invalid Gemini response.'];
    }

    if ($status >= 400) {
        $message = $data['error']['message'] ?? ('HTTP ' . $status);
        return ['ok' => false, 'error' => 'Gemini error: ' . $message];
    }

    $parts = $data['candidates'][0]['content']['parts'] ?? [];
    foreach ($parts as $part) {
        $inline = $part['inlineData'] ?? null;
        if (!is_array($inline)) {
            continue;
        }

        $mime = $inline['mimeType'] ?? '';
        $b64 = $inline['data'] ?? '';
        if (!is_string($b64) || $b64 === '') {
            continue;
        }

        $binary = base64_decode($b64, true);
        if ($binary === false) {
            continue;
        }

        return [
            'ok' => true,
            'mime' => is_string($mime) && $mime !== '' ? $mime : 'image/png',
            'data' => $binary,
        ];
    }

    return ['ok' => false, 'error' => 'Gemini did not return an image.'];
}
