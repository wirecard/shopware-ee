/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

// {block name="backend/wirecard_elastic_engine_extend_order/store/order_details"}
Ext.define('Shopware.apps.WirecardElasticEngineExtendOrder.store.OrderDetails', {
    extend: 'Ext.data.Store',

    model: 'Shopware.apps.WirecardElasticEngineExtendOrder.model.OrderDetails',

    proxy: {
        type: 'ajax',
        url: '{url controller="wirecardElasticEngineTransactions" action="details"}',
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
// {/block}
