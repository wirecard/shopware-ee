{block name="wirecard_elastic_engine_sepa_form"}
    {block name="wirecard_elastic_engine_sepa_firstname"}
        <div class="wirecard--sepa-firstname">
            <input class="is--required" type="text" name="sepa_firstname" required="required" placeholder="Firstname" />
        </div>
    {/block}
    {block name="wirecard_elastic_engine_sepa_lastname"}
        <div class="wirecard--sepa-lastname">
            <input class="is--required" type="text" name="sepa_lastname" required="required" placeholder="Lastname" />
        </div>
    {/block}
    {block name="wirecard_elastic_engine_sepa_iban"}
        <div class="wirecard--sepa-iban">
            <input class="is--required" type="text" name="sepa_iban" required="required" placeholder="IBAN" />
        </div>
    {/block}
    {$wirecardFormFields.showBic}
    {if $wirecardFormFields.showBic}
        {block name="wirecard_elastic_engine_sepa_bic"}
            <div class="wirecard--sepa-bic">
                <input type="text" name="sepa_bic" required="required" placeholder="BIC" />
            </div>
        {/block}
    {/if}
{/block}
