<?php

namespace App\Libraries;

use App\Models\ProductEmbeddingModel;
use App\Models\ProductModel;
use Config\Cohere as CohereConfig;
use RuntimeException;

/**
 * Semantic product search built on Cohere Embed v4.0.
 *
 * Responsibilities
 *  - turn an existing product row into a searchable embedding document
 *  - persist / refresh product embeddings (only when the document changes)
 *  - embed a customer query once per search and rank stored vectors
 *
 * The service NEVER decides deterministic marketplace rules (stock, price,
 * seller, availability, pagination). It only returns relevance-ordered ids
 * which are then fed into the existing product query.
 */
class SemanticSearchService
{
    private CohereConfig $config;
    private CohereClient $client;
    private ProductModel $products;
    private ProductEmbeddingModel $embeddings;

    /**
     * Multilingual vocabulary appended to a product's embedding document,
     * keyed by the EXISTING categories.slug value.
     *
     * The catalog is written in English; Cebuano/Bisaya and Tagalog shoppers
     * describe the same items with completely different words. Adding this
     * controlled vocabulary to the document side (not the query side) lets the
     * multilingual model line up "stick pang sulat nga blue ang tubig" with
     * "Gel Pen ... blue ink" without translating anything at search time.
     *
     * @var array<string, string>
     */
    private const CATEGORY_GLOSS = [
        'paper-pads' => 'papel, bond paper, pad paper, intermediate pad, yellow pad, sticky notes, sheets, writing paper, papel para sa assignment, pang notes, pang print',
        'pens-pencils' => 'ballpen, bolpen, pen, panulat, pangsulat, pang sulat, stick pang sulat, gamit pang sulat, lapis, pencil, sign pen, gel pen, ink, tinta, tubig sa bolpen, tubig sa ballpen, writing instrument',
        'art-supplies' => 'pang drawing, pangdrawing, gamit pang drawing, drawing, sketching, art, arts and crafts, crayon, coloring, pangkolor, kolor, pintura, paint, marker, highlighter, brush',
        'backpacks' => 'bag, backpack, bagpack, school bag, bag sa eskwela, bag pang eskwela, sako, tote, pouch, carrier, luggage',
        'calculators' => 'calculator, kalkulator, pang kwenta, pangkwenta, pang math, pang matematika, scientific calculator, graphing calculator, math, numbers',
        'organizers' => 'organizer, desk organizer, file organizer, pang organize, pang ayos, holder, tray, rack, caddy, pang tago sa gamit',
        'storage' => 'storage, storage box, kahon, container, bin, crate, pang tago, pang imbak, pang sulod, keeper',
        'glue-adhesives' => 'glue, pandikit, pang dikit, pangdikit, adhesive, paste, tape, sticker, pang tapal, epoxy, gum',
        'notebooks-planners' => 'notebook, notbuk, libro, notes, pang notes, gamit pang notes, journal, diary, planner, composition notebook, assignment notebook, papel pang notes',
        'writing-instruments' => 'ballpen, bolpen, pen, gel pen, sign pen, rollerball, fountain pen, marker, panulat, pangsulat, pang sulat, stick pang sulat, gamit pang sulat, ink, tinta, tubig sa bolpen, tubig sa ballpen, writing',
    ];

    /**
     * Colour terms already present in existing name/description text, mapped
     * to their Cebuano/Tagalog equivalents. Nothing is invented: a colour is
     * only added to the document when the product text already mentions it.
     * Keys are regex alternations matched on whole words only.
     *
     * @var array<string, string>
     */
    private const COLOUR_GLOSS = [
        'blue'          => 'blue, asul, bughaw',
        'black'         => 'black, itom, itim',
        'red'           => 'red, pula',
        'green'         => 'green, berde, lunhaw',
        'yellow'        => 'yellow, dalag, dilaw',
        'orange'        => 'orange, kahel',
        'purple|violet' => 'purple, violet, lila',
        'pink'          => 'pink, rosas',
        'white'         => 'white, puti',
        'gray|grey'     => 'grey, gray, abuhon, abo',
        'brown'         => 'brown, kape, kayumanggi',
        'silver'        => 'silver, plata',
        'gold'          => 'gold, bulawan, ginto',
        'neon'          => 'neon, hayag nga kolor',
        'pastel'        => 'pastel, malumo nga kolor',
    ];

    /**
     * Products whose existing text advertises a range of colours ("assorted
     * colors", "six pastel colors", ...) genuinely include the individual
     * colours, so the concrete colour words are made searchable. This expands
     * an attribute the product text already states - it does not invent one.
     */
    private const MULTICOLOUR_PATTERN = '/\b(?:assorted|multi-?colou?red|multi-?colou?rs?|(?:two|three|four|five|six|seven|eight|nine|ten|twelve|\d+)[\s-]*colou?rs?)\b|\bcolou?rs\b/u';

    private const MULTICOLOUR_GLOSS = 'assorted colors, nagkalain-laing kolor, iba-ibang kulay, available in blue, asul, black, itom, red, pula, green, berde, yellow, dilaw, purple, lila, pink, rosas, orange';

    /**
     * Function words that carry no product signal. Removing them tightens the
     * query embedding and improves the query cache hit rate. This is NOT
     * translation - the remaining Cebuano/Tagalog content words are embedded
     * as-is by the multilingual model.
     *
     * @var list<string>
     */
    private const QUERY_STOPWORDS = [
        // Cebuano / Bisaya
        'gusto', 'unta', 'nako', 'naku', 'ko', 'nimo', 'niya', 'iyahang', 'iyaha', 'iyang',
        'tung', 'tong', 'kanang', 'kana', 'kani', 'ang', 'ug', 'og', 'nga', 'naa',
        'moy', 'mo', 'diay', 'pod', 'pud', 'gyud', 'jud', 'ba', 'ra', 'lang', 'man',
        'nangita', 'pangita', 'akoa', 'akong', 'ako', 'ni', 'sa', 'para', 'kay',
        // Tagalog / Filipino
        'yung', 'ung', 'ako', 'ninyo', 'niyo', 'meron', 'mayroon', 'ang', 'ng',
        'nang', 'po', 'ba', 'yan', 'iyan', 'ito', 'may', 'kailangan', 'hanap',
        'hanapin', 'gusto', 'bang',
        // English
        'i', 'a', 'an', 'the', 'is', 'are', 'do', 'you', 'have', 'want', 'need',
        'looking', 'look', 'for', 'some', 'something', 'any', 'me', 'my', 'of',
        'to', 'with', 'that', 'this', 'please', 'im', 'am',
    ];

    public function __construct(
        ?CohereConfig $config = null,
        ?CohereClient $client = null,
        ?ProductModel $products = null,
        ?ProductEmbeddingModel $embeddings = null
    ) {
        $this->config     = $config ?? config(CohereConfig::class);
        $this->client     = $client ?? new CohereClient($this->config);
        $this->products   = $products ?? new ProductModel();
        $this->embeddings = $embeddings ?? new ProductEmbeddingModel();
    }

    public function isEnabled(): bool
    {
        return $this->config->isConfigured();
    }

    public function config(): CohereConfig
    {
        return $this->config;
    }

    // ---------------------------------------------------------------------
    // Embedding documents
    // ---------------------------------------------------------------------

    /**
     * Build the searchable text representation of an existing product row.
     * Only fields that exist in the products/categories/shops tables are used.
     *
     * @param array<string, mixed> $product Row from ProductModel::getEmbeddingSources()
     */
    public function buildDocument(array $product): string
    {
        $name        = trim((string) ($product['name'] ?? ''));
        $description = trim((string) ($product['description'] ?? ''));
        $category    = trim((string) ($product['category_name'] ?? ''));
        $slug        = trim((string) ($product['category_slug'] ?? ''));
        $shop        = trim((string) ($product['shop_name'] ?? ''));
        $warranty    = trim((string) ($product['warranty_period'] ?? ''));

        $lines = [];

        if ($name !== '') {
            $lines[] = $name;
        }
        if ($category !== '') {
            $lines[] = 'Category: ' . $category;
        }
        if ($description !== '') {
            $lines[] = $description;
        }
        if ($warranty !== '') {
            $lines[] = 'Warranty: ' . $warranty;
        }
        if ($shop !== '') {
            $lines[] = 'Shop: ' . $shop;
        }

        $haystack = mb_strtolower($name . ' ' . $description);

        $terms = [];
        if ($slug !== '' && isset(self::CATEGORY_GLOSS[$slug])) {
            $terms[] = self::CATEGORY_GLOSS[$slug];
        }

        foreach (self::COLOUR_GLOSS as $pattern => $gloss) {
            if (preg_match('/\b(?:' . $pattern . ')\b/u', $haystack) === 1) {
                $terms[] = $gloss;
            }
        }

        if (preg_match(self::MULTICOLOUR_PATTERN, $haystack) === 1) {
            $terms[] = self::MULTICOLOUR_GLOSS;
        }

        // NOTE: deliberately no marketplace-wide "school supplies" gloss here.
        // A phrase shared by every document adds a constant component to every
        // vector, which inflates similarity for generic queries and destroys
        // the score separation the relevance threshold depends on.
        if ($terms !== []) {
            $lines[] = 'Also known as: ' . implode(', ', $terms);
        }

        return implode("\n", $lines);
    }

    public function documentHash(string $document): string
    {
        return sha1($this->config->model . '|' . $this->config->outputDimension . '|' . $document);
    }

    // ---------------------------------------------------------------------
    // Write path
    // ---------------------------------------------------------------------

    /**
     * Refresh the embedding for a single product.
     *
     * @return string One of: unchanged, embedded, skipped
     */
    public function syncProduct(int $productId, bool $force = false): string
    {
        $rows = $this->products->getEmbeddingSources([$productId]);
        if ($rows === []) {
            return 'skipped';
        }

        $result = $this->syncProducts($rows, $force);

        return $result['embedded'] > 0 ? 'embedded' : ($result['unchanged'] > 0 ? 'unchanged' : 'skipped');
    }

    /**
     * Refresh embeddings for a set of product rows, batching Cohere requests.
     *
     * A failing batch is logged and skipped: product data is never touched,
     * so a partial run can simply be re-run later.
     *
     * @param list<array<string, mixed>> $rows Rows from ProductModel::getEmbeddingSources()
     *
     * @return array{embedded:int, unchanged:int, failed:int, errors:list<string>}
     */
    public function syncProducts(array $rows, bool $force = false): array
    {
        $stats = ['embedded' => 0, 'unchanged' => 0, 'failed' => 0, 'errors' => []];

        if ($rows === []) {
            return $stats;
        }

        if (! $this->isEnabled()) {
            throw new RuntimeException('Cohere is not configured (missing COHERE_API_KEY or disabled).');
        }

        $ids     = array_map(static fn ($r) => (int) $r['id'], $rows);
        $existing = $this->embeddings->getContentHashes(
            $ids,
            $this->config->model,
            $this->config->outputDimension
        );

        $pending = [];
        foreach ($rows as $row) {
            $id       = (int) $row['id'];
            $document = $this->buildDocument($row);
            $hash     = $this->documentHash($document);

            if (! $force && isset($existing[$id]) && $existing[$id] === $hash) {
                $stats['unchanged']++;
                continue;
            }

            $pending[] = ['id' => $id, 'document' => $document, 'hash' => $hash];
        }

        foreach (array_chunk($pending, $this->config->batchSize) as $chunk) {
            try {
                $vectors = $this->client->embedDocuments(array_column($chunk, 'document'));

                foreach ($chunk as $i => $item) {
                    $vector = $vectors[$i] ?? [];
                    if ($vector === []) {
                        $stats['failed']++;
                        continue;
                    }

                    $this->embeddings->upsertEmbedding(
                        $item['id'],
                        $this->config->model,
                        count($vector),
                        ProductEmbeddingModel::pack($vector),
                        $item['hash'],
                        $item['document']
                    );
                    $stats['embedded']++;
                }
            } catch (\Throwable $e) {
                $stats['failed'] += count($chunk);
                $message = $e->getMessage();
                $stats['errors'][] = $message;
                log_message('error', 'Product embedding batch failed (' . count($chunk) . ' products): ' . $message);
            }
        }

        return $stats;
    }

    // ---------------------------------------------------------------------
    // Read path
    // ---------------------------------------------------------------------

    /**
     * Reduce a natural-language query to its content words. The query is NOT
     * translated - only language-agnostic filler is dropped.
     */
    public function normalizeQuery(string $query): string
    {
        $clean = mb_strtolower(trim($query));
        $clean = preg_replace('/[^\p{L}\p{N}\s\-]+/u', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;
        $clean = trim($clean);

        if ($clean === '') {
            return '';
        }

        $words = explode(' ', $clean);
        $kept  = array_values(array_filter(
            $words,
            static fn ($w) => $w !== '' && ! in_array($w, self::QUERY_STOPWORDS, true)
        ));

        // If filtering removed everything, keep the original wording.
        return $kept === [] ? $clean : implode(' ', $kept);
    }

    /**
     * Validate customer search input.
     *
     * @return array{valid:bool, query:string, reason:string}
     */
    public function validateQuery(?string $raw): array
    {
        $query = trim((string) $raw);
        // Strip control characters that only ever appear in abusive input.
        $query = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $query) ?? $query;
        $query = preg_replace('/\s+/u', ' ', $query) ?? $query;
        $query = trim($query);

        if ($query === '') {
            return ['valid' => false, 'query' => '', 'reason' => 'empty'];
        }
        if (mb_strlen($query) < $this->config->minQueryLength) {
            return ['valid' => false, 'query' => $query, 'reason' => 'too_short'];
        }
        if (mb_strlen($query) > $this->config->maxQueryLength) {
            $query = mb_substr($query, 0, $this->config->maxQueryLength);
        }

        return ['valid' => true, 'query' => $query, 'reason' => ''];
    }

    /**
     * Embed a customer query (once per unique query, then cached).
     *
     * @return list<float> L2-normalised vector
     */
    public function embedQuery(string $query): array
    {
        $normalized = $this->normalizeQuery($query);
        if ($normalized === '') {
            return [];
        }

        $cacheKey = 'cohere_q_' . sha1($this->config->model . '|' . $this->config->outputDimension . '|' . $normalized);

        $cache = cache();
        if ($cache !== null) {
            $cached = $cache->get($cacheKey);
            if (is_string($cached) && $cached !== '') {
                $vector = ProductEmbeddingModel::unpack($cached);
                if ($vector !== []) {
                    return $vector;
                }
            }
        }

        $vector = $this->client->embedQuery($normalized);
        if ($vector === []) {
            return [];
        }

        $packed = ProductEmbeddingModel::pack($vector);

        if ($cache !== null && $this->config->queryCacheTtl > 0) {
            $cache->save($cacheKey, $packed, $this->config->queryCacheTtl);
        }

        return ProductEmbeddingModel::unpack($packed);
    }

    /**
     * Semantic relevance ranking.
     *
     * @param float|null $minScoreOverride Stricter/looser absolute floor for
     *                                     this call only (the chat assistant
     *                                     demands higher precision than the grid)
     *
     * @return array{ids:list<int>, scores:array<int, float>}
     */
    public function searchIds(string $query, ?int $categoryId = null, ?float $minScoreOverride = null): array
    {
        $empty = ['ids' => [], 'scores' => []];

        if (! $this->isEnabled()) {
            return $empty;
        }

        $vector = $this->embedQuery($query);
        if ($vector === []) {
            return $empty;
        }

        $candidates = $this->embeddings->getSearchCandidates(
            $this->config->model,
            count($vector),
            $categoryId
        );

        if ($candidates === []) {
            return $empty;
        }

        $scores   = [];
        $minScore = $minScoreOverride ?? $this->config->minScore;

        foreach ($candidates as $candidate) {
            $score = self::dot($vector, $candidate['vector']);
            if ($score >= $minScore) {
                $scores[$candidate['product_id']] = $score;
            }
        }

        if ($scores === []) {
            return $empty;
        }

        arsort($scores);

        // Adaptive cutoff. Absolute cosine scores shift with query length, so a
        // fixed floor alone either drowns short queries in noise or rejects
        // them outright. Keeping only matches close to the best match trims the
        // long tail of "technically non-zero but obviously unrelated" products.
        $cutoff = reset($scores) * $this->config->relativeCutoff;
        $scores = array_filter($scores, static fn ($s) => $s >= $cutoff);

        if ($this->config->topK > 0 && count($scores) > $this->config->topK) {
            $scores = array_slice($scores, 0, $this->config->topK, true);
        }

        return ['ids' => array_map('intval', array_keys($scores)), 'scores' => $scores];
    }

    /**
     * Blend semantic scores with the pre-existing keyword matches so an exact
     * product-name search can never rank worse than it does today.
     *
     * @param array<int, float> $semanticScores
     * @param list<int>         $keywordIds
     *
     * @return list<int> Relevance-ordered product ids
     */
    public function mergeRanked(array $semanticScores, array $keywordIds): array
    {
        $merged = $semanticScores;

        foreach ($keywordIds as $id) {
            $id = (int) $id;
            // Literal matches are always relevant; boost them above the
            // purely-semantic tail while keeping their semantic ordering.
            $merged[$id] = ($merged[$id] ?? $this->config->minScore) + 0.5;
        }

        arsort($merged);

        return array_map('intval', array_keys($merged));
    }

    /**
     * Dot product of two equal-length, L2-normalised vectors == cosine similarity.
     *
     * @param list<float> $a
     * @param list<float> $b
     */
    private static function dot(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        if ($n === 0 || count($a) !== count($b)) {
            return -1.0;
        }

        $sum = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $sum += $a[$i] * $b[$i];
        }

        return $sum;
    }
}
