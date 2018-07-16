Ext.define('Shopware.apps.WirecardTransactions.controller.Main', {
    extend: 'Enlight.app.Controller',

    init: function () {
        var me = this;
        me.mainWindow = me.getView('Window').create({}).show();
    }
});
