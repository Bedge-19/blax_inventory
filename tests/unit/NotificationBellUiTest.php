<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regression test for Notification Bell UI and Dropdown configurations.
 * Prevents double-toggle event conflicts across Customer, Tenant, and Admin interfaces.
 */
class NotificationBellUiTest extends CIUnitTestCase
{
    /**
     * Customer header must have notif-dropdown-toggle without conflicting inline onclick.
     */
    public function testCustomerHeaderNotificationBellHasNoInlineOnclick()
    {
        $filePath = APPPATH . 'Views/components/marketplace_header.php';
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);

        // Assert toggle button and panel exist
        $this->assertStringContainsString('id="notif-dropdown-toggle"', $content);
        $this->assertStringContainsString('id="notif-dropdown"', $content);

        // Assert NO inline onclick on notif-dropdown-toggle
        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]*id="notif-dropdown-toggle"[^>]*onclick=/i',
            $content,
            'Customer notification toggle must not have an inline onclick attribute'
        );

        // Assert accessibility attributes
        $this->assertStringContainsString('aria-label="Notifications"', $content);
        $this->assertStringContainsString('aria-haspopup="true"', $content);
        $this->assertStringContainsString('aria-expanded="false"', $content);
    }

    /**
     * Tenant layout must have notif-toggle without conflicting inline click listener.
     */
    public function testTenantLayoutNotificationBellHasNoDuplicateInlineListener()
    {
        $filePath = APPPATH . 'Views/layouts/tenant.php';
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);

        // Assert toggle button and panel exist
        $this->assertStringContainsString('id="notif-toggle"', $content);
        $this->assertStringContainsString('id="notif-panel"', $content);

        // Assert NO inline onclick on notif-toggle
        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]*id="notif-toggle"[^>]*onclick=/i',
            $content,
            'Tenant notification toggle must not have an inline onclick attribute'
        );

        // Assert NO duplicate inline script registering click on notif-toggle
        $this->assertDoesNotMatchRegularExpression(
            '/\bnt\.addEventListener\s*\(\s*[\'"]click[\'"]/i',
            $content,
            'Tenant layout must not register duplicate inline click listener on notif-toggle'
        );

        // Assert accessibility attributes
        $this->assertStringContainsString('aria-label="Notifications"', $content);
        $this->assertStringContainsString('aria-haspopup="true"', $content);
        $this->assertStringContainsString('aria-expanded="false"', $content);
    }

    /**
     * Admin layout must have admin-notif-toggle without conflicting inline onclick.
     */
    public function testAdminLayoutNotificationBellHasNoInlineOnclick()
    {
        $filePath = APPPATH . 'Views/layouts/admin.php';
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);

        // Assert toggle button and panel exist
        $this->assertStringContainsString('id="admin-notif-toggle"', $content);
        $this->assertStringContainsString('id="admin-notif-panel"', $content);

        // Assert NO inline onclick on admin-notif-toggle
        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]*id="admin-notif-toggle"[^>]*onclick=/i',
            $content,
            'Admin notification toggle must not have an inline onclick attribute'
        );

        // Assert accessibility attributes
        $this->assertStringContainsString('aria-label="Notifications"', $content);
        $this->assertStringContainsString('aria-haspopup="true"', $content);
        $this->assertStringContainsString('aria-expanded="false"', $content);
    }

    /**
     * Central blax-ui.js must bind all three notification dropdown pairs independently.
     */
    public function testBlaxUiHandlesAllThreeNotificationDropdownPairs()
    {
        $filePath = FCPATH . 'js/blax-ui.js';
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);

        // Assert Customer pair is defined
        $this->assertStringContainsString('notif-dropdown-toggle', $content);
        $this->assertStringContainsString('notif-dropdown', $content);

        // Assert Tenant pair is defined
        $this->assertStringContainsString('notif-toggle', $content);
        $this->assertStringContainsString('notif-panel', $content);

        // Assert Admin pair is defined
        $this->assertStringContainsString('admin-notif-toggle', $content);
        $this->assertStringContainsString('admin-notif-panel', $content);

        // Assert outside click and Escape key dismissal
        $this->assertStringContainsString('Escape', $content);
        $this->assertStringContainsString('closeAllNotifPanels', $content);
        $this->assertStringContainsString('stopPropagation', $content);
    }
}
