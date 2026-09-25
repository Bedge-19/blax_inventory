<?php

namespace App\Libraries;

use App\Models\ProductModel;
use App\Models\OrderModel;
use App\Libraries\CohereClient;
use Config\Database;

/**
 * SeasonalAiService
 *
 * Provides intelligent, inventory-aware seasonal demand forecasting and
 * restock recommendations for merchants in Blax Marketplace (Polomolok/South Cotabato).
 * Powered by Cohere Generative AI (command-a-03-2025).
 */
class SeasonalAiService
{
    protected ?CohereClient $cohereClient = null;

    /**
     * Master catalog of seasonal calendar definitions.
     */
    protected static array $seasons = [
        'school' => [
            'key'                  => 'school',
            'name'                 => 'Back-to-School & Academics Rush',
            'short_name'           => 'School Season',
            'icon'                 => 'school',
            'badge_color'          => 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700',
            'accent_hex'           => '#004ac6',
            'period_label'         => 'June – August (Primary) & January (Mid-Year)',
            'velocity_multiplier'  => 2.8,
            'description'          => 'Massive local student surge in notebooks, writing instruments, bond paper, graphing pads, and school project printing.',
            'target_categories'    => ['school supplies', 'stationery', 'paper', 'office supplies', 'printing', 'art supplies'],
            'keywords'             => [
                'notebook', 'spiral', 'composition', 'pad', 'intermediate', 'yellow', 'bond', 'paper', 'a4', 'short', 'long',
                'ballpen', 'pen', 'gel pen', 'pencil', 'crayon', 'color', 'folder', 'envelope', 'eraser', 'ruler', 'sharpener',
                'bag', 'uniform', 'scissor', 'glue', 'paste', 'binder', 'index', 'drawing', 'art', 'cartolina', 'manila',
                'marker', 'highlighter', 'correction', 'staple', 'scientific'
            ],
            'expansion_ideas'      => [
                [
                    'name'         => 'Math Geometry Compass & Ruler Set',
                    'category'     => 'School Supplies',
                    'demand_surge' => '+320%',
                    'est_price'    => 85.00,
                    'reason'       => 'Mandatory equipment for Grade 7-12 mathematics across Polomolok schools.'
                ],
                [
                    'name'         => 'Correction Tape with 6m Refill Pack',
                    'category'     => 'Stationery',
                    'demand_surge' => '+280%',
                    'est_price'    => 45.00,
                    'reason'       => 'High-turnover impulse consumable heavily purchased during examination and lecture weeks.'
                ],
                [
                    'name'         => 'Heavy-Duty 2-Pocket Portfolio Folders',
                    'category'     => 'Paper & Filing',
                    'demand_surge' => '+240%',
                    'est_price'    => 28.00,
                    'reason'       => 'Standard student requirement for periodic portfolio clearances and subject submissions.'
                ]
            ]
        ],
        'holiday' => [
            'key'                  => 'holiday',
            'name'                 => 'Holiday Rush & Year-End Celebrations',
            'short_name'           => 'Holiday Season',
            'icon'                 => 'celebration',
            'badge_color'          => 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-700',
            'accent_hex'           => '#7c3aed',
            'period_label'         => 'October – December',
            'velocity_multiplier'  => 2.5,
            'description'          => 'Surge in customized company giveaways, personalized sublimation mugs, festival tarpaulins, gift bags, and calendars.',
            'target_categories'    => ['custom gifts', 'printing', 'packaging', 'crafts', 'merchandise', 'stationery'],
            'keywords'             => [
                'gift', 'mug', 'calendar', 'planner', 'tarpaulin', 'photo', 'frame', 'sticker', 'box', 'ribbon', 'greeting',
                'card', 'packaging', 'tag', 'souvenir', 't-shirt', 'print', 'craft', 'tote', 'keychain', 'giveaway', 'festive',
                'christmas', 'holiday', 'wrapping'
            ],
            'expansion_ideas'      => [
                [
                    'name'         => 'Customized Desk Calendar Standees (12-Month)',
                    'category'     => 'Custom Print',
                    'demand_surge' => '+410%',
                    'est_price'    => 120.00,
                    'reason'       => 'Highest volume corporate giveaway for local clinics, shops, and institutions in South Cotabato.'
                ],
                [
                    'name'         => 'Sublimation Ceramic Mugs (White / Color Rim)',
                    'category'     => 'Corporate Giveaways',
                    'demand_surge' => '+360%',
                    'est_price'    => 95.00,
                    'reason'       => 'Standard corporate gift exchange and family Christmas party souvenir favorite.'
                ],
                [
                    'name'         => 'Metallic Gel Pen & Greeting Gift Sets',
                    'category'     => 'Craft Supplies',
                    'demand_surge' => '+220%',
                    'est_price'    => 140.00,
                    'reason'       => 'Popular gift item for students and teachers during year-end school celebrations.'
                ]
            ]
        ],
        'graduation' => [
            'key'                  => 'graduation',
            'name'                 => 'Graduation, Moving-Up & Recognition',
            'short_name'           => 'Graduation Season',
            'icon'                 => 'workspace_premium',
            'badge_color'          => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-700',
            'accent_hex'           => '#059669',
            'period_label'         => 'March – May',
            'velocity_multiplier'  => 2.4,
            'description'          => 'Heavy demand for certificate printing, graduation ribbons, medals, diploma holders, and stage tarpaulins.',
            'target_categories'    => ['awards', 'printing', 'certificates', 'frames', 'stationery'],
            'keywords'             => [
                'certificate', 'medal', 'ribbon', 'plaque', 'diploma', 'photo', 'frame', 'program', 'tarpaulin', 'souvenir',
                'sash', 'award', 'trophy', 'token', 'toga', 'lei', 'garland', 'glossy', 'parchment', 'linen'
            ],
            'expansion_ideas'      => [
                [
                    'name'         => 'Gold Embossed Certificate Frames (Letter / A4)',
                    'category'     => 'Frames & Awards',
                    'demand_surge' => '+450%',
                    'est_price'    => 180.00,
                    'reason'       => 'Essential companion purchase for graduates and award recipients receiving diplomas.'
                ],
                [
                    'name'         => 'Parchment & Specialty Linen Board Paper (100s)',
                    'category'     => 'Specialty Paper',
                    'demand_surge' => '+380%',
                    'est_price'    => 210.00,
                    'reason'       => 'Institutional requirement for printed diplomas and recognition certificates.'
                ],
                [
                    'name'         => 'Custom Tri-Color Graduation Lei Ribbon Materials',
                    'category'     => 'Recognition Accessories',
                    'demand_surge' => '+300%',
                    'est_price'    => 110.00,
                    'reason'       => 'High-margin seasonal craft purchased in bulk by parents and school organizers.'
                ]
            ]
        ],
        'exams' => [
            'key'                  => 'exams',
            'name'                 => 'Midterms, Finals & Heavy Document Printing',
            'short_name'           => 'Exam & Thesis Season',
            'icon'                 => 'description',
            'badge_color'          => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-700',
            'accent_hex'           => '#d97706',
            'period_label'         => 'September – October & February – March',
            'velocity_multiplier'  => 2.2,
            'description'          => 'Surge in high-volume document duplication, thesis hardbound binding, ink refills, and copier paper reams.',
            'target_categories'    => ['copier paper', 'ink', 'binding', 'printing', 'office supplies'],
            'keywords'             => [
                'bond', 'paper', 'a4', 'short', 'long', 'copy', 'xerox', 'ink', 'toner', 'cartridge', 'photo paper',
                'ring bind', 'spiral', 'plastic cover', 'staple', 'stapler', 'puncher', 'clip', 'fastener', 'clear book',
                'morocco', 'board'
            ],
            'expansion_ideas'      => [
                [
                    'name'         => 'Double-A 80gsm Premium Copier Paper (500s)',
                    'category'     => 'Copier Paper',
                    'demand_surge' => '+360%',
                    'est_price'    => 240.00,
                    'reason'       => 'Zero-jam requirement for thesis research submissions and bulk examination printing.'
                ],
                [
                    'name'         => 'CISS 4-Color Universal Dye Ink Refill Set (100ml)',
                    'category'     => 'Printer Consumables',
                    'demand_surge' => '+290%',
                    'est_price'    => 280.00,
                    'reason'       => 'Continuous weekly refill demand by college students and printing operators.'
                ],
                [
                    'name'         => 'PVC Rigid Clear Binding Covers (100 Sheets Pack)',
                    'category'     => 'Binding Materials',
                    'demand_surge' => '+230%',
                    'est_price'    => 195.00,
                    'reason'       => 'Standard cover protective shield for research papers, feasibility studies, and project reports.'
                ]
            ]
        ],
        'business' => [
            'key'                  => 'business',
            'name'                 => 'Business Permits & Year-End Tax Compliance',
            'short_name'           => 'Business Permit Season',
            'icon'                 => 'domain',
            'badge_color'          => 'bg-indigo-100 text-indigo-800 border-indigo-300 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-700',
            'accent_hex'           => '#4f46e5',
            'period_label'         => 'January – February',
            'velocity_multiplier'  => 1.9,
            'description'          => 'Annual renewal rush for commercial permit tarpaulins, carbonless duplicate receipts, and ledger files.',
            'target_categories'    => ['business forms', 'office filing', 'stamps', 'signage', 'printing'],
            'keywords'             => [
                'receipt', 'invoice', 'columnar', 'ledger', 'permit', 'stamp', 'dater', 'carbonless', 'stamp pad',
                'envelope', 'brown envelope', 'filing', 'box', 'signboard', 'official', 'binder'
            ],
            'expansion_ideas'      => [
                [
                    'name'         => 'Pre-Inked Custom Business Verification Stamp',
                    'category'     => 'Office Equipment',
                    'demand_surge' => '+310%',
                    'est_price'    => 350.00,
                    'reason'       => 'Crucial operational requirement for renewed merchant business permits in January.'
                ],
                [
                    'name'         => 'Carbonless 2-Ply Duplicate Official Receipt Booklets',
                    'category'     => 'Business Forms',
                    'demand_surge' => '+260%',
                    'est_price'    => 140.00,
                    'reason'       => 'Standard annual replenishment for local Polomolok merchant commercial transactions.'
                ],
                [
                    'name'         => 'Heavy-Duty Expanding File Wallet (Long Document)',
                    'category'     => 'Filing & Storage',
                    'demand_surge' => '+210%',
                    'est_price'    => 85.00,
                    'reason'       => 'Required for safely compiling municipal and barangay permit application documents.'
                ]
            ]
        ]
    ];

    public function __construct(?CohereClient $cohereClient = null)
    {
        try {
            $this->cohereClient = $cohereClient ?? service('cohere');
        } catch (\Throwable $e) {
            $this->cohereClient = null;
        }
    }

    /**
     * Automatically determine the primary active season based on the calendar month.
     */
    public function detectActiveSeason(): string
    {
        $month = (int) date('n');

        // Sept & Oct: Midterm & Finals Exam Season (plus back-to-school secondary)
        if ($month === 9 || $month === 10) {
            return 'exams';
        }
        // Nov & Dec: Holiday & Year-End Rush
        if ($month === 11 || $month === 12) {
            return 'holiday';
        }
        // Jan & Feb: Business Permits + Mid-year academic reset
        if ($month === 1 || $month === 2) {
            return 'business';
        }
        // Mar, Apr, May: Graduation, Moving-Up & Summer
        if ($month >= 3 && $month <= 5) {
            return 'graduation';
        }
        // Jun, Jul, Aug: Core Philippine School Opening
        return 'school';
    }

    /**
     * Get all available season definitions.
     */
    public function getSeasonsList(): array
    {
        return self::$seasons;
    }

    /**
     * Generate full Seasonal AI Restock Analysis for a specific shop.
     *
     * @param int         $shopId
     * @param string|null $seasonKey ('school'|'holiday'|'graduation'|'exams'|'business')
     * @param bool        $allowExternalAi Whether to make live external Cohere API calls on cache miss
     * @return array
     */
    public function getSeasonalAnalysis(int $shopId, ?string $seasonKey = null, bool $allowExternalAi = true): array
    {
        $activeDetected = $this->detectActiveSeason();
        $selectedKey    = ($seasonKey && isset(self::$seasons[$seasonKey])) ? $seasonKey : $activeDetected;
        $seasonDef      = self::$seasons[$selectedKey];

        $db = Database::connect();

        // 1. Fetch active products for this shop with primary image and category
        $products = $db->table('products p')
            ->select('p.id, p.sku, p.name, p.description, p.price, p.stock_quantity, p.low_stock_threshold, p.is_bestseller, c.name as category_name, pi.image_url')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->join('product_images pi', 'pi.product_id = p.id AND pi.is_primary = 1', 'left')
            ->where('p.shop_id', $shopId)
            ->where('p.deleted_at', null)
            ->get()
            ->getResultArray();

        // 2. Fetch 60-day sales velocity per product
        $twoMonthsAgo = date('Y-m-d 00:00:00', strtotime('-60 days'));
        $salesRows = $db->table('order_items oi')
            ->select('oi.product_id, SUM(oi.quantity) as total_qty, COUNT(DISTINCT oi.order_id) as order_count')
            ->join('orders o', 'o.id = oi.order_id')
            ->where('o.shop_id', $shopId)
            ->where('o.placed_at >=', $twoMonthsAgo)
            ->where('LOWER(o.status) !=', 'cancelled')
            ->groupBy('oi.product_id')
            ->get()
            ->getResultArray();

        $velocityMap = [];
        foreach ($salesRows as $sr) {
            $velocityMap[(int) $sr['product_id']] = (int) $sr['total_qty'];
        }

        // 3. Score and forecast each product
        $recommendations       = [];
        $totalPotentialUplift  = 0.0;
        $criticalCount         = 0;
        $highPriorityCount     = 0;
        $healthyCount          = 0;

        $multiplier = (float) $seasonDef['velocity_multiplier'];
        $keywords   = $seasonDef['keywords'];

        foreach ($products as $p) {
            $pId        = (int) $p['id'];
            $name       = (string) $p['name'];
            $desc       = (string) ($p['description'] ?? '');
            $catName    = (string) ($p['category_name'] ?? '');
            $stock      = (int) ($p['stock_quantity'] ?? 0);
            $threshold  = max(5, (int) ($p['low_stock_threshold'] ?? 10));
            $price      = (float) ($p['price'] ?? 0.0);
            $qtySold60d = (int) ($velocityMap[$pId] ?? 0);
            $qtySold30d = (int) ceil($qtySold60d / 2);

            // Compute textual relevance to the season
            $matchCount = 0;
            $haystack   = strtolower($name . ' ' . $desc . ' ' . $catName);
            foreach ($keywords as $kw) {
                if (str_contains($haystack, strtolower($kw))) {
                    $matchCount++;
                }
            }

            // Also check category match
            $categoryMatch = false;
            foreach ($seasonDef['target_categories'] as $tCat) {
                if (str_contains(strtolower($catName), $tCat)) {
                    $categoryMatch = true;
                    $matchCount += 2;
                    break;
                }
            }

            // Products with at least 1 keyword match or bestsellers are included in analysis
            $isRelevant = ($matchCount > 0) || ((int) ($p['is_bestseller'] ?? 0) === 1);
            if (!$isRelevant && count($products) > 10) {
                // If shop has plenty of products, filter out completely unrelated products
                continue;
            }

            // Effective velocity baseline
            $baseVelocity = max(3, $qtySold30d > 0 ? $qtySold30d : (int) ceil($threshold * 0.7));

            // Forecasted monthly demand during this season
            $seasonalVelocity = (int) ceil($baseVelocity * $multiplier);

            // Target Seasonal Buffer: 60-day safety stock buffer
            $targetBuffer = max(15, (int) ceil($seasonalVelocity * 1.35));

            // Needed restock units
            $restockUnits = max(0, $targetBuffer - $stock);

            // Days of stock remaining at seasonal rate
            $dailySeasonalRate = max(0.2, $seasonalVelocity / 30);
            $daysRemaining     = round($stock / $dailySeasonalRate, 1);

            // Urgency Classification
            $urgency = 'healthy';
            if ($stock <= 0) {
                $urgency = 'critical';
                $criticalCount++;
            } elseif ($stock <= $threshold || $daysRemaining <= 7) {
                $urgency = 'critical';
                $criticalCount++;
            } elseif ($stock < ($targetBuffer * 0.5) || $daysRemaining <= 18) {
                $urgency = 'high';
                $highPriorityCount++;
            } elseif ($stock < $targetBuffer) {
                $urgency = 'moderate';
            } else {
                $healthyCount++;
            }

            // Potential revenue uplift if restocked to target buffer
            $estUplift = $restockUnits * $price;
            if ($urgency !== 'healthy') {
                $totalPotentialUplift += $estUplift;
            }

            // Generate initial baseline AI rationale narrative
            $aiReason = $this->generateRationale(
                $name,
                $seasonDef['short_name'],
                $multiplier,
                $stock,
                $targetBuffer,
                $restockUnits,
                $daysRemaining,
                $urgency
            );

            $recommendations[] = [
                'id'                 => $pId,
                'name'               => $name,
                'sku'                => $p['sku'] ?? 'SKU-' . $pId,
                'category'           => $catName ?: 'General Supplies',
                'price'              => $price,
                'current_stock'      => $stock,
                'threshold'          => $threshold,
                'target_buffer'      => $targetBuffer,
                'restock_units'      => $restockUnits,
                'days_remaining'     => $daysRemaining,
                'urgency'            => $urgency,
                'est_revenue_uplift' => $estUplift,
                'image_url'          => function_exists('product_image_url') ? product_image_url($p['image_url'] ?? null) : ($p['image_url'] ?? null),
                'ai_rationale'       => $aiReason,
                'stock_pct'          => min(100, $targetBuffer > 0 ? round(($stock / $targetBuffer) * 100) : 0),
            ];
        }

        // Sort: Critical first, then High, then Highest Potential Revenue Uplift
        usort($recommendations, function ($a, $b) {
            $urgencyRank = ['critical' => 1, 'high' => 2, 'moderate' => 3, 'healthy' => 4];
            $rankA = $urgencyRank[$a['urgency']] ?? 5;
            $rankB = $urgencyRank[$b['urgency']] ?? 5;
            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }
            return $b['est_revenue_uplift'] <=> $a['est_revenue_uplift'];
        });

        // 4. Enrich via Cohere Generative AI (command-a-03-2025)
        $expansionIdeas = $seasonDef['expansion_ideas'] ?? [];
        $cohereAdvice = $this->enrichWithCohereAi(
            $shopId,
            $selectedKey,
            $seasonDef,
            $recommendations,
            $expansionIdeas,
            $allowExternalAi
        );

        $isCohereActive = ($this->cohereClient && $this->cohereClient->isConfigured());

        return [
            'season'                   => $seasonDef,
            'is_currently_active'      => ($selectedKey === $activeDetected),
            'detected_active_season'   => $activeDetected,
            'selected_season_key'      => $selectedKey,
            'available_seasons'        => self::$seasons,
            'total_analyzed_items'     => count($recommendations),
            'critical_stockout_count'  => $criticalCount,
            'high_priority_count'      => $highPriorityCount,
            'healthy_count'            => $healthyCount,
            'items_needing_restock'    => $criticalCount + $highPriorityCount,
            'est_seasonal_opp_revenue' => $totalPotentialUplift,
            'recommendations'          => $recommendations,
            'expansion_ideas'          => $expansionIdeas,
            'cohere_powered'           => $isCohereActive,
            'cohere_model'             => $isCohereActive ? ($this->cohereClient->config()->chatModel ?? 'command-a-03-2025') : null,
            'cohere_summary'           => $seasonDef['cohere_executive_summary'] ?? null,
        ];
    }

    /**
     * Generate concise spotlight summary specifically for the Tenant Dashboard widget.
     * Guaranteed instant (0ms external latency) by using deterministic local heuristics.
     *
     * @param int $shopId
     * @return array
     */
    public function getDashboardSpotlight(int $shopId): array
    {
        $analysis = $this->getSeasonalAnalysis($shopId, null, false);

        // Filter top 3 highest urgency items that need restock
        $urgentItems = array_values(array_filter($analysis['recommendations'], function ($r) {
            return in_array($r['urgency'], ['critical', 'high'], true) && $r['restock_units'] > 0;
        }));

        $topThree = array_slice($urgentItems, 0, 3);

        return [
            'season_name'          => $analysis['season']['name'],
            'short_name'           => $analysis['season']['short_name'],
            'icon'                 => $analysis['season']['icon'],
            'badge_color'          => $analysis['season']['badge_color'],
            'critical_count'       => $analysis['critical_stockout_count'],
            'high_priority_count'  => $analysis['high_priority_count'],
            'est_opp_revenue'      => $analysis['est_seasonal_opp_revenue'],
            'top_items'            => $topThree,
            'total_needing_restock'=> $analysis['items_needing_restock'],
            'cohere_powered'       => $analysis['cohere_powered'] ?? false,
            'cohere_summary'       => $analysis['cohere_summary'] ?? null,
        ];
    }

    /**
     * Enrich seasonal predictions, product rationales, and expansion opportunities
     * using Cohere Generative AI (command-a-03-2025).
     */
    protected function enrichWithCohereAi(
        int $shopId,
        string $seasonKey,
        array &$seasonDef,
        array &$recommendations,
        array &$expansionIdeas,
        bool $allowExternalAi = true
    ): ?array {
        if (! $this->cohereClient || ! $this->cohereClient->isConfigured()) {
            return null;
        }

        $cacheKey = "cohere_seasonal_v1_{$shopId}_{$seasonKey}";
        try {
            $cached = cache($cacheKey);
            if (is_array($cached) && !empty($cached['executive_summary'])) {
                $this->applyCohereData($cached, $seasonDef, $recommendations, $expansionIdeas);
                return $cached;
            }
        } catch (\Throwable $e) {
            // Cache lookup fail-safe
        }

        if (! $allowExternalAi) {
            return null;
        }

        // Prepare top items needing restock for the model prompt
        $itemsForAi = [];
        foreach ($recommendations as $r) {
            if ($r['restock_units'] > 0 || in_array($r['urgency'], ['critical', 'high'], true)) {
                $itemsForAi[] = [
                    'product_name'   => $r['name'],
                    'category'       => $r['category'],
                    'current_stock'  => $r['current_stock'],
                    'target_buffer'  => $r['target_buffer'],
                    'units_to_order' => $r['restock_units'],
                    'days_left'      => $r['days_remaining'],
                    'urgency'        => $r['urgency'],
                ];
                if (count($itemsForAi) >= 5) {
                    break;
                }
            }
        }

        $multiplier = $seasonDef['velocity_multiplier'] ?? 2.0;
        $seasonName = $seasonDef['name'] ?? $seasonDef['short_name'];
        $period     = $seasonDef['period_label'] ?? '';

        $prompt = "You are the Senior Retail Demand Analyst for Blax Marketplace in Polomolok, South Cotabato, Philippines.\n"
            . "Provide strategic inventory intelligence for a local merchant during '{$seasonName}' ({$period}, {$multiplier}x demand velocity surge).\n\n"
            . "Merchant's Top Restock Items:\n" . json_encode($itemsForAi, JSON_PRETTY_PRINT) . "\n\n"
            . "Generate:\n"
            . "1. executive_summary: A 2-sentence actionable retail advisory for this merchant for {$seasonName} in Polomolok.\n"
            . "2. product_rationales: Persuasive, concise 1-2 sentence rationale per product explaining why replenishment is critical at this seasonal velocity in Polomolok.\n"
            . "3. expansion_ideas: 3 high-demand items for this season to expand their store catalog.\n";

        $schema = [
            'type' => 'object',
            'properties' => [
                'executive_summary' => ['type' => 'string'],
                'product_rationales' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'product_name' => ['type' => 'string'],
                            'rationale'    => ['type' => 'string'],
                        ],
                        'required' => ['product_name', 'rationale'],
                    ],
                ],
                'expansion_ideas' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name'         => ['type' => 'string'],
                            'category'     => ['type' => 'string'],
                            'demand_surge' => ['type' => 'string'],
                            'est_price'    => ['type' => 'number'],
                            'reason'       => ['type' => 'string'],
                        ],
                        'required' => ['name', 'category', 'demand_surge', 'est_price', 'reason'],
                    ],
                ],
            ],
            'required' => ['executive_summary'],
        ];

        try {
            $messages = [
                ['role' => 'user', 'content' => $prompt]
            ];
            $aiResult = $this->cohereClient->chat($messages, $schema);

            if (is_array($aiResult) && !empty($aiResult['executive_summary'])) {
                // Cache for 24 hours (86400 seconds) to ensure lightning-fast dashboard performance
                try {
                    cache()->save($cacheKey, $aiResult, 86400);
                } catch (\Throwable $e) {}

                $this->applyCohereData($aiResult, $seasonDef, $recommendations, $expansionIdeas);
                return $aiResult;
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Cohere Seasonal AI enrichment fallback: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Apply Cohere AI generated data to the active seasonal state.
     */
    protected function applyCohereData(
        array $cohereData,
        array &$seasonDef,
        array &$recommendations,
        array &$expansionIdeas
    ): void {
        if (!empty($cohereData['executive_summary'])) {
            $seasonDef['cohere_executive_summary'] = $cohereData['executive_summary'];
        }

        if (!empty($cohereData['product_rationales']) && is_array($cohereData['product_rationales'])) {
            $rationaleMap = [];
            foreach ($cohereData['product_rationales'] as $pr) {
                if (!empty($pr['product_name']) && !empty($pr['rationale'])) {
                    $rationaleMap[strtolower(trim($pr['product_name']))] = trim($pr['rationale']);
                }
            }

            foreach ($recommendations as &$r) {
                $nameKey = strtolower(trim($r['name']));
                if (isset($rationaleMap[$nameKey])) {
                    $r['ai_rationale'] = $rationaleMap[$nameKey];
                } else {
                    // Check partial match
                    foreach ($rationaleMap as $target => $customReason) {
                        if (str_contains($nameKey, $target) || str_contains($target, $nameKey)) {
                            $r['ai_rationale'] = $customReason;
                            break;
                        }
                    }
                }
            }
            unset($r);
        }

        if (!empty($cohereData['expansion_ideas']) && is_array($cohereData['expansion_ideas']) && count($cohereData['expansion_ideas']) >= 2) {
            $expansionIdeas = array_slice($cohereData['expansion_ideas'], 0, 3);
        }
    }

    /**
     * Formulate an intuitive, business-grounded baseline AI narrative.
     */
    protected function generateRationale(
        string $productName,
        string $seasonName,
        float $multiplier,
        int $stock,
        int $target,
        int $restockUnits,
        float $daysRemaining,
        string $urgency
    ): string {
        $multiplierPct = round(($multiplier - 1.0) * 100);

        if ($urgency === 'critical') {
            if ($stock <= 0) {
                return "Out of Stock! Demand surges by +{$multiplierPct}% in {$seasonName}. Reorder +{$restockUnits} units immediately to prevent missed customer orders.";
            }
            return "Critical Stockout Risk! At {$seasonName} velocity (+{$multiplierPct}%), your {$stock} remaining units will run out in ~{$daysRemaining} days. Restock +{$restockUnits} units now.";
        }

        if ($urgency === 'high') {
            return "Pre-Season Buffer Needed: Demand accelerates by +{$multiplierPct}%. Adding +{$restockUnits} units secures 60 days of uninterrupted seasonal fulfillment.";
        }

        if ($urgency === 'moderate') {
            return "Steady demand anticipated. Recommend maintaining a buffer of {$target} units (replenish +{$restockUnits} units before supplier peak price adjustments).";
        }

        return "Healthy Seasonal Level: Your current stock of {$stock} units comfortably satisfies projected {$seasonName} demand without locking extra capital.";
    }
}
