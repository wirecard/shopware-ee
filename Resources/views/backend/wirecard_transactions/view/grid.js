Ext.define('Shopware.apps.WirecardTransactions.view.Grid', {
    extend: 'Shopware.grid.Panel',
    alias: 'widget.order-listing-grid',
    region: 'center',

    snippets: {
        orderState: '{s name="OrderState" namespace="backend/wirecard_elastic_engine/order_window"}{/s}',
        paymentState: '{s name="PaymentState" namespace="backend/wirecard_elastic_engine/order_window"}{/s}',
        paymentMethod: '{s name="PaymentMethod" namespace="backend/wirecard_elastic_engine/order_window"}{/s}',
        orderNumber: '{s name="OrderNumber" namespace="backend/wirecard_elastic_engine/order_window"}{/s}',
        transactionId: '{s name="TransactionId" namespace="backend/wirecard_elastic_engine/order_window"}{/s}',
        providerTransactionId: '{s name="ProviderTransactionId" namespace="backend/wirecard_elastic_engine/order_window"}{/s}',
        amount: '{s name="Amount" namespace="backend/wirecard_elastic_engine/order_window"}{/s}',
        orderTime: '{s name="OrderTime" namespace="backend/wirecard_elastic_engine/order_window"}{/s}'
    },

    configure: function() {
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
            cleared: {
                header: me.snippets.paymentState,
                draggable: false,
                renderer: me.paymentStatusRenderer
            },
            status: {
                header: me.snippets.orderState,
                draggable: false,
                renderer: me.orderStatusRenderer
            },
            paymentId: {
                header: me.snippets.paymentMethod,
                draggable: false,
                renderer: me.paymentMethodRenderer
            },
            number: {
                header: me.snippets.orderNumber,
                draggable: false
            },
            transactionId: {
                header: me.snippets.transactionId,
                draggable: false
            },
            temporaryId: {
                header: me.snippets.providerTransactionId,
                draggable: false,
                renderer: me.providerTransactionRenderer
            },
            invoiceAmount: {
                header: me.snippets.amount,
                draggable: false
            },
            orderTime: {
                header: me.snippets.orderTime,
                renderer: me.dateColumnRenderer,
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
            iconCls: 'sprite-shopping-basket',
            tooltip: 'Open order details',

            handler: function (view, rowIndex, colIndex, item, opts, record) {
                Shopware.app.Application.addSubApplication({
                    name: 'Shopware.apps.Order',
                    action: 'detail',
                    params: {
                        orderId: record.get('id')
                    }
                });
            }
        });

        return items;
    },

    /**
     * @param { String } value
     * @param { Object } metaData
     * @param { Ext.data.Model } record
     * @return { String }
     */
    paymentMethodRenderer: function(value, metaData, record) {
        var status = record.getPayment().first();

        if (status instanceof Ext.data.Model) {
            return status.get('description');
        }

        return value;
    },

    /**
     * @param { String } value
     * @param { Object } metaData
     * @param { Ext.data.Model } record
     * @return { String }
     */
    paymentStatusRenderer: function(value, metaData, record) {
        var status = record.getPaymentStatus().first();

        if (status instanceof Ext.data.Model) {
            return status.get('description');
        }

        return value;
    },

    /**
     * @param { String } value
     * @param { Object } metaData
     * @param { Ext.data.Model } record
     * @return { String }
     */
    providerTransactionRenderer: function(value, metaData, record) {
        var status = record.getWirecardTransactions().first();

        if (status instanceof Ext.data.Model) {
            return status.get('providerTransactionId');
        }

        return '';
    },

    /**
     * @param { String } value
     * @param { Object } metaData
     * @param { Ext.data.Model } record
     * @return { String }
     */
    orderStatusRenderer: function(value, metaData, record) {
        var status = record.getOrderStatus().first();

        if (status instanceof Ext.data.Model) {
            return status.get('description');
        }

        return value;
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
