// {block name="backend/wirecard_extend_order/model/wirecard_order_details"}
Ext.define('Shopware.apps.WirecardExtendOrder.model.WirecardOrderDetails', {
    extend: 'Ext.data.Model',

    fields: [
        'id',
        'orderNumber',
        'transactionId',
        'providerTransactionId',
        'paymentStatus'
    ]

});
// {/block}
