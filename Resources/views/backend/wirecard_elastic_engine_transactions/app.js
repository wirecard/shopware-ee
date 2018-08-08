/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

// {block name="backend/wirecard_elastic_engine_transactions/app"}
Ext.define('Shopware.apps.WirecardElasticEngineTransactions', {
    extend: 'Enlight.app.SubApplication',
    name: 'Shopware.apps.WirecardElasticEngineTransactions',
    loadPath: '{url action=load}',
    bulkLoad: true,

    controllers: [
        'Main'
    ],
    views: [
        'Window',
        'Grid',
        'MailSupportWindow',
        'MailToolbar',
        'MailSupportForm'
    ],
    models: [
        'Transaction'
    ],
    stores: [
        'Transactions'
    ],

    /**
     * Called after the component is initialized.
     * @returns { * }
     */
    launch: function () {
        return this.getController('Main').mainWindow;
    }
});
// {/block}
