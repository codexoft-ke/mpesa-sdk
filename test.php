<?php

require 'vendor/autoload.php';

use codexoft\MpesaSdk\Mpesa;

$mpesa = new Mpesa([
    "env" => "production",
    "shortCodeType"=>"paybill",
    "requester"=>"254795375735",
    "businessShortCode" => "4139669",
    "credentials" => [
        "passKey" => "3fa72775ef18916e801df734211393237b945b4f58cffda50154e625eb868a6c",
        "initiatorName" => "CODEXOFTAPI",
        "initiatorPass" => "Codexoft@mpesa29.",
    ],
    "appInfo" => [
        "consumerKey" => "eGPmyGsQesi5UMOZ78Lq9OASbu9mlH8Tc6nXNdffeffgvCAR",
        "consumerSecret" => "iW8FsAV7tgDG5ZDsSEm0HFYg9Smsb6FWIBzrA6NWcah08jIwtKAj998uaDNeA7bx",
    ],
]);

$response = $mpesa->initiateB2B("100", "paybill","303030","TEST","https://tunnel.gospeladmin.co.ke/Personal/OpenSource/MpesaSDK/callback.php");

echo json_encode($response);
