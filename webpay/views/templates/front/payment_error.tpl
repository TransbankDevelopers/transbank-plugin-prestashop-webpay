{extends file='page.tpl'}
{assign var='current_step' value='payment'}
{if isset($tpl_dir)}
    {include file="$tpl_dir./order-steps.tpl"}
    {include file="$tpl_dir./errors.tpl"}
{/if}

{block name="content"}

<p class="alert alert-danger">Ha ocurrido un error con su pago.</p>
<div class="box order-confirmation">
    <p>
        <b>Respuesta de la Transaccion:</b>
        <br/><b>Error:</b> {$error}
        <br/><b>Detalle:</b> {$detail}
    </p>
</div>

{/block}
