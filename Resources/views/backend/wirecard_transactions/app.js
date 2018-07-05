// {block name="backend/wirecard_transactions/app"}
Ext.define('Shopware.apps.WirecardTransactions', {
    extend: 'Enlight.app.SubApplication',

    name: 'Shopware.apps.WirecardTransactions',

    loadPath: '{url action=load}',
    bulkLoad: true,

    controllers: [ 'Main' ],

    views: [
        'Window',
        'Grid'
    ],

    models: [
        'ShopwareOrder',
        'WirecardTransactions'
    ],

    stores: [
        'Order'
    ],

    launch: function() {
        return this.getController('Main').mainWindow;
    }
});

// {/block}
