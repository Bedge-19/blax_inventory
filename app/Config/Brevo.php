<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Brevo extends BaseConfig
{
    /**
     * Brevo API Key (Transactional HTTP API)
     * e.g. xkeysib-...
     */
    public string $apiKey = '';

    /**
     * Verified Sender Email on Brevo
     */
    public string $senderEmail = '';

    /**
     * Sender display name
     */
    public string $senderName = '';

    public function __construct()
    {
        parent::__construct();

        $rawApiKey = (string) (getenv('BREVO_API_KEY')
            ?: (env('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? ($_SERVER['BREVO_API_KEY'] ?? $this->apiKey))));

        $rawSenderEmail = (string) (getenv('BREVO_SENDER_EMAIL')
            ?: (env('BREVO_SENDER_EMAIL') ?: ($_ENV['BREVO_SENDER_EMAIL'] ?? ($_SERVER['BREVO_SENDER_EMAIL'] ?? $this->senderEmail))));

        $rawSenderName = (string) (getenv('BREVO_SENDER_NAME')
            ?: (env('BREVO_SENDER_NAME') ?: ($_ENV['BREVO_SENDER_NAME'] ?? ($_SERVER['BREVO_SENDER_NAME'] ?? $this->senderName))));

        $this->apiKey      = $this->sanitizeCredential($rawApiKey);
        $this->senderEmail = $this->sanitizeCredential($rawSenderEmail);
        $this->senderName  = $this->sanitizeCredential($rawSenderName);

        // Fallback sender email and name from Config\Email if unset
        if (empty($this->senderEmail)) {
            $emailConfig = config(\Config\Email::class);
            $this->senderEmail = !empty($emailConfig->fromEmail) ? trim((string) $emailConfig->fromEmail) : '';
        }

        if (empty($this->senderName)) {
            $emailConfig = config(\Config\Email::class);
            $this->senderName = !empty($emailConfig->fromName) ? trim((string) $emailConfig->fromName) : 'Blax General Merchandise';
        }
    }

    /**
     * Strip accidental angle brackets <>, quotes, and whitespace from environment strings.
     */
    protected function sanitizeCredential(string $value): string
    {
        $value = trim($value, " \t\n\r\0\x0B\"'<>");
        return str_replace(['<', '>'], '', $value);
    }
}
