{**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
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
        {elseif $wirecardElasticEngineErrorCode == 6}
            {include file='frontend/_includes/messages.tpl' type='error' content="{s name="MsgAmountNotInRange" namespace="frontend/wirecard_elastic_engine/checkout"}{/s}"}
        {elseif $wirecardElasticEngineErrorCode == 7}
            {include file='frontend/_includes/messages.tpl' type='error' content="{s name="MsgMissingPhone" namespace="frontend/wirecard_elastic_engine/checkout"}{/s}"}
        {/if}
    </div>
{/block}
