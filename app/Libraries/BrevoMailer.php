<?php

namespace App\Libraries;

use Config\Brevo as BrevoConfig;
use Config\Services;
use Throwable;

class BrevoMailer
{
    private BrevoConfig $config;

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
        $toEmail = trim($toEmail);
        if ($toEmail === '' || ! filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            log_message('error', 'BrevoMailer: Invalid recipient email address: ' . $toEmail);
            return false;
        }

        $apiKey = trim($this->config->apiKey);
        if ($apiKey === '') {
            // Unconfigured; return false so caller can fall back to SMTP
            return false;
        }

        $senderEmail = trim($this->config->senderEmail);
        if ($senderEmail === '' || ! filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
            log_message('error', 'BrevoMailer: Invalid or missing sender email in configuration.');
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

            $statusCode = $response->getStatusCode();
            $responseBody = (string) $response->getBody();

            if ($statusCode >= 200 && $statusCode < 300) {
                log_message('info', "BrevoMailer: Email successfully sent to {$toEmail} (Status {$statusCode})");
                return true;
            }

            log_message('error', "BrevoMailer: Failed to send email to {$toEmail}. HTTP {$statusCode}: {$responseBody}");
            return false;
        } catch (Throwable $e) {
            log_message('error', "BrevoMailer: Exception while sending email to {$toEmail}: " . $e->getMessage());
            return false;
        }
    }
}
