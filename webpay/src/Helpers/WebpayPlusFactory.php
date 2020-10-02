<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use Configuration;
use TransbankSdkWebpay;

class WebpayPlusFactory
{
    public static function create()
    {
        $config = [
            "MODO" => Configuration::get('WEBPAY_AMBIENT'),
            "PRIVATE_KEY" => Configuration::get('WEBPAY_SECRETCODE'),
            "PUBLIC_CERT" => Configuration::get('WEBPAY_CERTIFICATE'),
            "WEBPAY_CERT" => Configuration::get('WEBPAY_CERTIFICATETRANSBANK'),
            "COMMERCE_CODE" => Configuration::get('WEBPAY_STOREID')
        ];
        return new TransbankSdkWebpay($config);
    }
}
