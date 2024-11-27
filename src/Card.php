<?php

namespace Jorjika\BogPayment;

use Exception;

class Card extends Payment
{
    /**
     *  Charge payment method with given payment method id
     *
     * @throws Exception
     */
    public function charge($parentTransactionId): array
    {
        if (! $parentTransactionId) {
            throw new Exception('Payment method id is required');
        }

        $response = $this->apiClient->post("/ecommerce/orders/$parentTransactionId", $this->payload);

        $this->resetPayload();

        return [
            'id' => $response['id'],
            'redirect_url' => $response['_links']['redirect']['href'],
            'details_url' => $response['_links']['details']['href'],
        ];
    }

    public function delete($parentTransactionId)
    {
        return $this->apiClient->delete("/charges/card/$parentTransactionId");
    }
}
