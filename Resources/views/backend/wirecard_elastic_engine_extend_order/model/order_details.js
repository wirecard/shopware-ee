/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

// {block name="backend/wirecard_elastic_engine_extend_order/model/order_details"}
Ext.define('Shopware.apps.WirecardElasticEngineExtendOrder.model.OrderDetails', {
    extend: 'Ext.data.Model',

    fields: [
        'transactions'
    ]
});
// {/block}
