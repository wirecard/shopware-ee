//{block name="backend/wirecard_extend_order/store/wirecard_order_details"}
Ext.define('Shopware.apps.WirecardExtendOrder.store.WirecardOrderDetails', {
    extend: 'Ext.data.Store',

    model: 'Shopware.apps.WirecardExtendOrder.model.WirecardOrderDetails',
    
    proxy: {
        type: 'ajax',
        url: '{url controller="wirecardTransactions" action="details"}',

        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
//{/block}
