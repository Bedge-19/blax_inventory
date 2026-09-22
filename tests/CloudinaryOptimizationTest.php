<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class CloudinaryOptimizationTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('status');
    }

    public function testProductImageUrlAppliesCardVariantByDefault(): void
    {
        $rawCloudUrl = 'https://res.cloudinary.com/blax/image/upload/v1740000000/blax/products/prod_123.jpg';
        $transformed = product_image_url($rawCloudUrl);

        $this->assertStringContainsString('c_limit,w_480,h_480,f_auto,q_auto', $transformed);
        $this->assertSame(
            'https://res.cloudinary.com/blax/image/upload/c_limit,w_480,h_480,f_auto,q_auto/v1740000000/blax/products/prod_123.jpg',
            $transformed
        );
    }

    public function testProductImageUrlAppliesThumbnailAndDetailVariants(): void
    {
        $rawCloudUrl = 'https://res.cloudinary.com/blax/image/upload/v1740000000/blax/products/prod_123.jpg';

        $thumb = product_image_url($rawCloudUrl, 'thumbnail');
        $this->assertStringContainsString('c_fill,w_160,h_160,f_auto,q_auto', $thumb);

        $detail = product_image_url($rawCloudUrl, 'detail');
        $this->assertStringContainsString('c_limit,w_960,h_960,f_auto,q_auto', $detail);
    }

    public function testProfileImageUrlAppliesAvatarVariant(): void
    {
        $rawCloudUrl = 'https://res.cloudinary.com/blax/image/upload/v1740000000/blax/profiles/usr_1.jpg';
        $avatar = profile_image_url($rawCloudUrl);

        $this->assertStringContainsString('c_fill,g_face,w_100,h_100,f_auto,q_auto', $avatar);
    }

    public function testLogoUrlAppliesLogoVariant(): void
    {
        $rawCloudUrl = 'https://res.cloudinary.com/blax/image/upload/v1740000000/blax/shop_logos/logo_1.png';
        $logo = logo_url($rawCloudUrl);

        $this->assertStringContainsString('c_limit,w_200,h_200,f_auto,q_auto', $logo);
    }

    public function testCmsImageUrlAppliesBannerVariant(): void
    {
        $rawCloudUrl = 'https://res.cloudinary.com/blax/image/upload/v1740000000/blax/cms/banner_1.jpg';
        $banner = cms_image_url($rawCloudUrl);

        $this->assertStringContainsString('c_limit,w_1440,f_auto,q_auto', $banner);
    }

    public function testExistingTransformIsReplacedWithoutDuplication(): void
    {
        $urlWithTransform = 'https://res.cloudinary.com/blax/image/upload/w_200,c_fill/v1740000000/blax/products/prod_123.jpg';
        $replaced = product_image_url($urlWithTransform, 'detail');

        $this->assertSame(
            'https://res.cloudinary.com/blax/image/upload/c_limit,w_960,h_960,f_auto,q_auto/v1740000000/blax/products/prod_123.jpg',
            $replaced
        );
        $this->assertStringNotContainsString('w_200', $replaced);
    }

    public function testRawDocumentsAreNotTransformed(): void
    {
        $rawPdfUrl = 'https://res.cloudinary.com/blax/raw/upload/v1740000000/blax/printing/documents/doc_1.pdf';
        $result = cloudinary_transform_url($rawPdfUrl, 'card');

        $this->assertSame($rawPdfUrl, $result);
    }

    public function testLocalAndUnsplashUrlsRemainIntact(): void
    {
        $unsplash = 'https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=600&q=80';
        $this->assertSame($unsplash, product_image_url($unsplash));

        $local = 'uploads/products/sample.jpg';
        $this->assertSame(base_url($local), product_image_url($local));
    }

    public function testBackwardCompatibilityWithCustomFallback(): void
    {
        $customFallback = 'https://images.unsplash.com/custom-fallback.jpg';
        $emptyResult = product_image_url('', $customFallback);

        $this->assertSame($customFallback, $emptyResult);
    }
}
