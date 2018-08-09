{**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 *}

{extends file='parent:frontend/checkout/finish.tpl'}

{namespace name='frontend/wirecard_elastic_engine/checkout'}

{block name='frontend_checkout_finish_teaser_actions'}
    {if $wirecardElasticEnginePayment}
        <p class="wirecardee--payment-message">
            {if $wirecardElasticEnginePaymentStatus == 'pending'}
                {s name='PaymentStatusPendingMessage'}{/s}
                {s name='PaymentStatusPendingMail'}{/s}
            {elseif $wirecardElasticEnginePaymentStatus == 'success'}
                {s name='PaymentStatusSuccessMessage'}{/s}
            {elseif $wirecardElasticEnginePaymentStatus == 'canceled'}
                {s name='PaymentStatusCancelMessage'}{/s}
            {/if}
        </p>
    {/if}
    {$smarty.block.parent}
{/block}

{block name='frontend_checkout_finish_information_wrapper'}
    {if $wirecardElasticEngineBankData}
        <div class="panel has--border wirecardee--bankdata is--rounded finish--teaser">
            <div class="panel--title primary is--underline">
                {s name="BankInformationTitle"}{/s}
            </div>
            <div class="panel--body is--wide">
                <div class="wirecardee--bankdata-amount">
                    <strong>{s name="Amount"}{/s}:</strong>
                    {if $sAmountWithTax && $sUserData.additional.charge_vat}{$sAmountWithTax|currency}{else}{$sAmount|currency}{/if}
                </div>
                <div class="wirecardee--bankdata-iban">
                    <strong>{s name="IBAN"}{/s}:</strong>
                    {$wirecardElasticEngineBankData.iban}
                </div>
                {if $wirecardElasticEngineBankData.bic}
                    <div class="wirecardee--bankdata-bic">
                        <strong>{s name="BIC"}{/s}:</strong>
                        {$wirecardElasticEngineBankData.bic}
                    </div>
                {/if}
                <div class="wirecardee--bankdata-reference">
                    <strong>{s name="ProviderTransactionReferenceID"}{/s}:</strong>
                    {$wirecardElasticEngineBankData.reference}
                </div>
                {if $wirecardElasticEngineBankData.bankName}
                    <div class="wirecardee--bankdata-name">{$wirecardElasticEngineBankData.bankName}</div>
                {/if}
                {if $wirecardElasticEngineBankData.address}
                    <div class="wirecardee--bankdata-address">
                        {$wirecardElasticEngineBankData.address}<br>
                        {$wirecardElasticEngineBankData.city} {$wirecardElasticEngineBankData.state}
                    </div>
                {/if}
            </div>
        </div>
    {/if}
    {$smarty.block.parent}
{/block}
