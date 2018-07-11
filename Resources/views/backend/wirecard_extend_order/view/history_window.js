// {block name="backend/wirecard_extend_order/view/history_window"}
Ext.define('Shopware.apps.WirecardExtendOrder.view.HistoryWindow', {
    extend: 'Enlight.app.Window',
    alias: 'widget.wirecard-extend-order-history-window',
    height: 600,
    title: 'Wirecard Transaction',
    layout: 'anchor',
    bodyPadding: 10,
    autoScroll: true,

    style: {
        background: '#EBEDEF'
    },

    snippets: {
        createdAt: 'Created at',
        orderNumber: 'Order number',
        requestId: 'Request ID',
        parentTransactionId: 'Parent transaction ID',
        transactionId: 'Transaction ID',
        providerTransactionId: 'Provider transaction ID',
        transactionType: 'Transaction Type',
        amount: 'Amount',
        response: 'Response'
    },

    initComponent: function() {
        var me = this;

        me.items = me.createItems();

        me.callParent(arguments);
    },

    createItems: function() {
        var me = this;

        return [{
            xtype: 'container',
            renderTpl: me.createListTemplate(),
            renderData: me.record.data
        }];
    },

    createListTemplate: function() {
        var me = this;

        return Ext.create('Ext.XTemplate',
            '{literal}<tpl for=".">',
            '<div class="wirecard-history-entry-pnl">',
            '<p><label class="x-form-item-label">' + me.snippets.createdAt + ':</label> {createdAt}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.orderNumber + ':</label> {orderNumber}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.requestId + ':</label> {requestId}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.parentTransactionId + ':</label> {parentTransactionId}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.transactionId + ':</label> {transactionId}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.providerTransactionId + ':</label> {providerTransactionId}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.transactionType + ':</label> {transactionType}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.amount + ':</label> {[this.formatNumber(values.amount)]} {currency}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.response + ':</label></p>',
            '<div><pre>{[this.asJson(values.response)]}</pre></div>',
            '</div>',
            '</tpl>{/literal}', {
                formatNumber: function(value) {
                    return Ext.util.Format.number(value);
                },
                asJson: function(value) {
                    return JSON.stringify(value, null, 2);
                }
            }
        );
    }
});
// {/block}
