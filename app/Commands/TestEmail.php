<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Email as EmailConfig;

class TestEmail extends BaseCommand
{
    protected $group       = 'Email';
    protected $name        = 'email:test';
    protected $description = 'Send a test email to verify SMTP configuration.';
    protected $usage       = 'email:test <email> [subject] [message] [--from <email>]';
    protected $arguments   = [
        'email'   => 'Recipient email address (required)',
        'subject' => 'Email subject (default: "Test email from Blax Inventory")',
        'message' => 'Email body (default: "This is a test message from Blax Inventory.")',
    ];
    protected $options     = [
        '--from' => 'From email address (default: configured fromEmail in Config/Email.php or .env)',
    ];

    public function run(array $params)
    {
        $to      = $params[0] ?? CLI::getOption('to');
        $subject = $params[1] ?? CLI::getOption('subject') ?? 'Test email from Blax Inventory';
        $body    = $params[2] ?? CLI::getOption('message') ?? 'This is a test message from Blax Inventory.';

        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            CLI::error('Valid recipient email is required.');
            CLI::write('Usage: php spark email:test recipient@example.com [subject] [message]');
            return EXIT_ERROR;
        }

        $fromOption  = CLI::getOption('from');
        $emailConfig = config(EmailConfig::class);
        $from        = $fromOption ?: ($emailConfig->fromEmail ?? null);
        $fromName    = $emailConfig->fromName ?: 'Blax General Merchandise';

        // 1. Check if Brevo is configured
        $brevoConfig = config(\Config\Brevo::class);
        if (!empty($brevoConfig->apiKey)) {
            CLI::write('Attempting to send email via Brevo HTTP API (Port 443)...', 'yellow');
            $brevoMailer = new \App\Libraries\BrevoMailer();
            if ($brevoMailer->send($to, 'Test Recipient', $subject, $body)) {
                CLI::write("✓ Test email successfully sent to {$to} via Brevo HTTP API!", 'green');
                return EXIT_SUCCESS;
            }
            CLI::error('Brevo HTTP API failed: ' . ($brevoMailer->lastError ?: 'Unknown error'));
            CLI::write('Falling back to SMTP...', 'yellow');
        } else {
            CLI::write('Notice: BREVO_API_KEY is not set. Using SMTP directly...', 'light_gray');
        }

        // 2. SMTP fallback
        CLI::write('Attempting to send email via SMTP service...', 'yellow');
        try {
            $email = service('email');
            $email->clear(true);
            $email->setTo($to);

            if ($from) {
                $email->setFrom($from, $fromName);
            }

            $email->setSubject($subject);
            $email->setMessage($body);

            if ($email->send()) {
                CLI::write("Test email successfully sent to {$to} via SMTP.", 'green');
                return EXIT_SUCCESS;
            } else {
                CLI::error('Email failed to send via SMTP.');
                CLI::write($email->printDebugger(['headers', 'subject']));
                return EXIT_ERROR;
            }
        } catch (\Throwable $e) {
            CLI::error('Email error: ' . $e->getMessage());
            return EXIT_ERROR;
        }
    }
}