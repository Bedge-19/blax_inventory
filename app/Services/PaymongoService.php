<?php

namespace App\Services;

class PaymongoService
{
    private string $secretKey;
    private string $publicKey;
    private string $apiBase = 'https://api.paymongo.com/v1';

    public function __construct(?string $secretKey = null, ?string $publicKey = null)
    {
        $this->secretKey = $secretKey
            ?? (string) (getenv('PAYMONGO_SECRET_KEY') ?: ($_ENV['PAYMONGO_SECRET_KEY'] ?? ''));

        $this->publicKey = $publicKey
            ?? (string) (getenv('PAYMONGO_PUBLIC_KEY') ?: ($_ENV['PAYMONGO_PUBLIC_KEY'] ?? ''));
    }

    /**
     * Create a GCash Checkout Session for an amount (in PHP, e.g. 150.00).
     *
     * @param float  $amountInPhp  Amount in PHP
     * @param string $itemName     Item/service name
     * @param string $successUrl   Redirect URL upon success
     * @param string $cancelUrl    Redirect URL upon cancel
     * @param array  $metadata     Custom metadata tracking
     * @return array{success: bool, checkout_url?: string, session_id?: string, error?: string}
     */
    public function createGcashCheckoutSession(
        float $amountInPhp,
        string $itemName,
        string $successUrl,
        string $cancelUrl,
        array $metadata = []
    ): array {
        // Amount must be in centavos (PHP * 100), minimum 2000 centavos (₱20.00) for PayMongo
        $amountInCentavos = max(2000, (int) round($amountInPhp * 100));

        $payload = [
            'data' => [
                'attributes' => [
                    'send_email_receipt'   => false,
                    'show_description'     => true,
                    'show_line_items'      => true,
                    'payment_method_types' => ['qrph'],
                    'line_items'           => [
                        [
                            'currency' => 'PHP',
                            'amount'   => $amountInCentavos,
                            'name'     => $itemName,
                            'quantity' => 1,
                        ]
                    ],
                    'success_url' => $successUrl,
                    'cancel_url'  => $cancelUrl,
                    'metadata'    => !empty($metadata) ? $metadata : null,
                ]
            ]
        ];

        $res = $this->request('POST', '/checkout_sessions', $payload);

        if (!empty($res['data']['attributes']['checkout_url'])) {
            return [
                'success'      => true,
                'checkout_url' => $res['data']['attributes']['checkout_url'],
                'session_id'   => $res['data']['id'] ?? '',
                'data'         => $res['data'],
            ];
        }

        $errorMsg = 'Failed to generate PayMongo checkout session.';
        if (!empty($res['errors'][0]['detail'])) {
            $errorMsg = $res['errors'][0]['detail'];
        }

        return [
            'success' => false,
            'error'   => $errorMsg,
        ];
    }

    /**
     * Retrieve a Checkout Session by ID and check payment state.
     *
     * @param string $sessionId
     * @return array{success: bool, paid: bool, session?: array, error?: string}
     */
    public function getCheckoutSession(string $sessionId): array
    {
        $res = $this->request('GET', '/checkout_sessions/' . urlencode($sessionId));

        if (empty($res['data'])) {
            return [
                'success' => false,
                'paid'    => false,
                'error'   => $res['errors'][0]['detail'] ?? 'Checkout session not found.',
            ];
        }

        $attributes = $res['data']['attributes'] ?? [];
        $payments   = $attributes['payments'] ?? [];
        $paymentIntent = $attributes['payment_intent'] ?? [];
        $piStatus = $paymentIntent['attributes']['status'] ?? '';

        // Strictly verify that either status is paid, payment_intent succeeded, or at least one payment record is paid/succeeded
        $hasPaidPayment = false;
        if (!empty($payments) && is_array($payments)) {
            foreach ($payments as $p) {
                $pStatus = $p['attributes']['status'] ?? '';
                if ($pStatus === 'paid' || $pStatus === 'succeeded') {
                    $hasPaidPayment = true;
                    break;
                }
            }
        }

        $sessionStatus = $attributes['status'] ?? '';
        $isPaid = $hasPaidPayment || $piStatus === 'succeeded' || $sessionStatus === 'paid';

        return [
            'success'  => true,
            'paid'     => $isPaid,
            'session'  => $res['data'],
            'payments' => $payments,
        ];
    }

    /**
     * Retrieve all merchant wallet accounts and current balance.
     *
     * @return array{success: bool, wallets?: array, source_account?: array, available_balance?: float, error?: string}
     */
    public function getWallets(): array
    {
        $res = $this->request('GET', '/v2/wallets');

        if (!empty($res['errors'])) {
            $msg = $res['errors'][0]['detail'] ?? 'Failed to retrieve PayMongo wallets.';
            return ['success' => false, 'error' => $msg];
        }

        $wallets = $res['data'] ?? [];
        if (empty($wallets) || !is_array($wallets)) {
            return [
                'success'           => false,
                'wallets'           => [],
                'available_balance' => 0.0,
                'error'             => 'No active PayMongo wallet found for this merchant account.',
            ];
        }

        $firstWallet = $wallets[0] ?? [];
        $attributes  = $firstWallet['attributes'] ?? [];
        $accounts    = $attributes['accounts'] ?? ($firstWallet['accounts'] ?? []);
        
        $sourceAccount = null;
        if (!empty($accounts) && is_array($accounts)) {
            $acc = $accounts[0];
            $sourceAccount = [
                'number' => (string) ($acc['number'] ?? $acc['account_number'] ?? ''),
                'name'   => (string) ($acc['name'] ?? $acc['account_name'] ?? ''),
                'bic'    => (string) ($acc['bic'] ?? 'PAEYPHM2XXX'),
            ];
        }

        // Available balance is returned in centavos
        $centavos = (int) ($attributes['available_balance'] ?? ($attributes['balance'] ?? 0));
        $availableBalance = round($centavos / 100, 2);

        return [
            'success'           => true,
            'wallets'           => $wallets,
            'source_account'    => $sourceAccount,
            'available_balance' => $availableBalance,
        ];
    }

    /**
     * Create an external outbound GCash disbursement using PayMongo v2 Batch Transfers API.
     *
     * @param array{
     *     amount: float,
     *     destination_number: string,
     *     destination_name: string,
     *     reference_number: string,
     *     description?: string,
     *     callback_url?: string,
     *     metadata?: array
     * } $data
     * @param string $idempotencyKey Deterministic idempotency key for this transfer
     * @return array{success: bool, batch_id?: string, transfer_id?: string, status?: string, error?: string, transfer?: array}
     */
    public function createTransfer(array $data, string $idempotencyKey): array
    {
        $amountInPhp = (float) ($data['amount'] ?? 0);
        if ($amountInPhp <= 0) {
            return ['success' => false, 'error' => 'Transfer amount must be greater than zero.'];
        }

        $destNumber = preg_replace('/\D/', '', (string) ($data['destination_number'] ?? ''));
        if (!preg_match('/^09\d{9}$/', $destNumber)) {
            return ['success' => false, 'error' => 'Invalid GCash recipient number (must be 09XXXXXXXXX).'];
        }

        $destName = trim((string) ($data['destination_name'] ?? ''));
        if ($destName === '') {
            return ['success' => false, 'error' => 'GCash recipient account name is required.'];
        }

        // 1. Verify wallet availability and balance
        $walletRes = $this->getWallets();
        if (!$walletRes['success']) {
            return [
                'success' => false,
                'error'   => 'Cannot connect to PayMongo Wallet: ' . ($walletRes['error'] ?? 'Wallet unavailable'),
            ];
        }

        $walletBal = (float) ($walletRes['available_balance'] ?? 0.0);
        if ($walletBal < $amountInPhp) {
            return [
                'success'    => false,
                'error'      => 'Insufficient PayMongo wallet balance (Current: ₱' . number_format($walletBal, 2) . ', Required: ₱' . number_format($amountInPhp, 2) . '). Please fund your PayMongo Wallet.',
                'error_code' => 'insufficient_wallet_balance',
            ];
        }

        $sourceAccount = $walletRes['source_account'] ?? null;
        if (empty($sourceAccount['number']) || empty($sourceAccount['bic'])) {
            return [
                'success' => false,
                'error'   => 'PayMongo Wallet source account details could not be resolved.',
            ];
        }

        // Amount in centavos (PHP * 100)
        $amountInCentavos = (int) round($amountInPhp * 100);

        // Normalize reference number to alphanumeric and spaces as required by PayMongo
        $rawRef = (string) ($data['reference_number'] ?? ('WD ' . date('YmdHis')));
        $cleanRef = trim(preg_replace('/[^A-Za-z0-9 ]/', ' ', $rawRef));

        $payload = [
            'transfers' => [
                [
                    'provider'            => 'instapay',
                    'amount'              => $amountInCentavos,
                    'currency'            => 'PHP',
                    'purpose'             => 'Disbursement',
                    'description'         => mb_substr((string) ($data['description'] ?? 'Tenant Withdrawal'), 0, 100),
                    'reference_number'    => $cleanRef,
                    'source_account'      => $sourceAccount,
                    'destination_account' => [
                        'number' => $destNumber,
                        'name'   => mb_substr($destName, 0, 100),
                        'bic'    => 'GXCHPHM2XXX', // G-Xchange, Inc. / GCash
                    ],
                    'callback_url'        => !empty($data['callback_url']) ? (string) $data['callback_url'] : '',
                    'metadata'            => !empty($data['metadata']) ? $data['metadata'] : null,
                ]
            ]
        ];

        $extraHeaders = [];
        if (!empty($idempotencyKey)) {
            $extraHeaders['Idempotency-Key'] = trim($idempotencyKey);
        }

        $res = $this->request('POST', '/v2/batch_transfers', $payload, $extraHeaders);

        if (!empty($res['errors'])) {
            $errorDetail = $res['errors'][0]['detail'] ?? ($res['errors'][0]['code'] ?? 'Transfer failed.');
            return [
                'success' => false,
                'error'   => 'PayMongo Transfer Error: ' . $errorDetail,
            ];
        }

        $batchId   = $res['data']['id'] ?? '';
        $transfers = $res['data']['transfers'] ?? [];
        $firstTr   = $transfers[0] ?? [];
        $trId      = $firstTr['id'] ?? '';
        $status    = strtolower((string) ($firstTr['status'] ?? 'pending'));

        if (empty($trId) && empty($batchId)) {
            return [
                'success' => false,
                'error'   => 'PayMongo did not return a valid transfer ID.',
            ];
        }

        return [
            'success'     => true,
            'batch_id'    => $batchId,
            'transfer_id' => $trId,
            'status'      => $status,
            'transfer'    => $firstTr,
        ];
    }

    /**
     * Retrieve transfer status from PayMongo by transfer ID.
     *
     * @param string $transferId
     * @return array{success: bool, status?: string, transfer?: array, error?: string}
     */
    public function getTransfer(string $transferId): array
    {
        $res = $this->request('GET', '/v2/transfers/' . urlencode($transferId));

        if (!empty($res['errors'])) {
            return [
                'success' => false,
                'error'   => $res['errors'][0]['detail'] ?? 'Transfer not found.',
            ];
        }

        $transfer = $res['data'] ?? [];
        $status   = strtolower((string) ($transfer['status'] ?? 'unknown'));

        return [
            'success'  => true,
            'status'   => $status,
            'transfer' => $transfer,
        ];
    }

    /**
     * Execute an HTTP request with Basic Auth to PayMongo API.
     */
    private function request(string $method, string $endpoint, ?array $body = null, array $extraHeaders = []): array
    {
        if (str_starts_with($endpoint, 'https://')) {
            $url = $endpoint;
        } elseif (str_starts_with($endpoint, '/v2/')) {
            $url = 'https://api.paymongo.com' . $endpoint;
        } else {
            $url = rtrim($this->apiBase, '/') . '/' . ltrim($endpoint, '/');
        }

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($this->secretKey . ':'),
        ];

        foreach ($extraHeaders as $headerKey => $headerVal) {
            $headers[] = $headerKey . ': ' . $headerVal;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
        } elseif (strtoupper($method) !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
        }

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            log_message('error', 'PayMongo cURL Error: ' . $err);
            return ['errors' => [['detail' => 'Network error connecting to PayMongo.']]];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
