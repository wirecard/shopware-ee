// {block name="backend/wirecard_transactions/app"}
Ext.define('Shopware.apps.WirecardTransactions', {
    extend: 'Enlight.app.SubApplication',
    name: 'Shopware.apps.WirecardTransactions',
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
    launch: function () {
        return this.getController('Main').mainWindow;
    }
});
// {/block}
