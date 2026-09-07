<?php

namespace App\Services;

use Config\Email;
use RuntimeException;

class MailService
{
    private Email $config;

    public function __construct(?Email $config = null)
    {
        $this->config = $config ?? config(Email::class);
    }

    /**
     * Send the one-time approval notification using the DB-backed recipient.
     */
    public function sendTenantVerified(array $user, array $shop): bool
    {
        $recipient = trim((string) ($user['email'] ?? ''));
        if ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Tenant email address is invalid.');
        }
        if (trim($this->config->fromEmail) === '') {
            throw new RuntimeException('Email sender is not configured.');
        }

        $email = service('email');
        $email->clear(true);
        $email->setFrom($this->config->fromEmail, $this->config->fromName ?: 'RHK General Merchandise');
        $email->setTo($recipient);
        $email->setSubject('Your RHK General Merchandise account has been verified');
        $email->setMessage(view('emails/tenant_verified', [
            'firstName' => (string) ($user['first_name'] ?? 'Tenant'),
            'shopName'  => (string) ($shop['shop_name'] ?? 'your shop'),
            'loginUrl'  => base_url('login'),
        ]));

        if (! $email->send(false)) {
            throw new RuntimeException('Email service could not send the verification message.');
        }

        return true;
    }
}
