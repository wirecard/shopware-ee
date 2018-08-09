{**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 *}

{namespace name="frontend/wirecard_elastic_engine/ideal"}
{block name="wirecard_elastic_engine_ideal_form"}
    <select name="wirecardElasticEngine[idealBank]">
        {foreach from=$wirecardElasticEngineViewAssignments.idealBanks key="idealBankKey" item="idealBankLabel"}
            <option value="{$idealBankKey}">{$idealBankLabel}</option>
        {/foreach}
    </select>
{/block}
