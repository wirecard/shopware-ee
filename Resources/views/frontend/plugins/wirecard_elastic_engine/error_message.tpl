{block name='frontend_checkout_shipping_payment_wirecard_elastic_engine_error'}
    <div class="wirecard-elastic-engine--error">
        {if $wirecardElasticEngineErrorCode == 1}
            {include file='frontend/_includes/messages.tpl' type='error' content="{s name="MsgCancledByUser" namespace="frontend/wirecard_elastic_engine/checkout"}{/s}"}
        {elseif $wirecardElasticEngineErrorCode == 2}
            {include file='frontend/_includes/messages.tpl' type='error' content="{s name="MsgErrorStartingProcessFailed" namespace="frontend/wirecard_elastic_engine/checkout"}{/s}"}
        {elseif $wirecardElasticEngineErrorCode == 3}
            {include file='frontend/_includes/messages.tpl' type='error' content="{s name="MsgErrorNotAValidMethod" namespace="frontend/wirecard_elastic_engine/checkout"}{/s}"}
        {elseif $wirecardElasticEngineErrorCode == 4}
            {include file='frontend/_includes/messages.tpl' type='error' content="{s name="MsgErrorFailureResponse" namespace="frontend/wirecard_elastic_engine/checkout"}{/s}"}
        {elseif $wirecardElasticEngineErrorCode == 5}
            {include file='frontend/_includes/messages.tpl' type='error' content="{s name="MsgErrorCriticalNoOrder" namespace="frontend/wirecard_elastic_engine/checkout"}{/s}"}
        {/if}
    </div>
{/block}
