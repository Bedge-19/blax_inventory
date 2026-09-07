<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\ShopModel;
use App\Models\CategoryModel;
use App\Models\SiteContentModel;

class Home extends BaseController
{
    public function index()
    {
        $productModel  = new ProductModel();
        $shopModel     = new ShopModel();
        $categoryModel = new CategoryModel();

        $search     = trim((string) ($this->request->getGet('search') ?? $this->request->getGet('q') ?? ''));
        $categoryId = $this->request->getGet('category_id');
        $categoryId = $categoryId !== null && $categoryId !== '' ? (int) $categoryId : null;

        $page     = max(1, (int) $this->request->getGet('page'));
        $perPage  = 20;

        // ---- AI semantic relevance (optional, never required) ----
        // Cohere only supplies relevance-ordered product ids. All deterministic
        // marketplace rules stay in the existing product query below.
        $semantic       = false;
        $semanticIds    = [];
        $semanticNotice = null;

        if ($search !== '') {
            $semanticResult = $this->resolveSemanticIds($productModel, $search, $categoryId);
            $semantic       = $semanticResult['semantic'];
            $semanticIds    = $semanticResult['ids'];
            $semanticNotice = $semanticResult['notice'];
        }

        if ($semantic) {
            $result = $productModel->getGlobalProductsByRelevance($semanticIds, $categoryId, $perPage, $page);
        } else {
            $result = $productModel->getGlobalProductsPaginated($categoryId, $search !== '' ? $search : null, $perPage, $page);
        }

        $pager = $result['pager'];
        $total = $pager ? (int) $pager->getTotal() : count($result['products']);
        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
            $productModel = new ProductModel();
            if ($semantic) {
                $result = $productModel->getGlobalProductsByRelevance($semanticIds, $categoryId, $perPage, $page);
            } else {
                $result = $productModel->getGlobalProductsPaginated($categoryId, $search !== '' ? $search : null, $perPage, $page);
            }
        }

        $shops      = $shopModel->getMostRatedShops(5);
        $categories = $categoryModel->orderBy('sort_order', 'ASC')->findAll();

        $shopResults = [];
        if ($search !== '') {
            $shopSearch  = $shopModel->getAllShopsPaginated($search, null, 'top-rated', 6, 1);
            $shopResults = $shopSearch['shops'] ?? [];
        }

        $siteContents = [];
        try {
            $scm = new SiteContentModel();
            $siteContents = $scm->getContentMap('home');
        } catch (\Throwable $e) { $siteContents = []; }

        return view('customer/home', [
            'products'       => $result['products'],
            'pager'          => $result['pager'],
            'totalPages'     => $totalPages,
            'currentPage'    => $page,
            'perPage'        => $perPage,
            'totalProducts'  => $total,
            'shops'          => $shops,
            'shopResults'    => $shopResults,
            'categories'     => $categories,
            'search'         => $search,
            'searchQuery'    => $search,
            'categoryId'     => $categoryId,
            'siteContents'   => $siteContents,
            'semantic'       => $semantic,
            'semanticNotice' => $semanticNotice,
        ]);
    }

    /**
     * Resolve relevance-ordered product ids for a natural-language query.
     *
     * Returns semantic = false whenever semantic search is unavailable, fails,
     * or finds nothing, so the caller falls back to the pre-existing keyword
     * search and the page behaves exactly as it did before.
     *
     * @return array{semantic: bool, ids: list<int>, notice: string|null}
     */
    private function resolveSemanticIds(ProductModel $productModel, string $search, ?int $categoryId): array
    {
        $none = ['semantic' => false, 'ids' => [], 'notice' => null];

        try {
            $service = service('semanticSearch');

            if (! $service->isEnabled()) {
                return $none;
            }

            $validated = $service->validateQuery($search);
            if (! $validated['valid']) {
                return $none;
            }

            $matches = $service->searchIds($validated['query'], $categoryId);
            if ($matches['ids'] === []) {
                return $none;
            }

            // Blend in the literal keyword matches so an exact product-name
            // search can never rank worse than it does today.
            $keywordIds = $productModel->getMatchingIdsByKeyword($validated['query'], $categoryId);
            $ordered    = $service->mergeRanked($matches['scores'], $keywordIds);

            return ['semantic' => true, 'ids' => $ordered, 'notice' => null];
        } catch (\Throwable $e) {
            // Never surface Cohere internals to the customer.
            log_message('error', 'Semantic search unavailable: ' . $e->getMessage());

            return ['semantic' => false, 'ids' => [], 'notice' => 'smart_search_unavailable'];
        }
    }

    public function aiQuery()
    {
        $message      = trim((string) ($this->request->getPost('message') ?? ''));
        $productModel = new ProductModel();

        $products = $this->findProductsForNaturalQuery($productModel, $message, 4);

        if ($products === []) {
            $reply    = 'I could not find an exact match. Here are some popular options from the marketplace:';
            $products = array_slice($productModel->getProductsWithDetails(), 0, 4);
        } else {
            $reply = 'I found ' . count($products) . ' matching item(s) in our catalog:';
        }

        return $this->response->setJSON([
            'status'   => 'success',
            'reply'    => $reply,
            'products' => $products,
        ]);
    }

    /**
     * Shared natural-language product lookup: AI relevance first, existing
     * keyword search as the fallback.
     */
    private function findProductsForNaturalQuery(ProductModel $productModel, string $message, int $limit): array
    {
        if ($message === '') {
            return [];
        }

        $resolved = $this->resolveSemanticIds($productModel, $message, null);

        if ($resolved['semantic']) {
            $result = $productModel->getGlobalProductsByRelevance($resolved['ids'], null, $limit, 1);
            if ($result['products'] !== []) {
                return $result['products'];
            }
        }

        return array_slice($productModel->getProductsWithDetails(null, null, $message), 0, $limit);
    }
}
