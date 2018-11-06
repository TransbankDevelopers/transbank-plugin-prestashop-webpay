{extends file='page.tpl'}
{assign var='current_step' value='payment'}
{if isset($tpl_dir)}
    {include file="$tpl_dir./order-steps.tpl"}
    {include file="$tpl_dir./errors.tpl"}
{/if}

{block name="content"}

<form method="post" action="{$url_token}">
    <input type="hidden" name="token_ws" value="{$token_webpay}" />

    {if ({$token_webpay} == '0')}
        <p class="alert alert-danger">Ocurrio un error al intentar conectar con WebPay o los datos de conexion son incorrectos.</p>
        <p class="cart_navigation clearfix" id="cart_navigation">
            <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button-exclusive btn btn-default"><i class="icon-chevron-left"></i>{l s='Other payment methods' mod='webpay'}</a>
        </p>
    {else}
        <div class="box cheque-box">
            <h3 class="page-subheading">Pago por WebPay</h3>
            <p>Se realizara la compra a traves de WebPay por un total de ${$total}</p>
        </div>
        <p class="cart_navigation clearfix" id="cart_navigation">
            <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button-exclusive btn btn-default"><i class="icon-chevron-left"></i>{l s='Other payment methods' mod='webpay'}</a>
            <button type="submit" class="btn btn-primary">
                <span>Pagar<i class="icon-chevron-right right"></i></span>
            </button>
        </p>
    {/if}
</form>

{/block}
