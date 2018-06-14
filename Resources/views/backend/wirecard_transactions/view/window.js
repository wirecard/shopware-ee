Ext.define('Shopware.apps.WirecardTransactions.view.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.wirecard-transaction-window',
    height: 450,
    title: 'TEST',

    configure: function() {
        return {
            listingGrid: 'Shopware.apps.WirecardTransactions.view.Grid',
            listingStore: 'Shopware.apps.WirecardTransactions.store.Order'
        }
    }
});
