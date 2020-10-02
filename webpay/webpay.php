<?php

use PrestaShop\Module\WebpayPlus\Model\WebpayTransaction;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once('libwebpay/HealthCheck.php');
require_once('libwebpay/LogHandler.php');
require_once("libwebpay/Telemetry/PluginVersion.php");
require_once('libwebpay/Utils.php');

class WebPay extends PaymentModule {

    protected $_errors = array();
	var $healthcheck;
	var $log;
    
    private $paymentTypeCodearray = [
        "VD" => "Venta débito",
        "VN" => "Venta normal",
        "VC" => "Venta en cuotas",
        "SI" => "3 cuotas sin interés",
        "S2" => "2 cuotas sin interés",
        "NC" => "N cuotas sin interés",
    ];

    public function __construct() {

        $this->name = 'webpay';
        $this->tab = 'payments_gateways';
        $this->version = '3.0.6';
        $this->author = 'Transbank';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = 'Webpay Plus';
        $this->description = 'Recibe pagos en línea con tarjetas de crédito y Redcompra en tu Prestashop a través de Webpay Plus';
        $this->controllers = array('payment', 'validate');
        $this->confirmUninstall = '¿Estás seguro/a que deseas desinstalar este módulo de pago?';

        // Context::getContext()->cookie->__set('WEBPAY_TITLE', "Pago con Tarjetas de Credito o Redcompra");
        // Context::getContext()->cookie->__set('WEBPAY_BUTTON_TITLE', "Pago electronico con Tarjetas de Credito o Redcompra a traves de Webpay Plus");

        $this->loadIntegrationCertificates();

        $this->pluginValidation();
        $this->loadPluginConfiguration();

		$config = array(
            'MODO' => $this->ambient,
            'COMMERCE_CODE' => $this->storeID,
            'PUBLIC_CERT' => $this->certificate,
            'PRIVATE_KEY' => $this->secretCode,
            'WEBPAY_CERT' => $this->certificateTransbank,
            'ECOMMERCE' => 'prestashop'
        );

        $this->healthcheck = new HealthCheck($config);
		$this->datos_hc = json_decode($this->healthcheck->printFullResume());
		$this->log = new LogHandler();
    }

    public function install() {
		$this->setupPlugin();
		
        return parent::install() &&
        $this->registerHook('header') &&
        $this->registerHook('paymentOptions') &&
        $this->registerHook('paymentReturn') &&
        $this->registerHook('displayPayment') &&
        $this->registerHook('displayPaymentReturn');
        
    }
    
    public function uninstall() {
        if (!parent::uninstall() || !Configuration::deleteByName("webpay"))
            return false;
        return true;
    }

    public function hookPaymentReturn($params) {
        if (!$this->active)
            return;

        $nameOrderRef = isset($params['order']) ? 'order' : 'objOrder';
        $orderId = $params[$nameOrderRef]->id;
    
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . WebpayTransaction::TABLE_NAME . ' WHERE `order_id` = "' . $orderId . '"';
        $transaction = \Db::getInstance()->getRow($sql);
        $webpayTransaction = new WebpayTransaction($transaction['id']);
        $transbankResponse = json_decode($webpayTransaction->transbank_response, true);
        $transactionDate = strtotime($transbankResponse['transactionDate']);
        $paymentTypeCode = $transbankResponse['detailOutput']['paymentTypeCode'];
        if ($paymentTypeCode == "VD") {
            $paymentType = "Débito";
        } elseif ($paymentTypeCode == "VP") {
            $paymentType = "Prepago";
        } else {
            $paymentType = "Crédito";
        }
        if (in_array($paymentTypeCode, ["SI", "S2", "NC", "VC"])) {
            $tipo_cuotas = $this->paymentTypeCodearray[$paymentTypeCode];
        } else {
            $tipo_cuotas = "Sin cuotas";
        }
        
        $this->smarty->assign(array(
            'shop_name' => $this->context->shop->name,
            'total_to_pay' =>  $params[$nameOrderRef]->getOrdersTotalPaid(),
            'status' => 'ok',
            'id_order' => $orderId,
            'WEBPAY_RESULT_DESC' => "Transacción aprobada",
            'WEBPAY_VOUCHER_NROTARJETA' => $transbankResponse['cardDetail']['cardNumber'],
            'WEBPAY_VOUCHER_TXDATE_FECHA' => date("d-m-Y", $transactionDate),
            'WEBPAY_VOUCHER_TXDATE_HORA' => date("H:i:s", $transactionDate),
            'WEBPAY_VOUCHER_TOTALPAGO' => number_format($transbankResponse['detailOutput']['amount'], 0, ',', '.'),
            'WEBPAY_VOUCHER_ORDENCOMPRA' => $transbankResponse['buyOrder'],
            'WEBPAY_VOUCHER_AUTCODE' => $transbankResponse['detailOutput']['authorizationCode'],
            'WEBPAY_VOUCHER_TIPOCUOTAS' => $tipo_cuotas,
            'WEBPAY_VOUCHER_TIPOPAGO' => $paymentType,
            'WEBPAY_VOUCHER_NROCUOTAS' => $transbankResponse['detailOutput']['sharesNumber'],
            'WEBPAY_RESULT_CODE' => $webpayTransaction->response_code
        ));

        if (isset($params[$nameOrderRef]->reference) && !empty($params[$nameOrderRef]->reference)) {
            $this->smarty->assign('reference', $params[$nameOrderRef]->reference);
        }

        return $this->display(__FILE__, 'views/templates/hook/payment_return.tpl');
    }

    public function hookPayment($params) {
        if (!$this->active) {
            return;
        }
        Context::getContext()->smarty->assign(array(
            'logo' => "https://www.transbank.cl/public/img/LogoWebpay.png",
            'title' => $title
        ));
        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    public function hookPaymentOptions($params) {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $payment_options = [
            $this->getWebpayPaymentOption()
        ];
        return $payment_options;
    }

    public function checkCurrency($cart) {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getWebpayPaymentOption() {
       $WPOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
       $paymentController = $this->context->link->getModuleLink($this->name,'payment',array(),true);
       
       return $WPOption->setCallToActionText('Pago con tarjetas de crédito o Redcompra')
           ->setAction($paymentController)
           ->setLogo('https://www.transbankdevelopers.cl/public/library/img/svg/logo_webpay_plus.svg');
    }
    
    public function getContent() {

        $activeShopID = (int)Context::getContext()->shop->id;
        $shopDomainSsl = Tools::getShopDomainSsl(true, true);
        $theEnvironmentChanged=false;

        if (Tools::getIsset('webpay_updateSettings')) {
            if (Tools::getValue('ambient') !=  Configuration::get('WEBPAY_AMBIENT')) {
                $theEnvironmentChanged=true;
            }
            

            Configuration::updateValue('WEBPAY_STOREID', trim(Tools::getValue('storeID')));
            Configuration::updateValue('WEBPAY_SECRETCODE', trim(Tools::getValue('secretCode')));
            Configuration::updateValue('WEBPAY_CERTIFICATE', Tools::getValue('certificate'));
            Configuration::updateValue('WEBPAY_CERTIFICATETRANSBANK', Tools::getValue('certificateTransbank'));
            Configuration::updateValue('WEBPAY_AMBIENT', Tools::getValue('ambient'));
            Configuration::updateValue('WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT', (int)Tools::getValue('webpay_default_order_state_id_after_payment'));
            $this->loadPluginConfiguration();
            $this->pluginValidation();

        } else {
            $this->loadPluginConfiguration();
        }
    
        $config = $this->getConfigForHealthCheck();
    
        $this->healthcheck = new HealthCheck($config);
        if ($theEnvironmentChanged) {
            $rs = $this->healthcheck->getpostinstallinfo();
        }
        
        if (Tools::getValue('ambient') === 'PRODUCCION') {
            $this->sendPluginVersion($config);
        }

        $this->datos_hc = json_decode($this->healthcheck->printFullResume());

        $ostatus = new OrderState(1);
        $statuses = $ostatus->getOrderStates(1);
        $defaultPaymentStatus = Configuration::get('WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT');

        Context::getContext()->smarty->assign(
            array(
                'default_after_payment_order_state_id' => $defaultPaymentStatus,
                'payment_states' => $statuses,
                'errors' => $this->_errors,
                'post_url' => $_SERVER['REQUEST_URI'],
                'data_storeid_init' => $this->storeID_init,
                'data_secretcode_init' => $this->secretCode_init,
                'data_certificate_init' => $this->certificate_init,
                'data_certificatetransbank_init' => $this->certificateTransbank_init,
                'data_storeid' => $this->storeID,
                'data_secretcode' => $this->secretCode,
                'data_certificate' => $this->certificate,
                'data_certificatetransbank' => $this->certificateTransbank,
                'data_ambient' => $this->ambient,
                'data_title' => $this->title,
                'version' => $this->version,
                'api_version' => '1.0',
                'img_icono' => "https://www.transbank.cl/public/img/LogoWebpay.png",
                'cert_vs_private' =>$this->datos_hc->validate_certificates->consistency->cert_vs_private_key,
                'commerce_code_validate' =>$this->datos_hc->validate_certificates->consistency->commerce_code_validate,
                'subject_commerce_code' =>$this->datos_hc->validate_certificates->cert_info->subject_commerce_code,
                'cert_version' =>$this->datos_hc->validate_certificates->cert_info->version,
                'cert_is_valid' =>$this->datos_hc->validate_certificates->cert_info->is_valid,
                'valid_from' =>$this->datos_hc->validate_certificates->cert_info->valid_from,
                'valid_to' =>$this->datos_hc->validate_certificates->cert_info->valid_to,
                'init_status' => null, //$this->datos_hc->validate_init_transaction->status->string,
                'init_error_error' => null, //(isset($this->datos_hc->validate_init_transaction->response->error)) ? $this->datos_hc->validate_init_transaction->response->error : NULL,
                'init_error_detail' => null, // (isset($this->datos_hc->validate_init_transaction->response->detail)) ? $this->datos_hc->validate_init_transaction->response->detail : NULL,
                'init_success_url' => null, //$this->datos_hc->validate_init_transaction->response->url,
                'init_success_token' => null, //$this->datos_hc->validate_init_transaction->response->token_ws,
                'php_status' =>$this->datos_hc->server_resume->php_version->status,
                'php_version' =>$this->datos_hc->server_resume->php_version->version,
                'server_version' =>$this->datos_hc->server_resume->server_version->server_software,
                'ecommerce' =>$this->datos_hc->server_resume->plugin_info->ecommerce,
                'ecommerce_version' =>$this->datos_hc->server_resume->plugin_info->ecommerce_version,
                'current_plugin_version' =>$this->datos_hc->server_resume->plugin_info->current_plugin_version,
                'last_plugin_version' =>$this->datos_hc->server_resume->plugin_info->last_plugin_version,
                'openssl_status' =>$this->datos_hc->php_extensions_status->openssl->status,
                'openssl_version' =>$this->datos_hc->php_extensions_status->openssl->version,
                'SimpleXML_status' =>$this->datos_hc->php_extensions_status->SimpleXML->status,
                'SimpleXML_version' =>$this->datos_hc->php_extensions_status->SimpleXML->version,
                'soap_status' =>$this->datos_hc->php_extensions_status->soap->status,
                'soap_version' =>$this->datos_hc->php_extensions_status->soap->version,
                'dom_status' =>$this->datos_hc->php_extensions_status->dom->status,
                'dom_version' =>$this->datos_hc->php_extensions_status->dom->version,
                'php_info' =>$this->datos_hc->php_info->string->content,
                'lockfile' => json_decode($this->log->getLockFile(),true)['status'],
                'logs' => (isset( json_decode($this->log->getLastLog(),true)['log_content'])) ?  json_decode($this->log->getLastLog(),true)['log_content'] : NULL,
                'log_file' => (isset( json_decode($this->log->getLastLog(),true)['log_file'])) ?  json_decode($this->log->getLastLog(),true)['log_file'] : NULL,
                'log_weight' => (isset( json_decode($this->log->getLastLog(),true)['log_weight'])) ?  json_decode($this->log->getLastLog(),true)['log_weight'] : NULL,
                'log_regs_lines' => (isset( json_decode($this->log->getLastLog(),true)['log_regs_lines'])) ?  json_decode($this->log->getLastLog(),true)['log_regs_lines'] : NULL,
                'log_days' => $this->log->getValidateLockFile()['max_logs_days'],
                'log_size' => $this->log->getValidateLockFile()['max_log_weight'],
                'log_dir' => json_decode($this->log->getResume(),true)['log_dir'],
                'logs_count' => json_decode($this->log->getResume(),true)['logs_count']['log_count'],
                'logs_list' => json_decode($this->log->getResume(),true)['logs_list']
            )
        );

        return $this->display($this->name, 'views/templates/admin/config.tpl');
    }

    private function pluginValidation() {
        $this->_errors = array();
    }
    /**
     * @return array
     */
    public function getConfigForHealthCheck()
    {
        $config = [
            'MODO' => $this->ambient,
            'COMMERCE_CODE' => $this->storeID,
            'PUBLIC_CERT' => $this->certificate,
            'PRIVATE_KEY' => $this->secretCode,
            'WEBPAY_CERT' => $this->certificateTransbank,
            'ECOMMERCE' => 'prestashop'
        ];
        
        return $config;
    }
    /**
     * @param array $config
     */
    public function sendPluginVersion(array $config)
    {
        $telemetryData = $this->healthcheck->getPluginInfo($this->healthcheck->ecommerce);
        (new \Transbank\Telemetry\PluginVersion())->registerVersion($config['COMMERCE_CODE'],
            $telemetryData['current_plugin_version'], $telemetryData['ecommerce_version'],
            \Transbank\Telemetry\PluginVersion::ECOMMERCE_PRESTASHOP);
    }
    
    private function adminValidation() {
        $this->_errors = array();
    }

    private function loadPluginConfiguration() {
        $this->storeID = Configuration::get('WEBPAY_STOREID');
        $this->secretCode = Configuration::get('WEBPAY_SECRETCODE');
        $this->certificate = Configuration::get('WEBPAY_CERTIFICATE');
        $this->certificateTransbank = Configuration::get('WEBPAY_CERTIFICATETRANSBANK');
        $this->ambient = Configuration::get('WEBPAY_AMBIENT');
        $this->title = Context::getContext()->cookie->WEBPAY_TITLE;
    }

    private function setupPlugin() {
        $this->loadIntegrationCertificates();
        Configuration::updateValue('WEBPAY_STOREID', $this->storeID_init);
        Configuration::updateValue('WEBPAY_SECRETCODE', str_replace("<br/>", "\n", $this->secretCode_init));
        Configuration::updateValue('WEBPAY_CERTIFICATE', str_replace("<br/>", "\n", $this->certificate_init));
        Configuration::updateValue('WEBPAY_CERTIFICATETRANSBANK', str_replace("<br/>", "\n", $this->certificateTransbank_init));
        Configuration::updateValue('WEBPAY_AMBIENT', "INTEGRACION");
        // We assume that the default state is "PREPARATION" and then set it
        // as the default order status after payment for our plugin
        $orderInPreparationStateId = Configuration::get('PS_OS_PREPARATION');
        Configuration::updateValue('WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT', $orderInPreparationStateId);
    }

    private function loadIntegrationCertificates() {
        $this->storeID_init = "597020000540";

        $this->secretCode_init = "-----BEGIN RSA PRIVATE KEY-----"
        . "<br/>MIIEowIBAAKCAQEAvuNgBxMAOBlNI7Fw5sHGY1p6DB6EMK83SL4b1ZILSJs/8/MC"
        . "<br/>X8Pkys3CvJmSIiKU7fnWkgXchEdqXJV+tzgoED/y99tXgoMssi0ma+u9YtPvpT7B"
        . "<br/>a5rk5HpLuaFNeuE3l+mpkXDZZKFSZJ1fV/Hyn3A1Zz+7+X2qiGrAWWdjeGsIkz4r"
        . "<br/>uuMFLQVdPVrdAxEWoDRybEUhraQJ1kwmx92HFfRlsbNAmEljG9ngx/+/JLA28cs9"
        . "<br/>oULy4/M7fVUzioKsBJmjRJd6s4rI2YIDpul6dmgloWgEfzfLNnAsZhJryJNBr2Wb"
        . "<br/>E6DL5x/U2XQchjishMbDIPjmDgS0HLLMjRCMpQIDAQABAoIBAEkSwa/zliHjjaQc"
        . "<br/>SRwNEeT2vcHl7LS2XnN6Uy1uuuMQi2rXnBEM7Ii2O9X28/odQuXWvk0n8UKyFAVd"
        . "<br/>NSTuWmfeEyTO0rEjhfivUAYAOH+coiCf5WtL4FOWfWaSWRaxIJcG2+LRUGc1WlUp"
        . "<br/>6VXBSR+/1LGxtEPN13phY0DWUz3FEfGBd4CCPLpzq7HyZWEHUvbaw89xZJSr/Zwh"
        . "<br/>BDZZyTbuwSHc9X9LlQsbaDuW/EyOMmDvSxmSRJO10FRMxyg8qbE4edtUK4jd61i0"
        . "<br/>kGFqdDu9sj5k8pDxOsN2F270SMlIwejZ1uunB87w9ezIcR9YLq9aa22cT8BZdOxb"
        . "<br/>uZ3PAAECgYEA6xfgRtcvpJUBWBVNsxrSg6Ktx2848eQne9NnbWHdZuNjH8OyN7SW"
        . "<br/>Fn0r4HsTw59/NJ1L5F3co5L5baEtRbRLWRpD72xjrXsQSsoKliCik1xgDIplMvOh"
        . "<br/>teA2GdeSv9wglqnotGcj5B/8+vn3tEzMjy+UUsyFn0fIaDC3zK3W2qUCgYEAz90g"
        . "<br/>va+FCcU8cnykb5Yn1u1izdK1c6S++v1bQFf6590ZMNy3p0uGrwAk/MzuBkJ421GK"
        . "<br/>p4pInUvO/Mb2BCcoHtr3ON3v0DCLl6Ae2Gb7lG0dLgcZ1EK7MDpMvKCqNHAv8Qu8"
        . "<br/>QBZOA08L8buVkkRt7jxJrPuOFDI5JAaWCmMOSgECgYEA3GvzfZgu9Go862B2DJL+"
        . "<br/>hCuYMiCHTM01c/UfyT/z/Y7/ln2+8FniS02rQPtE6ar28tb0nDahM8EPGon/T5ae"
        . "<br/>+vkUbzy6LKLxAJ501JPeurnm2Hs+LUqe+U8yioJD9p2m9Hx0UglOborLgGm0pRlI"
        . "<br/>xou+zu8x7ci5D292NXNcun0CgYAVKV378bKJnBrbTPUwpwjHSMOWUK1IaK1IwCJa"
        . "<br/>GprgoBHAd7f6wCWmC024ruRMntfO/C4xgFKEMQORmG/TXGkpOwGQOIgBme+cMCDz"
        . "<br/>xwg1xCYEWZS3l1OXRVgqm/C4BfPbhmZT3/FxRMrigUZo7a6DYn/drH56b+KBWGpO"
        . "<br/>BGegAQKBgGY7Ikdw288DShbEVi6BFjHKDej3hUfsTwncRhD4IAgALzaatuta7JFW"
        . "<br/>NrGTVGeK/rE6utA/DPlP0H2EgkUAzt8x3N0MuVoBl/Ow7y5sqIQKfEI7h0aRdXH5"
        . "<br/>ecefOL6iiJWQqX2+237NOd0fJ4E1+BCMu/+HnyCX+cFM2FgoE6tC"
        . "<br/>-----END RSA PRIVATE KEY-----";

        $this->certificate_init = "-----BEGIN CERTIFICATE-----"
        . "<br/>MIIDeDCCAmACCQDjtGVIe/aeCTANBgkqhkiG9w0BAQsFADB+MQswCQYDVQQGEwJj"
        . "<br/>bDENMAsGA1UECAwEc3RnbzENMAsGA1UEBwwEc3RnbzEMMAoGA1UECgwDdGJrMQ0w"
        . "<br/>CwYDVQQLDARjY3JyMRUwEwYDVQQDDAw1OTcwMjAwMDA1NDAxHTAbBgkqhkiG9w0B"
        . "<br/>CQEWDmNjcnJAZ21haWwuY29tMB4XDTE4MDYwODEzNDYwNloXDTIyMDYwNzEzNDYw"
        . "<br/>NlowfjELMAkGA1UEBhMCY2wxDTALBgNVBAgMBHN0Z28xDTALBgNVBAcMBHN0Z28x"
        . "<br/>DDAKBgNVBAoMA3RiazENMAsGA1UECwwEY2NycjEVMBMGA1UEAwwMNTk3MDIwMDAw"
        . "<br/>NTQwMR0wGwYJKoZIhvcNAQkBFg5jY3JyQGdtYWlsLmNvbTCCASIwDQYJKoZIhvcN"
        . "<br/>AQEBBQADggEPADCCAQoCggEBAL7jYAcTADgZTSOxcObBxmNaegwehDCvN0i+G9WS"
        . "<br/>C0ibP/PzAl/D5MrNwryZkiIilO351pIF3IRHalyVfrc4KBA/8vfbV4KDLLItJmvr"
        . "<br/>vWLT76U+wWua5OR6S7mhTXrhN5fpqZFw2WShUmSdX1fx8p9wNWc/u/l9qohqwFln"
        . "<br/>Y3hrCJM+K7rjBS0FXT1a3QMRFqA0cmxFIa2kCdZMJsfdhxX0ZbGzQJhJYxvZ4Mf/"
        . "<br/>vySwNvHLPaFC8uPzO31VM4qCrASZo0SXerOKyNmCA6bpenZoJaFoBH83yzZwLGYS"
        . "<br/>a8iTQa9lmxOgy+cf1Nl0HIY4rITGwyD45g4EtByyzI0QjKUCAwEAATANBgkqhkiG"
        . "<br/>9w0BAQsFAAOCAQEAhX2/fZ6+lyoY3jSU9QFmbL6ONoDS6wBU7izpjdihnWt7oIME"
        . "<br/>a51CNssla7ZnMSoBiWUPIegischx6rh8M1q5SjyWYTvnd3v+/rbGa6d40yZW3m+W"
        . "<br/>p/3Sb1e9FABJhZkAQU2KGMot/b/ncePKHvfSBzQCwbuXWPzrF+B/4ZxGMAkgxtmK"
        . "<br/>WnWrkcr2qakpHzERn8irKBPhvlifW5sdMH4tz/4SLVwkek24Sp8CVmIIgQR3nyR9"
        . "<br/>8hi1+Iz4O1FcIQtx17OvhWDXhfEsG0HWygc5KyTqCkVBClVsJPRvoCSTORvukcuW"
        . "<br/>18gbYO3VlxwXnvzLk4aptC7/8Jq83XY8o0fn+A=="
        . "<br/>-----END CERTIFICATE-----";

        $this->certificateTransbank_init = "-----BEGIN CERTIFICATE-----"
        . "<br/>MIIEDzCCAvegAwIBAgIJAMaH4DFTKdnJMA0GCSqGSIb3DQEBCwUAMIGdMQswCQYD"
        . "<br/>VQQGEwJDTDERMA8GA1UECAwIU2FudGlhZ28xETAPBgNVBAcMCFNhbnRpYWdvMRcw"
        . "<br/>FQYDVQQKDA5UUkFOU0JBTksgUy5BLjESMBAGA1UECwwJU2VndXJpZGFkMQswCQYD"
        . "<br/>VQQDDAIyMDEuMCwGCSqGSIb3DQEJARYfc2VndXJpZGFkb3BlcmF0aXZhQHRyYW5z"
        . "<br/>YmFuay5jbDAeFw0xODA4MjQxOTU2MDlaFw0yMTA4MjMxOTU2MDlaMIGdMQswCQYD"
        . "<br/>VQQGEwJDTDERMA8GA1UECAwIU2FudGlhZ28xETAPBgNVBAcMCFNhbnRpYWdvMRcw"
        . "<br/>FQYDVQQKDA5UUkFOU0JBTksgUy5BLjESMBAGA1UECwwJU2VndXJpZGFkMQswCQYD"
        . "<br/>VQQDDAIyMDEuMCwGCSqGSIb3DQEJARYfc2VndXJpZGFkb3BlcmF0aXZhQHRyYW5z"
        . "<br/>YmFuay5jbDCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAJN+OJgQQqMb"
        . "<br/>iRZDb3x+JoTfSjyYsRc5k2CWvLpTPFxXuhDyp6mbdIpWIiNYEC4vufVZo5A3THar"
        . "<br/>cbnJRlW/4NVv5QM3gHN9WJ4QeIsrTLtvcIPlfUJNPLNeDqy84zum2YqAFmX5LWsp"
        . "<br/>SF1Ls6n7el8KNJAceaU+2ooN8QZdFZ3RnMc2vrHY7EU6wYGmf/VCEaDZCKqY6ElY"
        . "<br/>mt6/9b2lkhpQLdBn01IqqFpGrD+5DLmYrQur4/1BDVtdNLggX0K7kPk/mkPDq4ME"
        . "<br/>ytkc9/RI5HfJWoQ4EDQF6qcqPqxlMFDf5KEaoLVL230EdwOl0UyvlF25S9ubRyHy"
        . "<br/>mKWIEFSSXe0CAwEAAaNQME4wHQYDVR0OBBYEFP3nYSPX3YKF11RArC09hxjEMMBv"
        . "<br/>MB8GA1UdIwQYMBaAFP3nYSPX3YKF11RArC09hxjEMMBvMAwGA1UdEwQFMAMBAf8w"
        . "<br/>DQYJKoZIhvcNAQELBQADggEBAFHqOPGeg5IpeKz9LviiBGsJDReGVkQECXHp1QP4"
        . "<br/>8RpWDdXBKQqKUi7As97wmVksweaasnGlgL4YHShtJVPFbYG9COB+ElAaaiOoELsy"
        . "<br/>kjF3tyb0EgZ0Z3QIKabwxsxdBXmVyHjd13w6XGheca9QFane4GaqVhPVJJIH/zD2"
        . "<br/>mSc1boVSpaRc1f0oiMtiZf/rcY1/IyMXA9RVxtOtNs87Wjnwq6AiMjB15fLHfT7d"
        . "<br/>R48O6P0ZpWLlZwScyqDWcsg/4wNCL5Kaa5VgM03SKM6XoWTzkT7p0t0FPZVoGCyG"
        . "<br/>MX5lzVXafBH/sPd545fBH2J3xAY3jtP764G4M8JayOFzGB0="
        . "<br/>-----END CERTIFICATE-----";

        $this->ambient = Configuration::get('WEBPAY_AMBIENT');
        $this->title = Context::getContext()->cookie->WEBPAY_TITLE;
    }
}
