<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\SiteContentModel;
use App\Controllers\Admin;

final class CmsIntegrationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function tearDown(): void
    {
        \Config\Services::resetSingle('renderer');
        parent::tearDown();
    }

    public function testGetAllKeyMapReturnsAllEntries(): void
    {
        $model = new SiteContentModel();
        $map = $model->getAllKeyMap();

        $this->assertIsArray($map);
        $this->assertArrayHasKey('hero_badge', $map);
        $this->assertArrayHasKey('hero_title', $map);
    }

    public function testAdminContentSeedsDefaultsAndMigratesLegacyHome(): void
    {
        $controller = new Admin();
        $request = \Config\Services::request();
        $response = \Config\Services::response();
        $logger = \Config\Services::logger();

        session()->set([
            'user_id'    => 1,
            'user_role'  => 'admin',
            'isLoggedIn' => true,
        ]);

        $controller->initController($request, $response, $logger);
        $viewOutput = (string) $controller->content();

        $this->assertNotEmpty($viewOutput);

        // Check database that legacy 'home' was migrated or doesn't exist
        $db = \Config\Database::connect();
        $legacyCount = $db->table('site_contents')->where('page', 'home')->countAllResults();
        $this->assertSame(0, $legacyCount, "All legacy 'home' records must be migrated to 'home_banners'");

        // Check that catalog_title and catalog_subtitle exist in home_banners
        $catTitle = $db->table('site_contents')->where('page', 'home_banners')->where('content_key', 'catalog_title')->get()->getRowArray();
        $this->assertNotNull($catTitle, "catalog_title must exist in home_banners");

        $catSub = $db->table('site_contents')->where('page', 'home_banners')->where('content_key', 'catalog_subtitle')->get()->getRowArray();
        $this->assertNotNull($catSub, "catalog_subtitle must exist in home_banners");

        // Check view contains live preview elements
        $this->assertStringContainsString('Live Preview', $viewOutput);
        $this->assertStringContainsString('pv-announcement-bar', $viewOutput);
        $this->assertStringContainsString('pv-hero-banner', $viewOutput);
        $this->assertStringContainsString('pv-footer', $viewOutput);
        $this->assertStringContainsString('Live on Homepage Hero', $viewOutput);
        $this->assertStringContainsString('Important Developer Notice', $viewOutput);
    }

    public function testAnnouncementBarRendersWhenActiveAndHidesWhenInactive(): void
    {
        // 1. Active announcement
        $activeCms = [
            'announcement_active' => ['text_value' => '1'],
            'announcement_text'   => ['text_value' => '⚡ Flash Sale Today Only!'],
        ];

        $html = view('components/announcement_bar', ['cms' => $activeCms]);
        $this->assertStringContainsString('⚡ Flash Sale Today Only!', $html);
        $this->assertStringContainsString('blax-global-announcement-bar', $html);
        $this->assertStringContainsString('dismissGlobalAnnouncement', $html);

        // 2. Inactive announcement
        $inactiveCms = [
            'announcement_active' => ['text_value' => '0'],
            'announcement_text'   => ['text_value' => '⚡ Flash Sale Today Only!'],
        ];

        $inactiveHtml = view('components/announcement_bar', ['cms' => $inactiveCms]);
        $this->assertStringNotContainsString('blax-global-announcement-bar', $inactiveHtml, 'Inactive announcement bar must not render HTML');

        // 3. Empty text
        $emptyTextCms = [
            'announcement_active' => ['text_value' => '1'],
            'announcement_text'   => ['text_value' => ''],
        ];

        $emptyHtml = view('components/announcement_bar', ['cms' => $emptyTextCms]);
        $this->assertStringNotContainsString('blax-global-announcement-bar', $emptyHtml, 'Empty announcement text must not render HTML');
    }

    public function testFooterRendersContactInformationAndCustomCopyright(): void
    {
        $cms = [
            'footer_contact_email' => ['text_value' => 'custom-support@example.com'],
            'footer_phone'         => ['text_value' => '+63 999 888 7777'],
            'footer_address'       => ['text_value' => '123 Market St, Polomolok Hub'],
            'footer_copyright'     => ['text_value' => '© 2026 Customized Blax Copyright Notice.'],
        ];

        $html = view('components/footer', ['cms' => $cms]);

        $this->assertStringContainsString('custom-support@example.com', $html);
        $this->assertStringContainsString('+63 999 888 7777', $html);
        $this->assertStringContainsString('123 Market St, Polomolok Hub', $html);
        $this->assertStringContainsString('© 2026 Customized Blax Copyright Notice.', $html);
        $this->assertStringContainsString('Contact Us', $html);
    }

    public function testHomeViewRendersHeroCtaAndPromotionalBlocks(): void
    {
        $cms = [
            'hero_badge'          => ['text_value' => 'Spring Deals'],
            'hero_title'          => ['text_value' => 'Polomolok Prime Marketplace'],
            'hero_subtitle'       => ['text_value' => 'Exclusive print and retail network.'],
            'hero_cta_text'       => ['text_value' => 'Start Shopping Now'],
            'hero_image'          => ['image_url' => 'https://example.com/hero.jpg'],
            'promo_title'         => ['text_value' => 'Exclusive 50% Off Print Run'],
            'promo_tagline'       => ['text_value' => 'Limited time discount on custom flyers and brochures.'],
            'promo_banner_image'  => ['image_url' => 'https://example.com/promo.jpg'],
            'catalog_title'       => ['text_value' => 'All Available Products'],
            'catalog_subtitle'    => ['text_value' => 'Browse verified inventory.'],
            'announcement_active' => ['text_value' => '1'],
            'announcement_text'   => ['text_value' => 'Free shipping on orders ₱500+'],
        ];

        $html = view('customer/home', [
            'products'       => [],
            'pager'          => null,
            'totalPages'     => 1,
            'currentPage'    => 1,
            'perPage'        => 20,
            'totalProducts'  => 0,
            'shops'          => [],
            'shopResults'    => [],
            'categories'     => [],
            'search'         => '',
            'searchQuery'    => '',
            'categoryId'     => '',
            'siteContents'   => $cms,
            'semantic'       => false,
            'semanticNotice' => '',
        ]);

        // Verify Hero CTA comes from CMS
        $this->assertStringContainsString('Start Shopping Now', $html);

        // Verify Promotional Block is NOT rendered
        $this->assertStringNotContainsString('Special Promotion', $html);

        // Verify Catalog Headings
        $this->assertStringContainsString('All Available Products', $html);
        $this->assertStringContainsString('Browse verified inventory.', $html);

        // Verify Announcement bar is included in layout
        $this->assertStringContainsString('Free shipping on orders ₱500+', $html);
    }
}
