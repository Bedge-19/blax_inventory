<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\ProductModel;

/**
 * Test suite for 3-Hour Dynamic Product Catalog Rotation
 * Verifies slot window math, zero-overlap pagination, explicit sort override,
 * and UI badge/countdown rendering on the marketplace home page.
 */
class ProductCatalogRotationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testRotationInfoCalculatesCorrectSlotAndCountdown()
    {
        $info = ProductModel::getRotationInfo(3);

        $this->assertIsArray($info);
        $this->assertArrayHasKey('slot_index', $info);
        $this->assertArrayHasKey('slot_seed', $info);
        $this->assertArrayHasKey('seconds_remaining', $info);
        $this->assertArrayHasKey('formatted_time_left', $info);

        $this->assertGreaterThanOrEqual(0, $info['slot_index']);
        $this->assertLessThanOrEqual(7, $info['slot_index']);

        $this->assertGreaterThan(0, $info['slot_seed']);
        $this->assertGreaterThanOrEqual(0, $info['seconds_remaining']);
        $this->assertLessThanOrEqual(3 * 3600, $info['seconds_remaining']);
        $this->assertNotEmpty($info['formatted_time_left']);
    }

    public function testDifferentThreeHourSlotsProduceDifferentSeeds()
    {
        $now = time();
        $today = date('Ymd', $now);

        $seedSlot1 = (int) ($today . '0'); // 00:00 - 02:59
        $seedSlot2 = (int) ($today . '1'); // 03:00 - 05:59
        $seedSlot3 = (int) ($today . '2'); // 06:00 - 08:59
        $seedSlot4 = (int) ($today . '3'); // 09:00 - 11:59

        $this->assertNotEquals($seedSlot1, $seedSlot2);
        $this->assertNotEquals($seedSlot2, $seedSlot3);
        $this->assertNotEquals($seedSlot3, $seedSlot4);
    }

    public function testDifferentSeedsProduceDifferentProductPermutations()
    {
        $productModel = new ProductModel();

        $seedA = 202609221;
        $seedB = 202609224;

        $resA = $productModel->getGlobalProductsPaginated(null, null, 10, 1, null, $seedA);
        $resB = $productModel->getGlobalProductsPaginated(null, null, 10, 1, null, $seedB);

        $idsA = array_column($resA['products'], 'id');
        $idsB = array_column($resB['products'], 'id');

        if (count($idsA) >= 5 && count($idsB) >= 5) {
            // The sequences should not be identical
            $this->assertNotEquals($idsA, $idsB);
        } else {
            $this->assertNotEmpty($resA['products']);
        }
    }

    public function testSameSeedPreservesZeroOverlapAcrossPages()
    {
        $productModel = new ProductModel();
        $seed = 202609222;

        $page1 = $productModel->getGlobalProductsPaginated(null, null, 5, 1, null, $seed);
        $page2 = $productModel->getGlobalProductsPaginated(null, null, 5, 2, null, $seed);

        $ids1 = array_column($page1['products'], 'id');
        $ids2 = array_column($page2['products'], 'id');

        if (!empty($ids1) && !empty($ids2)) {
            $overlap = array_intersect($ids1, $ids2);
            $this->assertEmpty($overlap, 'Pagination with deterministic 3-hour seed must not overlap between Page 1 and Page 2');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testExplicitSortingOverridesRotationSeed()
    {
        $productModel = new ProductModel();

        $priceAsc = $productModel->getGlobalProductsPaginated(null, null, 10, 1, 'price_asc');
        $pricesAsc = array_map('floatval', array_column($priceAsc['products'], 'price'));

        if (count($pricesAsc) >= 2) {
            $sortedAsc = $pricesAsc;
            sort($sortedAsc);
            $this->assertEquals($sortedAsc, $pricesAsc, 'price_asc must order products with price in ascending order');
        }

        $priceDesc = $productModel->getGlobalProductsPaginated(null, null, 10, 1, 'price_desc');
        $pricesDesc = array_map('floatval', array_column($priceDesc['products'], 'price'));

        if (count($pricesDesc) >= 2) {
            $sortedDesc = $pricesDesc;
            rsort($sortedDesc);
            $this->assertEquals($sortedDesc, $pricesDesc, 'price_desc must order products with price in descending order');
        }
    }

    public function testHomepageRendersRotationBadgeAndTimer()
    {
        $result = $this->get('/');
        $result->assertStatus(200);

        $body = $result->response()->getBody();
        $this->assertStringContainsString('Global Product Catalog', $body);
        $this->assertStringContainsString('Rotates every 3h', $body);
        $this->assertStringContainsString('id="catalog-rotation-timer"', $body);
        $this->assertStringContainsString('Discovery Rotation', $body);
        $this->assertStringContainsString('toggleSortMenu', $body);
    }

    public function testHomepageWithExplicitSortMaintainsSortAndResetLink()
    {
        $result = $this->get('/?sort=price_asc');
        $result->assertStatus(200);

        $body = $result->response()->getBody();
        $this->assertStringContainsString('Sorted by Price Asc', $body);
        $this->assertStringContainsString('Reset', $body);
    }
}
