/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

// {block name="backend/order/view/detail/window"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.WirecardElasticEngineExtendOrder.view.detail.Window', {
    /**
     * Override the customer detail window
     * @string
     */
    override: 'Shopware.apps.Order.view.detail.Window',

    /**
     * Adds the Wirecard Info tab to the order details.
     * @returns { * }
     */
    getTabs: function() {
        var me = this,
            result = me.callParent();

        result.push(Ext.create('Shopware.apps.WirecardElasticEngineExtendOrder.view.detail.InfoTab'));

        return result;
    },

    /**
     * Creates the Wirecard Info Tab - just for Wirecard payments!
     * @returns { * }
     */
    createTabPanel: function() {
        var me = this,
            result = me.callParent(),
            payment = me.record.getPayment().first();

        if (payment.get('name').substr(0, 23) === 'wirecard_elastic_engine') {
            result.add(Ext.create('Shopware.apps.WirecardElasticEngineExtendOrder.view.detail.InfoTab', {
                record: me.record,
                orderStatusStore: me.orderStatusStore,
                paymentStatusStore: me.paymentStatusStore
            }));
        }

        return result;
    }
});
// {/block}
