{extends file='parent:frontend/checkout/cart.tpl'}

{block name='frontend_checkout_cart_error_messages'}
    {if $wirecardElasticEngineUpdateCart}
        {include file='frontend/_includes/messages.tpl' type='error' content="{s name='UpdateCart' namespace='frontend/wirecard_elastic_engine/checkout'}Check your cart{/s}"}
    {/if}
    {$smarty.block.parent}
{/block}