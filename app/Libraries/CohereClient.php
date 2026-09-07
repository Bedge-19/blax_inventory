<?php

namespace App\Libraries;

use Config\Cohere as CohereConfig;
use RuntimeException;

/**
 * Minimal server-side wrapper around the Cohere Embed API (v2).
 *
 * This is the ONLY class that reads the API key. The key is never returned,
 * echoed, logged, or included in an exception message.
 */
class CohereClient
{
    private CohereConfig $config;

    public function __construct(?CohereConfig $config = null)
    {
        $this->config = $config ?? config(CohereConfig::class);
    }

    public function config(): CohereConfig
    {
        return $this->config;
    }

    public function isConfigured(): bool
    {
        return $this->config->isConfigured();
    }

    /**
     * Embed product texts for storage.
     *
     * @param list<string> $texts
     *
     * @return list<list<float>>
     */
    public function embedDocuments(array $texts): array
    {
        return $this->embed($texts, 'search_document');
    }

    /**
     * Embed a single customer search query.
     *
     * @return list<float>
     */
    public function embedQuery(string $text): array
    {
        $vectors = $this->embed([$text], 'search_query');

        return $vectors[0] ?? [];
    }

    /**
     * Conversational completion with enforced structured (JSON) output.
     *
     * @param list<array{role:string, content:string}> $messages
     * @param array<string, mixed>                     $jsonSchema
     *
     * @return array<string, mixed> Decoded JSON object produced by the model
     */
    public function chat(array $messages, array $jsonSchema): array
    {
        if ($messages === []) {
            throw new RuntimeException('Cohere chat requires at least one message.');
        }

        if (! $this->config->isChatConfigured()) {
            throw new RuntimeException('Cohere chat is not configured (missing COHERE_API_KEY or disabled).');
        }

        $payload = [
            'model'           => $this->config->chatModel,
            'messages'        => $messages,
            'temperature'     => $this->config->chatTemperature,
            'response_format' => [
                'type'        => 'json_object',
                'json_schema' => $jsonSchema,
            ],
        ];

        $decoded = $this->request('v2/chat', $payload, $this->config->chatTimeout);

        $text = $decoded['message']['content'][0]['text'] ?? null;
        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Cohere chat returned an empty response.');
        }

        $parsed = json_decode($text, true);
        if (! is_array($parsed)) {
            throw new RuntimeException('Cohere chat returned malformed structured output.');
        }

        return $parsed;
    }

    /**
     * @param list<string> $texts
     *
     * @return list<list<float>>
     */
    private function embed(array $texts, string $inputType): array
    {
        $texts = array_values(array_filter(array_map(
            static fn ($t) => trim((string) $t),
            $texts
        ), static fn ($t) => $t !== ''));

        if ($texts === []) {
            return [];
        }

        if (count($texts) > $this->config->batchSize) {
            throw new RuntimeException('Too many texts for a single Cohere request.');
        }

        if (! $this->config->isConfigured()) {
            throw new RuntimeException('Cohere is not configured (missing COHERE_API_KEY or disabled).');
        }

        $payload = [
            'model'           => $this->config->model,
            'input_type'      => $inputType,
            'embedding_types' => ['float'],
            'truncate'        => 'END',
            'texts'           => $texts,
        ];

        if ($this->config->outputDimension > 0) {
            $payload['output_dimension'] = $this->config->outputDimension;
        }

        $decoded = $this->request('v2/embed', $payload, $this->config->timeout);

        $vectors = $decoded['embeddings']['float'] ?? null;
        if (! is_array($vectors) || $vectors === []) {
            throw new RuntimeException('Cohere API response contained no embeddings.');
        }

        $out = [];
        foreach ($vectors as $vector) {
            if (! is_array($vector) || $vector === []) {
                throw new RuntimeException('Cohere API returned a malformed embedding vector.');
            }
            $out[] = array_map('floatval', array_values($vector));
        }

        if (count($out) !== count($texts)) {
            throw new RuntimeException('Cohere API returned an unexpected number of embeddings.');
        }

        return $out;
    }

    /**
     * Single place where an authenticated Cohere request is issued.
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function request(string $endpoint, array $payload, int $timeout): array
    {
        $client = service('curlrequest', [
            'baseURI'         => rtrim($this->config->baseUrl, '/') . '/',
            'timeout'         => $timeout,
            'connect_timeout' => $this->config->connectTimeout,
            'http_errors'     => false,
        ], null, null, false);

        try {
            $response = $client->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config->apiKey,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'json' => $payload,
            ]);
        } catch (\Throwable $e) {
            // Transport-level failure (DNS, TLS, timeout).
            throw new RuntimeException('Cohere request failed: ' . $this->scrub($e->getMessage()));
        }

        $status = $response->getStatusCode();
        $body   = (string) $response->getBody();

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException(sprintf(
                'Cohere API returned HTTP %d: %s',
                $status,
                $this->scrub($this->extractApiMessage($body))
            ));
        }

        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Cohere API returned an unreadable response.');
        }

        return $decoded;
    }

    /**
     * Pull a short human-readable message out of a Cohere error payload
     * without leaking the raw body.
     */
    private function extractApiMessage(string $body): string
    {
        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            $message = $decoded['message'] ?? ($decoded['error']['message'] ?? null);
            if (is_string($message) && $message !== '') {
                return mb_substr($message, 0, 200);
            }
        }

        return 'unexpected error response';
    }

    /**
     * Defensive last line: make sure the credential can never reach a log,
     * an exception trace, or a response body.
     */
    private function scrub(string $message): string
    {
        $key = $this->config->apiKey;
        if ($key !== '') {
            $message = str_replace($key, '[REDACTED]', $message);
        }

        return preg_replace('/Bearer\s+\S+/i', 'Bearer [REDACTED]', $message) ?? $message;
    }
}
