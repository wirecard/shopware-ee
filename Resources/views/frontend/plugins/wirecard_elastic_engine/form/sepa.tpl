{namespace name="frontend/wirecard_elastic_engine/sepa"}
{block name="wirecard_elastic_engine_sepa_form"}
    {block name="wirecard_confirm_mandate_field"}
        <input type="hidden" id="wirecard-sepa--confirm-mandate" name="wirecardPayment[sepaConfirmMandate]" required="required" value="" />
    {/block}
    {block name="wirecard_elastic_engine_sepa_firstname"}
        <div class="wirecard--sepa-firstname">
            <input class="is--required" id="wirecard-sepa--first-name" type="text" name="wirecardPayment[sepaFirstName]" required="required" placeholder="{s name="FirstName"}{/s}" />
        </div>
    {/block}
    {block name="wirecard_elastic_engine_sepa_lastname"}
        <div class="wirecard--sepa-lastname">
            <input class="is--required" id="wirecard-sepa--last-name" type="text" name="wirecardPayment[sepaLastName]" required="required" placeholder="{s name="LastName"}{/s}" />
        </div>
    {/block}
    {block name="wirecard_elastic_engine_sepa_iban"}
        <div class="wirecard--sepa-iban">
            <input class="is--required" id="wirecard-sepa--iban" type="text" name="wirecardPayment[sepaIban]" required="required" placeholder="{s name="IBAN"}{/s}" />
        </div>
    {/block}
    {if $wirecardFormFields.showBic}
        {block name="wirecard_elastic_engine_sepa_bic"}
            <div class="wirecard--sepa-bic">
                <input type="text" id="wirecard-sepa--bic" name="wirecardPayment[sepaBic]" placeholder="{s name="BIC"}{/s}" />
            </div>
        {/block}
    {/if}
{/block}
