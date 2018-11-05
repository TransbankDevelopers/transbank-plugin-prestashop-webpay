<?php
require_once('../../config/config.inc.php');

require_once('libwebpay/loghandler.php');

$log         = new loghandler('prestashop');
$loghandler  = json_decode($log->getResume(), true);

if ($_POST["action_check"] == "true") {
	$log->setLockStatus(true);
	$log->setnewconfig($_POST['days'] , $_POST['size']);
	//echo 'es true !!';
}
else
	$log->setLockStatus(false);

$response = [
   'success' => true,
   'log'     => $loghandler
];

echo json_encode($response);

//echo $_POST["action_check"];
//echo "<script>window.close();</script>";
