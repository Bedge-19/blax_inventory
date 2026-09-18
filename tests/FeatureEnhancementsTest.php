<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\SiteContentModel;
use App\Models\ComplianceModel;
use App\Models\CategoryModel;
use App\Models\PayoutModel;

final class FeatureEnhancementsTest extends CIUnitTestCase
{
    public function testPlatformDeductionPercentStorage(): void
    {
        $model = new SiteContentModel();
        
        $saved = $model->setPlatformDeductionPercent(4.50);
        $this->assertTrue($saved);

        $current = $model->getPlatformDeductionPercent();
        $this->assertEquals(4.50, $current);

        // Reset to default 3.00
        $model->setPlatformDeductionPercent(3.00);
        $this->assertEquals(3.00, $model->getPlatformDeductionPercent());
    }

    public function testPayoutModelIncludesDeductionPercent(): void
    {
        $payoutModel = new PayoutModel();
        $this->assertContains('deduction_percent', $payoutModel->allowedFields);
    }

    public function testCategoryViewCountCanBeIncremented(): void
    {
        $catModel = new CategoryModel();
        $first = $catModel->first();
        if ($first) {
            $initial = (int) ($first['view_count'] ?? 0);
            $catModel->builder()->where('id', $first['id'])->increment('view_count', 1);
            $fresh = $catModel->find($first['id']);
            $this->assertEquals($initial + 1, (int) ($fresh['view_count'] ?? 0));
        } else {
            $this->assertTrue(true);
        }
    }

    public function testComplianceModelAllowedFields(): void
    {
        $compModel = new ComplianceModel();
        $this->assertContains('reported_user_id', $compModel->allowedFields);
        $this->assertContains('reported_shop_id', $compModel->allowedFields);
        $this->assertContains('issue_type', $compModel->allowedFields);
    }
}
