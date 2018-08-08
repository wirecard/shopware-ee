{**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 *}

{namespace name="frontend/wirecard_elastic_engine/sepa_direct_debit"}
{block name="wirecard_elastic_engine_sepa_form"}
    {block name="wirecard_confirm_mandate_field"}
        <input type="hidden" id="wirecardee-sepa--confirm-mandate"
               name="wirecardElasticEngine[sepaConfirmMandate]"
               required="required" value=""/>
    {/block}
    {block name="wirecard_elastic_engine_sepa_firstname"}
        <div class="wirecardee-sepa--first-name">
            <input class="is--required" id="wirecardee-sepa--first-name" type="text"
                   name="wirecardElasticEngine[sepaFirstName]" autocomplete="off"
                   required="required" placeholder="{s name="FirstName"}{/s}"/>
        </div>
    {/block}
    {block name="wirecard_elastic_engine_sepa_lastname"}
        <div class="wirecardee-sepa--last-name">
            <input class="is--required" id="wirecardee-sepa--last-name" type="text"
                   name="wirecardElasticEngine[sepaLastName]" autocomplete="off"
                   required="required" placeholder="{s name="LastName"}{/s}"/>
        </div>
    {/block}
    {block name="wirecard_elastic_engine_sepa_iban"}
        <div class="wirecardee-sepa--iban">
            <input class="is--required" id="wirecardee-sepa--iban" type="text"
                   name="wirecardElasticEngine[sepaIban]" autocomplete="off"
                   required="required" placeholder="{s name="IBAN"}{/s}"/>
        </div>
    {/block}
    {if $wirecardElasticEngineViewAssignments.showBic}
        {block name="wirecard_elastic_engine_sepa_bic"}
            <div class="wirecardee-sepa--bic">
                <input type="text" id="wirecardee-sepa--bic" name="wirecardElasticEngine[sepaBic]"
                       autocomplete="off" placeholder="{s name="BIC"}{/s}"/>
            </div>
        {/block}
    {/if}
{/block}
