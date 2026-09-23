<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    public string $fromEmail  = '';
    public string $fromName   = '';
    public string $recipients = '';

    /**
     * The "user agent"
     */
    public string $userAgent = 'CodeIgniter';

    /**
     * The mail sending protocol: mail, sendmail, smtp
     */
    public string $protocol = 'smtp';

    /**
     * The server path to Sendmail.
     */
    public string $mailPath = '/usr/sbin/sendmail';

    /**
     * SMTP Server Hostname
     */
    public string $SMTPHost = '';

    /**
     * SMTP Username
     */
    public string $SMTPUser = '';

    /**
     * SMTP Password
     */
    public string $SMTPPass = '';

    /**
     * SMTP Port
     */
    public int $SMTPPort = 587;

    /**
     * SMTP Timeout (in seconds)
     */
    public int $SMTPTimeout = 5;

    /**
     * Enable persistent SMTP connections
     */
    public bool $SMTPKeepAlive = false;

    /**
     * SMTP Encryption.
     *
     * @var string '', 'tls' or 'ssl'. 'tls' will issue a STARTTLS command
     *             to the server. 'ssl' means implicit SSL. Connection on port
     *             465 should set this to ''.
     */
    public string $SMTPCrypto = 'tls';

    /**
     * Enable word-wrap
     */
    public bool $wordWrap = true;

    /**
     * Character count to wrap at
     */
    public int $wrapChars = 76;

    /**
     * Type of mail, either 'text' or 'html'
     */
    public string $mailType = 'html';

    /**
     * Character set (utf-8, iso-8859-1, etc.)
     */
    public string $charset = 'UTF-8';

    /**
     * Whether to validate the email address
     */
    public bool $validate = false;

    /**
     * Email Priority. 1 = highest. 5 = lowest. 3 = normal
     */
    public int $priority = 3;

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $CRLF = "\r\n";

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $newline = "\r\n";

    /**
     * Enable BCC Batch Mode.
     */
    public bool $BCCBatchMode = false;

    /**
     * Number of emails in each BCC batch
     */
    public int $BCCBatchSize = 200;

    /**
     * Enable notify message from server
     */
    public bool $DSN = false;

    public function __construct()
    {
        // Let CI's parent load dotted-key values from .env (local dev).
        parent::__construct();

        // -----------------------------------------------------------
        // Explicit env-var overrides for Vercel (underscore/caps names)
        // -----------------------------------------------------------
        // Vercel does not allow dots in env var names, so CI's auto-
        // mapping of "email.SMTPHost" never fires. We read the Vercel-
        // style names directly and fall back to whatever CI already set.
        // -----------------------------------------------------------

        $fromName = getenv('EMAIL_FROMNAME') ?: getenv('EMAIL_FROM_NAME');
        if ($fromName !== false && $fromName !== '') {
            $this->fromName = $fromName;
        }

        $fromEmail = getenv('EMAIL_FROMEMAIL') ?: getenv('EMAIL_FROM_EMAIL');
        if ($fromEmail !== false && $fromEmail !== '') {
            $this->fromEmail = $fromEmail;
        }

        $protocol = getenv('EMAIL_PROTOCOL');
        if ($protocol !== false && $protocol !== '') {
            $this->protocol = $protocol;
        }

        $smtpHost = getenv('EMAIL_SMTPHOST') ?: getenv('EMAIL_SMTP_HOST');
        if ($smtpHost !== false && $smtpHost !== '') {
            $this->SMTPHost = $smtpHost;
        }

        $smtpUser = getenv('EMAIL_SMTPUSER') ?: getenv('EMAIL_SMTP_USER');
        if ($smtpUser !== false && $smtpUser !== '') {
            $this->SMTPUser = $smtpUser;
        }

        $smtpPass = getenv('EMAIL_SMTPPASS') ?: getenv('EMAIL_SMTP_PASS');
        if ($smtpPass !== false && $smtpPass !== '') {
            $this->SMTPPass = $smtpPass;
        }

        $smtpPort = getenv('EMAIL_SMTPPORT') ?: getenv('EMAIL_SMTP_PORT');
        if ($smtpPort !== false && $smtpPort !== '') {
            $this->SMTPPort = (int) $smtpPort;
        }

        $smtpCrypto = getenv('EMAIL_SMTPCRYPTO') ?: getenv('EMAIL_SMTP_CRYPTO');
        if ($smtpCrypto !== false && $smtpCrypto !== '') {
            $this->SMTPCrypto = $smtpCrypto;
        }
    }
}
