Ext.define('Shopware.apps.WirecardTransactions.model.WirecardTransactions', {
    extend: 'Shopware.data.Model',

    fields: [
        { name: 'id', type: 'int' },
        { name: 'transactionId', type: 'string' },
        { name: 'providerTransactionId', type: 'string' }
    ]
});
