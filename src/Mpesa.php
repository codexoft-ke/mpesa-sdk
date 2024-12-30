<?php

namespace codexoft\MpesaSdk;

use Exception;
use InvalidArgumentException;

class Mpesa
{
    private $environment;
    private $accessToken;
    private $businessShortCode;
    private $initiatorName;
    private $consumerKey;
    private $consumerSecret;
    private $initiatorPass;
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
        $this->baseUrl = ($config['env'] === 'production') ? 'api.safaricom.co.ke' : 'sandbox.safaricom.co.ke';
        $this->passKey = $config['credentials']['passKey'];
        $this->initiatorPass = $config['credentials']['initiatorPass'];
        $this->businessShortCode = $config['businessShortCode'];
        $this->initiatorName = $config['credentials']['initiatorName'];
        $this->consumerKey = $config['appInfo']['consumerKey'];
        $this->consumerSecret = $config['appInfo']['consumerSecret'];
        $this->accessToken = $this->generateAccessToken();
    }

    /**
     * Validates the configuration array.
     *
     * @param array $config
     * @throws InvalidArgumentException
     */
    private function validateConfig(array $config): void
    {
        $requiredKeys = ['env', 'credentials', 'appInfo', 'businessShortCode'];
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
        $url = "https://{$this->baseUrl}/oauth/v1/generate?grant_type=client_credentials";
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
     * Log API requests and responses for debugging.
     *
     * @param string $message
     * @param array $data
     */
    private function log(string $message, array $data = []): void
    {
        // Example logging implementation (optional)
        $logFile = __DIR__ . '/mpesa.log';
        $logMessage = '[' . date('Y-m-d H:i:s') . "] $message: " . json_encode($data) . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    // Placeholder for additional methods like STK push, B2C payments, etc.
}
