Ext.define('Shopware.apps.WirecardTransactions.store.Transactions', {
    extend: 'Shopware.store.Listing',

    configure: function () {
        return {
            controller: 'WirecardTransactions'
        };
    },

    sorter: [{
        property: 'orderNumber',
        direction: 'DESC'
    }],

    model: 'Shopware.apps.WirecardTransactions.model.Transaction'
});
