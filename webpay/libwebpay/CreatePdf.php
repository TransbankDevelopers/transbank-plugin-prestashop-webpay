<?php
require_once('../../../config/config.inc.php');
if (!defined('_PS_VERSION_')) exit;

require_once('ReportPdfLog.php');
require_once('HealthCheck.php');

$config = array(
    'MODO' => $_POST["ambient"],
    'COMMERCE_CODE' => $_POST["storeID"],
    'PUBLIC_CERT' => $_POST["certificate"],
    'PRIVATE_KEY' => $_POST["secretCode"],
    'WEBPAY_CERT' => $_POST["certificateTransbank"],
    'ECOMMERCE' => 'prestashop'
);

$document = $_POST["document"];
$healthcheck = new HealthCheck($config);
$json = $healthcheck->printFullResume();

$temp = json_decode($json);
if ($document == "report"){
    unset($temp->php_info);
} else {
    $temp = array('php_info' => $temp->php_info);
}

$rl = new ReportPdfLog($document);
$rl->getReport(json_encode($temp));
