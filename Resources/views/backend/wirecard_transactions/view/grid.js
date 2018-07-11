Ext.define('Shopware.apps.WirecardTransactions.view.Grid', {
    extend: 'Shopware.grid.Panel',
    alias: 'widget.order-listing-grid',
    region: 'center',

    snippets: {
        OrderNumber: '{s name="OrderNumber" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        TransactionId: '{s name="TransactionId" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        ParentTransactionId: '{s name="ParentTransactionId" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        ProviderTransactionId: '{s name="ProviderTransactionId" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        TransactionType: '{s name="TransactionType" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        TransactionState: '{s name="TransactionState" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        PaymentMethod: '{s name="PaymentMethod" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        Amount: '{s name="Amount" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        Currency: '{s name="Currency" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}'
    },

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
                draggable: false
            },
            amount: {
                header: me.snippets.Amount,
                draggable: false
            },
            currency: {
                header: me.snippets.Currency,
                draggable: false
            }
            // created_at: {
            //     header: me.snippets.orderTime,
            //     renderer: me.dateColumnRenderer,
            //     draggable: false
            // }
        };
    },

    /**
     * @returns { Array }
     */
    createActionColumnItems: function () {
        var me = this,
            items = me.callParent(arguments);

        items.push({
            iconCls: 'sprite-pencil',
            tooltip: 'Open order details',

            handler: function (view, rowIndex, colIndex, item, opts, record) {
                Shopware.app.Application.addSubApplication({
                    name: 'Shopware.apps.Order',
                    action: 'detail',
                    params: {
                        orderId: record.get('orderId')
                    }
                });
            }
        });

        return items;
    },

    /**
     * @param { String } value
     * @returns { String }
     */
    dateColumnRenderer: function (value) {
        if (value === Ext.undefined) {
            return value;
        }

        return Ext.util.Format.date(value, 'm.d.Y H:i');
    }
});
