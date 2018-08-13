{**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 *}

{extends file='parent:frontend/checkout/cart.tpl'}

{block name='frontend_checkout_cart_error_messages'}
    {if $wirecardElasticEngineUpdateCart}
        {include file='frontend/_includes/messages.tpl' type='error' content="{s name='UpdateCart' namespace='frontend/wirecard_elastic_engine/checkout'}Check your cart{/s}"}
    {/if}
    {$smarty.block.parent}
{/block}
