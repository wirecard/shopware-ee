Ext.define('Shopware.apps.WirecardTransactions.model.Transaction', {
    extend: 'Shopware.data.Model',

    configure: function () {
        return {
            controller: 'WirecardTransactions'
        };
    },
    fields: [
        { name: 'id', type: 'int' },
        { name: 'orderId', type: 'int' },
        { name: 'orderStatus', type: 'int' },
        { name: 'orderPaymentMethod', type: 'string' },
        { name: 'orderNumber', type: 'string' },
        { name: 'paymentUniqueId', type: 'string' },
        { name: 'type', type: 'string' },
        { name: 'transactionId', type: 'string' },
        { name: 'parentTransactionId', type: 'string' },
        { name: 'providerTransactionId', type: 'string' },
        { name: 'transactionType', type: 'string' },
        { name: 'paymentMethod', type: 'string' },
        { name: 'amount', type: 'float' },
        { name: 'currency', type: 'string' },
        { name: 'response', type: 'object' },
        { name: 'request', type: 'object' },
        { name: 'requestId', type: 'string' },
        { name: 'createdAt', type: 'string' }
    ]
});
