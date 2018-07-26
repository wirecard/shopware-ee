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

{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_index_header'}
    {include file="frontend/wirecard_elastic_engine_payment/header.tpl"}
{/block}

{block name="frontend_index_content"}
    <div class="wirecardee-redirect" style="padding-top: 50px;">
        <div class="content content--checkout confirm--content">
            <h2>{s name="PaymentRedirectHeader" namespace="frontend/wirecard_elastic_engine/checkout"}WirecardRedirect{/s}</h2>
            <form id="wirecardee-redirect--form" method="{$method}" action="{$url}">
                {foreach from=$formFields item='field' key='key'}
                    <input type="hidden" name="{$key}" value="{$field}"/>
                {/foreach}
                <button class="btn is--primary is--large right is--icon-right" type="submit">
                    {s name="PaymentRedirectButton" namespace="frontend/wirecard_elastic_engine/checkout"}Send{/s}
                    {* loading spinner icon *}
                    <i class="js--loading"></i>
                </button>
            </form>
            <script type="text/javascript">
                document.getElementById('wirecardee-redirect--form').submit();
            </script>
        </div>
    </div>
{/block}
