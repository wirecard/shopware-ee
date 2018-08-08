/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

// {block name="backend/wirecard_elastic_engine_extend_order/controller/extend_order"}
Ext.define('Shopware.apps.WirecardElasticEngineExtendOrder.controller.ExtendOrder', {
    override: 'Shopware.apps.Order.controller.Main',

    init: function () {
        var me = this;
        me.callParent(arguments);
    }
});
// {/block}
