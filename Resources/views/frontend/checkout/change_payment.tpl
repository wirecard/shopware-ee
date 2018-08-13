{**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 *}

{extends file='parent:frontend/checkout/change_payment.tpl'}

{block name='frontend_checkout_payment_fieldset_template'}
    {assign "payment" $payment_mean.name|substr:0:23}
    {if $payment == "wirecard_elastic_engine"}
        {if $payment_mean.name == "wirecard_elastic_engine_credit_card"}
            <div class="wirecardee--payments">
                <img title="{$payment_mean.description}" alt="{$payment_mean.description}"
                     src="{s name="CreditCardLogo" namespace="frontend/wirecard_elastic_engine/payments"}{/s}" />
            </div>
        {elseif $payment_mean.name == "wirecard_elastic_engine_ideal"}
            <div class="wirecardee--payments">
                <img title="{$payment_mean.description}" alt="{$payment_mean.description}"
                     src="{s name="iDEALLogo" namespace="frontend/wirecard_elastic_engine/payments"}{/s}" />
            </div>
        {elseif $payment_mean.name == "wirecard_elastic_engine_paypal"}
            <div class="wirecardee--payments">
                <img title="{$payment_mean.description}" alt="{$payment_mean.description}"
                     src="{s name="PayPalLogo" namespace="frontend/wirecard_elastic_engine/payments"}{/s}" />
            </div>
        {elseif $payment_mean.name == "wirecard_elastic_engine_sepa"}
            <div class="wirecardee--payments">
                <img title="{$payment_mean.description}" alt="{$payment_mean.description}"
                     src="{s name="SepaLogo" namespace="frontend/wirecard_elastic_engine/payments"}{/s}" />
            </div>
        {elseif $payment_mean.name == "wirecard_elastic_engine_sofort"}
            <div class="wirecardee--payments">
                <img title="{$payment_mean.description}" alt="{$payment_mean.description}"
                     src="{s name="SofortLogo" namespace="frontend/wirecard_elastic_engine/payments"}{/s}" />
            </div>
        {/if}
    {/if}
    {$smarty.block.parent}
{/block}
