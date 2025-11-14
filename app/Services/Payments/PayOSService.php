<?php

// app/Services/Payments/PayOSService.php
namespace App\Services\Payments;

use PayOS\PayOS;

class PayOSService
{
    public function client(): PayOS
    {
        return new PayOS(
            config('services.payos.client_id', env('PAYOS_CLIENT_ID')),
            config('services.payos.api_key', env('PAYOS_API_KEY')),
            config('services.payos.checksum_key', env('PAYOS_CHECKSUM_KEY')),
        );
    }

    public function createLink(array $payload): array
    {
        return $this->client()->createPaymentLink($payload);
    }

    public function verifyWebhook(array $payload): bool
    {
        return (bool) $this->client()->verifyWebhookData($payload);
    }
}
