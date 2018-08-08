/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

Ext.define('Shopware.apps.WirecardElasticEngineTransactions.store.Transactions', {
    extend: 'Shopware.store.Listing',
    sorters: [{
        property: 'id',
        direction: 'DESC'
    }],
    model: 'Shopware.apps.WirecardElasticEngineTransactions.model.Transaction',

    /**
     * @returns { { controller: string } }
     */
    configure: function () {
        return {
            controller: 'WirecardElasticEngineTransactions'
        };
    }
});
