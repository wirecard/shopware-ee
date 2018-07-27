/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

// {block name="backend/wirecard_elastic_engine_extend_order/view/transaction_details_window"}
// {namespace name="backend/wirecard_elastic_engine/transactions_window"}
Ext.define('Shopware.apps.WirecardElasticEngineExtendOrder.view.TransactionDetailsWindow', {
    extend: 'Enlight.app.Window',
    alias: 'widget.wirecardee-extend-order-transaction-details-window',
    height: 600,
    title: '{s name="DetailsWindowTitle"}{/s}',
    layout: 'anchor',
    bodyPadding: 10,
    autoScroll: true,

    style: {
        background: '#EBEDEF'
    },

    snippets: {
        CreatedAt: '{s name="CreatedAt"}{/s}',
        OrderNumber: '{s name="OrderNumber"}{/s}',
        PaymentUniqueId: '{s name="PaymentUniqueId"}{/s}',
        Type: '{s name="Type"}{/s}',
        TransactionId: '{s name="TransactionId"}{/s}',
        ParentTransactionId: '{s name="ParentTransactionId"}{/s}',
        ProviderTransactionId: '{s name="ProviderTransactionId"}{/s}',
        ProviderTransactionReference: '{s name="ProviderTransactionReference"}{/s}',
        TransactionType: '{s name="TransactionType"}{/s}',
        TransactionState: '{s name="TransactionState"}{/s}',
        PaymentMethod: '{s name="PaymentMethod"}{/s}',
        Amount: '{s name="Amount"}{/s}',
        Currency: '{s name="Currency"}{/s}',
        Response: '{s name="Response"}{/s}',
        RequestId: '{s name="RequestId"}{/s}',
        Request: '{s name="Request"}{/s}',
        OrderCanceledErrorTitle: '{s name="OrderCanceledErrorTitle"}{/s}',
        OrderCanceledErrorText: '{s name="OrderCanceledErrorText"}{/s}'
    },

    initComponent: function () {
        var me = this;

        me.items = me.createItems();

        me.callParent(arguments);
    },

    /**
     * Returns an array containing the transaction list template.
     * @returns { *[] }
     */
    createItems: function () {
        var me = this;

        return [{
            xtype: 'container',
            renderTpl: me.createListTemplate(),
            renderData: me.record.data
        }];
    },

    /**
     * Creates the transaction list template.
     * @returns { Ext.XTemplate}
     */
    createListTemplate: function () {
        var me = this;

        return Ext.create('Ext.XTemplate',
            '{literal}<tpl for=".">',
            '<div class="wirecardee-transaction-details-entry-pnl">',
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
