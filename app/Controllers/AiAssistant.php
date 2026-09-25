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
        $actions     = [];

        $lowerMsg = mb_strtolower($message);

        // 1. Order Tracking & Store Pick-up QR Codes
        $isTrackingOrQr = (
            (str_contains($lowerMsg, 'track') || str_contains($lowerMsg, 'delivery') || str_contains($lowerMsg, 'subay') || str_contains($lowerMsg, 'courier'))
            && (str_contains($lowerMsg, 'order') || str_contains($lowerMsg, 'pickup') || str_contains($lowerMsg, 'pick-up') || str_contains($lowerMsg, 'qr') || str_contains($lowerMsg, 'how') || str_contains($lowerMsg, 'unsaon') || str_contains($lowerMsg, 'paano') || str_contains($lowerMsg, 'asa'))
        ) || str_contains($lowerMsg, 'qr code') || str_contains($lowerMsg, 'pickup qr') || str_contains($lowerMsg, 'pick-up qr');

        // 2. Verified Polomolok Shops / Partners
        $isShops = (
            str_contains($lowerMsg, 'shop') || str_contains($lowerMsg, 'store') || str_contains($lowerMsg, 'tindahan') || str_contains($lowerMsg, 'merchant') || str_contains($lowerMsg, 'partner')
        ) && (
            str_contains($lowerMsg, 'browse') || str_contains($lowerMsg, 'show') || str_contains($lowerMsg, 'list') || str_contains($lowerMsg, 'verified') || str_contains($lowerMsg, 'polomolok') || str_contains($lowerMsg, 'asa') || str_contains($lowerMsg, 'saan') || str_contains($lowerMsg, 'where')
        );

        // 3. Custom PDF Printing Services & Downpayments
        $isPrinting = (
            str_contains($lowerMsg, 'pdf') || str_contains($lowerMsg, 'photocopy') || str_contains($lowerMsg, 'imprinta') || (str_contains($lowerMsg, 'print') && (str_contains($lowerMsg, 'request') || str_contains($lowerMsg, 'document') || str_contains($lowerMsg, 'upload') || str_contains($lowerMsg, 'downpayment') || str_contains($lowerMsg, 'binding') || str_contains($lowerMsg, 'file') || str_contains($lowerMsg, 'how')))
        );

        // 4. Catalog Overview Query
        $isCatalogOverview = str_contains($lowerMsg, 'merchandise') && (str_contains($lowerMsg, 'available') || str_contains($lowerMsg, 'product') || str_contains($lowerMsg, 'supplies') || str_contains($lowerMsg, 'what'));

        if ($isTrackingOrQr) {
            $intent = 'order_tracking';
            $reply = "Here is how to track your orders and use store pick-up QR codes on Blax:\n\n🛵 **Live Delivery Tracking**:\nFor Doorstep Deliveries within Polomolok, go to **My Orders** and tap **'Track Order'** to monitor your courier's live GPS movement on our interactive map!\n\n📲 **Store Pick-up QR Code**:\nFor Store Pick-up orders, your dedicated Pick-up QR code is displayed on your order details. Present this QR code to the merchant at the shop counter for instant scanning and immediate release of your items!";
            $actions = [
                ['label' => 'View My Orders & QR Codes', 'url' => base_url('customer/orders'), 'icon' => 'qr_code_2'],
            ];
        } elseif ($isShops) {
            $intent = 'shop_directory';
            $reply = "Blax Marketplace connects you with verified local merchants and print hubs located throughout Polomolok, South Cotabato!\n\n🏬 You can browse all partner shops, view verified business permits, store addresses, and customer ratings in the **Shops Directory**.";
            $actions = [
                ['label' => 'Browse Verified Shops', 'url' => base_url('shops'), 'icon' => 'storefront'],
            ];
            $searchQuery = 'merchandise';
        } elseif ($isPrinting) {
            $intent = 'printing_service';
            $reply = "Here is how custom document printing works on Blax Marketplace:\n\n📄 **1. Upload Your File**: Visit **Printing Services** and upload your PDF document.\n⚙️ **2. Choose Print Specs**: Select Color or Grayscale, paper size (Letter/A4/Legal), page count, and binding options.\n💰 **3. Quote & Downpayment**: The shop reviews your request, calculates the price, and you can pay securely via GCash or online payment.\n📦 **4. Claim or Deliver**: Track printing status in real-time until ready for store pick-up or doorstep delivery!";
            $actions = [
                ['label' => 'Go to Printing Services', 'url' => base_url('printing-services'), 'icon' => 'print'],
            ];
            $searchQuery = 'paper';
        } elseif ($isCatalogOverview) {
            $intent = 'product_search';
            $searchQuery = 'school office supplies';
            $reply = "Here are some popular school supplies, office stationery, and merchandise available from verified shops in Polomolok:";
        } else {
            // ---- Stage 1: understand the message via AI model ----------------
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
        }

        // ---- Stage 2: products, only when the intent calls for it --------
        $products = [];
        if (in_array($intent, ['product_search', 'printing_service', 'shop_directory'], true) && trim($searchQuery) !== '') {
            $products = $this->findProducts($semantic, $productModel, $searchQuery, $pricePref);
        }

        // ---- Stage 3: choose the customer-facing message ------------------
        if ($intent === 'product_search' && $products === []) {
            $replyText = $replyEmpty ?? $reply ?? 'Sorry, I could not find a matching product. Could you describe it differently?';
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
            'actions'  => $actions,
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
