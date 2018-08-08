/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

// {namespace name="backend/wirecard_elastic_engine/transactions_window"}
Ext.define('Shopware.apps.WirecardElasticEngineTransactions.view.Grid', {
    extend: 'Shopware.grid.Panel',
    alias: 'widget.wirecardee-transactions-grid',
    region: 'center',

    viewConfig: {
        enableTextSelection: true
    },

    snippets: {
        OrderNumber: '{s name="OrderNumber"}{/s}',
        PaymentUniqueId: '{s name="PaymentUniqueId"}{/s}',
        Type: '{s name="Type"}{/s}',
        TransactionId: '{s name="TransactionId"}{/s}',
        ParentTransactionId: '{s name="ParentTransactionId"}{/s}',
        ProviderTransactionId: '{s name="ProviderTransactionId"}{/s}',
        TransactionType: '{s name="TransactionType"}{/s}',
        TransactionState: '{s name="TransactionState"}{/s}',
        PaymentMethod: '{s name="PaymentMethod"}{/s}',
        Amount: '{s name="Amount"}{/s}',
        Currency: '{s name="Currency"}{/s}',
        OpenTransactionTooltip: '{s name="OpenTransactionTooltip"}{/s}',
        OpenOrderTooltip: '{s name="OpenOrderTooltip"}{/s}',
        NoOrderNumber: '{s name="NoOrderNumber"}{/s}',
        OrderCanceled: '{s name="OrderCanceled"}{/s}'
    },

    /**
     * @returns { { columns: *|{ orderNumber, paymentUniqueId, type, transactionId, parentTransactionId, providerTransactionId, transactionType, paymentMethod, amount, currency }, rowEditing: boolean, deleteButton: boolean, deleteColumn: boolean, editButton: boolean, editColumn: boolean, addButton: boolean } }
     */
    configure: function () {
        var me = this;

        return {
            columns: me.getColumns(),
            rowEditing: false,
            deleteButton: false,
            deleteColumn: false,
            editButton: false,
            editColumn: false,
            addButton: false
        };
    },

    /**
     * @returns { Object }
     */
    getColumns: function () {
        var me = this;

        return {
            orderNumber: {
                header: me.snippets.OrderNumber,
                draggable: false,
                renderer: me.orderNumberRenderer
            },
            paymentUniqueId: {
                header: me.snippets.PaymentUniqueId,
                draggable: false
            },
            type: {
                header: me.snippets.Type,
                draggable: false
            },
            transactionId: {
                header: me.snippets.TransactionId,
                draggable: false
            },
            parentTransactionId: {
                header: me.snippets.ParentTransactionId,
                draggable: false
            },
            providerTransactionId: {
                header: me.snippets.ProviderTransactionId,
                draggable: false
            },
            transactionType: {
                header: me.snippets.TransactionType,
                draggable: false
            },
            paymentMethod: {
                header: me.snippets.PaymentMethod,
                draggable: false,
                renderer: me.paymentMethodRenderer
            },
            amount: {
                header: me.snippets.Amount,
                draggable: false
            },
            currency: {
                header: me.snippets.Currency,
                draggable: false
            }
        };
    },

    /**
     * @returns { Array }
     */
    createActionColumnItems: function () {
        var me = this,
            items = me.callParent(arguments);

        items.push({
            tooltip: me.snippets.OpenTransactionTooltip,
            handler: function (view, rowIndex, colIndex, item, opts, record) {
                Ext.create('Shopware.apps.WirecardElasticEngineExtendOrder.view.TransactionDetailsWindow', { record: record }).show();
            },
            getClass: function (value, meta, record) {
                var transaction = record.data;
                return transaction.statusMessage ? 'sprite-exclamation' : 'sprite-magnifier-medium';
            }
        });

        items.push({
            iconCls: 'sprite-pencil',
            tooltip: me.snippets.OpenOrderTooltip,
            handler: function (view, rowIndex, colIndex, item, opts, record) {
                if (!record.get('orderId') || record.get('orderStatus') < 0) {
                    return;
                }
                Shopware.app.Application.addSubApplication({
                    name: 'Shopware.apps.Order',
                    action: 'detail',
                    params: {
                        orderId: record.get('orderId')
                    }
                });
            },
            getClass: function (value, meta, record) {
                if (!record.get('orderId') || record.get('orderStatus') < 0) {
                    return 'x-hide-display';
                }
            }
        });

        return items;
    },

    /**
     * @param { String } value
     * @param { Object } style
     * @param { Object } row
     * @returns { String }
     */
    orderNumberRenderer: function (value, style, row) {
        var me = this;
        if (!row.data.orderId) {
            return me.snippets.NoOrderNumber;
        }
        if (!row.data.orderId || row.data.orderStatus < 0) {
            return value + ' ' + me.snippets.OrderCanceled;
        }
        return value;
    },

    /**
     * @param { String } value
     * @param { Object } style
     * @param { Object } row
     * @returns { String }
     */
    paymentMethodRenderer: function (value, style, row) {
        if (row.data.orderPaymentMethod) {
            return row.data.orderPaymentMethod;
        }
        if (value) {
            return value;
        }
        return '';
    }
});
