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

{namespace name="frontend/wirecard_elastic_engine/sepa"}
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
