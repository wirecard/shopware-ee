// {block name="backend/wirecard_extend_order/view/detail/wirecard_info_tab"}

Ext.define('Shopware.apps.WirecardExtendOrder.view.detail.WirecardInfoTab', {
    extend: 'Ext.container.Container',
    padding: 10,
    title: '{s name="TabTitle" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
    autoScroll: true,

    snippets: {
        infoTitle: '{s name="InfoTitle" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
        wirecardOrderNumber: '{s name="WirecardOrderNumber" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
        transactionId: '{s name="TransactionId" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
        providerTransactionId: '{s name="ProviderTransactionId" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}'
    },

    initComponent: function() {
        var me = this;

        me.items = [
            me.createInfoContainer()
        ];
        me.callParent(arguments);
        me.loadData(me.record.data);
    },

    loadData: function(data) {
        var me = this,
            detailsStore = Ext.create('Shopware.apps.WirecardExtendOrder.store.WirecardOrderDetails'),
            panel = me.child('[alias=wirecard-info-panel]');

        detailsStore.getProxy().extraParams = {
            orderNumber: data.number
        };

        detailsStore.load({
            callback: function(records, operation) {
                var data = records[0].getData();

                panel.add({
                    xtype: 'container',
                    renderTpl: me.createInfoTemplate(),
                    renderData: data
                });
            }
        });
    },

    createInfoContainer: function(data) {
        var me = this;

        return Ext.create('Ext.panel.Panel', {
            title: me.snippets.infoTitle,
            alias: 'wirecard-info-panel',
            bodyPadding: 10,
            flex: 1,
            paddingRight: 5,
            items: [
            ]
        });
    },

    createInfoTemplate: function() {
        var me = this;

        return new Ext.XTemplate(
            '{literal}<tpl for=".">',
            '<div class="wirecard-info-pnl">',
            '<p>' + me.snippets.wirecardOrderNumber + ': {id}</p>',
            '<p>' + me.snippets.transactionId + ': {transactionId}</p>',
            '<p>' + me.snippets.providerTransactionId + ': {providerTransactionId}</p>',
            '</div>',
            '</tpl>{/literal}'
        );
    }
});
// {/block}
