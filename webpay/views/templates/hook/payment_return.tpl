
{if ($WEBPAY_RESULT_CODE == 0)}
    <p class="alert alert-success">{l s='Su pedido está completo.' mod='webpay'}</p>
    <div class="box order-confirmation">
        <h3 class="page-subheading">Detalles del pago :</h3>
        <p>
            Respuesta de la Transaccion : {$WEBPAY_RESULT_DESC}
            <br/>Tarjeta de credito: **********{$WEBPAY_VOUCHER_NROTARJETA}
            <br/>Fecha de Transaccion : {$WEBPAY_VOUCHER_TXDATE_FECHA}
            <br/>Hora de Transaccion : {$WEBPAY_VOUCHER_TXDATE_HORA}
            <br/>Monto Compra : {$WEBPAY_VOUCHER_TOTALPAGO}
            <br/>Orden de Compra : {$WEBPAY_VOUCHER_ORDENCOMPRA}
            <br/>Codigo de Autorizacion : {$WEBPAY_VOUCHER_AUTCODE}
            <br/>Tipo de Pago : {$WEBPAY_VOUCHER_TIPOPAGO}
            <br/>Tipo de Cuotas : {$WEBPAY_VOUCHER_TIPOCUOTAS}
            <br/>Numero de cuotas : {$WEBPAY_VOUCHER_NROCUOTAS}
        </p>
    </div>
{else}
    <p class="alert alert-danger">Ha ocurrido un error con su pago.</p>
    <div class="box order-confirmation">
        <h3 class="page-subheading">Detalles del pago :</h3>
        <p>
            Respuesta de la Transaccion : {$WEBPAY_RESULT_DESC}
            <br/>Orden de Compra : {$WEBPAY_VOUCHER_ORDENCOMPRA}
            <br/>Fecha de Transaccion : {$WEBPAY_VOUCHER_TXDATE_FECHA}
            <br/>Hora de Transaccion : {$WEBPAY_VOUCHER_TXDATE_HORA}
        </p>
    </div>
{/if}
