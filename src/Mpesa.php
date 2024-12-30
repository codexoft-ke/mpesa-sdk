<?php

/**
 * Mpesa SDK for handling M-Pesa API integration.
 * 
 * This class provides methods to interact with Safaricom's M-Pesa services, including generating
 * access tokens, initiating payments, and handling responses.
 * 
 * @package codexoft\MpesaSdk
 */
namespace codexoft\MpesaSdk;

use Exception;
use InvalidArgumentException;

/**
 * Summary of Mpesa
 */
class Mpesa
{
    /**
     * Summary of environment
     * @var 
     */
    private $environment;
    /**
     * Summary of accessToken
     * @var 
     */
    private $accessToken;
    /**
     * Summary of timeStamp
     * @var 
     */
    private $timeStamp;
    /**
     * Summary of businessShortCode
     * @var 
     */
    private $businessShortCode;
    /**
     * Summary of shortCodeType
     * @var 
     */
    private $shortCodeType;
    /**
     * Summary of shortCodePassword
     * @var 
     */
    private $shortCodePassword;
    /**
     * Summary of initiatorName
     * @var 
     */
    private $initiatorName;
    /**
     * Summary of consumerKey
     * @var 
     */
    private $consumerKey;
    /**
     * Summary of consumerSecret
     * @var 
     */
    private $consumerSecret;
    /**
     * Summary of securityCredential
     * @var 
     */
    private $securityCredential;
    /**
     * Summary of initiatorPass
     * @var 
     */
    private $initiatorPass;
    /**
     * Summary of requester
     * @var 
     */
    private $requester;
    /**
     * Summary of baseUrl
     * @var 
     */
    private $baseUrl;
    /**
     * Summary of passKey
     * @var 
     */
    private $passKey;

    /**
     * Mpesa class constructor.
     *
     * @param array $config Configuration array:
     *   - env: string ('sandbox' or 'production')
     *   - credentials: array {
     *       passKey: string, Paybill passkey
     *       initiatorPass: string, Inititator username
     *       initiatorName: string Initiator password
     *     }
     *   - appInfo: array {
     *       consumerKey: string, Your app consumer key
     *       consumerSecret: string You app secret key
     *     }
     *   - businessShortCode: string Your paybill no or till
     *   - shortCodeType: string ('paybill' or 'till')
     * @throws InvalidArgumentException if required parameters are missing.
     */
    public function __construct(array $config)
    {
        $this->validateConfig($config);

        $this->environment = $config['env'];
        $this->requester = $config['requester'];
        $this->timeStamp = date('YmdHis');
        $this->shortCodeType = $config['shortCodeType'];
        $this->passKey = $config['credentials']['passKey'];
        $this->businessShortCode = $config['businessShortCode'];
        $this->baseUrl = ($config['env'] === 'production') ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';
        $this->consumerSecret = $config['appInfo']['consumerSecret'];
        $this->initiatorName = $config['credentials']['initiatorName'];
        $this->initiatorPass = $config['credentials']['initiatorPass'];
        $this->consumerKey = $config['appInfo']['consumerKey'];
        $this->shortCodePassword = $this->generatePassword();
        $this->accessToken = $this->generateAccessToken();
        $this->securityCredential = $this->generateSecurityCredential();
    }

    public function getBusinessName(){
       return self::queryOrgInfo($this->shortCodeType, $this->businessShortCode)['OrganizationName'];
    }

    /**
     * Generating Security Credential
     * @return string
     */
    private function generateSecurityCredential()
    {
        $certificate = ($this->environment === 'production') ? "/Certificates/ProductionCertificate.cer" : "Certificates/SandboxCertificate.cer";
        $publicKey = file_get_contents(__DIR__ . (string) $certificate);
        openssl_public_encrypt($this->initiatorPass, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);
        return base64_encode($encrypted);
    }

    /**
     * Generate paybill password
     * @return string
     */
    private function generatePassword()
    {
        return base64_encode("$this->businessShortCode$this->passKey$this->timeStamp");
    }

    /**
     * Validates the configuration array.
     *
     * @param array $config
     * @throws InvalidArgumentException
     */
    private function validateConfig(array $config): void
    {
        $requiredKeys = ['env', 'credentials', 'appInfo', 'businessShortCode', 'shortCodeType', 'requester'];
        foreach ($requiredKeys as $key) {
            if (!isset($config[$key])) {
                throw new InvalidArgumentException("Missing required configuration parameter: $key");
            }
        }

        $requiredCredentials = ['passKey', 'initiatorPass', 'initiatorName'];
        foreach ($requiredCredentials as $key) {
            if (!isset($config['credentials'][$key])) {
                throw new InvalidArgumentException("Missing required credentials parameter: $key");
            }
        }

        $requiredAppInfo = ['consumerKey', 'consumerSecret'];
        foreach ($requiredAppInfo as $key) {
            if (!isset($config['appInfo'][$key])) {
                throw new InvalidArgumentException("Missing required appInfo parameter: $key");
            }
        }
    }

    /**
     * Generates an access token using consumerKey and consumerSecret.
     *
     * @return string
     * @throws Exception
     */
    private function generateAccessToken(): string
    {
        $url = "{$this->baseUrl}/oauth/v1/generate?grant_type=client_credentials";
        $auth = base64_encode("{$this->consumerKey}:{$this->consumerSecret}");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Basic $auth",
            "Content-Type: application/json",
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if (isset($result['access_token'])) {
            return $result['access_token'];
        }

        $errorMessage = $result['errorMessage'] ?? 'Unknown error occurred';
        throw new Exception("Failed to generate access token: $errorMessage. Response: $response");
    }

    /**
     * Magic getter for properties.
     *
     * @param string $property
     * @return mixed
     * @throws Exception
     */
    public function __get(string $property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }

        throw new Exception("Property $property does not exist.");
    }

    /**
     * Magic setter for properties.
     *
     * @param string $property
     * @param mixed $value
     * @return void
     * @throws Exception
     */
    public function __set(string $property, $value): void
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new Exception("Property $property does not exist.");
        }
    }

    /**
     * Format phone number
     * @param mixed $phoneNumber
     * @return mixed
     */
    private function formatPhoneNumber($phoneNumber)
    {
        if (isset($phoneNumber)) {
            $numberLength = strlen($phoneNumber);
            $phoneNumber = match ($numberLength) {
                9 => "254$phoneNumber",
                10 => '254' . substr($phoneNumber, 1),
                default => (substr($phoneNumber, 0, 4) === '254') ? substr($phoneNumber, 1) : $phoneNumber,
            };
            return $phoneNumber;
        } else {
            return null;
        }
    }

    /**
     * Send api request to daraja api
     * @param mixed $endPoint
     * @param mixed $data
     * @throws \Exception
     * @return array
     */
    public function sendRequest($endPoint, $data)
    {

        $stkPushHeader = [
            "Content-Type:application/json",
            "Authorization:Bearer $this->accessToken"
        ];

        $curl = curl_init();
        $dataString = json_encode($data);
        curl_setopt($curl, CURLOPT_URL, "$this->baseUrl/$endPoint");
        curl_setopt($curl, CURLOPT_HTTPHEADER, $stkPushHeader); //setting custom header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $dataString);
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if (!$response) {
            throw new Exception("No response was received: " . curl_error($curl));
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid Json Received");
        }
        return [
            'response' => json_decode($response, true),
            'httpCode' => $httpCode
        ];
    }

    /**
     * Initiate STK Push
     * @param mixed $amount Payment amount
     * @param mixed $phoneNumber Phone number to send STK
     * @param mixed $accountNumber  Account number
     * @param mixed $callBackUrl URL to receive webhook
     * @param mixed $description
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @return mixed
     */
    public function stkPush($amount, $phoneNumber, $accountNumber, $callBackUrl, $description = "STK Push Request")
    {
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        if (!isset($phoneNumber) || empty($phoneNumber)) {
            throw new InvalidArgumentException("Phone number is required");
        }

        if (!isset($amount) || empty($amount)) {
            throw new InvalidArgumentException("Amount is required");
        }

        if (!isset($accountNumber) || empty($accountNumber)) {
            throw new InvalidArgumentException("Account Number is required");
        }

        if (!isset($callBackUrl) || empty($callBackUrl)) {
            throw new InvalidArgumentException("Callback url is required");
        }

        $sendRequest = self::sendRequest("mpesa/stkpush/v1/processrequest", [
            'Amount' => $amount,
            'PartyA' => $phoneNumber,
            'CallBackURL' => $callBackUrl,
            'Timestamp' => $this->timeStamp,
            'TransactionDesc' => $description,
            'PhoneNumber' => $phoneNumber,
            'PartyB' => $this->businessShortCode,
            'AccountReference' => $accountNumber,
            'TransactionType' => 'CustomerPayBillOnline',
            'BusinessShortCode' => $this->businessShortCode,
            'Password' => $this->shortCodePassword,
        ]);

        if (!$sendRequest['response']) {
            throw new Exception("No response received");
        }

        if ($sendRequest['httpCode'] !== 200) {
            throw new Exception("Request failed with status {$sendRequest['httpCode']}");
        }

        return $sendRequest['response'];
    }

    /**
     * Get paybill or till info
     * @param mixed $type  ('till' or 'paybill')
     * @param mixed $shortCode Short code to get info
     * @throws \Exception
     * @return array
     */
    public function queryOrgInfo($type, $shortCode)
    {
        switch ($type) {
            case 'till':
                $identifierType = 2;
                break;

            case 'paybill':
                $identifierType = 4;
                break;

            default:
                throw new Exception("Identifier type is not supported");
                break;
        }

        $sendRequest = self::sendRequest("sfcverify/v1/query/info", [
            'IdentifierType' => $identifierType,
            'Identifier' => $shortCode
        ]);

        if (!$sendRequest['response']) {
            throw new Exception("No response received");
        }
        
        if ($sendRequest['httpCode'] !== 200) {
            throw new Exception("Request failed with status {$sendRequest['httpCode']}");
        }

        return $sendRequest['response'];
    }

    /**
     * Generate Payment QR Code
     * @param int $amount Payment amount
     * @param mixed $accountNumber Account Number to pay
     * @param int $size QR Code size
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @return array
     */
    public function generateQRCode($amount, $accountNumber, $size = 300)
    {

        if (!isset($amount) || empty($amount)) {
            throw new InvalidArgumentException("Amount is required");
        }

        $sendRequest = self::sendRequest("mpesa/qrcode/v1/generate", [
            'MerchantName' => $this->getBusinessName(),
            'RefNo' => $accountNumber,
            'Amount' => $amount,
            'TrxCode' => $this->shortCodeType === "paybill" ? "PB" : "BG",
            'CPI' => $this->businessShortCode,
            'Size' => $size
        ]);

        if (!$sendRequest['response']) {
            throw new Exception("No response received");
        }

        if ($sendRequest['httpCode'] !== 200) {
            throw new Exception("Request failed with status {$sendRequest['httpCode']}");
        }

        return $sendRequest['response'];
    }
    /**
     * Query STK Push
     * @param mixed $checkoutRequestCode Checkout request code
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @return mixed
     */
    public function stkPushQuery($checkoutRequestCode)
    {
        if (!isset($checkoutRequestCode) || empty($checkoutRequestCode)) {
            throw new InvalidArgumentException("Checkout request code is required");
        }

        $sendRequest = self::sendRequest("mpesa/stkpushquery/v1/query", [
            'BusinessShortCode' => $this->businessShortCode,
            'Password' => $this->shortCodePassword,
            'Timestamp' => $this->timeStamp,
            'CheckoutRequestID' => $checkoutRequestCode
        ]);

        if (!$sendRequest['response']) {
            throw new Exception("No response received");
        }

        if ($sendRequest['httpCode'] !== 200) {
            throw new Exception("Request failed with status {$sendRequest['httpCode']}");
        }

        return $sendRequest['response'];
    }

    /**
     * Register urls for C2B
     * @param mixed $responseType  ('Cancelled' or 'Completed')
     * @param mixed $confirmationUrl This is the URL that receives the confirmation request from API upon payment completion.
     * @param mixed $validationUrl This is the URL that receives the validation request from the API upon payment submission.
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @return mixed
     */
    public function registerUrl($responseType, $confirmationUrl, $validationUrl)
    {

        if (!isset($confirmationUrl) || empty($confirmationUrl)) {
            throw new InvalidArgumentException("Confirmation url is required");
        }

        if (!isset($validationUrl) || empty($validationUrl)) {
            throw new InvalidArgumentException("Validation url is required");
        }

        $sendRequest = self::sendRequest("mpesa/c2b/v2/registerurl", [
            'ShortCode' => $this->businessShortCode,
            'ResponseType' => $responseType,
            'ConfirmationURL' => $confirmationUrl,
            'ValidationURL' => $validationUrl
        ]);

        if (!$sendRequest['response']) {
            throw new Exception("No response received");
        }

        if ($sendRequest['httpCode'] !== 200) {
            throw new Exception("Request failed with status {$sendRequest['httpCode']}");
        }

        return $sendRequest['response'];
    }

    /**
     * Initiate payment from paybill to phone number
     * @param mixed $amount AMount to send
     * @param mixed $phoneNumber Payment to be sent to
     * @param mixed $commandID Payment Type: SalaryPayment,BusinessPayment,PromotionPayment
     * @param mixed $resultUrl Url to receive webhook
     * @param mixed $queueTimeoutUrl QueueTimeOutURL
     * @param mixed $remarks Payment remarks
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @return mixed
     */
    public function initiateB2C($amount, $phoneNumber, $commandID, $resultUrl, $queueTimeoutUrl = null, $remarks = "Business Payment")
    {

        if (!isset($amount) || empty($amount)) {
            throw new InvalidArgumentException("Amount is required");
        }

        if (!isset($phoneNumber) || empty($phoneNumber)) {
            throw new InvalidArgumentException("Phone number is required");
        }

        if (!isset($resultUrl) || empty($resultUrl)) {
            throw new InvalidArgumentException("Result URL is required");
        }

        if (!in_array($commandID, ['SalaryPayment', 'BusinessPayment', 'PromotionPayment'])) {
            throw new InvalidArgumentException("Invalid command ID. Must be one of: SalaryPayment, BusinessPayment, PromotionPayment");
        }

        $sendRequest = self::sendRequest("mpesa/b2c/v1/paymentrequest", [
            'InitiatorName' => $this->initiatorName,
            'SecurityCredential' => $this->securityCredential,
            'CommandID' => $commandID,
            'Amount' => $amount,
            'PartyA' => $this->businessShortCode,
            'PartyB' => $this->formatPhoneNumber($phoneNumber),
            'Remarks' => $remarks,
            'QueueTimeOutURL' => $queueTimeoutUrl ?? $resultUrl,
            'ResultURL' => $resultUrl,
            'Occasion' => ''
        ]);

        if (!$sendRequest['response']) {
            throw new Exception("No response received");
        }

        if ($sendRequest['httpCode'] !== 200) {
            throw new Exception("Request failed with status {$sendRequest['httpCode']}");
        }

        return $sendRequest['response'];
    }

    /**
     * Fetch transaction status
     * @param mixed $transactionID Mpesa REF ID to check status
     * @param mixed $resultUrl URL to receive webhook
     * @param mixed $queueTimeoutUrl queueTimeoutUrl
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @return mixed
     */
    public function transactionStatus($transactionID, $resultUrl, $queueTimeoutUrl = null)
    {

        if (!isset($transactionID) || empty($transactionID)) {
            throw new InvalidArgumentException("Transaction ID is required");
        }

        if (!isset($resultUrl) || empty($resultUrl)) {
            throw new InvalidArgumentException("Result Url is required");
        }


        $sendRequest = self::sendRequest("mpesa/transactionstatus/v1/query", [
            'Initiator' => $this->initiatorName,
            'SecurityCredential' => $this->securityCredential,
            'CommandID' => 'TransactionStatusQuery',
            'TransactionID' => $transactionID,
            'PartyA' => $this->businessShortCode,
            'IdentifierType' => $this->shortCodeType === 'paybill' ? '4' : '2',
            'ResultURL' => $resultUrl,
            'QueueTimeOutURL' => $queueTimeoutUrl ?? $resultUrl,
            'Remarks' => 'Transaction Status Query',
            'Occasion' => ''
        ]);

        if (!$sendRequest['response']) {
            throw new Exception("No response received");
        }

        if ($sendRequest['httpCode'] !== 200) {
            throw new Exception("Request failed with status {$sendRequest['httpCode']}");
        }

        return $sendRequest['response'];

    }

    /**
     * Check account balance
     * @param mixed $resultUrl URL to receive webhook
     * @param mixed $queueTimeoutUrl queueTimeoutUrl
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @return mixed
     */
    public function accountBalance($resultUrl, $queueTimeoutUrl = null)
    {

        if (!isset($resultUrl) || empty($resultUrl)) {
            throw new InvalidArgumentException("Result Url is required");
        }


        $sendRequest = self::sendRequest("mpesa/accountbalance/v1/query", [
            'Initiator' => $this->initiatorName,
            'SecurityCredential' => $this->securityCredential,
            'CommandID' => 'AccountBalance',
            'PartyA' => $this->businessShortCode,
            'IdentifierType' => $this->shortCodeType === 'paybill' ? '4' : '2',
            'ResultURL' => $resultUrl,
            'QueueTimeOutURL' => $queueTimeoutUrl ?? $resultUrl,
            'Remarks' => 'Account Balance Query',
            'Occasion' => ''
        ]);

        if (!$sendRequest['response']) {
            throw new Exception("No response received");
        }

        if ($sendRequest['httpCode'] !== 200) {
            throw new Exception("Request failed with status {$sendRequest['httpCode']}");
        }

        return $sendRequest['response'];

    }
    /**
     *  Reverse a transaction payted to short code
     * @param mixed $amount Amount of the transaction
     * @param mixed $transactionID Mpesa ref no for the transaction
     * @param mixed $resultUrl URL to receive webhook
     * @param mixed $queueTimeoutUrl queueTimeoutUrl
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @return mixed
     */
    public function reverseTransaction($amount, $transactionID, $resultUrl, $queueTimeoutUrl = null)
    {

        if (!isset($transactionID) || empty($transactionID)) {
            throw new InvalidArgumentException("Transaction ID is required");
        }

        if (!isset($amount) || empty($amount)) {
            throw new InvalidArgumentException("Amount is required");
        }

        if (!isset($resultUrl) || empty($resultUrl)) {
            throw new InvalidArgumentException("Result Url is required");
        }


        $sendRequest = self::sendRequest("mpesa/reversal/v1/request", [
            'Initiator' => $this->initiatorName,
            'SecurityCredential' => $this->securityCredential,
            'CommandID' => 'TransactionReversal',
            'TransactionID' => $transactionID,
            'Amount' => $amount,
            'ReceiverParty' => $this->businessShortCode,
            'RecieverIdentifierType' => "11",
            'ResultURL' => $resultUrl,
            'QueueTimeOutURL' => $queueTimeoutUrl ?? $resultUrl,
            'Remarks' => 'Transaction Reversal',
            'Occasion' => ''
        ]);

        if (!$sendRequest['response']) {
            throw new Exception("No response received");
        }

        if ($sendRequest['httpCode'] !== 200) {
            throw new Exception("Request failed with status {$sendRequest['httpCode']}");
        }

        return $sendRequest['response'];
    }

    /**
     * Remit tax to Kenya Revenue Authority (KRA)
     * @param mixed $amount Amount to pay
     * @param mixed $paymentRegistrationNo The payment registration number (PRN) issued by KRA.
     * @param mixed $resultUrl URL to receive webhook
     * @param mixed $queueTimeoutUrl queueTimeoutUrl
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @return mixed
     */
    public function taxRemittance($amount, $paymentRegistrationNo, $resultUrl, $queueTimeoutUrl = null)
    {

        if (!isset($paymentRegistrationNo) || empty($paymentRegistrationNo)) {
            throw new InvalidArgumentException("Payment Registration No is required");
        }

        if (!isset($amount) || empty($amount)) {
            throw new InvalidArgumentException("Amount is required");
        }

        if (!isset($resultUrl) || empty($resultUrl)) {
            throw new InvalidArgumentException("Result Url is required");
        }


        $sendRequest = self::sendRequest("mpesa/b2b/v1/remittax", [
            'Initiator' => $this->initiatorName,
            'SecurityCredential' => $this->securityCredential,
            'CommandID' => 'PayTaxToKRA',
            'SenderIdentifierType' => '4',
            'RecieverIdentifierType' => '4',
            'Amount' => $amount,
            'PartyA' => $this->businessShortCode,
            'PartyB' => '572572',
            'AccountReference' => $paymentRegistrationNo,
            'Remarks' => 'Tax Remittance',
            'QueueTimeOutURL' => $queueTimeoutUrl ?? $resultUrl,
            'ResultURL' => $resultUrl
        ]);

        if (!$sendRequest['response']) {
            throw new Exception("No response received");
        }

        if ($sendRequest['httpCode'] !== 200) {
            throw new Exception("Request failed with status {$sendRequest['httpCode']}");
        }

        return $sendRequest['response'];
    }

    /**
     * Initiate payment to business
     * @param mixed $amount Amount to pay
     * @param mixed $paymentType ('PaybillToPaybill', 'PaybillToTill', 'B2BAccountTopUp')
     * @param mixed $shortCode Short code to receive payment
     * @param mixed $accountNumber Account number
     * @param mixed $resultUrl URL to receive webhook
     * @param mixed $queueTimeoutUrl queueTimeoutUrl
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @return mixed
     */
    public function initiateB2B($amount, $paymentType, $shortCode, $accountNumber, $resultUrl, $queueTimeoutUrl = null)
    {

        if (!isset($shortCode) || empty($shortCode)) {
            throw new InvalidArgumentException("Short code is required");
        }

        if (!isset($amount) || empty($amount)) {
            throw new InvalidArgumentException("Amount is required");
        }

        if (!isset($shortCodeType) || empty($shortCodeType) || !in_array($shortCodeType, ['paybill', 'till'])) {
            throw new InvalidArgumentException("Shode code is invalid. Must be either 'paybill' or 'till'");
        }

        if (!isset($paymentType) || empty($paymentType) || !in_array($paymentType, ['PaybillToPaybill', 'PaybillToTill', 'B2BAccountTopUp'])) {
            throw new InvalidArgumentException("Payment type is invalid. Must be one of: PaybillToPaybill, PaybillToTill, B2BAccountTopUp");
        }

        $commandID = match ($paymentType) {
            'PaybillToPaybill' => "BusinessPayBill",
            'PaybillToTill' => "BusinessBuyGoods",
            'B2BAccountTopUp' => "BusinessPayToBulk",
        };

        if (!isset($resultUrl) || empty($resultUrl)) {
            throw new InvalidArgumentException("Result Url is required");
        }


        $sendRequest = self::sendRequest("mpesa/b2b/v1/paymentrequest", [
            'Initiator' => $this->initiatorName,
            'SecurityCredential' => $this->securityCredential,
            'CommandID' => $commandID,
            'SenderIdentifierType' => '4',
            'RecieverIdentifierType' => '4',
            'Amount' => $amount,
            'PartyA' => $this->businessShortCode,
            'PartyB' => $shortCode,
            'AccountReference' => $accountNumber,
            'Requester' => $this->requester,
            'Remarks' => 'OK',
            'QueueTimeOutURL' => $queueTimeoutUrl ?? $resultUrl,
            'ResultURL' => $resultUrl
        ]);

        if (!$sendRequest['response']) {
            throw new Exception("No response received");
        }

        if ($sendRequest['httpCode'] !== 200) {
            throw new Exception("Request failed with status {$sendRequest['httpCode']}");
        }

        return $sendRequest['response'];
    }
    /**
     * Summary of billManager
     * @return void
     */
    public function billManager()
    {

    }

    /**
     * Initiate USSD Push to nominated phone number to authorize payment to btb.
     * @param mixed $amount Payment amount
     * @param mixed $receiverShortCode Short code to receive payment
     * @param mixed $callBackUrl URL to receive webhook
     * @param mixed $partnerName Partner name eg Vendor Name
     * @param mixed $paymentRef Payment Ref if not supplied it will be autogenerated an be binded to the response
     * @param mixed $requestRef Request Ref if not supplied it will be autogenerated an be binded to the response
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @return mixed
     */
    public function initiateB2BExpressCheckout($amount, $receiverShortCode, $callBackUrl, $partnerName, $paymentRef = null, $requestRef = null)
    {

        if (!isset($amount) || empty($amount)) {
            throw new InvalidArgumentException("Amount is required");
        }

        if (!isset($receiverShortCode) || empty($receiverShortCode)) {
            throw new InvalidArgumentException("Short code is required");
        }

        if (!isset($partnerName) || empty($partnerName)) {
            throw new InvalidArgumentException("Partner Name is required");
        }

        if (!isset($callBackUrl) || empty($callBackUrl)) {
            throw new InvalidArgumentException("Callback Url is required");
        }

        $requestRefID = str_replace(".", "", uniqid('B2B_', true));
        $paymentRefID = str_replace(".", "", uniqid('PAYREFID_', true));

        $sendRequest = self::sendRequest("v1/ussdpush/get-msisdn", [
            'PrimaryPartyCode' => $this->businessShortCode,
            'ReceiverPartyCode' => $receiverShortCode,
            'Amount' => $amount,
            'CallBackUrl' => $callBackUrl,
            'RequestRefID' => $requestRef ?? $requestRefID,
            'PaymentRef' => $paymentRef ?? $paymentRefID,
            'PartnerName' => $partnerName
        ]);

        if (!$sendRequest['response']) {
            throw new Exception("No response received");
        }

        if ($sendRequest['httpCode'] !== 200) {
            throw new Exception($sendRequest['response']['errorMessage'] ?? "Request failed with status {$sendRequest['httpCode']}");
        }

        return $sendRequest['response'];
    }

    /**
     * Create M-Pesa standing order
     * @param mixed $amount Payment amount
     * @param mixed $phoneNumber Customer phone number
     * @param mixed $accountReference Account reference number
     * @param mixed $startDate Start date of the payment
     * @param mixed $endDate End date of the payment
     * @param mixed $standingOrderName Standing order name and must be unique
     * @param mixed $callBackUrl URL to receive webhook
     * @param mixed $frequency Payment Frequency  (1 - One Off, 2 - Daily, 3 - Weekly, 4 - Monthly, 5 - Bi-Monthly, 6 - Quarterly, 7 - Half Year, 8 - Yearly)
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @return mixed
     */
    public function mpesaRatiba($amount, $phoneNumber, $accountReference, $startDate, $endDate, $standingOrderName, $callBackUrl, $frequency = "2")
    {

        if (!isset($amount) || empty($amount)) {
            throw new InvalidArgumentException("Amount is required");
        }

        if (!isset($phoneNumber) || empty($phoneNumber)) {
            throw new InvalidArgumentException("Phone number is required");
        }

        if (!isset($accountReference) || empty($accountReference)) {
            throw new InvalidArgumentException("Account reference is required");
        }

        if (!isset($callBackUrl) || empty($callBackUrl)) {
            throw new InvalidArgumentException("Callback Url is required");
        }

        if (!isset($startDate) || empty($startDate)) {
            throw new InvalidArgumentException("Start Date is required");
        }

        if (!isset($endDate) || empty($endDate)) {
            throw new InvalidArgumentException("End Date is required");
        }

        if (!isset($standingOrderName) || empty($standingOrderName)) {
            throw new InvalidArgumentException("Standing Order Name is required");
        }

        $requestRefID = str_replace(".", "", uniqid('B2B_', true));
        $paymentRefID = str_replace(".", "", uniqid('PAYREFID_', true));

        $sendRequest = self::sendRequest("mpesa/standingorders/v1/create", [
            'StandingOrderName' => $standingOrderName,
            'StartDate' => $startDate,
            'EndDate' => $endDate,
            'BusinessShortCode' => $this->businessShortCode,
            'TransactionType' => $this->shortCodeType === "paybill" ? "Standing Order Customer Pay Bill" : "Standing Order Customer Pay Marchant",
            'ReceiverPartyIdentifierType' => $this->shortCodeType === "paybill" ? "4" : "2",
            'Amount' => $amount,
            'PartyA' => $this->formatPhoneNumber($phoneNumber),
            'CallBackURL' => $callBackUrl,
            'AccountReference' => $accountReference,
            'TransactionDesc' => $transactionDesc ?? "Payment to $this->businessShortCode",
            'Frequency' => $frequency,
            'Password' => $this->shortCodePassword,
            'Timestamp' => $this->timeStamp
        ]);

        if (!$sendRequest['response']) {
            throw new Exception("No response received");
        }

        if ($sendRequest['httpCode'] !== 200) {
            throw new Exception($sendRequest['response']['errorMessage'] ?? "Request failed with status {$sendRequest['httpCode']}");
        }

        return $sendRequest['response'];
    }


}
