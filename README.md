# BOG Payment Gateway

The BOG Payment package provides seamless integration with the Bank of Georgia's payment gateway, enabling Laravel applications to process payments efficiently.


[![Latest Version on Packagist](https://img.shields.io/packagist/v/nikajorjika/bog-payment.svg?style=flat-square)](https://packagist.org/packages/nikajorjika/bog-payment)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/nikajorjika/bog-payment/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/nikajorjika/bog-payment/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/nikajorjika/bog-payment/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/nikajorjika/bog-payment/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/nikajorjika/bog-payment.svg?style=flat-square)](https://packagist.org/packages/nikajorjika/bog-payment)

### Features
- Payment Processing: Initiate and manage transactions through the Bank of Georgia.
- Transaction Status: Retrieve and handle the status of payments.
- Secure Communication: Ensure secure data transmission with the payment gateway.

## Installation

You can install the package via composer:

```bash
composer require nikajorjika/bog-payment
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="bog-payment-config"
```

This is the contents of the published config file:

```php
<?php

/*
|--------------------------------------------------------------------------
| BOG Payment Configuration
|--------------------------------------------------------------------------
|
| This file is for setting up the Bank of Georgia payment gateway integration.
| You can define your callback URLs, API credentials, and other necessary
| settings here. Make sure to update these values in your environment file.
|
*/

return [
    /*
    |--------------------------------------------------------------------------
    | Callback URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by BOG to send payment notifications to your application.
    | Make sure this endpoint is accessible publicly and handles the callback
    | appropriately to update your payment records.
    |
    */
    'callback_url' => env('BOG_CALLBACK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Redirect URLs
    |--------------------------------------------------------------------------
    |
    | After the payment process, users will be redirected to these URLs depending
    | on whether the payment was successful or failed. Set these URLs to ensure
    | a smooth user experience.
    |
    */
    'redirect_urls' => [
        /*
        | URL to redirect to on successful payment
        */
        'success' => env('BOG_REDIRECT_SUCCESS'),

        /*
        | URL to redirect to on failed payment
        */
        'fail' => env('BOG_REDIRECT_FAIL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | BOG API Credentials
    |--------------------------------------------------------------------------
    |
    | These credentials are used to authenticate your application with the
    | Bank of Georgia payment API. Make sure to keep these values secure.
    |
    */
    'client_id' => env('BOG_CLIENT_ID', ''),
    'secret' => env('BOG_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | BOG Payment API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for accessing the Bank of Georgia payment API. You can set
    | this to the test or live endpoint depending on your environment.
    |
    */
    'base_url' => env('BOG_BASE_URL', 'https://api.bog.ge/payments/v1'),
    
    /*
    |--------------------------------------------------------------------------
    | BOG Public Key
    |--------------------------------------------------------------------------
    |
    | This public key is used to verify the signature of the callback requests
    | sent by the Bank of Georgia payment gateway. Make sure to keep this key
    | up to date in your environment file.
    | Here you can see the latest public key: https://api.bog.ge/docs/payments/standard-process/callback
    |
    */
    'public_key' => env('BOG_PUBLIC_KEY')
];

```

## Usage

### Simple Payment Processing
```php
use Jorjika\BogPayment\Facades\Pay;
// ...
$paymentDetails = Pay::orderId($transaction->id)
            ->redirectUrl(route('bog.v1.transaction.status', ['transaction_id' => $transaction->id]))
            ->amount($data['total_amount'])
            ->process();
```
`process()` will return an array of payment details as an associative array.

here's an example of the response:
```php
$paymentDetails = [
    'id' => 'test-id',
    'redirect_url' => 'https://example.com/redirect',
    'details_url' => 'https://example.com/details',
]
```
**Recommended**: At this stage, create a transaction record and store the returned payment details in your database.

Once you’ve saved the payment information, redirect the user to the `redirect_url` provided in the response. This URL will direct the user to the payment gateway where they can complete the transaction. After successful payment processing, the user will be redirected back to the redirect_url specified in your request.

### Save Card During Payment

To save the card during the payment process, you can use the `saveCard()` method. This method will save the card details for future transactions.

When you want to save card during the payment, you need to do the following:

```php
use Jorjika\BogPayment\Facades\Pay;

// SaveCard method will initiate another request that notifies bank to save card details
$response = Pay::orderId($external_order_id)->amount($amount)->saveCard()->process();

// Example response
$response = [
    'id' => 'test-id',
    'redirect_url' => 'https://example.com/redirect',
    'details_url' => 'https://example.com/details',
];
```
When you receive the response, you can save the card details in your database, where `id` is the saved card(parent transaction) id that you would use for later transactions.

### Payment with Saved Card
Once you have saved new payment method id in your database, you can initiate payments on saved cards like so:

```php
use Jorjika\BogPayment\Facades\Card;

$response = Card::orderId($external_order_id)->amount($amount)->charge("test-id");

// Example response
$response = [
    'id' => 'test-id',
    'redirect_url' => 'https://example.com/redirect',
    'details_url' => 'https://example.com/details',
];
```
Functionality above will charge saved card without the user interaction.


## Building the payload

Although the package provides a convenient way to initiate payments, you can also build the payment payload manually using the provided traits.

The BuildsPayment trait helps you build the payload for payments quickly by providing the following methods

Here's how you do it:

```php

getPayload(): // Retrieves the current payload array.

orderId($externalOrderId): // Sets the external order ID for the payment.

callbackUrl($callbackUrl): // Sets a custom callback URL for the payment process.

redirectUrl($statusUrl): // Sets both success and fail URLs to the same value for redirection after the payment.

redirectUrls($failUrl, $successUrl): // Sets separate fail and success URLs for redirection after the payment.

saveCard(): // Sets the save card flag to true for the payment.

amount($totalAmount, $currency = 'GEL', $basket = []): // Defines the total amount, currency, and optionally, the basket details for the payment.

// These methods allow for easy customization of the payment payload to suit various payment requirements.
```
### Set `Buyer`

You can set the buyer details for the payment by using the `setBuyer()` method. This method accepts an array of buyer details, including the buyer's full_name, masked_email, and masked_phone.

here's the example of how you can set the buyer details:

```php
use Jorjika\BogPayment\Facades\Pay;

$buyer = [
    'full_name' => 'John Doe',
    'masked_email' => 'john**@gmail.com',
    'masked_phone' => '59512****10',
];
    
$paymentDetails = Pay::orderId($transaction->id)
            ->redirectUrl(route('bog.v1.transaction.status', ['transaction_id' => $transaction->id]))
            ->amount($data['total_amount'])
            ->buyer($buyer) // Set new buyer details
            ->process();

// Optionally you can set buyer details separately

$paymentDetails = Pay::orderId($transaction->id)
            ->redirectUrl(route('bog.v1.transaction.status', ['transaction_id' => $transaction->id]))
            ->amount($data['total_amount'])
            ->buyerName($buyer['full_name']) // Set new buyer full name
            ->buyerEmail($buyer['masked_email']) // Set new buyer masked email
            ->buyerPhone($buyer['masked_phone']) // Set new buyer masked phone
            ->process();
```


## Callback Handling

The package handles callback behavior automatically. When a payment is processed, it will send a POST request to your callback URL with the payment details. The package then verifies the request's signature to ensure its authenticity and fires the Nikajorjika\BogPayment\Events\TransactionStatusUpdated event, which contains all relevant payment details.

To utilize this functionality, register an event listener in your application to capture and respond to the transaction status updates as needed.

Example: Registering a Listener for Transaction Status Updates
Add the following code to your event listener:

```php
namespace App\Listeners;

use Nikajorjika\BogPayment\Events\TransactionStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleTransactionStatusUpdate implements ShouldQueue
{
use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param  \Nikajorjika\BogPayment\Events\TransactionStatusUpdated  $event
     * @return void
     */
    public function handle(array $event)
    {
        // Implement your logic here
    }
```
### Setting Up the Event Listener

Register the event listener in your EventServiceProvider to ensure it's triggered when the TransactionStatusUpdated event is fired:

```php
protected $listen = [
    \Nikajorjika\BogPayment\Events\TransactionStatusUpdated::class => [
        \App\Listeners\HandleTransactionStatusUpdate::class,
    ],
];
```
This setup allows your application to handle transaction status updates efficiently, enabling you to respond to each status change in real time.


## Handling Transaction Status

The package provides a convenient way to retrieve the status of a transaction using the `Transaction` Facade's `get()` method. This method sends a GET request to the Bank of Georgia payment API to retrieve the transaction status.

Here's how you can use it:

```php
use Jorjika\BogPayment\Facades\Transaction;

$transactionDetails = Transaction::get($order_id); // Returns array of transaction details
```

See example of the response [Official Documentation](https://api.bog.ge/docs/payments/standard-process/get-payment-details)


## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nika Jorjoliani](https://github.com/nikajorjika)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
