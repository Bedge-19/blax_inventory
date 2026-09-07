<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Cohere Embed configuration.
 *
 * The API key is NEVER stored in this file. It is read from the environment
 * variable COHERE_API_KEY (see .env). Every other value below may be
 * overridden from .env using the "cohere.<property>" prefix, e.g.
 *
 *     cohere.minScore = 0.30
 *     cohere.enabled  = false
 */
class Cohere extends BaseConfig
{
    /**
     * Populated from COHERE_API_KEY. Never commit a value here.
     */
    public string $apiKey = '';

    public string $baseUrl = 'https://api.cohere.com';

    /**
     * Embedding model. The project targets Cohere Embed v4.0.
     */
    public string $model = 'embed-v4.0';

    /**
     * Generative model used by the RHK Assistant chat.
     *
     * Must support `response_format: json_object` with a json_schema.
     * Verified: command-a-03-2025 works; c4ai-aya-expanse-32b returns HTTP 400.
     */
    public string $chatModel = 'command-a-03-2025';

    /**
     * Master switch for the conversational layer. When false the assistant
     * degrades to semantic search with canned replies (previous behaviour).
     */
    public bool $chatEnabled = true;

    /**
     * Chat round trips measured ~3s, so this is deliberately looser than the
     * embed timeout.
     */
    public int $chatTimeout = 15;

    /**
     * Low temperature keeps the structured output and the few-shot Cebuano
     * style stable.
     */
    public float $chatTemperature = 0.2;

    /**
     * Messages (not turns) of conversation history sent to the model.
     * Bounded to keep token usage flat regardless of chat length.
     */
    public int $historyMessages = 6;

    /**
     * Maximum product cards rendered inside a single chat reply.
     */
    public int $maxChatProducts = 4;

    /**
     * Stricter relevance floor for the assistant chat.
     *
     * The catalog grid can tolerate a few loosely-related extras, but the
     * assistant makes an explicit verbal claim ("here are the blue ballpens"),
     * so a weak match becomes a wrong statement. Higher than $minScore on
     * purpose.
     */
    public float $chatMinScore = 0.30;

    /**
     * Output vector size. embed-v4.0 supports 256 / 512 / 1024 / 1536.
     * Changing this invalidates every stored embedding (re-run products:embed --all).
     */
    public int $outputDimension = 1024;

    /**
     * Master switch. When false (or the key is missing) the marketplace
     * silently falls back to the pre-existing keyword search.
     */
    public bool $enabled = true;

    /**
     * Absolute cosine floor. Below this a query has no real answer in the
     * catalog (e.g. "refrigerator" against a school-supply marketplace).
     */
    public float $minScore = 0.22;

    /**
     * Adaptive cutoff: keep only matches scoring at least this fraction of the
     * best match. Cosine magnitudes shift with query length, so this is what
     * actually keeps weakly-related products out of the grid.
     * 1.0 = only the single best match, 0.0 = disabled.
     */
    public float $relativeCutoff = 0.80;

    /**
     * Maximum number of semantically-matched product ids to hand to the
     * deterministic product query.
     */
    public int $topK = 120;

    /**
     * Texts per Cohere embed request. The API accepts up to 96.
     */
    public int $batchSize = 96;

    /**
     * HTTP timeouts (seconds). Kept short so a slow API can never hang a page.
     */
    public int $timeout        = 6;
    public int $connectTimeout = 3;

    /**
     * How long a query embedding is cached (seconds). Repeat searches cost
     * zero API calls.
     */
    public int $queryCacheTtl = 3600;

    /**
     * Search input validation bounds.
     */
    public int $minQueryLength = 3;
    public int $maxQueryLength = 200;

    public function __construct()
    {
        parent::__construct();

        // Honour the documented COHERE_API_KEY variable name.
        if ($this->apiKey === '') {
            $this->apiKey = trim((string) (env('COHERE_API_KEY') ?? ''), " \t\n\r\0\x0B'\"");
        }

        // .env values arrive as strings; normalise to the declared types.
        $this->outputDimension = (int) $this->outputDimension;
        $this->topK            = (int) $this->topK;
        $this->batchSize       = max(1, min(96, (int) $this->batchSize));
        $this->timeout         = (int) $this->timeout;
        $this->connectTimeout  = (int) $this->connectTimeout;
        $this->queryCacheTtl   = (int) $this->queryCacheTtl;
        $this->minQueryLength  = (int) $this->minQueryLength;
        $this->maxQueryLength  = (int) $this->maxQueryLength;
        $this->minScore        = (float) $this->minScore;
        $this->relativeCutoff  = max(0.0, min(1.0, (float) $this->relativeCutoff));
        $this->enabled         = (bool) $this->enabled;
        $this->chatEnabled     = (bool) $this->chatEnabled;
        $this->chatTimeout     = (int) $this->chatTimeout;
        $this->chatTemperature = (float) $this->chatTemperature;
        $this->historyMessages = max(0, (int) $this->historyMessages);
        $this->maxChatProducts = max(1, (int) $this->maxChatProducts);
        $this->chatMinScore    = (float) $this->chatMinScore;
    }

    /**
     * True only when semantic search can actually be attempted.
     */
    public function isConfigured(): bool
    {
        return $this->enabled && $this->apiKey !== '';
    }

    /**
     * True only when the conversational layer can actually be attempted.
     */
    public function isChatConfigured(): bool
    {
        return $this->chatEnabled && $this->apiKey !== '';
    }
}
