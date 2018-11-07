<?php
require_once(_PS_MODULE_DIR_.'webpay/vendor/transbank/transbank-sdk/init.php');

use Transbank\Webpay\Configuration;
use Transbank\Webpay\Webpay;

class TransbankSdkWebpay {

    var $transaction;

    function __construct($config) {
        $environment = isset($config["MODO"]) ? $config["MODO"] : 'INTEGRACION';
        $configuration = Configuration::forTestingWebpayPlusNormal();
        if ($environment != Webpay::INTEGRACION) {
            $configuration = new Configuration();
            $configuration->setEnvironment(Webpay::PRODUCCION);
            $configuration->setCommerceCode($config["COMMERCE_CODE"]);
            $configuration->setPrivateKey($config["PRIVATE_KEY"]);
            $configuration->setPublicCert($config["PUBLIC_CERT"]);
            $configuration->setWebpayCert(Webpay::defaultCert(Webpay::PRODUCCION));
        }
        $this->transaction = (new Webpay($configuration))->getNormalTransaction();
    }

	public function createTransaction($amount, $sessionId, $buyOrder, $returnUrl, $finalUrl) {
        $result = array();
		try{
            $initResult = $this->transaction->initTransaction($amount, $buyOrder, $sessionId, $returnUrl, $finalUrl);
            if (isset($initResult) && isset($initResult->url) && isset($initResult->token)) {
                $result = array(
					"url" => $initResult->url,
					"token_ws" => $initResult->token
				);
            } else {
                throw new Exception("No se ha creado la transacción");
            }
		} catch(Exception $e) {
            $result = array(
                "error" => 'Error conectando a Webpay',
                "detail" => $e->getMessage()
            );
		}
		return $result;
    }

    public function commitTransaction($tokenWs) {
        if ($tokenWs == null) {
            throw new Exception("El token webpay es requerido");
        }
        return $this->transaction->getTransactionResult($tokenWs);
    }
}
?>
