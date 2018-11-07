<?php
require_once(dirname(__FILE__).'../../../../../config/config.inc.php');
if (!defined('_PS_VERSION_')) exit;

require_once(_PS_MODULE_DIR_.'webpay/libwebpay/TransbankSdkWebpay.php');
require_once(_PS_MODULE_DIR_.'webpay/libwebpay/LogHandler.php');

class WebPayValidateModuleFrontController extends ModuleFrontController {

    private $paymentTypeCodearray = array(
        "VD" => "Venta Debito",
        "VN" => "Venta Normal",
        "VC" => "Venta en cuotas",
        "SI" => "3 cuotas sin interés",
        "S2" => "2 cuotas sin interés",
        "NC" => "N cuotas sin interés",
    );

    public function initContent() {

        $this->display_column_left = true;
        $this->display_column_right = true;
        parent::initContent();

        $this->log = new LogHandler();

        if (Context::getContext()->cookie->PAYMENT_OK == "WAITING") {
            $this->processPayment($_POST);
        } else {
            $this->processRedirect($_POST);
        }
    }

    private function validateData($cart) {

        $authorized = false;

        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'webpay') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        if ($cart->id == null) {
            $this->log->logInfo('validateData - 1');
            $id_usuario = Context::getContext()->customer->id;
            $sql = "SELECT id_cart FROM ps_cart p WHERE p.id_customer = $id_usuario ORDER BY p.id_cart DESC";
            $id_carro = Db::getInstance()->getValue($sql, $use_cache = true);
            $cart->id = $id_carro;
            $customer = new Customer($cart->id_customer);
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
            return false;
        }

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            $this->log->logInfo('validateData - 2');
            Tools::redirect('index.php?controller=order&step=1');
            return false;
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            $this->log->logInfo('validateData - 3');
            Tools::redirect('index.php?controller=order&step=1');
            return false;
        }

        return true;
    }

	private function processPayment($data) {
        $this->log->logInfo('processPayment');

        $cart = Context::getContext()->cart;

        if (!$this->validateData($cart)) {
            return;
        }

        $token_ws = isset($data["token_ws"]) ? $data["token_ws"] : null;

        $config = array(
            "MODO" => Configuration::get('WEBPAY_AMBIENT'),
            "PRIVATE_KEY" => Configuration::get('WEBPAY_SECRETCODE'),
            "PUBLIC_CERT" => Configuration::get('WEBPAY_CERTIFICATE'),
            "WEBPAY_CERT" => Configuration::get('WEBPAY_CERTIFICATETRANSBANK'),
            "COMMERCE_CODE" => Configuration::get('WEBPAY_STOREID'),
            "URL_FINAL" => Configuration::get('WEBPAY_NOTIFYURL'),
            "URL_RETURN" => Configuration::get('WEBPAY_POSTBACKURL')
        );

        $result = array();

		try {
			$transbankSdkWebpay = new TransbankSdkWebpay($config);
            $result = $transbankSdkWebpay->commitTransaction($token_ws);
		} catch(Exception $e) {
            Context::getContext()->cookie->__set('PAYMENT_OK', 'FAIL');
            Context::getContext()->cookie->__set('WEBPAY_RESULT_CODE', 500);
            Context::getContext()->cookie->__set('WEBPAY_RESULT_DESC', $e->getMessage());
            Context::getContext()->cookie->__set('WEBPAY_TX_ANULADA', "SI");
        }

		if (isset($result->buyOrder)) {

            if ($result->detailOutput->responseCode == 0){
                $transactionResponse = "Transacción aprobada";
            } else {
                $transactionResponse = $result->detailOutput->responseDescription;
            }

            $date_tmp = strtotime($result->transactionDate);
            $date_tx_hora = date('H:i:s',$date_tmp);
            $date_tx_fecha = date('d-m-Y',$date_tmp);

            if($result->detailOutput->paymentTypeCode == "SI" || $result->detailOutput->paymentTypeCode == "S2" ||
                $result->detailOutput->paymentTypeCode == "NC" || $result->detailOutput->paymentTypeCode == "VC" ) {
                $tipo_cuotas = $this->paymentTypeCodearray[$result->detailOutput->paymentTypeCode];
            } else {
                $tipo_cuotas = "Sin cuotas";
            }

            Context::getContext()->cookie->__set('PAYMENT_OK', 'SUCCESS');
            Context::getContext()->cookie->__set('WEBPAY_RESULT_CODE', $result->detailOutput->responseCode);
            Context::getContext()->cookie->__set('WEBPAY_RESULT_DESC', $transactionResponse);
            Context::getContext()->cookie->__set('WEBPAY_TX_ANULADA', "NO");
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TXRESPTEXTO', $transactionResponse);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TOTALPAGO', $result->detailOutput->amount);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_ACCDATE', $result->accountingDate);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_ORDENCOMPRA', $result->buyOrder);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TXDATE_HORA', $date_tx_hora);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TXDATE_FECHA', $date_tx_fecha);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_NROTARJETA', $result->cardDetail->cardNumber);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_AUTCODE', $result->detailOutput->authorizationCode);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TIPOPAGO', $this->paymentTypeCodearray[$result->detailOutput->paymentTypeCode]);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TIPOCUOTAS', $tipo_cuotas);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_RESPCODE', $result->detailOutput->responseCode);
            Context::getContext()->cookie->__set('WEBPAY_VOUCHER_NROCUOTAS', $result->detailOutput->sharesNumber);

            $this->toRedirect($result->urlRedirection, array("token_ws" => $token_ws));
        } else {
            $this->processRedirect($data);
        }
    }

    private function processRedirect($data) {

        $cart = Context::getContext()->cart;

        if (!$this->validateData($cart)) {
            return;
        }

        $customer = new Customer($cart->id_customer);
        $currency = Context::getContext()->currency;
        $amount = (float)$cart->getOrderTotal(true, Cart::BOTH);

        $orderStatus = null;

        if(Context::getContext()->cookie->WEBPAY_TX_ANULADA == "SI"){
            $orderStatus = Configuration::get('PS_OS_CANCELED');
        } elseif (Context::getContext()->cookie->WEBPAY_RESULT_CODE == 0){
            $orderStatus = Configuration::get('PS_OS_PREPARATION');
        } else {
            $orderStatus = Configuration::get('PS_OS_ERROR');
        }

        $this->module->validateOrder((int)$cart->id,
                                    $orderStatus,
                                    $amount,
                                    $this->module->displayName,
                                    NULL,
                                    NULL,
                                    (int)$currency->id,
                                    false,
                                    $customer->secure_key);

        $dataUrl = 'id_cart='.(int)$cart->id.
                    '&id_module='.(int)$this->module->id.
                    '&id_order='.$this->module->currentOrder.
                    '&key='.$customer->secure_key;

        Tools::redirect('index.php?controller=order-confirmation&' . $dataUrl);
    }

    public function toRedirect($url, $data = []) {
        echo  "<form action='" . $url . "' method='POST' name='webpayForm'>";
        foreach ($data as $name => $value) {
            echo "<input type='hidden' name='".htmlentities($name)."' value='".htmlentities($value)."'>";
        }
        echo  "</form>"
                ."<script language='JavaScript'>"
                ."document.webpayForm.submit();"
                ."</script>";
    }
}
