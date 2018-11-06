<?php
require_once(dirname(__FILE__).'../../../../../config/config.inc.php');
if (!defined('_PS_VERSION_')) exit;

class WebpayValidationModuleFrontController extends ModuleFrontController {
    /**
     * This class should be use by your Instant Payment
     * Notification system to validate the order remotely
     */
    public function postProcess() {
        /**
         * If the module is not active anymore, no need to process anything.
         */
        if ($this->module->active == false) {
            die;
        }

        /**
         * Since it is an example, we choose sample data,
         * You'll have to get the correct values :)
         */
        $cart_id = 1;
        $customer_id = 1;
        $amount = 100.00;

        /**
         * Restore the context from the $cart_id & the $customer_id to process the validation properly.
         */
        Context::getContext()->cart = new Cart((int)$cart_id);
        Context::getContext()->customer = new Customer((int)$customer_id);
        Context::getContext()->currency = new Currency((int)Context::getContext()->cart->id_currency);
        Context::getContext()->language = new Language((int)Context::getContext()->customer->id_lang);

        $secure_key = Context::getContext()->customer->secure_key;

        if ($this->isValidOrder() === true) {
            $payment_status = Configuration::get('PS_OS_PAYMENT');
            $message = null;
        } else {
            $payment_status = Configuration::get('PS_OS_ERROR');

            /**
             * Add a message to explain why the order has not been validated
             */
            $message = $this->module->l('An error occurred while processing payment');
        }

        $module_name = $this->module->displayName;
        $currency_id = (int)Context::getContext()->currency->id;

        return $this->module->validateOrder($cart_id, $payment_status, $amount, $module_name, $message, array(), $currency_id, false, $secure_key);
    }

    protected function isValidOrder() {
        /**
         * Add your checks right there
         */
        return true;
    }
}
