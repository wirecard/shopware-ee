// {block name="backend/wirecard_extend_order/store/order_details"}
Ext.define('Shopware.apps.WirecardExtendOrder.store.OrderDetails', {
    extend: 'Ext.data.Store',

    model: 'Shopware.apps.WirecardExtendOrder.model.OrderDetails',

    proxy: {
        type: 'ajax',
        url: '{url controller="wirecardTransactions" action="details"}',

        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
// {/block}
