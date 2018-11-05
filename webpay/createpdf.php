<?php
	require_once('../../config/config.inc.php');
	if (!defined('_PS_VERSION_'))
		exit;
	require_once('libwebpay/tcpdf/reportPDFlog.php');
	require_once('libwebpay/healthcheck.php');

	$ecommerce = 'prestashop';//obtener nombre de ecommerce
	$arg =  array('MODO' => $_POST["ambient"],
				'COMMERCE_CODE' => $_POST["storeID"],
				'PUBLIC_CERT' => $_POST["certificate"],
				'PRIVATE_KEY' => $_POST["secretCode"],
				'WEBPAY_CERT' => $_POST["certificateTransbank"],
				'ECOMMERCE' => 'prestashop');
	$document = $_POST["document"];
	$healthcheck = new HealthCheck($arg);
	@$json =$healthcheck->printFullResume();
	$rl = new reportPDFlog($ecommerce, $document);
	$temp = json_decode($json);
	if ($document == "report"){
		unset($temp->php_info);
	}
	else
	{
		$temp = array('php_info' => $temp->php_info);
	}
	$json = json_encode($temp);
	$rl->getReport($json);
