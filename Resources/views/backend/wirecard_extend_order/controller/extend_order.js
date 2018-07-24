// {block name="backend/wirecard_extend_order/controller/extend_order"}
Ext.define('Shopware.apps.WirecardExtendOrder.controller.ExtendOrder', {
    override: 'Shopware.apps.Order.controller.Main',

    init: function () {
        var me = this;
        me.callParent(arguments);
    }
});
// {/block}
