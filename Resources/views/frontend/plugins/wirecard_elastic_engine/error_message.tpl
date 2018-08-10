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

{block name='frontend_checkout_shipping_payment_wirecard_elastic_engine_error'}
    <div class="wirecardee--error">
        {if $wirecardElasticEngineErrorCode == 1}
            {include file='frontend/_includes/messages.tpl' type='error' content="{s name="MsgErrorStartingProcessFailed" namespace="frontend/wirecard_elastic_engine/checkout"}{/s}"}
        {elseif $wirecardElasticEngineErrorCode == 2}
            {include file='frontend/_includes/messages.tpl' type='error' content="{s name="MsgErrorFailureResponse" namespace="frontend/wirecard_elastic_engine/checkout"}{/s}"}
        {elseif $wirecardElasticEngineErrorCode == 3}
            {include file='frontend/_includes/messages.tpl' type='error' content="{s name="MsgCanceledByUser" namespace="frontend/wirecard_elastic_engine/checkout"}{/s}"}
        {elseif $wirecardElasticEngineErrorCode == 5}
            {include file='frontend/_includes/messages.tpl' type='error' content="{s name="MsgUserTooYoung" namespace="frontend/wirecard_elastic_engine/checkout"}{/s}"}
        {/if}
    </div>
{/block}
