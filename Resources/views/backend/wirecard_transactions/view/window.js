Ext.define('Shopware.apps.WirecardTransactions.view.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.wirecardee-transactions-window',
    height: 550,
    title: '{s name="WindowTitle" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',

    configure: function () {
        return {
            listingGrid: 'Shopware.apps.WirecardTransactions.view.Grid',
            listingStore: 'Shopware.apps.WirecardTransactions.store.Transactions'
        };
    }
});
