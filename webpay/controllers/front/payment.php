<?php
require_once(dirname(__FILE__).'../../../../../config/config.inc.php');
if (!defined('_PS_VERSION_')) exit;

require_once(_PS_MODULE_DIR_.'webpay/libwebpay/TransbankSdkWebpay.php');
require_once(_PS_MODULE_DIR_.'webpay/libwebpay/LogHandler.php');

class WebPayPaymentModuleFrontController extends ModuleFrontController {

    public function initContent() {

        $this->ssl = true;
        $this->display_column_left = false;
        parent::initContent();

        $cart = $this->context->cart;

        $this->log = new LogHandler();

        $order = new Order(Order::getOrderByCartId($cart->id));

        $config = array(
            "MODO" => Configuration::get('WEBPAY_AMBIENT'),
            "PRIVATE_KEY" => Configuration::get('WEBPAY_SECRETCODE'),
            "PUBLIC_CERT" => Configuration::get('WEBPAY_CERTIFICATE'),
            "WEBPAY_CERT" => Configuration::get('WEBPAY_CERTIFICATETRANSBANK'),
            "COMMERCE_CODE" => Configuration::get('WEBPAY_STOREID'),
            "URL_FINAL" => Configuration::get('WEBPAY_NOTIFYURL'),
            "URL_RETURN" => Configuration::get('WEBPAY_POSTBACKURL')
        );

        try {

            $amount = $cart->getOrderTotal(true, Cart::BOTH);
            $sessionId = uniqid();
            $buyOrder = $cart->id;
            $returnUrl = $config['URL_RETURN'];
            $finalUrl = $config['URL_FINAL'];

            $transbankSdkWebpay = new TransbankSdkWebpay($config);
            $result = $transbankSdkWebpay->initTransaction($amount, $sessionId, $buyOrder, $returnUrl, $finalUrl);

        } catch(Exception $e) {
            $result["error"] = "Error conectando a Webpay";
            $result["detail"] = $e->getMessage();
        }

        Context::getContext()->cookie->__set('PAYMENT_OK', 'WAITING');
        Context::getContext()->cookie->__set('WEBPAY_TX_ANULADA', "");
        Context::getContext()->cookie->__set('WEBPAY_RESULT_CODE', "");
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TXRESPTEXTO', "");
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TOTALPAGO', "");
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_ACCDATE', "");
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_ORDENCOMPRA', "");
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TXDATE_HORA', "");
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TXDATE_FECHA', "");
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_NROTARJETA', "");
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_AUTCODE', "");
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TIPOPAGO', "");
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TIPOCUOTAS', "");
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_RESPCODE', "");
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_NROCUOTAS', "");

        Context::getContext()->smarty->assign(array(
            'url' => isset($result["url"]) ? $result["url"] : '',
            'token_ws' => isset($result["token_ws"]) ? $result["token_ws"] : '',
            'amount' => $cart->getOrderTotal(true, Cart::BOTH)
        ));

        $this->setTemplate('module:webpay/views/templates/front/payment_execution.tpl');
    }
}
