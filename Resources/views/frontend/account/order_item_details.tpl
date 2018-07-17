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
