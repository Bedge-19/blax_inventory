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
        $fromName    = $emailConfig->fromName ?: 'RHK General Merchandise';

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
                CLI::success("Test email successfully sent to {$to}");
                return EXIT_SUCCESS;
            } else {
                CLI::error('Email failed to send.');
                CLI::write($email->printDebugger(['headers', 'subject']));
                return EXIT_ERROR;
            }
        } catch (\Throwable $e) {
            CLI::error('Email error: ' . $e->getMessage());
            return EXIT_ERROR;
        }
    }
}