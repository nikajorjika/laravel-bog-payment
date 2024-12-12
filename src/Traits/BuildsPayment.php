<?php

namespace Jorjika\BogPayment\Traits;

use RuntimeException;

trait BuildsPayment
{
    protected array $payload;

    protected bool $saveCard = false;

    public function resetPayload($data = null): void
    {
        $this->saveCard = false;
        $this->payload = $data ?? [
            'callback_url' => ! empty(config('bog-payment.callback_url')) ? config('bog-payment.callback_url') : secure_url(route('bog-payment.callback', [], false)),
            'redirect_urls' => config('bog-payment.redirect_urls'),
            'purchase_units' => [
                'currency' => 'GEL',
            ],
        ];
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function saveCard()
    {
        $this->saveCard = true;

        return $this;
    }

    public function orderId($externalOrderId)
    {
        $this->payload['external_order_id'] = $externalOrderId;

        return $this;
    }

    public function callbackUrl($callbackUrl)
    {
        $this->payload['callback_url'] = $callbackUrl;

        return $this;
    }

    public function redirectUrl($statusUrl)
    {
        $this->payload['redirect_urls'] = [
            'fail' => $statusUrl,
            'success' => $statusUrl,
        ];

        return $this;
    }

    public function redirectUrls($failUrl, $successUrl)
    {
        $this->payload['redirect_urls'] = [
            'fail' => $failUrl,
            'success' => $successUrl,
        ];

        return $this;
    }

    public function manual(): static
    {
        $this->payload['capture'] = 'manual';

        return $this;
    }

    public function automatic(): static
    {
        $this->payload['capture'] = 'automatic';

        return $this;
    }

    public function amount(float $totalAmount, string $currency = 'GEL', array $basket = [])
    {
        if (! isset($this->payload['external_order_id']) || empty($this->payload['external_order_id'])) {
            throw new RuntimeException('Please set order id before setting amount.');
        }

        if (empty($basket)) {
            $basket = [
                [
                    'quantity' => 1,
                    'unit_price' => $totalAmount,
                    'product_id' => $this->payload['external_order_id'],
                ],
            ];
        }

        $this->payload['purchase_units'] = [
            'currency' => $currency,
            'total_amount' => $totalAmount,
            'basket' => $basket,
        ];

        return $this;
    }
}
