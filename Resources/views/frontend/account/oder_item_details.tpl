{extends file='parent:frontend/account/order_item_details.tpl'}

{block name="frontend_account_order_item_label_trackingcode"}
    {$smarty.block.parent}
    <p class="is--strong">Zahlstatus</p>
{/block}


{block name='frontend_account_order_item_trackingcode'}
    {$smarty.block.parent}
    <p>
        {if $offerPosition.cleared==9}
            Teilweise in Rechnung gestellt
        {elseif $offerPosition.cleared==10}
            Komplett in Rechnung gestellt
        {elseif $offerPosition.cleared==11}
            Teilweise bezahlt
        {elseif $offerPosition.cleared==12}
            Komplett bezahlt
        {elseif $offerPosition.cleared==13}
            1. Mahnung
        {elseif $offerPosition.cleared==14}
            2. Mahnung
        {elseif $offerPosition.cleared==15}
            3. Mahnung
        {elseif $offerPosition.cleared==16}
            Inkasso
        {elseif $offerPosition.cleared==17}
            Offen
        {elseif $offerPosition.cleared==18}
            Reserviert
        {elseif $offerPosition.cleared==19}
            Verzoegert
        {elseif $offerPosition.cleared==20}
            Wiedergutschrift
        {elseif $offerPosition.cleared==21}
            �berpr�fung notwendig
        {elseif $offerPosition.cleared==35}
            Vorgang abgebrochen
        {/if}
    </p>
{/block}
