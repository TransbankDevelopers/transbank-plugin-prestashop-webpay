<?php
      require_once('../../config/config.inc.php');
      if (!defined('_PS_VERSION_'))
		exit;
      require_once('libwebpay/tcpdf/reportPDFlog.php');
      require_once('libwebpay/healthcheck.php');

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
		  
			try
			{
				
				$response = $healthcheck->getInitTransaction();
				
				echo json_encode(['success' => true, 'msg' => json_decode($response)]);
			}
			catch (Exception $e)
			{
				echo json_encode(['success' => false, 'msg' => $e->getMessage()]);  
			}
		  
		  break;
	  }	  

