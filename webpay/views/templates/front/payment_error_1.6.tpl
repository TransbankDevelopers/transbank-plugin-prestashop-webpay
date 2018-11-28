
{capture name=path}{l s='Pago a través de WebPay' mod='webpay'}{/capture}
<h2>{l s='Order summary' mod='webpay'}</h2>

{assign var='current_step' value='payment'}


<p class="alert alert-danger">Ha ocurrido un error con su pago.</p>
<div class="box order-confirmation">
    <p>
        <b>Respuesta de la Transaccion:</b>
        <br/><b>Error:</b> {$error}
        <br/><b>Detalle:</b> {$detail}
    </p>
</div>
