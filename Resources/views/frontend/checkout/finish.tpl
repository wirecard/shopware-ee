{**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 *}

{extends file='parent:frontend/checkout/finish.tpl'}

{block name='frontend_checkout_finish_teaser_actions'}
    {if $wirecardElasticEnginePayment}
        <p class="wirecardee--payment-message">
            {if $wirecardElasticEnginePaymentStatus == 'pending'}
                {s name='PaymentStatusPendingMessage' namespace='frontend/wirecard_elastic_engine/checkout'}{/s}
                {s name='PaymentStatusPendingMail' namespace='frontend/wirecard_elastic_engine/checkout'}{/s}
            {elseif $wirecardElasticEnginePaymentStatus == 'success'}
                {s name='PaymentStatusSuccessMessage' namespace='frontend/wirecard_elastic_engine/checkout'}{/s}
            {elseif $wirecardElasticEnginePaymentStatus == 'canceled'}
                {s name='PaymentStatusCancelMessage' namespace='frontend/wirecard_elastic_engine/checkout'}{/s}
            {/if}
        </p>
    {/if}
    {$smarty.block.parent}
{/block}
