# Deprecated

## ⚠️ Deprecation Warning

This package is **deprecated** and will no longer receive updates, bug fixes, or security patches. 

### 🔄 Recommended Alternatives
The package has been moved to a new vendor. To continue receiving updates, please switch to the new package.
- **[RedberryProducts/laravel-bog-payment](https://github.com/RedberryProducts/laravel-bog-payment)** – Maintained and actively supported.

### ❓ What This Means
- **No new features** will be added.
- **No bug fixes** or **security patches** will be provided.
- **No support** for future Laravel versions.
- The repository may be archived in the near future.

### 🚀 Migration Guide
1. In composer.json, replace `"nikajorjika/bog-payment": "^1.0"` with `"redberryproducts/laravel-bog-payment": "^1.0"`
2. Run `composer update`
3. Replace `Jorjika\BogPayment\Facades\Pay` with `RedberryProducts\LaravelBogPayment\Facades\Pay`
4. Replace `Jorjika\BogPayment\Facades\Transaction` with `RedberryProducts\LaravelBogPayment\Facades\Transaction`
5. In your Listener, replace `Nikajorjika\BogPayment\Events\TransactionStatusUpdated` with `RedberryProducts\LaravelBogPayment\Events\TransactionStatusUpdated`

### ❤️ Thank You

# BOG Payment Gateway

The BOG Payment package provides seamless integration with the Bank of Georgia's payment gateway, enabling Laravel applications to process payments efficiently.


[![Latest Version on Packagist](https://img.shields.io/packagist/v/nikajorjika/bog-payment.svg?style=flat-square)](https://packagist.org/packages/nikajorjika/bog-payment)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/nikajorjika/bog-payment/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/nikajorjika/bog-payment/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/nikajorjika/bog-payment/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/nikajorjika/bog-payment/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/nikajorjika/bog-payment.svg?style=flat-square)](https://packagist.org/packages/nikajorjika/bog-payment)

### Demo
You can find a demo project [here](https://github.com/nikajorjika/bog-payment-demo)

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

Once published, the configuration file will be available at:
```bash
config/bog-payment.php
```

## Environment Variables
Add the following variables to your `.env` file to configure the package:

```dotenv
BOG_SECRET=[your_client_secret]
BOG_CLIENT_ID=[your_client_id]
BOG_PUBLIC_KEY=[your_public_key] # Can be found at https://api.bog.ge/docs/payments/standard-process/callback
```

You can find up to date `BOG_PUBLIC_KEY` in the Bank of Georgia's [API documentation](https://api.bog.ge/docs/payments/standard-process/callback).

You can also configure additional environment variables as needed. But this is the minimum that you need to implement.

## Usage

### Usage Example: Simple Payment Processing
To initiate a payment, use the `Pay` facade to set the order details and process the transaction:

```php
use Jorjika\BogPayment\Facades\Pay;
use App\Models\Transaction;

// Step 1: Create a transaction record
$transaction = Transaction::create([
    'user_id'    => auth()->id(),
    'amount'     => $data['total_amount'],
    'status'     => 'pending', // Initial status
]);

// Step 2: Process the payment
$paymentDetails = Pay::orderId($transaction->id)
    ->redirectUrl(route('bog.v1.transaction.status', ['transaction_id' => $transaction->id]))
    ->amount($transaction->amount)
    ->process();

// Step 3: Update the transaction with payment details
$transaction->update([
    'transaction_id'   => $paymentDetails['id'],
]);

// Step 4: Redirect user to the payment gateway
return redirect($paymentDetails['redirect_url']);
```

here's an example of the response:
```php
$paymentDetails = [
    'id' => 'test-id',
    'redirect_url' => 'https://example.com/redirect',
    'details_url' => 'https://example.com/details',
]
```

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
Setting Up the Event Listener
To handle transaction status updates efficiently, you need to register an event listener that listens for the TransactionStatusUpdated event triggered by the package.

1. Generating the Listener Automatically
   You can generate the event listener using the Artisan command:
    ```bash
    php artisan make:listener HandleTransactionStatusUpdate --event=\Nikajorjika\BogPayment\Events\TransactionStatusUpdated
    ```
    This command will create a listener class at `app/Listeners/HandleTransactionStatusUpdate.php`, which you can customize to handle the event logic.

This approach provides flexibility by allowing dynamic event registrations at runtime without modifying the EventServiceProvider. 

For more details on event handling in Laravel, refer to the official [documentation](https://laravel.com/docs/11.x/events#event-discovery).
   

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

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nika Jorjoliani](https://github.com/nikajorjika)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
