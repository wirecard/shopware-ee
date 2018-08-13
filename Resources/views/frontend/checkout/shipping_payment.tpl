{**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 *}

{extends file='parent:frontend/checkout/shipping_payment.tpl'}

{block name='frontend_account_payment_error_messages'}
    {block name='frontend_account_payment_error_messages_wirecard_elastic_engine_errors'}
        {if $wirecardElasticEngineErrorCode}
            {include file='frontend/plugins/wirecard_elastic_engine/error_message.tpl'}
        {/if}
    {/block}
    {$smarty.block.parent}
{/block}
