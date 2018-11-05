<?php
require_once(dirname(__FILE__).'/../../../../config/config.inc.php');
if (!defined('_PS_VERSION_')) exit;
require_once(dirname(__FILE__).'/../../libwebpay/healthcheck.php');
require_once(dirname(__FILE__).'/../../libwebpay/loghandler.php');

$type = $_POST['type'];

switch($type)
{
    case 'checkInit':

    $response = [];

    $arg = [
        'MODO' 			=> $_POST['MODE'],
        'COMMERCE_CODE'	=> $_POST['C_CODE'],
        'PUBLIC_CERT'   => $_POST['PUBLIC_CERT'],
        'PRIVATE_KEY'	=> $_POST['PRIVATE_KEY'],
        'WEBPAY_CERT'	=> $_POST['WEBPAY_CERT'],
        'ECOMMERCE'     => 'prestashop'
    ];

    $healthcheck = new HealthCheck($arg);

    try {

        $response = $healthcheck->getInitTransaction();

        $log         = new loghandler('prestashop');
        $loghandler  = json_decode($log->getResume(), true);

        echo json_encode(['success' => true, 'msg' => json_decode($response), 'log' => $loghandler]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }

    break;
}

