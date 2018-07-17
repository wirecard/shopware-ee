{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_checkout_confirm_information_wrapper'}
    {$smarty.block.parent}
    {if $wirecardFormFields}
        <div class="panel has--border wirecard--aditional-form-fields">
            <div class="panel--title primary is--underline">
                Additional Form Fields
            </div>
            <div class="panel--body is--wide">
                {if $wirecardFormFields.method == 'wirecard_elastic_engine_sepa'}
                    {include file="frontend/plugins/wirecard_elastic_engine/form/sepa.tpl"}
                    {include file="frontend/plugins/wirecard_elastic_engine/form/sepa_mandate.tpl"}
                {/if}
            </div>
        </div>
    {/if}
{/block}
