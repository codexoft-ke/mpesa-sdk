<?php

require 'vendor/autoload.php';

use codexoft\MpesaSdk\Mpesa;

$mpesa = new Mpesa([
    "env" => "production",
    "businessShortCode" => "4139669",
    "credentials" => [
        "passKey" => "3fa72775ef18916e801df734211393237b945b4f58cffda50154e625eb868a6c",
        "initiatorPass" => "CODEXOFTAPI",
        "initiatorName" => "Codexoft@mpesa29.",
    ],
    "appInfo" => [
        "consumerKey" => "eGPmyGsQesi5UMOZ78Lq9OASbu9mlH8Tc6nXNdffeffgvCAR",
        "consumerSecret" => "iW8FsAV7tgDG5ZDsSEm0HFYg9Smsb6FWIBzrA6NWcah08jIwtKAj998uaDNeA7bx",
    ],
]);

echo $mpesa->accessToken;
