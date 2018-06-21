Ext.define('Shopware.apps.WirecardTransactions.store.Order', {
    extend: 'Shopware.store.Listing',

    configure: function() {
        return {
            controller: 'WirecardTransactions'
        };
    },
    model: 'Shopware.apps.WirecardTransactions.model.ShopwareOrder'
});
