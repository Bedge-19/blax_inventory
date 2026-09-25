<?php

namespace App\Libraries;

use Config\Brevo as BrevoConfig;
use Config\Services;
use Throwable;

class BrevoMailer
{
    private BrevoConfig $config;

    /**
     * Diagnostic properties for error reporting and debugging.
     */
    public ?string $lastError = null;
    public ?int $lastStatusCode = null;

    public function __construct(?BrevoConfig $config = null)
    {
        $this->config = $config ?? config(BrevoConfig::class);
    }

    /**
     * Send transactional email via Brevo HTTP API (Port 443).
     *
     * @param string $toEmail   Recipient email address
     * @param string $toName    Recipient display name
     * @param string $subject   Email subject
     * @param string $htmlBody  HTML email content
     * @return bool True if successfully accepted by Brevo (HTTP 2xx), false otherwise.
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $this->lastError = null;
        $this->lastStatusCode = null;

        $toEmail = trim($toEmail);
        if ($toEmail === '' || ! filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $this->lastError = "Invalid recipient email address: '{$toEmail}'";
            log_message('error', 'BrevoMailer: ' . $this->lastError);
            return false;
        }

        $apiKey = trim($this->config->apiKey);
        if ($apiKey === '') {
            $this->lastError = 'BREVO_API_KEY is not configured or empty.';
            return false;
        }

        $senderEmail = strtolower(trim($this->config->senderEmail));
        if ($senderEmail === '' || ! filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
            $this->lastError = "BREVO_SENDER_EMAIL is invalid or not configured ('{$senderEmail}').";
            log_message('error', 'BrevoMailer: ' . $this->lastError);
            return false;
        }

        $senderName = trim($this->config->senderName) ?: 'Blax General Merchandise';
        $toName     = trim($toName) ?: $toEmail;

        $payload = [
            'sender'      => [
                'name'  => $senderName,
                'email' => $senderEmail,
            ],
            'to'          => [
                [
                    'email' => $toEmail,
                    'name'  => $toName,
                ],
            ],
            'subject'     => $subject,
            'htmlContent' => $htmlBody,
        ];

        try {
            $client = Services::curlrequest([
                'timeout'     => 15,
                'http_errors' => false,
            ]);

            $response = $client->post('https://api.brevo.com/v3/smtp/email', [
                'headers' => [
                    'accept'       => 'application/json',
                    'api-key'      => $apiKey,
                    'content-type' => 'application/json',
                ],
                'json'    => $payload,
            ]);

            $this->lastStatusCode = $response->getStatusCode();
            $responseBody = (string) $response->getBody();

            if ($this->lastStatusCode >= 200 && $this->lastStatusCode < 300) {
                log_message('info', "BrevoMailer: Email successfully sent to {$toEmail} (Status {$this->lastStatusCode})");
                return true;
            }

            $this->lastError = "HTTP {$this->lastStatusCode}: {$responseBody}";
            log_message('error', "BrevoMailer: Failed to send email to {$toEmail}. {$this->lastError}");
            return false;
        } catch (Throwable $e) {
            $this->lastError = "Exception: " . $e->getMessage();
            log_message('error', "BrevoMailer: Exception while sending email to {$toEmail}: " . $e->getMessage());
            return false;
        }
    }
}
