
ALTER TABLE `ps_order_payment` 
ADD 
  (`byorder` varchar(255) DEFAULT NULL,
  `authorizationcode` varchar(255) DEFAULT NULL,
  `paymenttype` varchar(255) DEFAULT NULL,
  `tipo_cuotas` varchar(255) DEFAULT NULL,
  `sharesnumber` varchar(255) DEFAULT NULL,
  `responsecode` varchar(255) DEFAULT NULL);

