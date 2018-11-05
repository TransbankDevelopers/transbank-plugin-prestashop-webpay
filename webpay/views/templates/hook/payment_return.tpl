{*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{if ($WEBPAY_TX_ANULADA == "SI")}
    
    <p class="alert alert-danger">La Transaccion fue Anulada por el Cliente.</p>   
          
{else}
{if ($WEBPAY_RESULT_CODE === '0')}
<p class="alert alert-success">{l s='Su pedido está completo.'  mod='webpay'}</p>
        
<div class="box order-confirmation">
		<h3 class="page-subheading">Detalles del pago :</h3>
<p>  		
		            Respuesta de la Transaccion : {$WEBPAY_RESULT_DESC}
                <br />Tarjeta de credito: **********{$WEBPAY_VOUCHER_NROTARJETA}
                <br />Fecha de Transaccion :  {$WEBPAY_VOUCHER_TXDATE_FECHA}
                <br />Hora de Transaccion :  {$WEBPAY_VOUCHER_TXDATE_HORA}
                <br />Monto Compra :  ${$WEBPAY_VOUCHER_TOTALPAGO}                
                <br />Orden de Compra :  {$WEBPAY_VOUCHER_ORDENCOMPRA}
                <br />Codigo de Autorizacion :  {$WEBPAY_VOUCHER_AUTCODE}
                <br />Tipo de Pago :  {$WEBPAY_VOUCHER_TIPOPAGO}
                <br />Tipo de Cuotas :  {$WEBPAY_VOUCHER_TIPOCUOTAS}
                <br />Numero de cuotas :  {$WEBPAY_VOUCHER_NROCUOTAS}
                
	</p>
</div>
{else}
    <p class="alert alert-danger">Ha ocurrido un error con su pago. </p>  
   <div class="box order-confirmation">
   		<h3 class="page-subheading">Detalles del pago :</h3>
   		<p>  
                Respuesta de la Transaccion : {$WEBPAY_RESULT_DESC} 
                <br />Orden de Compra :  {$WEBPAY_VOUCHER_ORDENCOMPRA}
                <br />Fecha de Transaccion :  {$WEBPAY_VOUCHER_TXDATE_FECHA}
                <br />Hora de Transaccion :  {$WEBPAY_VOUCHER_TXDATE_HORA}
      </p>
	 </div>
{/if}
{/if}
