<?php

use PrestaShop\Module\WebpayPlus\Helpers\WebpayPlusFactory;
use PrestaShop\Module\WebpayPlus\Model\WebpayTransaction;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(_PS_MODULE_DIR_ . 'webpay/libwebpay/TransbankSdkWebpay.php');
require_once(_PS_MODULE_DIR_ . 'webpay/libwebpay/LogHandler.php');

/**
 * Class WebPayValidateModuleFrontController
 */
class WebPayValidateModuleFrontController extends ModuleFrontController
{
    
    /**
     * @var LogHandler
     */
    protected $log;
    
    public $display_column_right = false;
    public $display_footer = false;
    public $display_column_left = false;
    public $ssl = true;
    
    protected $responseData = [];
    /**
     * @var string[]
     */
    private $paymentTypeCodearray = [
        "VD" => "Venta débito",
        "VN" => "Venta normal",
        "VC" => "Venta en cuotas",
        "SI" => "3 cuotas sin interés",
        "S2" => "2 cuotas sin interés",
        "NC" => "N cuotas sin interés",
    ];
    
    public function initContent()
    {
        parent::initContent();
    
        $this->stopIfComingFromErrorOnWebpayForm();
        $this->stopIfComingFromAnTimeoutErrorOnWebpay();
    
        $webpayTransaction = $this->getTransactionByToken();
        
        $cart = new Cart($webpayTransaction->cart_id);
        if (!$this->validateData($cart)) {
            $this->throwError('Can not validate order cart');
        }
        if ($webpayTransaction->status == WebpayTransaction::STATUS_APPROVED) {
            $dataUrl = 'id_cart=' . (int)$cart->id . '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key;
            Tools::redirect('index.php?controller=order-confirmation&' . $dataUrl);
        }elseif ($webpayTransaction->status == WebpayTransaction::STATUS_INITIALIZED) {
            $this->processPayment($webpayTransaction, $cart);
        } else {
            $this->showToErrorPage('Esta compra se encuentra en estado rechazado o cancelado y no se puede aceptar el pago' );
        }
    }
    
    /**
     * @param $data
     * @return |null
     */
    protected function getTokenWs($data)
    {
        $token_ws = isset($data["token_ws"]) ? $data["token_ws"] : null;
        
        if (!isset($token_ws)) {
            $this->throwError('RESPONSE: No se recibió el token');
        }
        
        return $token_ws;
    }
    
    private function validateData($cart)
    {
        
        if ($cart->id == null) {
            (new LogHandler())->logDebug('Cart id was null. Redirecto to confirmation page of the last order');
            $id_usuario = Context::getContext()->customer->id;
            $sql = "SELECT id_cart FROM " . _DB_PREFIX_ . "cart p WHERE p.id_customer = $id_usuario ORDER BY p.id_cart DESC";
            $id_carro = Db::getInstance()->getValue($sql, $use_cache = true);
            $cart->id = $id_carro;
            $customer = new Customer($cart->id_customer);
            Tools::redirect('index.php?controller=order-confirmation&id_cart=' . (int)$cart->id . '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
            
            return false;
        }
        
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            $this->throwError('Error: id_costumer or id_address_delivery or id_address_invoice or $this->module->active was true');
            return false;
        }
        
        $customer = new Customer($cart->id_customer);
        
        if (!Validate::isLoadedObject($customer)) {
            $this->throwError();
            
            return false;
        }
        
        return true;
    }
    
    /**
     * @param $webpayTransaction WebpayTransaction
     * @param $cart
     */
    private function processPayment($webpayTransaction, $cart)
    {
        $amount = $cart->getOrderTotal(true, Cart::BOTH);
        
        $transbankSdkWebpay = WebpayPlusFactory::create();
        $result = $transbankSdkWebpay->commitTransaction($webpayTransaction->token);
    
        $webpayTransaction->transbank_response = json_encode($result);
        $webpayTransaction->status = WebpayTransaction::STATUS_FAILED;
        $updateResult = $webpayTransaction->save();
        
        if (!$updateResult) {
            $this->throwError('No se pudo guardar en base de datos el resultado de la transacción: ' . \DB::getMsgError());
        }
        if (is_array($result) && isset($result['error'])) {
            $this->throwError('Error: ' . $result['detail']);
        }
        if (isset($result->buyOrder) && isset($result->detailOutput) && $result->detailOutput->responseCode == 0) {
            
            $customer = new Customer($cart->id_customer);
            $currency = Context::getContext()->currency;
            $OKStatus = Configuration::get('WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT');
            
            $this->module->validateOrder((int)$cart->id, $OKStatus, $amount, $this->module->displayName,
                'Pago exitoso', [], (int)$currency->id, false, $customer->secure_key);
            
            $order = new Order($this->module->currentOrder);
            $payment = $order->getOrderPaymentCollection();
            if (isset($payment[0])) {
                $payment[0]->transaction_id = $cart->id;
                $payment[0]->card_number = '**** **** **** ' . $result->cardDetail->cardNumber;
                $payment[0]->card_brand = '';
                $payment[0]->card_expiration = '';
                $payment[0]->card_holder = '';
                $payment[0]->save();
            }
            
            \Db::getInstance()->update(WebpayTransaction::TABLE_NAME, [
                'response_code' => $result->detailOutput->responseCode,
                'order_id' => $order->id,
                'vci' => $result->VCI,
                'status' => WebpayTransaction::STATUS_APPROVED
            ], 'id = ' . pSQL($webpayTransaction->id));
            
            $this->toRedirect($result->urlRedirection, ["token_ws" => $webpayTransaction->token]);
            
        } else {
    
             \Db::getInstance()->update(WebpayTransaction::TABLE_NAME, [
                'response_code' => isset($result->detailOutput->responseCode) ? $result->detailOutput->responseCode : null,
            ], 'id = ' . pSQL($webpayTransaction->id));
            
            $this->responseData['PAYMENT_OK'] = 'FAIL';
            
            
            if (isset($result->detailOutput->responseDescription)) {
                $this->showToErrorPage($result->detailOutput->responseDescription);
                
            } else {
                $error = isset($result["error"]) ? $result["error"] : 'Error en el pago';
                $detail = isset($result["detail"]) ? $result["detail"] : 'Indefinido';
                $this->showToErrorPage($error . ', ' . $detail);
            }
        }
    }
    
    private function showToErrorPage($description = '', $resultCode = null)
    {
    
    
        $WEBPAY_RESULT_DESC = $description;
        $WEBPAY_RESULT_CODE = $resultCode;
        $WEBPAY_VOUCHER_ORDENCOMPRA = isset($this->responseData['WEBPAY_VOUCHER_ORDENCOMPRA']) ? $this->responseData['WEBPAY_VOUCHER_ORDENCOMPRA'] : null;
        $WEBPAY_VOUCHER_TXDATE_HORA = isset($this->responseData['WEBPAY_VOUCHER_TXDATE_HORA']) ? $this->responseData['WEBPAY_VOUCHER_TXDATE_HORA'] : null;
        $WEBPAY_VOUCHER_TXDATE_FECHA = isset($this->responseData['WEBPAY_VOUCHER_TXDATE_FECHA']) ? $this->responseData['WEBPAY_VOUCHER_TXDATE_FECHA'] : null;
        
        $this->setErrorPage($WEBPAY_RESULT_CODE, $WEBPAY_RESULT_DESC, $WEBPAY_VOUCHER_ORDENCOMPRA,
            $WEBPAY_VOUCHER_TXDATE_HORA, $WEBPAY_VOUCHER_TXDATE_FECHA);
    }
    
    private function toRedirect($url, $data = [])
    {
        echo "<form action='" . $url . "' method='POST' name='webpayForm'>";
        foreach ($data as $name => $value) {
            echo "<input type='hidden' name='" . htmlentities($name) . "' value='" . htmlentities($value) . "'>";
        }
        echo "</form>" . "<script language='JavaScript'>" . "document.webpayForm.submit();" . "</script>";
    }
    
    /**
     * @param $WEBPAY_RESULT_CODE
     * @param $WEBPAY_RESULT_DESC
     * @param $WEBPAY_VOUCHER_ORDENCOMPRA
     * @param $WEBPAY_VOUCHER_TXDATE_HORA
     * @param $WEBPAY_VOUCHER_TXDATE_FECHA
     * @throws PrestaShopException
     */
    private function setErrorPage(
        $WEBPAY_RESULT_CODE, $WEBPAY_RESULT_DESC, $WEBPAY_VOUCHER_ORDENCOMPRA, $WEBPAY_VOUCHER_TXDATE_HORA,
        $WEBPAY_VOUCHER_TXDATE_FECHA
    ) {
        Context::getContext()->smarty->assign([
            'WEBPAY_RESULT_CODE' => $WEBPAY_RESULT_CODE,
            'WEBPAY_RESULT_DESC' => $WEBPAY_RESULT_DESC,
            'WEBPAY_VOUCHER_ORDENCOMPRA' => $WEBPAY_VOUCHER_ORDENCOMPRA,
            'WEBPAY_VOUCHER_TXDATE_HORA' => $WEBPAY_VOUCHER_TXDATE_HORA,
            'WEBPAY_VOUCHER_TXDATE_FECHA' => $WEBPAY_VOUCHER_TXDATE_FECHA
        ]);
        
        if (Utils::isPrestashop_1_6()) {
            $this->setTemplate('payment_error_1.6.tpl');
        } else {
            $this->setTemplate('module:webpay/views/templates/front/payment_error.tpl');
        }
    }
    
    protected function throwError($message, $redirectTo = 'index.php?controller=order&step=3')
    {
        (new LogHandler())->logError($message);
        Tools::redirect($redirectTo);
        die;
    }
    /**
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function stopIfComingFromAnTimeoutErrorOnWebpay()
    {
        if (!isset($_GET['TBK_TOKEN']) && !isset($_POST['token_ws']) && isset($_POST['TBK_ID_SESION'])) {
            $sessionId = $_POST['TBK_ID_SESION'];
            $sqlQuery = 'SELECT * FROM ' . _DB_PREFIX_ . WebpayTransaction::TABLE_NAME . ' WHERE `session_id` = "' . $sessionId . '"';
            $transaction = \Db::getInstance()->getRow($sqlQuery);
            $errorMessage = 'TBK_TOKEN y token_ws was given on the final url, so the user clicked on "volver al comercio" after an error on webpay';
            if (!$transaction) {
                $this->throwError($errorMessage);
            }
            $webpayTransaction = new WebpayTransaction($transaction['id']);
            $webpayTransaction->status = WebpayTransaction::STATUS_FAILED;
            $webpayTransaction->transbank_response = json_encode(['error' => $errorMessage]);
            $webpayTransaction->save();
            $this->throwError($errorMessage);
        }
    }
    private function stopIfComingFromErrorOnWebpayForm()
    {
        if (isset($_GET['finish']) && isset($_POST['TBK_TOKEN']) && isset($_POST['token_ws'])) {
            $this->throwError('TBK_TOKEN y token_ws was given on the final url, so the user clicked on "volver al comercio" after an error on webpay');
        }
    }
    /**
     * @return WebpayTransaction
     */
    private function getTransactionByToken()
    {
        $token = $this->getTokenWs($_POST);
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . WebpayTransaction::TABLE_NAME . ' WHERE `token` = "' . $token . '"';
        $result = \Db::getInstance()->getRow($sql);
        
        if ($result === false) {
            $this->throwError('Webpay Token ' . $token . ' was not found on database');
        }
        
        $webpayTransaction = new WebpayTransaction($result['id']);
        
        return $webpayTransaction;
    }
}
