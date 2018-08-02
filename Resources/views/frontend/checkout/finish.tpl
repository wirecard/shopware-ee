{**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
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
