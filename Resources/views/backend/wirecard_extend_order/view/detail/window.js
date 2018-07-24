// {block name="backend/order/view/detail/window"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.WirecardExtendOrder.view.detail.Window', {
    /**
     * Override the customer detail window
     * @string
     */
    override: 'Shopware.apps.Order.view.detail.Window',

    getTabs: function() {
        var me = this,
            result = me.callParent();

        result.push(Ext.create('Shopware.apps.WirecardExtendOrder.view.detail.InfoTab'));

        return result;
    },
    createTabPanel: function() {
        var me = this,
            result = me.callParent(),
            payment = me.record.getPayment().first();

        if (payment.get('name').substr(0, 23) === 'wirecard_elastic_engine') {
            result.add(Ext.create('Shopware.apps.WirecardExtendOrder.view.detail.InfoTab', {
                record: me.record,
                orderStatusStore: me.orderStatusStore,
                paymentStatusStore: me.paymentStatusStore
            }));
        }

        return result;
    }
});
// {/block}
