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
            'callback_url' => ! empty(config('bog-payment.callback_url')) ? config('bog-payment.callback_url') : secure_url(route('bog-payment.callback',
                [], false)),
            'redirect_urls' => config('bog-payment.redirect_urls'),
            'purchase_units' => [
                'currency' => 'GEL',
            ],
        ];
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function saveCard(): static
    {
        $this->saveCard = true;

        return $this;
    }

    public function orderId($externalOrderId): static
    {
        $this->payload['external_order_id'] = $externalOrderId;

        return $this;
    }

    public function callbackUrl($callbackUrl): static
    {
        $this->payload['callback_url'] = $callbackUrl;

        return $this;
    }

    public function redirectUrl($statusUrl): static
    {
        $this->payload['redirect_urls'] = [
            'fail' => $statusUrl,
            'success' => $statusUrl,
        ];

        return $this;
    }

    public function redirectUrls($failUrl, $successUrl): static
    {
        $this->payload['redirect_urls'] = [
            'fail' => $failUrl,
            'success' => $successUrl,
        ];

        return $this;
    }

    public function automatic(): static
    {
        $this->payload['capture'] = 'automatic';
      
        return $this;
    }
  
    public function manual(): static
    {
        $this->payload['capture'] = 'manual';
      
        return $this;
    }
  
    public function buyer(array $buyer): static
    {
        $this->payload['buyer'] = $buyer;

        return $this;
    }


    public function buyerName(string $fullName): static
    {
        if (! isset($this->payload['buyer']) || ! is_array($this->payload['buyer'])) {
            $this->payload['buyer'] = [];
        }

        $this->payload['buyer']['full_name'] = $fullName;

        return $this;
    }

    public function buyerEmail(string $maskedEmail): static
    {
        if (! isset($this->payload['buyer']) || ! is_array($this->payload['buyer'])) {
            $this->payload['buyer'] = [];
        }

        $this->payload['buyer']['masked_email'] = $maskedEmail;

        return $this;
    }

    public function buyerPhone(string $maskedPhone): static
    {
        if (! isset($this->payload['buyer']) || ! is_array($this->payload['buyer'])) {
            $this->payload['buyer'] = [];
        }

        $this->payload['buyer']['masked_phone'] = $maskedPhone;

        return $this;
    }
  
    public function amount(float $totalAmount, string $currency = 'GEL', array $basket = []): static
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
