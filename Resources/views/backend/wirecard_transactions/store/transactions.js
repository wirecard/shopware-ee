Ext.define('Shopware.apps.WirecardTransactions.store.Order', {
    extend: 'Shopware.store.Listing',

    configure: function() {
        return {
            controller: 'WirecardTransactions'
        };
    },

    sorter: [{
        property: 'number',
        direction: 'DESC'
    }],

    model: 'Shopware.apps.WirecardTransactions.model.ShopwareOrder'
});
