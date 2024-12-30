<?php

namespace codexoft\MpesaSdk;

use Exception;
use InvalidArgumentException;

class Mpesa
{
    private $environment;
    private $accessToken;
    private $timeStamp;
    private $businessShortCode;
    private $businessName;
    private $shortCodeType;
    private $shortCodePassword;
    private $initiatorName;
    private $consumerKey;
    private $consumerSecret;
    private $securityCredential;
    private $initiatorPass;
    private $requester;
    private $baseUrl;
    private $passKey;

    /**
     * Mpesa class constructor.
     *
     * @param array $config Configuration array:
     *   - env: string ('sandbox' or 'production')
     *   - credentials: array {
     *       passKey: string,
     *       initiatorPass: string,
     *       initiatorName: string
     *     }
     *   - appInfo: array {
     *       consumerKey: string,
     *       consumerSecret: string
     *     }
     *   - businessShortCode: string
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
        $this->businessName = $this->queryOrgInfo($config['shortCodeType'], $config['businessShortCode'])['OrganizationName'];
    }

    private function generateSecurityCredential()
    {
        $certificate = ($this->environment === 'production') ? "/Certificates/ProductionCertificate.cer" : "Certificates/SandboxCertificate.cer";
        $publicKey = file_get_contents(__DIR__ . (string) $certificate);
        openssl_public_encrypt($this->initiatorPass, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);
        return base64_encode($encrypted);
    }

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
        $requiredKeys = ['env', 'credentials', 'appInfo', 'businessShortCode', 'shortCodeType','requester'];
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
    private function outputResponse($status, $message, $data = [])
    {
        return ["status" => $status, "message" => $message, $data];
    }

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
            'response' => $response,
            'httpCode' => $httpCode
        ];
    }

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

        return json_decode($sendRequest['response'], true);
    }

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

        return json_decode($sendRequest['response'], true);
    }

    public function generateQRCode($amount, $accountNumber, $size = 300)
    {

        if (!isset($amount) || empty($amount)) {
            throw new InvalidArgumentException("Amount is required");
        }

        $sendRequest = self::sendRequest("mpesa/qrcode/v1/generate", [
            'MerchantName' => $this->businessName,
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

        return json_decode($sendRequest['response'], true);
    }
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

        return json_decode($sendRequest['response'], true);
    }

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

        return json_decode($sendRequest['response'], true);
    }

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

        return json_decode($sendRequest['response'], true);
    }

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

        return json_decode($sendRequest['response'], true);

    }

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

        return json_decode($sendRequest['response'], true);

    }
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

        return json_decode($sendRequest['response'], true);
    }

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

        return json_decode($sendRequest['response'], true);
    }

    public function initiateB2B($amount, $shortCodeType, $shortCode,$accountNumber, $resultUrl, $queueTimeoutUrl = null)
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


        if (!isset($resultUrl) || empty($resultUrl)) {
            throw new InvalidArgumentException("Result Url is required");
        }


        $sendRequest = self::sendRequest("mpesa/b2b/v1/paymentrequest", [
            'Initiator' => $this->initiatorName,
            'SecurityCredential' => $this->securityCredential,
            'CommandID' => $shortCodeType === "paybill" ? "BusinessPayBill" : "BusinessBuyGoods",
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

        return json_decode($sendRequest['response'], true);
    }

    //If user has not supplie request ref id or payment ref id it will be auto generated for him/her and also return as part of the response
    public function initiateB2BExpressCheckout($amount,$receiverShortCode,$callBackUrl,$partnerName,$paymentRef=null,$requestRef=null,$queueTimeoutUrl = null){

        if (!isset($amount) || empty($amount)) {
            throw new InvalidArgumentException("Amount is required");
        }

        if (!isset($receiverShortCode) || empty($receiverShortCode)) {
            throw new InvalidArgumentException("Short code is required");
        }

        if (!isset($partnerName) || empty($partnerName)) {
            throw new InvalidArgumentException("Partner Name is required");
        }

        if (!isset($paymentRef) || empty($paymentRef)) {
            throw new InvalidArgumentException("PaymentRef is required");
        }

        if (!isset($callBackUrl) || empty($callBackUrl)) {
            throw new InvalidArgumentException("Callback Url is required");
        }

        $requestRefID = str_replace(".","", uniqid('B2B_', true));
        $paymentRefID = str_replace(".","", uniqid('PAYREFID_', true));

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
            throw new Exception("Request failed with status {$sendRequest['httpCode']}");
        }

        return json_decode($sendRequest['response'], true);
    }

}
