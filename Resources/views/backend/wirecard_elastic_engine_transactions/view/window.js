/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

Ext.define('Shopware.apps.WirecardElasticEngineTransactions.view.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.wirecardee-transactions-window',
    height: 550,
    title: '{s name="ListWindowTitle" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',

    /**
     * Configures the transaction window.
     * @returns { { listingGrid: string, listingStore: string } }
     */
    configure: function () {
        return {
            listingGrid: 'Shopware.apps.WirecardElasticEngineTransactions.view.Grid',
            listingStore: 'Shopware.apps.WirecardElasticEngineTransactions.store.Transactions'
        };
    }
});
