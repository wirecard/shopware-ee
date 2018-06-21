// {block name="backend/wirecard_extend_order/controller/extend_order_controller"}
Ext.define('Shopware.apps.WirecardExtendOrder.controller.ExtendOrderController', {
    override: 'Shopware.apps.Order.controller.Main',

    init: function() {
        var me = this;

        if (me.subApplication && me.subApplication.params && Ext.isNumeric(me.subApplication.params.orderId)) {
        }
        me.callParent(arguments);
    },

    showWirecardDetails: function(orderId) {
        var detailsStore = Ext.create('Shopware.apps.WirecardExtendOrder.store.WirecardOrderDetails');

        detailsStore.getProxy().extraParams = {
            orderId: orderId
        };

        detailsStore.load({
            callback: function(records, operation) {
            }
        });
    }
});
// {/block}
