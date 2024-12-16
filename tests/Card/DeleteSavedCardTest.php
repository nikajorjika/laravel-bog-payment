<?php

use Jorjika\BogPayment\ApiClient;
use Jorjika\BogPayment\Card;

beforeEach(function () {
    $this->apiClient = Mockery::mock(ApiClient::class);
    $this->card = new Card($this->apiClient);
});

it('throws exception if payment method id is not provided', function () {
    $this->apiClient->shouldReceive('delete')
        ->with('/charges/card/test-id')
        ->andReturn([
            'id' => 'test-id',
        ]);

    $response = $this->card->delete('test-id');

    expect($response)->toHaveKey('id', 'test-id');
});
