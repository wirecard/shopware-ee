Ext.define('Shopware.apps.WirecardTransactions.model.Transaction', {
    extend: 'Shopware.data.Model',

    configure: function () {
        return {
            controller: 'WirecardTransactions'
        };
    },
    fields: [
        { name: 'orderId', type: 'int' },
        { name: 'orderStatus', type: 'int' },
        { name: 'orderNumber', type: 'string' },
        { name: 'transactionId', type: 'string' },
        { name: 'parentTransactionId', type: 'string' },
        { name: 'providerTransactionId', type: 'string' },
        { name: 'transactionType', type: 'string' },
        { name: 'paymentMethod', type: 'string' },
        { name: 'amount', type: 'float' },
        { name: 'currency', type: 'string' }
    ]
});
