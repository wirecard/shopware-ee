{namespace name="frontend/wirecard_elastic_engine/sepa"}
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
                    {$wirecardFormFields.creditorName} {$wirecardFormFields.creditorAddress} <br/>
                    {s name="CreditorID"}{/s}: {$wirecardFormFields.creditorId}<br/>
                </td>
            </tr>
            <tr>
                <td class="text11">
                    <i>{s name="Debtor"}{/s}</i><br/>
                    {s name="AccountOwner"}{/s}:
                    <span class="first_last_name">{literal}${firstName} ${lastName}{/literal}</span><br/>
                    {s name="IBAN"}{/s}:
                    <span class="bank_iban">{literal}${iban}{/literal}</span><br/>
                    {if $wirecardFormFields.showBic}
                        {s name="BIC"}{/s}:
                        <span class="bank_bic">{literal}${bic}{/literal}</span>
                        <br/>
                    {/if}
                </td>
            </tr>
            <tr>
                <td class="text11justify">
                    {s name="SepaMandateText"}{/s}
                </td>
            </tr>
            <tr>
                <td class="text11justify">
                    <table border="0" width="100%">
                        <tr>
                            <td class="text11justify">
                                {$wirecardFormFields.creditorAddress} {$smarty.now|date_format:"d.m.Y"}<br/>
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
