# M-Pesa PHP SDK

A comprehensive PHP SDK for integrating with Safaricom's M-Pesa payment services. This package provides a simple and elegant way to interact with various M-Pesa APIs, including STK Push, B2B, B2C, C2B, QR Code generation, tax remittance, and more.

## Table of Contents
- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Available Methods](#available-methods)
  - [STK Push](#stk-push)
  - [STK Push Query](#stk-push-query)
  - [QR Code Generation](#qr-code-generation)
  - [C2B URL Registration](#c2b-url-registration)
  - [B2C Payments](#b2c-payments)
  - [B2B Payments](#b2b-payments)
  - [B2B Express Checkout](#b2b-express-checkout)
  - [Transaction Status](#transaction-status)
  - [Account Balance](#account-balance)
  - [Transaction Reversal](#transaction-reversal)
  - [Tax Remittance](#tax-remittance)
  - [Standing Orders (M-Pesa Ratiba)](#standing-orders-m-pesa-ratiba)
- [Error Handling](#error-handling)
- [Webhook Integration](#webhook-integration)
- [Advanced Usage](#advanced-usage)
- [Requirements](#requirements)
- [Security Best Practices](#security-best-practices)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)
- [Support](#support)
- [Credits](#credits)
- [Changelog](#changelog)

## Features

- Complete M-Pesa API integration
- Environment support for both sandbox and production
- Automatic access token management
- Built-in phone number formatting
- Comprehensive error handling and validation
- Webhook handling support
- Certificate management for security credentials
- Support for all M-Pesa transaction types:
  - Customer-to-Business (C2B)
  - Business-to-Customer (B2C)
  - Business-to-Business (B2B)
  - STK Push (M-Pesa Express)
  - QR Code Payments
  - Tax Remittance
  - Standing Orders

## Installation

Install the package via Composer:

```bash
composer require codexoft/mpesa-sdk
```

## Configuration

### Basic Configuration

```php
use codexoft\MpesaSdk\Mpesa;

$config = [
    'env' => 'sandbox', // or 'production'
    'credentials' => [
        'passKey' => 'your-pass-key',
        'initiatorPass' => 'your-initiator-pass',
        'initiatorName' => 'your-initiator-name'
    ],
    'appInfo' => [
        'consumerKey' => 'your-consumer-key',
        'consumerSecret' => 'your-consumer-secret'
    ],
    'businessShortCode' => 'your-shortcode',
    'shortCodeType' => 'paybill', // or 'till'
    'requester' => 'your-requester-id'
];
```
### Using Environment Variables (Recommended)

Create a .env file:

```env
MPESA_ENV=sandbox
MPESA_PASS_KEY=your-pass-key
MPESA_INITIATOR_PASS=your-initiator-pass
MPESA_INITIATOR_NAME=your-initiator-name
MPESA_CONSUMER_KEY=your-consumer-key
MPESA_CONSUMER_SECRET=your-consumer-secret
MPESA_BUSINESS_SHORTCODE=your-shortcode
MPESA_SHORTCODE_TYPE=paybill
MPESA_REQUESTER=your-requester-id
```

Load configuration from environment:

```php
$config = [
    'env' => $_ENV['MPESA_ENV'],
    'credentials' => [
        'passKey' => $_ENV['MPESA_PASS_KEY'],
        'initiatorPass' => $_ENV['MPESA_INITIATOR_PASS'],
        'initiatorName' => $_ENV['MPESA_INITIATOR_NAME']
    ],
    'appInfo' => [
        'consumerKey' => $_ENV['MPESA_CONSUMER_KEY'],
        'consumerSecret' => $_ENV['MPESA_CONSUMER_SECRET']
    ],
    'businessShortCode' => $_ENV['MPESA_BUSINESS_SHORTCODE'],
    'shortCodeType' => $_ENV['MPESA_SHORTCODE_TYPE'],
    'requester' => $_ENV['MPESA_REQUESTER']
];
```

## Basic Usage

Initialize the SDK:

```php
try {
    $mpesa = new Mpesa($config);
} catch (InvalidArgumentException $e) {
    // Handle configuration errors
    log_error("Configuration Error: " . $e->getMessage());
} catch (Exception $e) {
    // Handle other initialization errors
    log_error("Initialization Error: " . $e->getMessage());
}
```
## Available Methods

### STK Push

Initiate an STK push request (M-Pesa Express):

```php
try {
    $response = $mpesa->stkPush(
        amount: 100,
        phoneNumber: '254712345678',
        accountNumber: 'INV001',
        callBackUrl: 'https://example.com/callback',
        description: 'Payment for Invoice 001'
    );
    
    print_r($response);
} catch (Exception $e) {
    echo $e->getMessage();
}
```

### STK Push Query

Check the status of an STK push request:

```php
try {
    $response = $mpesa->stkPushQuery(
        checkoutRequestCode: 'ws_CO_123456789'
    );
    
    print_r($response);
} catch (Exception $e) {
    echo $e->getMessage();
}
```

### QR Code Generation

Generate a dynamic QR code for payments:

```php
try {
    $response = $mpesa->generateQRCode(
        amount: 100,
        accountNumber: 'INV001',
        size: 300 // Size in pixels
    );
    
    print_r($response);
} catch (Exception $e) {
    echo $e->getMessage();
}
```

### C2B URL Registration

Register URLs for C2B payment notifications:

```php
try {
    $response = $mpesa->registerUrl(
        responseType: 'Completed|Cancelled',
        confirmationUrl: 'https://example.com/confirmation',
        validationUrl: 'https://example.com/validation'
    );
    
    print_r($response);
} catch (Exception $e) {
    echo $e->getMessage();
}
```

### B2C Payments

Send money to customers (Business to Customer):

```php
try {
    $response = $mpesa->initiateB2C(
        amount: 100,
        phoneNumber: '254712345678',
        commandID: 'BusinessPayment|SalaryPayment|PromotionPayment', // or 'SalaryPayment', 'PromotionPayment'
        resultUrl: 'https://example.com/b2c-result',
        queueTimeoutUrl: 'https://example.com/b2c-timeout',
        remarks: 'Salary Payment'
    );
    
    print_r($response);
} catch (Exception $e) {
    echo $e->getMessage();
}
```

### B2B Payments

Make payments to other businesses:

```php
try {
    $response = $mpesa->initiateB2B(
        amount: 1000,
        paymentType: 'PaybillToPaybill|PaybillToTill|B2BAccountTopUp', // or 'PaybillToTill', 'B2BAccountTopUp'
        shortCode: '600000',
        accountNumber: 'ACC001',
        resultUrl: 'https://example.com/b2b-result',
        queueTimeoutUrl: 'https://example.com/b2b-timeout'
    );
    
    print_r($response);
} catch (Exception $e) {
    echo $e->getMessage();
}
```

### B2B Express Checkout

Initiate a B2B Express Checkout (USSD Push):

```php
try {
    $response = $mpesa->initiateB2BExpressCheckout(
        amount: 1000,
        receiverShortCode: '600000',
        callBackUrl: 'https://example.com/express-callback',
        partnerName: 'Vendor Name',
        paymentRef: 'PAY123', // optional
        requestRef: 'REQ123'  // optional
    );
    
    print_r($response);
} catch (Exception $e) {
    echo $e->getMessage();
}
```

### Transaction Status

Query the status of any transaction:

```php
try {
    $response = $mpesa->transactionStatus(
        transactionID: 'SLU000000',
        resultUrl: 'https://example.com/status-result',
        queueTimeoutUrl: 'https://example.com/status-timeout'
    );
    
    print_r($response);
} catch (Exception $e) {
    echo $e->getMessage();
}
```

### Account Balance

Check your business account balance:

```php
try {
    $response = $mpesa->accountBalance(
        resultUrl: 'https://example.com/balance-result',
        queueTimeoutUrl: 'https://example.com/balance-timeout'
    );
    
    print_r($response);
} catch (Exception $e) {
    echo $e->getMessage();
}
```

### Transaction Reversal

Reverse a completed transaction:

```php
try {
    $response = $mpesa->reverseTransaction(
        amount: 100,
        transactionID: 'SLU000000',
        resultUrl: 'https://example.com/reversal-result',
        queueTimeoutUrl: 'https://example.com/reversal-timeout'
    );
    
    print_r($response);
} catch (Exception $e) {
    echo $e->getMessage();
}
```

### Tax Remittance

Process tax payments to KRA:

```php
try {
    $response = $mpesa->taxRemittance(
        amount: 1000,
        paymentRegistrationNo: 'PRN12345',
        resultUrl: 'https://example.com/tax-result',
        queueTimeoutUrl: 'https://example.com/tax-timeout'
    );
    
    print_r($response);
} catch (Exception $e) {
    echo $e->getMessage();
}
```

### Standing Orders (M-Pesa Ratiba)

Create recurring payment orders:

```php
try {
    $response = $mpesa->mpesaRatiba(
        amount: 1000,
        phoneNumber: '254712345678',
        accountReference: 'ACC001',
        startDate: '2024-01-01',
        endDate: '2024-12-31',
        standingOrderName: 'Monthly Subscription',
        callBackUrl: 'https://example.com/ratiba-callback',
        frequency: '4' // Monthly
    );
    
    print_r($response);
} catch (Exception $e) {
    echo $e->getMessage();
}
```

Frequency options:
- 1: One Off
- 2: Daily
- 3: Weekly
- 4: Monthly
- 5: Bi-Monthly
- 6: Quarterly
- 7: Half Year
- 8: Yearly

## Error Handling

The SDK implements comprehensive error handling:

```php
try {
    // M-Pesa API call
} catch (InvalidArgumentException $e) {
    // Handle validation errors
    error_log("Validation error: " . $e->getMessage());
} catch (Exception $e) {
    // Handle API errors
    error_log("API error: " . $e->getMessage());
    
    // Get HTTP status code if available
    if (property_exists($e, 'httpCode')) {
        error_log("HTTP Status: " . $e->httpCode);
    }
}
```

## Webhook Integration

Example webhook handler for callbacks:

```php
<?php
// webhook.php

// Get the raw post data
$callbackData = file_get_contents('php://input');

// Validate JSON data
if (!$callbackJson = json_decode($callbackData, true)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid JSON');
}

// Log the callback
error_log("M-Pesa Callback: " . print_r($callbackJson, true));

// Process based on callback type
switch ($callbackJson['TransactionType'] ?? '') {
    case 'Pay Bill':
        // Handle C2B payment
        processC2BPayment($callbackJson);
        break;
    case 'B2B Payment':
        // Handle B2B payment
        processB2BPayment($callbackJson);
        break;
    // Add other transaction types
}

// Respond to M-Pesa
header('Content-Type: application/json');
echo json_encode([
    'ResultCode' => 0,
    'ResultDesc' => 'Success'
]);

function processC2BPayment($data) {
    // Implementation
}

function processB2BPayment($data) {
    // Implementation
}
```

## Advanced Usage

### Certificate Management

The SDK automatically handles certificates for different environments:

- Production: `/Certificates/ProductionCertificate.cer`
- Sandbox: `/Certificates/SandboxCertificate.cer`

### Phone Number Formatting

The SDK automatically formats phone numbers to the required format:

```php
// All these will be formatted to 254712345678
$phoneNumber = '0712345678';
$phoneNumber = '712345678';
$phoneNumber = '254712345678';
```

### Access Token Management

Access tokens are automatically generated and managed by the SDK. You don't need to handle token generation or refreshing manually.

## Requirements

- PHP 8.0 or higher
- Extensions:
  - curl
  - json
  - openssl
- Composer
- HTTPS enabled web server
- Valid M-Pesa API credentials
- Valid SSL certificate for production

## Security Best Practices

1. Environment Configuration
   - Use environment variables for sensitive data
   - Never commit credentials to version control
   - Use different credentials for sandbox and production

2. SSL/TLS
   - Always use HTTPS for callbacks
   - Keep certificates up to date
   - Validate SSL certificates

3. Error Handling
   - Log errors securely
   - Don't expose sensitive information in error messages
   - Implement proper error recovery

4. Data Validation
   - Validate all input data
   - Sanitize callback data
   - Implement request signing if needed

5. Access Control
   - Implement IP whitelisting for callbacks
   - Use strong passwords
   - Rotate credentials regularly

## Testing

### Sandbox Testing

```php
$config['env'] = 'sandbox';
// Use sandbox credentials
```

### Production Testing

```php
$config['env'] = 'production';
// Use production credentials
```

### Test Phone Numbers

For sandbox testing, use these test phone numbers:
- 254708374149
- 254708374150

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/my-new-feature`)
3. Commit your changes (`git commit -am 'Add some feature'`)
4. Push to the branch (`git push origin feature/my-new-feature`)
5. Create a new Pull Request

## License

This project is licensed under the MIT License. See the LICENSE file for details.

## Support

- For bugs and issues, create an issue in the GitHub repository
- For security issues, email [wainainamartin29@gmail.com]
- For more documentation, visit [https://developer.safaricom.co.ke/Documentation]

### Community Support
- GitHub Issues
- Stack Overflow tag: `mpesa-sdk`

## Credits

- codexoft
- Martin Wainaina
- Safaricom M-Pesa API Team

## Changelog

### [1.0.0] - 2024-XX-XX
- Initial release
- Basic M-Pesa integration features
- Comprehensive documentation

### [Unreleased]
- Future features and improvements
