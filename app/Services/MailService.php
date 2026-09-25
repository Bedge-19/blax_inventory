<?php

namespace App\Services;

use App\Libraries\BrevoMailer;
use Config\Email;
use RuntimeException;
use Throwable;

class MailService
{
    private Email $config;
    private BrevoMailer $brevoMailer;

    public function __construct(?Email $config = null, ?BrevoMailer $brevoMailer = null)
    {
        $this->config      = $config ?? config(Email::class);
        $this->brevoMailer = $brevoMailer ?? new BrevoMailer();
    }

    /**
     * Centralized email dispatcher:
     * 1. Tries Brevo HTTP API (Port 443 - works in Railway / Cloud).
     * 2. Falls back to CodeIgniter SMTP service('email') for local dev if Brevo is not configured or fails.
     */
    private function dispatch(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        // 1. Try Brevo HTTP API first
        if ($this->brevoMailer->send($toEmail, $toName, $subject, $htmlBody)) {
            return true;
        }

        // 2. Fall back to CodeIgniter native email service (SMTP)
        try {
            $fromEmail = trim((string) $this->config->fromEmail);
            if ($fromEmail === '') {
                log_message('error', 'MailService: Sender fromEmail is not configured for SMTP fallback.');
                return false;
            }

            $fromName = trim((string) $this->config->fromName) ?: 'Blax General Merchandise';

            $email = service('email');
            $email->clear(true);
            $email->setFrom($fromEmail, $fromName);
            $email->setTo($toEmail);
            $email->setSubject($subject);
            $email->setMessage($htmlBody);

            if ($email->send(false)) {
                log_message('info', "MailService: Sent email via SMTP fallback to {$toEmail}");
                return true;
            }

            log_message('error', "MailService: SMTP fallback failed for {$toEmail}. Debugger: " . $email->printDebugger(['headers', 'subject']));
            return false;
        } catch (Throwable $e) {
            log_message('error', "MailService: SMTP fallback exception for {$toEmail}: " . $e->getMessage());
            return false;
        }
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

        $firstName = (string) ($user['first_name'] ?? 'Tenant');
        $toName    = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $firstName;
        $subject   = 'Your RHK General Merchandise account has been verified';
        $htmlBody  = view('emails/tenant_verified', [
            'firstName' => $firstName,
            'shopName'  => (string) ($shop['shop_name'] ?? 'your shop'),
            'loginUrl'  => base_url('login'),
        ]);

        if (! $this->dispatch($recipient, $toName, $subject, $htmlBody)) {
            throw new RuntimeException('Email service could not send the verification message.');
        }

        return true;
    }

    /**
     * Send rejection notification using the DB-backed recipient.
     */
    public function sendTenantRejected(array $user, array $shop, string $reason): bool
    {
        $recipient = trim((string) ($user['email'] ?? ''));
        if ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Tenant email address is invalid.');
        }

        $firstName = (string) ($user['first_name'] ?? 'Applicant');
        $toName    = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $firstName;
        $subject   = 'Update regarding your merchant application';
        $htmlBody  = view('emails/tenant_rejected', [
            'firstName' => $firstName,
            'shopName'  => (string) ($shop['shop_name'] ?? 'your shop'),
            'reason'    => $reason,
        ]);

        if (! $this->dispatch($recipient, $toName, $subject, $htmlBody)) {
            throw new RuntimeException('Email service could not send the rejection message.');
        }

        return true;
    }

    /**
     * Send password reset request email.
     */
    public function sendPasswordReset(string $toEmail, string $firstName, string $resetLink): bool
    {
        $recipient = trim($toEmail);
        if ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Recipient email address is invalid.');
        }

        $subject  = 'Password Reset Request - Blax Marketplace';
        $htmlBody = "Hello " . esc($firstName) . ",<br><br>" .
            "We received a request to reset the password for your Blax account.<br>" .
            "Click the link below to set a new password:<br><br>" .
            "<a href=\"" . esc($resetLink) . "\">" . esc($resetLink) . "</a><br><br>" .
            "This link will expire in 1 hour.<br><br>" .
            "If you did not request a password reset, you can safely ignore this email.";

        $toName = trim($firstName) ?: 'User';

        return $this->dispatch($recipient, $toName, $subject, $htmlBody);
    }
}
