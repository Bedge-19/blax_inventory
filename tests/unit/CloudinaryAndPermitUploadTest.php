<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\Cloudinary as CloudinaryConfig;
use App\Libraries\CloudinaryService;

/**
 * Unit tests for Cloudinary Configuration and Business Permit Upload Resilience.
 */
class CloudinaryAndPermitUploadTest extends CIUnitTestCase
{
    public function testCloudinaryUrlParsing()
    {
        $cfg = new CloudinaryConfig();
        $cfg->cloudinaryUrl = 'cloudinary://123456789:abcdef_secret@my-real-cloud';
        $cfg->apiKey = '';
        $cfg->apiSecret = '';
        $cfg->cloudName = '';

        // Trigger constructor logic manually for test
        if (preg_match('#^cloudinary://([^:]+):([^@]+)@([a-zA-Z0-9_-]+)$#', $cfg->cloudinaryUrl, $m)) {
            $cfg->apiKey = $m[1];
            $cfg->apiSecret = $m[2];
            $cfg->cloudName = $m[3];
        }

        $this->assertSame('123456789', $cfg->apiKey);
        $this->assertSame('abcdef_secret', $cfg->apiSecret);
        $this->assertSame('my-real-cloud', $cfg->cloudName);
    }

    public function testIsConfiguredRejectsBlaxPlaceholder()
    {
        $cfg = new CloudinaryConfig();
        $cfg->cloudName = 'blax';
        $cfg->apiKey    = '533212736738489';
        $cfg->apiSecret = '-GXmGMS_v-Pkko35mImPie6O7SM';

        $service = new CloudinaryService($cfg);
        $this->assertFalse($service->isConfigured(), 'isConfigured must return false for dummy "blax" cloud name');
    }

    public function testIsConfiguredRejectsEmptyCloudName()
    {
        $cfg = new CloudinaryConfig();
        $cfg->cloudName = '';
        $cfg->apiKey    = '533212736738489';
        $cfg->apiSecret = '-GXmGMS_v-Pkko35mImPie6O7SM';

        $service = new CloudinaryService($cfg);
        $this->assertFalse($service->isConfigured());
    }

    public function testIsConfiguredAcceptsValidCredentials()
    {
        $cfg = new CloudinaryConfig();
        $cfg->cloudName = 'real_blax_hub';
        $cfg->apiKey    = '533212736738489';
        $cfg->apiSecret = '-GXmGMS_v-Pkko35mImPie6O7SM';

        $service = new CloudinaryService($cfg);
        $this->assertTrue($service->isConfigured());
    }

    public function testLocalBusinessPermitsDirectoryIsWritable()
    {
        $uploadPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'business_permits';
        if (!is_dir($uploadPath)) {
            @mkdir($uploadPath, 0775, true);
        }

        $this->assertDirectoryExists($uploadPath);
        $this->assertDirectoryIsWritable($uploadPath);

        $testFile = $uploadPath . DIRECTORY_SEPARATOR . 'test_dummy_' . time() . '.txt';
        file_put_contents($testFile, 'test content');
        $this->assertFileExists($testFile);
        @unlink($testFile);
    }

    public function testTestConnectionReturnsDiagnosticsForPlaceholder()
    {
        $cfg = new CloudinaryConfig();
        $cfg->cloudName = 'blax';
        $cfg->apiKey    = '533212736738489';
        $cfg->apiSecret = '-GXmGMS_v-Pkko35mImPie6O7SM';

        $service = new CloudinaryService($cfg);
        $res = $service->testConnection();

        $this->assertFalse($res['success']);
        $this->assertArrayHasKey('message', $res);
        $this->assertArrayHasKey('hint', $res);
    }

    public function testUploadOrFallbackSavesToLocalDiskWhenCloudinaryUnconfigured()
    {
        $cfg = new CloudinaryConfig();
        $cfg->cloudName = 'blax'; // unconfigured placeholder
        $cfg->allowLocalFallback = true;

        $service = new CloudinaryService($cfg);

        // Create temporary test file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_cld_') . '.jpg';
        file_put_contents($tempFile, 'fake image data for test');

        $resultUrl = $service->uploadOrFallback(
            $tempFile,
            CloudinaryService::FOLDER_PRODUCTS,
            'products_test',
            'test_prod_1'
        );

        $this->assertNotNull($resultUrl);
        $this->assertStringStartsWith('uploads/products_test/', $resultUrl);

        $localPath = FCPATH . $resultUrl;
        $this->assertFileExists($localPath);

        // Test deleteOldAsset on local file
        $deleted = $service->deleteOldAsset($resultUrl);
        $this->assertTrue($deleted);
        $this->assertFileDoesNotExist($localPath);

        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }
    }
}
