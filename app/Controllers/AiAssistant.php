<?php

namespace App\Controllers;

use App\Models\AiChatModel;
use App\Models\ProductModel;

/**
 * RHK Assistant - AI shopping assistant.
 *
 * One Cohere chat call per customer message (intent + reply), then at most one
 * embedding call when a product search is actually needed. The generative model
 * never receives product data; every card is built from the database.
 */
class AiAssistant extends BaseController
{
    private const HISTORY_SESSION_KEY = 'ai_chat_history';

    public function chat()
    {
        $session = session();

        $message = trim((string) ($this->request->getPost('message') ?? ''));
        if ($message === '') {
            // A non-JSON or empty body makes getJSON() throw in CI 4.6.
            try {
                $json    = $this->request->getJSON(true);
                $message = trim((string) (is_array($json) ? ($json['message'] ?? '') : ''));
            } catch (\Throwable $e) {
                $message = '';
            }
        }

        $semantic  = service('semanticSearch');
        $chat      = service('cohereChat');
        $validated = $chat->validateMessage($message);

        if (! $validated['valid']) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Please type a message first.',
            ]);
        }

        $message = $validated['message'];

        // Basic abuse protection on an endpoint that reaches an external API.
        $throttler = service('throttler');
        if ($throttler->check(md5('ai-chat-' . $this->request->getIPAddress()), 20, MINUTE) === false) {
            return $this->response->setStatusCode(429)->setJSON([
                'status'  => 'error',
                'message' => 'You are searching a bit too quickly. Please try again in a moment.',
            ]);
        }

        $history      = $this->loadHistory();
        $productModel = new ProductModel();

        $intent      = 'product_search';
        $searchQuery = $message;
        $pricePref   = 'none';
        $reply       = null;
        $replyEmpty  = null;
        $usedChatAi  = false;

        // ---- Stage 1: understand the message -----------------------------
        try {
            if ($chat->isEnabled()) {
                $result     = $chat->interpret($message, $history);
                $intent     = $result['intent'];
                $pricePref  = $result['price_pref'];
                $reply      = $result['reply'];
                $replyEmpty = $result['reply_empty'];
                $usedChatAi = true;

                if ($intent === 'product_search') {
                    $searchQuery = $result['search_query'];
                }
            }
        } catch (\Throwable $e) {
            // Degrade to search-only behaviour; never surface Cohere internals.
            log_message('error', 'Assistant chat model unavailable: ' . $e->getMessage());
        }

        // ---- Stage 2: products, only when the intent calls for it --------
        $products = [];
        if ($intent === 'product_search') {
            $products = $this->findProducts($semantic, $productModel, $searchQuery, $pricePref);
        }

        // ---- Stage 3: choose the customer-facing message ------------------
        if ($intent === 'product_search' && $products === []) {
            $replyText = $replyEmpty ?? 'Sorry, I could not find a matching product. Could you describe it differently?';
        } elseif ($reply !== null) {
            $replyText = $reply;
        } elseif ($products !== []) {
            // Chat model unavailable but the search still worked.
            $replyText = 'Here are ' . count($products) . ' item(s) from our catalog that may match:';
        } else {
            $replyText = 'Sorry, I could not find a matching product. Could you describe it differently?';
        }

        $this->rememberTurn($message, $replyText);
        $this->logTranscript($session->get('user_id'), $message, $replyText);

        return $this->response->setJSON([
            'status'   => 'success',
            'intent'   => $intent,
            'reply'    => $replyText,
            'products' => $products,
            'ai'       => $usedChatAi,
        ]);
    }

    /**
     * Semantic relevance -> deterministic database query -> card DTOs.
     *
     * @return list<array<string, mixed>>
     */
    private function findProducts($semantic, ProductModel $productModel, string $query, string $pricePref): array
    {
        if (trim($query) === '') {
            return [];
        }

        $cohereConfig = config(\Config\Cohere::class);
        $limit        = (int) $cohereConfig->maxChatProducts;

        try {
            if ($semantic->isEnabled()) {
                // Stricter floor than the catalog grid: the assistant states
                // out loud that these products match, so a weak hit is a lie.
                $matches = $semantic->searchIds($query, null, $cohereConfig->chatMinScore);

                if ($matches['ids'] !== []) {
                    $keywordIds = $productModel->getMatchingIdsByKeyword($query);
                    $ordered    = $semantic->mergeRanked($matches['scores'], $keywordIds);

                    $cards = $productModel->getChatProductCards($ordered, $pricePref, $limit);
                    if ($cards !== []) {
                        return $cards;
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Assistant semantic search failed: ' . $e->getMessage());
        }

        // Fallback: pre-existing keyword search, still rendered as real cards.
        $keywordIds = $productModel->getMatchingIdsByKeyword($query);

        return $keywordIds === []
            ? []
            : $productModel->getChatProductCards($keywordIds, $pricePref, $limit);
    }

    // ---------------------------------------------------------------------
    // Conversation memory
    // ---------------------------------------------------------------------

    /**
     * Bounded per-session history. Works for guests and logged-in customers
     * alike and keeps the prompt size flat.
     *
     * @return list<array{role:string, content:string}>
     */
    private function loadHistory(): array
    {
        $stored = session()->get(self::HISTORY_SESSION_KEY);

        return is_array($stored) ? $stored : [];
    }

    private function rememberTurn(string $userMessage, string $assistantReply): void
    {
        $history   = $this->loadHistory();
        $history[] = ['role' => 'user', 'content' => $userMessage];
        $history[] = ['role' => 'assistant', 'content' => $assistantReply];

        $keep = max(2, (int) config(\Config\Cohere::class)->historyMessages);

        session()->set(self::HISTORY_SESSION_KEY, array_slice($history, -$keep));
    }

    /**
     * Persist the transcript for signed-in users only.
     *
     * ai_chat_messages.user_id is NOT NULL with an FK to users(id), so guest
     * conversations cannot be stored without a schema change. They previously
     * defaulted to user_id 3 - an existing shop owner - which polluted that
     * account's history. Guests now keep session-only context instead.
     */
    private function logTranscript(mixed $userId, string $userMessage, string $assistantReply): void
    {
        $userId = (int) $userId;
        if ($userId <= 0) {
            return;
        }

        try {
            $chatModel = new AiChatModel();
            $chatModel->insert(['user_id' => $userId, 'sender' => 'user', 'message' => $userMessage]);
            $chatModel->insert(['user_id' => $userId, 'sender' => 'assistant', 'message' => $assistantReply]);
        } catch (\Throwable $e) {
            log_message('warning', 'Could not persist AI chat transcript: ' . $e->getMessage());
        }
    }
}
