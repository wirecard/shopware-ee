// {block name="backend/wirecard_extend_order/view/history_window"}
Ext.define('Shopware.apps.WirecardExtendOrder.view.HistoryWindow', {
    extend: 'Enlight.app.Window',
    alias: 'widget.wirecardee-extend-order-history-window',
    height: 600,
    title: 'Wirecard Transaction',
    layout: 'anchor',
    bodyPadding: 10,
    autoScroll: true,

    style: {
        background: '#EBEDEF'
    },

    snippets: {
        CreatedAt: '{s name="CreatedAt" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        OrderNumber: '{s name="OrderNumber" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        PaymentUniqueId: '{s name="PaymentUniqueId" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        Type: '{s name="Type" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        TransactionId: '{s name="TransactionId" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        ParentTransactionId: '{s name="ParentTransactionId" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        ProviderTransactionId: '{s name="ProviderTransactionId" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        ProviderTransactionReference: '{s name="ProviderTransactionReference" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        TransactionType: '{s name="TransactionType" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        TransactionState: '{s name="TransactionState" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        PaymentMethod: '{s name="PaymentMethod" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        Amount: '{s name="Amount" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        Currency: '{s name="Currency" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        Response: '{s name="Response" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        RequestId: '{s name="RequestId" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        Request: '{s name="Request" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        OrderCanceledErrorTitle: '{s name="OrderCanceledErrorTitle" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        OrderCanceledErrorText: '{s name="OrderCanceledErrorText" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}'
    },

    initComponent: function () {
        var me = this;

        me.items = me.createItems();

        me.callParent(arguments);
    },

    createItems: function () {
        var me = this;

        return [{
            xtype: 'container',
            renderTpl: me.createListTemplate(),
            renderData: me.record.data
        }];
    },

    createListTemplate: function () {
        var me = this;

        return Ext.create('Ext.XTemplate',
            '{literal}<tpl for=".">',
            '<div class="wirecard-history-entry-pnl">',
            '<p><label class="x-form-item-label">' + me.snippets.CreatedAt + ':</label> {createdAt}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.OrderNumber + ':</label> {orderNumber}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.PaymentUniqueId + ':</label> {paymentUniqueId}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.Type + ':</label> {type}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.TransactionId + ':</label> {transactionId}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.ParentTransactionId + ':</label> {parentTransactionId}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.ProviderTransactionId + ':</label> {providerTransactionId}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.ProviderTransactionReference + ':</label> {providerTransactionReference}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.TransactionType + ':</label> {transactionType}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.PaymentMethod + ':</label> {paymentMethod}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.Amount + ':</label> {[this.formatNumber(values.amount)]} {currency}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.RequestId + ':</label> {requestId}</p>',
            '<p><label class="x-form-item-label">' + me.snippets.Response + ':</label></p>',
            '<div><pre>{[this.asJson(values.response)]}</pre></div>',
            '</div>',
            '<p><label class="x-form-item-label">' + me.snippets.Request + ':</label></p>',
            '<div><pre>{[this.asJson(values.request)]}</pre></div>',
            '</div>',
            '</tpl>{/literal}', {
                formatNumber: function (value) {
                    return Ext.util.Format.number(value);
                },
                asJson: function (value) {
                    return JSON.stringify(value, null, 2);
                }
            }
        );
    }
});
// {/block}
