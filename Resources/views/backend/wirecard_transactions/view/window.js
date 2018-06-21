Ext.define('Shopware.apps.WirecardTransactions.view.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.wirecard-transaction-window',
    height: 450,
    title: '{s name="WindowTitle" namespace="backend/wirecard_elastic_engine/order_window"}{/s}',

    configure: function() {
        return {
            listingGrid: 'Shopware.apps.WirecardTransactions.view.Grid',
            listingStore: 'Shopware.apps.WirecardTransactions.store.Order'
        };
    }
});
