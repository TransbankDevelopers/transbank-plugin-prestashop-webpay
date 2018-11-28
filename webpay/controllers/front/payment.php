<?php
require_once(dirname(__FILE__).'../../../../../config/config.inc.php');
if (!defined('_PS_VERSION_')) exit;

require_once(_PS_MODULE_DIR_.'webpay/libwebpay/TransbankSdkWebpay.php');
require_once(_PS_MODULE_DIR_.'webpay/libwebpay/LogHandler.php');
require_once(_PS_MODULE_DIR_.'webpay/libwebpay/Utils.php');

class WebPayPaymentModuleFrontController extends ModuleFrontController {

    public function initContent() {

        $this->ssl = true;
        $this->display_column_left = false;
        parent::initContent();

        $cart = $this->context->cart;

        $log = new LogHandler();

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

        $amount = $cart->getOrderTotal(true, Cart::BOTH);
        $sessionId = uniqid();
        $buyOrder = $cart->id;
        $returnUrl = $config['URL_RETURN'];
        $finalUrl = $config['URL_FINAL'];

        $transbankSdkWebpay = new TransbankSdkWebpay($config);
        $result = $transbankSdkWebpay->initTransaction($amount, $sessionId, $buyOrder, $returnUrl, $finalUrl);

        if (isset($result["token_ws"])) {

            $date_tx_hora = date('H:i:s');
            $date_tx_fecha = date('d-m-Y');

            Context::getContext()->cookie->__set('PAYMENT_OK', 'WAITING');
            Context::getContext()->cookie->__set('WEBPAY_RESULT_CODE', "");
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TXRESPTEXTO', "");
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TOTALPAGO', $amount);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_ACCDATE', "");
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_ORDENCOMPRA', $buyOrder);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TXDATE_HORA', $date_tx_hora);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TXDATE_FECHA', $date_tx_fecha);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_NROTARJETA', "");
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_AUTCODE', "");
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TIPOPAGO', "");
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TIPOCUOTAS', "");
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_RESPCODE', "");
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_NROCUOTAS', "");

            Context::getContext()->smarty->assign(array(
                'url' => isset($result["url"]) ? $result["url"] : '',
                'token_ws' => isset($result["token_ws"]) ? $result["token_ws"] : '',
                'amount' => $amount
            ));

            if (Utils::isPrestashop_1_6()) {
                $this->setTemplate('payment_execution_1.6.tpl');
            } else {
                $this->setTemplate('module:webpay/views/templates/front/payment_execution.tpl');
            }

        } else {

            Context::getContext()->cookie->__set('PAYMENT_OK', 'FAIL');
            Context::getContext()->cookie->__set('WEBPAY_RESULT_CODE', 500);

            Context::getContext()->smarty->assign(array(
                'error' => isset($result["error"]) ? $result["error"] : '',
                'detail' => isset($result["detail"]) ? $result["detail"] : ''
            ));

            if (Utils::isPrestashop_1_6()) {
                $this->setTemplate('payment_error_1.6.tpl');
            } else {
                $this->setTemplate('module:webpay/views/templates/front/payment_error.tpl');
            }
        }
    }
}
