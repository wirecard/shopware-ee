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

{block name='frontend_checkout_finish_information_wrapper'}
    {if $wirecardElasticEngineBankData}
        <div class="panel has--border wirecard--bank-informations is--rounded finish--teaser">
            <div class="panel--title primary is--underline">
                {s name="BankInformationTitle" namespace="frontend/wirecard_elastic_engine/checkout"}{/s}
            </div>
            <div class="panel--body is--wide">
                <p>
                    <strong>{s name="Amount" namespace="frontend/wirecard_elastic_engine/checkout"}{/s}:</strong>
                    {if $sAmountWithTax && $sUserData.additional.charge_vat}{$sAmountWithTax|currency}{else}{$sAmount|currency}{/if}
                </p>
                {if $wirecardElasticEngineBankData.bankName}
                    <p>
                        {$wirecardElasticEngineBankData.bankName}
                    </p>
                {/if}
                <p>
                    <strong>{s name="IBAN" namespace="frontend/wirecard_elastic_engine/checkout"}{/s}:</strong>
                    {$wirecardElasticEngineBankData.iban}
                </p>
                <p>
                    <strong>{s name="BIC" namespace="frontend/wirecard_elastic_engine/checkout"}{/s}:</strong>
                    {$wirecardElasticEngineBankData.bic}
                </p>
                <p>
                    <strong>{s name="ProviderTransactionReferenceID" namespace="frontend/wirecard_elastic_engine/checkout"}{/s}:</strong>
                    {$wirecardElasticEngineBankData.reference}
                </p>
                {if $wirecardElasticEngineBankData.address}
                    <p>
                        {$wirecardElasticEngineBankData.address} <br>
                        {$wirecardElasticEngineBankData.city} {$wirecardElasticEngineBankData.state}
                    </p>
                {/if}
            </div>
        </div>
    {/if}
    {$smarty.block.parent}
{/block}
