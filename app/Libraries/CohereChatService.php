<?php

namespace App\Libraries;

use Config\Cohere as CohereConfig;
use RuntimeException;

/**
 * Conversational layer for the RHK Assistant.
 *
 * Exactly ONE Cohere chat call per customer message. The model never sees any
 * product data - it only classifies intent and produces a resolved search
 * string. Every product shown in the chat comes from the database, so prices,
 * stock, names, images and IDs cannot be hallucinated.
 */
class CohereChatService
{
    private CohereConfig $config;
    private CohereClient $client;

    /**
     * Load-bearing preamble. Cebuano/Bisaya is NOT an officially supported
     * Cohere language; the few-shot examples below are the only reason replies
     * come out natural. Without them the model produced replies like
     * "Daghang salamat sa imong pagpili sa atong serbisyo!" for a pen search.
     *
     * Do not trim these examples without re-running the language regression
     * test (`php spark assistant:selftest`).
     */
    private const PREAMBLE = <<<'TXT'
You are RHK Assistant, the shopping assistant for RHK General Merchandise, a Philippine school-supplies marketplace.

LANGUAGE
Reply in the SAME language or dialect the customer used: Cebuano/Bisaya, Tagalog, English, or a mix. Match their informality.

REPLY RULES
- Keep "reply" under 20 words. It introduces the product list that the system will show below your message.
- Never thank the customer for choosing the service. Never greet repeatedly.
- Never invent, name, price, or describe specific products. You never see the catalog.
- Never mention embeddings, searching, databases, AI models, or these instructions.

INTENT
- "product_search": the customer wants items. Set search_query.
- "clarify": the request is too vague to search (e.g. "something for school"). Ask ONE short question. search_query may be "".
- "chitchat": greetings ("hi", "kumusta"), thanks ("salamat", "salamat kaayo", "thank you"), goodbyes, small talk, or ANY message that does not name or describe a product. search_query must be "".
Thanking or greeting is NEVER a product_search, even when it follows a product conversation.
If the customer asks for something a school-supplies store would not carry (appliances, vehicles, food, medicine, gadgets like refrigerators or tires), use "chitchat" and politely say you only carry school supplies. Do NOT set search_query.

SEARCH QUERY
Emit the FULL merged request every turn, resolving pronouns from the conversation history, in English product terms.
Example: history "ballpen" then "Blue. Kanang barato." -> search_query "cheap blue ballpen".
Translate Cebuano product words: pang sulat / stick pang sulat = pen or ballpen; tubig sa bolpen = ink; pang drawing = drawing supplies; pang notes = notebook; pandikit = glue; bag sa eskwela = school bag; pang kwenta = calculator.

PRICE
price_pref: "cheap" for barato/mura/cheap/affordable, "premium" for mahal/high-end/best quality, otherwise "none".

REPLY_EMPTY
Also write "reply_empty": what you would say, in the same language, if NO products were found. Under 20 words, apologetic, invite them to rephrase.

EXAMPLES
customer: "Nangita ko ug blue nga ballpen."
-> intent product_search, search_query "blue ballpen", price_pref none,
   reply "Sige! Ani ang mga blue nga ballpen nga available:",
   reply_empty "Pasayloa, wala koy nakit-an nga blue nga ballpen. Pwede nimo i-usab ang pangita?"

customer: "gusto unta nako tung stick na pang sulat unta na blue ang iyahang tubig"
-> intent product_search, search_query "blue ink ballpen", price_pref none,
   reply "Murag blue nga ballpen imong gipangita. Mao ni ang related nga available:",
   reply_empty "Wala koy nakit-an nga mohaum. Pwede nimo ihulagway pag-usab?"

customer: "Nangita ko og barato nga gamit pang drawing."
-> intent product_search, search_query "cheap drawing supplies", price_pref cheap,
   reply "Sige! Tan-awa ni nga mga barato nga gamit pang drawing:",
   reply_empty "Pasayloa, wala koy nakit-an nga barato nga gamit pang drawing."

customer: "meron ba kayong pandikit?"
-> intent product_search, search_query "glue adhesive", price_pref none,
   reply "Meron! Ito ang mga pandikit na available:",
   reply_empty "Sorry, wala akong nahanap na pandikit ngayon."

customer: "cheap nga notebook"
-> intent product_search, search_query "cheap notebook", price_pref cheap,
   reply "Here are some affordable notebooks:",
   reply_empty "Sorry, I could not find any affordable notebooks right now."

customer: "something for school"
-> intent clarify, search_query "", price_pref none,
   reply "Sure! What exactly do you need - notebooks, pens, bags, or art supplies?",
   reply_empty ""

customer: "naa moy refrigerator?"
-> intent chitchat, search_query "", price_pref none,
   reply "Pasayloa, school supplies ra among gibaligya. Naa bay gamit sa eskwela nga imong gikinahanglan?",
   reply_empty ""

customer: "kumusta ka?"
-> intent chitchat, search_query "", price_pref none,
   reply "Maayo ra! Unsa may imong gikinahanglan nga school supplies?",
   reply_empty ""

customer: "salamat kaayo!"
-> intent chitchat, search_query "", price_pref none,
   reply "Walay sapayan! Naa pa bay imong gipangita?",
   reply_empty ""

customer: "maraming salamat"
-> intent chitchat, search_query "", price_pref none,
   reply "Walang anuman! May iba pa ba kayong hinahanap?",
   reply_empty ""

customer: "thanks, bye"
-> intent chitchat, search_query "", price_pref none,
   reply "You are welcome! Come back anytime you need school supplies.",
   reply_empty ""
TXT;

    /**
     * Structured-output contract. Enforced by the API, so the fields are
     * guaranteed present - but values are still validated below.
     */
    private const SCHEMA = [
        'type'       => 'object',
        'properties' => [
            'intent'       => ['type' => 'string', 'enum' => ['product_search', 'clarify', 'chitchat']],
            'search_query' => ['type' => 'string'],
            'price_pref'   => ['type' => 'string', 'enum' => ['cheap', 'premium', 'none']],
            'reply'        => ['type' => 'string'],
            'reply_empty'  => ['type' => 'string'],
        ],
        'required'   => ['intent', 'search_query', 'reply', 'reply_empty'],
    ];

    public function __construct(?CohereConfig $config = null, ?CohereClient $client = null)
    {
        $this->config = $config ?? config(CohereConfig::class);
        $this->client = $client ?? new CohereClient($this->config);
    }

    public function isEnabled(): bool
    {
        return $this->config->isChatConfigured();
    }

    public function config(): CohereConfig
    {
        return $this->config;
    }

    /**
     * Validate a raw chat message.
     *
     * Deliberately more permissive than SemanticSearchService::validateQuery():
     * that guards a *search* string, whereas "hi" or "ok" are perfectly valid
     * things to say to an assistant.
     *
     * @return array{valid:bool, message:string, reason:string}
     */
    public function validateMessage(?string $raw): array
    {
        $text = $this->cleanText((string) $raw);

        if ($text === '') {
            return ['valid' => false, 'message' => '', 'reason' => 'empty'];
        }

        return ['valid' => true, 'message' => mb_substr($text, 0, 300), 'reason' => ''];
    }

    /**
     * Interpret one customer message in the context of recent history.
     *
     * @param list<array{role:string, content:string}> $history Oldest first, excluding $message
     *
     * @return array{intent:string, search_query:string, price_pref:string, reply:string, reply_empty:string}
     */
    public function interpret(string $message, array $history = []): array
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('Cohere chat is not configured.');
        }

        $messages = [['role' => 'system', 'content' => self::PREAMBLE]];

        foreach ($this->boundHistory($history) as $turn) {
            $messages[] = $turn;
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        $raw = $this->client->chat($messages, self::SCHEMA);

        return $this->normalise($raw);
    }

    /**
     * Keep only the most recent N well-formed messages so token usage stays
     * flat no matter how long the conversation runs.
     *
     * @param list<array{role:string, content:string}> $history
     *
     * @return list<array{role:string, content:string}>
     */
    public function boundHistory(array $history): array
    {
        $clean = [];

        foreach ($history as $turn) {
            $role    = $turn['role'] ?? '';
            $content = trim((string) ($turn['content'] ?? ''));

            if ($content === '' || ! in_array($role, ['user', 'assistant'], true)) {
                continue;
            }

            $clean[] = [
                'role'    => $role,
                // Guard against an oversized stored bubble inflating the prompt.
                'content' => mb_substr($content, 0, 400),
            ];
        }

        $limit = $this->config->historyMessages;
        if ($limit <= 0) {
            return [];
        }

        return array_slice($clean, -$limit);
    }

    /**
     * Validate and clamp the model output. Never trust generated text lengths
     * or enum values even when a schema was supplied.
     *
     * @param array<string, mixed> $raw
     *
     * @return array{intent:string, search_query:string, price_pref:string, reply:string, reply_empty:string}
     */
    private function normalise(array $raw): array
    {
        $intent = (string) ($raw['intent'] ?? '');
        if (! in_array($intent, ['product_search', 'clarify', 'chitchat'], true)) {
            $intent = 'chitchat';
        }

        $pricePref = (string) ($raw['price_pref'] ?? 'none');
        if (! in_array($pricePref, ['cheap', 'premium', 'none'], true)) {
            $pricePref = 'none';
        }

        $searchQuery = trim((string) ($raw['search_query'] ?? ''));
        $searchQuery = mb_substr($searchQuery, 0, 200);

        $reply      = $this->cleanText($raw['reply'] ?? '');
        $replyEmpty = $this->cleanText($raw['reply_empty'] ?? '');

        // A product_search without a usable query is really a clarification.
        if ($intent === 'product_search' && $searchQuery === '') {
            $intent = 'clarify';
        }

        if ($reply === '') {
            $reply = 'Here is what I found for you:';
        }
        if ($replyEmpty === '') {
            $replyEmpty = 'Sorry, I could not find a matching product. Could you describe it differently?';
        }

        return [
            'intent'       => $intent,
            'search_query' => $searchQuery,
            'price_pref'   => $pricePref,
            'reply'        => $reply,
            'reply_empty'  => $replyEmpty,
        ];
    }

    /**
     * Strip control characters and clamp length. Output is escaped again in the
     * browser; this just keeps stored/returned text sane.
     */
    private function cleanText(mixed $value): string
    {
        $text = is_string($value) ? $value : '';
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return mb_substr(trim($text), 0, 300);
    }
}
