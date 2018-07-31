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

{namespace name="frontend/wirecard_elastic_engine/sepa-direct-debit"}
{block name="wirecard_elastic_engine_sepa_mandate"}
    <style>
        .wirecardee--sepa-mandate table {
            border-collapse: collapse;
            border-spacing: 0;
            border: none;
        }

        .wirecardee--sepa-mandate table td {
            border: none;
        }
    </style>
    <div class="modal-body wirecardee--sepa-mandate">
        <table border="0" cellpadding="0" cellspacing="0" class="stretch">
            <tr>
                <td class="text11justify">
                    <i>{s name="Creditor"}{/s}</i><br/>
                    {$wirecardElasticEngineViewAssignments.creditorName}{if $wirecardElasticEngineViewAssignments.creditorName and $wirecardElasticEngineViewAssignments.creditorAddress}, {/if}
                    {$wirecardElasticEngineViewAssignments.creditorAddress}<br/>
                    {s name="CreditorID"}{/s}: {$wirecardElasticEngineViewAssignments.creditorId}<br/>
                </td>
            </tr>
            <tr>
                <td class="text11">
                    <i>{s name="Debtor"}{/s}</i><br/>
                    {s name="AccountOwner"}{/s}:
                    <span class="first_last_name">{literal}${firstName} ${lastName}{/literal}</span><br/>
                    {s name="IBAN"}{/s}:
                    <span class="bank_iban">{literal}${iban}{/literal}</span><br/>
                    {if $wirecardElasticEngineViewAssignments.showBic}
                        {s name="BIC"}{/s}:
                        <span class="bank_bic">{literal}${bic}{/literal}</span>
                        <br/>
                    {/if}
                </td>
            </tr>
            <tr>
                <td class="text11justify">
                    {assign var="wirecardElasticEngineCreditorId" value=$wirecardElasticEngineViewAssignments.creditorId}
                    {assign var="wirecardElasticEngineCreditorName" value=$wirecardElasticEngineViewAssignments.creditorName}
                    {assign var="wirecardElasticEngineCreditorAddress" value=$wirecardElasticEngineViewAssignments.creditorAddress}
                    {s name="SepaMandateText"}{/s}
                </td>
            </tr>
            <tr>
                <td class="text11justify">
                    <table border="0" width="100%">
                        <tr>
                            <td class="text11justify">
                                {$wirecardElasticEngineViewAssignments.creditorAddress} {$smarty.now|date_format:"d.m.Y"}
                                <br/>
                                <span class="first_last_name"></span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input type="checkbox" id="wirecardee-sepa--confirm-check" name="sepa-check"
                                       required="required">
                                <label for="wirecardee-sepa--confirm-check">{s name="CheckboxText"}{/s}</label>
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: right;">
                                <button class="btn btn-primary" id="wirecardee-sepa--cancel-button"
                                        type="button">{s name="Cancel"}{/s}</button>
                                <button class="btn btn-primary" id="wirecardee-sepa--confirm-button"
                                        type="button">{s name="Confirm"}{/s}</button>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
{/block}
