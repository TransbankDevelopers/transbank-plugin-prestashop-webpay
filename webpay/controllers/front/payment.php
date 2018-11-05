<?php


require_once(dirname(__FILE__).'/../../libwebpay/webpay-config.php');
require_once(dirname(__FILE__).'/../../libwebpay/webpay-normal.php');


class WebPayPaymentModuleFrontController extends ModuleFrontController
{

    public $ssl = true;


    public $display_column_left = false;

    
    public function initContent()
    {

		$WebPayPayment = new WebPay();
		$cart = $this->context->cart;
		$cartId = $this->context->cart->id;
		parent::initContent();





      $order = new Order(Order::getOrderByCartId($cartId));

      Context::getContext()->smarty->assign(array(
        'nbProducts' => $cart->nbProducts(),
        'cust_currency' => $cart->id_currency,
        'currencies' => $this->module->getCurrency((int)$cart->id_currency),
        'total' => $cart->getOrderTotal(true, Cart::BOTH),
        'this_path' => $this->module->getPathUri(),
        'this_path_bw' => $this->module->getPathUri(),
        'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
        ));


      $url_base = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . "index.php?fc=module&module={$WebPayPayment->name}&controller=validate&cartId=" . $cartId;
      $url_exito   = $url_base."&return=ok";
      $url_fracaso = $url_base."&return=error";
      $url_confirmacion = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . "modules/{$WebPayPayment->name}/validate.php";

	  
      // error_log("path ".$url_base, 0);
      // error_log("path ".$url_exito, 0);
      // error_log("path ".$url_fracaso, 0);
      // error_log("path ".$url_confirmacion, 0);

      Configuration::updateValue('WEBPAY_URL_FRACASO', $url_fracaso);
      Configuration::updateValue('WEBPAY_URL_EXITO', $url_exito);
      Configuration::updateValue('WEBPAY_URL_CONFIRMACION', $url_confirmacion);


      $config = array(
        "MODO"            => Configuration::get('WEBPAY_AMBIENT'),             
        "PRIVATE_KEY"     => Configuration::get('WEBPAY_SECRETCODE'),
        "PUBLIC_CERT"     => Configuration::get('WEBPAY_CERTIFICATE'),
        "WEBPAY_CERT"     => Configuration::get('WEBPAY_CERTIFICATETRANSBANK'),            
        "COMMERCE_CODE" => Configuration::get('WEBPAY_STOREID'),
        "URL_FINAL"       => Configuration::get('WEBPAY_NOTIFYURL'),
        "URL_RETURN"      => Configuration::get('WEBPAY_POSTBACKURL'),
        "ECOMMERCE"      => 'prestashop'
        );    



      try{ 
        $wp_config = new WebPayConfig($config);
        $webpay = new WebPayNormal($wp_config);
        $result = $webpay->initTransaction($cart->getOrderTotal(true, Cart::BOTH), $sessionId="123abc", $ordenCompra=$cartId, $config['URL_RETURN']);
    }catch(Exception $e){
        $result["error"] = "Error conectando a Webpay";
        $result["detail"] = $e->getMessage();        
    }
    $url_token = '0';
    $token_webpay = '0';
    Context::getContext()->cookie->__set('pago_realizado', 'NO');
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


    if (isset($result["token_ws"])){
     $url_token = $result["url"];
     $token_webpay = $result["token_ws"];
 }  


 Context::getContext()->smarty->assign(array(
    'url_token' => $url_token,
    'token_webpay' => $token_webpay
    ));


        $this->setTemplate('module:webpay/views/templates/front/payment_execution.tpl');
}
}

