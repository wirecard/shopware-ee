//{block name="backend/wirecard_extend_order/controller/extend_order_controller"}
Ext.define('Shopware.apps.WirecardExtendOrder.controller.ExtendOrderController', {
    override: 'Shopware.apps.Order.controller.Main',

    init: function() {
        var me = this;
        orderId = me.subApplication.params.orderId;
        console.log(orderId);

        if (me.subApplication && me.subApplication.params && Ext.isNumeric(me.subApplication.params.orderId)) {
          // me.showWirecardDetails(me.subApplication.params.orderId);
        }
        me.callParent(arguments);
//        console.log(me.getView('main.Window'));
        //        console.log("-------------------- ENDE --------------------");
    },

    showWirecardDetails: function(orderId) {
        var me = this,
            detailsStore = Ext.create('Shopware.apps.WirecardExtendOrder.store.WirecardOrderDetails');

        detailsStore.getProxy().extraParams = {
            orderId: orderId
        };
        
        detailsStore.load({
            callback: function(records, operation) {
                console.log("Records");
      //          console.log(records);
      //          console.log(operation);
            }
            
        });
    }
});
//{/block}
