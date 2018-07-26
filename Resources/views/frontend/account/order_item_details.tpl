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

{extends file='parent:frontend/account/order_item_details.tpl'}

{block name="frontend_account_order_item_label_trackingcode"}
    {$smarty.block.parent}
    <p class="is--strong">{s name="filter/paymentState" namespace="backend/order/main"}{/s}</p>
{/block}

{block name='frontend_account_order_item_trackingcode'}
    {$smarty.block.parent}
    <p>
        {if $offerPosition.cleared==9}
            {s name="partially_invoiced" namespace="backend/static/payment_status"}{/s}
        {elseif $offerPosition.cleared==10}
            {s name="completely_invoiced" namespace="backend/static/payment_status"}{/s}
        {elseif $offerPosition.cleared==11}
            {s name="partially_paid" namespace="backend/static/payment_status"}{/s}
        {elseif $offerPosition.cleared==12}
            {s name="completely_paid" namespace="backend/static/payment_status"}{/s}
        {elseif $offerPosition.cleared==13}
            {s name="1st_reminder" namespace="backend/static/payment_status"}{/s}
        {elseif $offerPosition.cleared==14}
            {s name="2nd_reminder" namespace="backend/static/payment_status"}{/s}
        {elseif $offerPosition.cleared==15}
            {s name="3rd_reminder" namespace="backend/static/payment_status"}{/s}
        {elseif $offerPosition.cleared==16}
            {s name="encashment" namespace="backend/static/payment_status"}{/s}
        {elseif $offerPosition.cleared==17}
            {s name="open" namespace="backend/static/payment_status"}{/s}
        {elseif $offerPosition.cleared==18}
            {s name="reserved" namespace="backend/static/payment_status"}{/s}
        {elseif $offerPosition.cleared==19}
            {s name="delayed" namespace="backend/static/payment_status"}{/s}
        {elseif $offerPosition.cleared==20}
            {s name="re_crediting" namespace="backend/static/payment_status"}{/s}
        {elseif $offerPosition.cleared==21}
            {s name="review_necessary" namespace="backend/static/payment_status"}{/s}
        {elseif $offerPosition.cleared==30}
            {s name="no_credit_approved" namespace="backend/static/payment_status"}{/s}
        {elseif $offerPosition.cleared==31}
            {s name="the_credit_has_been_preliminarily_accepted" namespace="backend/static/payment_status"}{/s}
        {elseif $offerPosition.cleared==32}
            {s name="the_credit_has_been_accepted" namespace="backend/static/payment_status"}{/s}
        {elseif $offerPosition.cleared==33}
            {s name="the_payment_has_been_ordered" namespace="backend/static/payment_status"}{/s}
        {elseif $offerPosition.cleared==34}
            {s name="a_time_extension_has_been_registered" namespace="backend/static/payment_status"}{/s}
        {elseif $offerPosition.cleared==35}
            {s name="the_process_has_been_cancelled" namespace="backend/static/payment_status"}{/s}
        {/if}
    </p>
{/block}
